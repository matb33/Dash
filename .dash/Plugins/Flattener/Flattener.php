<?php

// TODO: write two sets of configs, one for Common and another for Event

namespace Plugins\Flattener;

use ErrorException;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;
use Plugins\Curl\Curl;

class Flattener extends AbstractShiftRefresh
{
	private $curl;

	const SUBREQ = "FLATTENER_SUBREQ";

	/*
	public function registerEvents()
	{
		$this->registerEvent( "Flattener.outputFolder" );
		$this->registerEvent( "Flattener.inputRelativeURLs" );
		$this->registerEvent( "Flattener.syncFolders" );
		$this->registerEvent( "Flattener.batch" );
		$this->registerEvent( "Flattener.curlContent" );
		$this->registerEvent( "Flattener.curlComplete" );
		$this->registerEvent( "Flattener.allComplete" );
	}
	*/

	public function init()
	{
		// Until we get Traits in PHP 5.4, we'll create a private instance of the Curl plugin
		$this->curl = new Curl();

		$this->addListeners( array( $this, "callback" ) );
	}

	public function run( Array $parameters )
	{
		if( ! $this->isFlattenerSubRequest() )
		{
			header( "Content-type: text/plain" );

			$settings = $this->getCommonSettings();

			$batch = $this->dispatchEvent( "Flattener.batch", $settings->offsetGet( "batch" ), $parameters );
			$batch = str_replace( "\r\n", "\n", $batch );

			$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings->offsetGet( "flatoutputfolder" ), $parameters );
			$outputFolder = $this->parseInlineVariables( $outputFolder, $parameters );

			$inputRelativeURLs = explode( "\n", $batch );
			$inputRelativeURLs = $this->dispatchEvent( "Flattener.inputRelativeURLs", $inputRelativeURLs, $parameters );

			$this->batchFlatten( $inputRelativeURLs, $outputFolder, $parameters, true );
			$this->syncFolders( $settings, $parameters, $outputFolder, $parameters, true );

			$this->dispatchEvent( "Flattener.allComplete", NULL, array( "outputFolder" => $outputFolder, "batch" => $batch, "inputRelativeURLs" => $inputRelativeURLs ) );
		}
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		if( ! $this->isFlattenerSubRequest() )
		{
			if( $this->testShiftRefresh( $settings ) )
			{
				$parameters = $event->getParameters();

				$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings->offsetGet( "flatoutputfolder" ), $parameters );
				$outputFolder = $this->parseInlineVariables( $outputFolder, $parameters );

				$batch = $this->dispatchEvent( "Flattener.batch", $settings->offsetGet( "batch" ), $parameters );
				$batch = trim( str_replace( "\r\n", "\n", $batch ) );

				$inputRelativeURL = NULL;
				$inputRelativeURLs = NULL;

				$echo = isset( $parameters[ "echo" ] ) ? $parameters[ "echo" ] === "true" : false;

				if( strlen( $batch ) > 0 )
				{
					$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings->offsetGet( "flatoutputfolder" ), $parameters );
					$outputFolder = $this->parseInlineVariables( $outputFolder, $parameters );

					$inputRelativeURLs = explode( "\n", $batch );
					$inputRelativeURLs = $this->dispatchEvent( "Flattener.inputRelativeURLs", $inputRelativeURLs, $parameters );

					$this->batchFlatten( $inputRelativeURLs, $outputFolder, $parameters, $echo );
				}
				else
				{
					$outputFolder = $this->parseInlineVariables( $outputFolder, $parameters );

					$inputRelativeURL = $_SERVER[ "REDIRECT_DOCUMENT_URI" ];
					$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . "?" . self::SUBREQ . "=1";
					$outputFile = $outputFolder . $inputRelativeURL;

					$this->fetchAndWrite( $inputURL, $outputFile );
				}

				$this->syncFolders( $settings, $parameters, $outputFolder, $parameters, $echo );

				$this->dispatchEvent( "Flattener.allComplete", NULL, array( "outputFolder" => $outputFolder, "batch" => $batch, "inputRelativeURLs" => $inputRelativeURLs, "inputRelativeURL" => $inputRelativeURL ) );
			}
		}
	}

	private function batchFlatten( Array $inputRelativeURLs, $outputFolder, Array $parameters = array(), $echo = false )
	{
		foreach( $inputRelativeURLs as $rawInputRelativeURLSet )
		{
			$rawInputRelativeURL = $this->parseInlineVariables( trim( $rawInputRelativeURLSet ), $parameters );

			if( strlen( $rawInputRelativeURL ) > 0 )
			{
				if( strpos( $rawInputRelativeURL, "=>" ) !== false )
				{
					list( $inputRelativeURL, $outputFile ) = explode( "=>", $rawInputRelativeURL );

					$inputRelativeURL = trim( $inputRelativeURL );
					$outputFile = "/" . ltrim( trim( $outputFile ), "/" );
				}
				else
				{
					$inputRelativeURL = $rawInputRelativeURL;
					$outputFile = parse_url( $inputRelativeURL, PHP_URL_PATH );
				}

				$qsConjunction = ( strpos( $inputRelativeURL, "?" ) === false ? "?" : "&" );
				$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . $qsConjunction . self::SUBREQ . "=1";
				$outputFile = $outputFolder . $outputFile;

				if( $echo ) echo "flatten url: " . $inputURL . ", path: " . $outputFile . " ";

				try
				{
					$this->fetchAndWrite( $inputURL, $outputFile );
					if( $echo ) echo "[OK]";
				}
				catch( ErrorException $e )
				{
					if( $echo ) echo "[ERR: " . $e->getMessage() . "]";
				}

				if( $echo ) echo "\n";
			}
		}
	}

	private function fetchAndWrite( $url, $outFile )
	{
		$outPath = dirname( $outFile );

		if( ! file_exists( $outPath ) )
		{
			self::mkdir( $outPath );
		}

		$this->curl->addHeader( "Cache-Control: no-cache" );
		$result = $this->curl->curl( $url );

		if( $result[ "success" ] === true && strpos( $result[ "header" ], " 404 " ) === false )
		{
			$content = $result[ "content" ];
			$content = $this->dispatchEvent( "Flattener.curlContent", $content, array( "url" => $url, "outPath" => $outPath, "outFile" => $outFile ) );
			if( $content !== NULL ) self::file_put_contents( $outFile, $content );
			$this->dispatchEvent( "Flattener.curlComplete", $content, array( "url" => $url, "outPath" => $outPath, "outFile" => $outFile ) );
		}
		else
		{
			throw new ErrorException( $result[ "error" ] );
		}
	}

	private function syncFolders( CommittableArrayObject $settings, Array $parameters, $outputFolder, Array $parameters = array(), $echo = false )
	{
		$syncFolders = $settings->offsetGet( "syncfolders" );
		$syncFolders = str_replace( "\r\n", "\n", $syncFolders );
		$syncFolders = explode( "\n", $syncFolders );
		$syncFolders = $this->dispatchEvent( "Flattener.syncFolders", $syncFolders, $parameters );

		foreach( $syncFolders as $configLine )
		{
			$rawFolders = explode( "=>", $configLine );
			$syncFolder = $this->parseInlineVariables( trim( $rawFolders[ 0 ] ), $parameters );
			$syncDestination = $this->parseInlineVariables( trim( $rawFolders[ 1 ] ), $parameters );

			$realSyncInput = realpath( $syncFolder );

			if( $realSyncInput !== false )
			{
				$inputIsFile = is_file( $realSyncInput );

				if( $inputIsFile )
				{
					$syncOutputFile = basename( $syncDestination );
					$syncDestination = dirname( $syncDestination );
				}

				if( ! file_exists( $syncDestination ) )
				{
					if( $echo ) echo "mkdir " . $syncDestination . "\n";
					self::mkdir( $syncDestination );
				}

				$realSyncDestination = realpath( $syncDestination );

				if( $inputIsFile )
				{
					$realSyncDestination = $realSyncDestination . DIRECTORY_SEPARATOR . $syncOutputFile;
					copy( $realSyncInput, $realSyncDestination );
				}
				else
				{
					switch( PHP_OS )
					{
						case "Linux":
							$command = "sudo rsync -vram --perms --chmod=a+rwx --exclude='.git*' --delete \"" . $realSyncInput . "/\" \"" . $realSyncDestination . "/\"";
						break;
						case "Windows":
						case "WINNT":
						default:
							$command = "robocopy \"" . $realSyncInput . "\" \"" . $realSyncDestination . "\" /PURGE /S /NJH /NJS /XD .svn .git";
					}
				}

				if( $echo ) echo $command . "\n";

				// $t1 = microtime( true );
				if( $echo )
				{
					system( $command );
				}
				else
				{
					exec( $command );
				}
				// echo ( microtime( true ) - $t1 ) . " seconds elapsed";
			}
			else
			{
				throw new ErrorException( "Invalid sync folder: " . $syncFolder );
			}
		}
	}

	private function isFlattenerSubRequest()
	{
		return strpos( $_SERVER[ "REQUEST_URI" ], self::SUBREQ ) !== false;
	}

	public static function mkdir( $folder )
	{
		$old_umask = umask( 0 );
		mkdir( $folder, 0777, true );
		umask( $old_umask );
	}

	public static function file_put_contents( $filename, $content )
	{
		file_put_contents( $filename, $content );
		chmod( $filename, 0777 );
	}

	public function renderCommonObservables( CommittableArrayObject $settings )
	{
		parent::renderCommonObservables( $settings );

		if( ! $settings->offsetExists( "flatoutputfolder" ) ) $settings->offsetSet( "flatoutputfolder", "" );
		if( ! $settings->offsetExists( "syncfolders" ) ) $settings->offsetSet( "syncfolders", "" );
		if( ! $settings->offsetExists( "batch" ) ) $settings->offsetSet( "batch", "" );

		?>flatoutputfolder: ko.observable( <?php echo json_encode( $settings->offsetGet( "flatoutputfolder" ) ); ?> ),
		syncfolders: ko.observable( <?php echo json_encode( $settings->offsetGet( "syncfolders" ) ); ?> ),
		batch: ko.observable( <?php echo json_encode( $settings->offsetGet( "batch" ) ); ?> ),
		<?php
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><p>These settings apply when running manually: <code>/-/Flattener?token1=value1&token2=value2</code></p>
		<label>
			<span>
				Destination folder for flattened files<br />
				<em>Relative to dash.php</em>
			</span>
			<input type="text" data-bind="value: flatoutputfolder" />
		</label>
		<label>
			<span>
				Folders to be sync'd as-is (via robocopy or rsync). Single files are supported (via copy).<br />
				<em>Specify one per line, relative to dash.php</em><br />
			</span>
			<textarea data-bind="value: syncfolders"></textarea>
		</label>
		<label>
			<span>Registered URL paths for batch flatten<br /><em>Specify absolute paths (without host)</em></span>
			<textarea data-bind="value: batch"></textarea>
		</label>

		<h3>rsync configuration for Linux users:</h3>
		<code>echo "www-data ALL=(ALL) NOPASSWD:/usr/bin/rsync" | sudo tee /etc/sudoers.d/apache-rsync && sudo chmod 0440 /etc/sudoers.d/apache-rsync</code>

		<h3>Inline parameters</h3>
		<p>Flattener supports inline parameters of format <var>%name</var> or <var>{%name}</var>, where <var>name</var> is pulled from parameters.</p>

		<h3>Events you can listen to:</h3>
		<ul>
			<li><strong>Flattener.outputFolder</strong> : Allows you to modify the outputFolder.</li>
			<li><strong>Flattener.inputRelativeURLs</strong> : Allows you to modify the inputRelativeURLs.</li>
			<li><strong>Flattener.syncFolders</strong> : Allows you to modify the syncFolders.</li>
			<li><strong>Flattener.batch</strong> : Allows you to modify the batch.</li>
			<li><strong>Flattener.curlContent</strong> : Allows you to modify the retrieved content for each file the flattener processes. Parameters passed are "url", "outPath" and "outFile".</li>
			<li><strong>Flattener.curlComplete</strong> : Allows you to chain another event after the flattener has processed a particular file. Content not available for modification, but file is written to disk. Parameters passed are "url", "outPath" and "outFile".</li>
			<li><strong>Flattener.allComplete</strong> : Allows you to chain another event after the flattener has finished all flattening. Parameters passed are "outputFolder", "batch", "inputRelativeURLs" and/or "inputRelativeURL".</li>
		</ul>
		<details>
			<summary>Toggle examples</summary>
			<h4>Example destination folder:</h4>
			<code>../../flat/{%sub}</code>

			<h4>Example as-is sync folders:</h4>
			<code>../inc => ../../flat/{%sub}/inc
../pub => ../../flat/{%sub}/public</code>
			
			<h4>Example batch flatten:</h4>
			<code>/about.html
/careers.html
/contact.html
/error.html
/index.html
/people.html
/subscribe.html => /subscription.html
/terms.html
/work.html</code>

			<h4>Example run usage with inline variables:</h4>
			<code>/-/Flattener?token1=value1&token2=value2</code></p>
		</details>
		<?php
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "flatoutputfolder" ) ) $settings->offsetSet( "flatoutputfolder", "" );
		if( ! $settings->offsetExists( "syncfolders" ) ) $settings->offsetSet( "syncfolders", "" );
		if( ! $settings->offsetExists( "batch" ) ) $settings->offsetSet( "batch", "" );

		?>flatoutputfolder: ko.observable( <?php echo json_encode( $settings->offsetGet( "flatoutputfolder" ) ); ?> ),
		syncfolders: ko.observable( <?php echo json_encode( $settings->offsetGet( "syncfolders" ) ); ?> ),
		batch: ko.observable( <?php echo json_encode( $settings->offsetGet( "batch" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>
				Destination folder for flattened file<br />
				<em>Relative to dash.php</em>
			</span>
			<input type="text" data-bind="value: flatoutputfolder" />
		</label>
		<label>
			<span>
				Folders to be sync'd as-is (via robocopy or rsync). Single files are supported (via copy).<br />
				<em>Specify one per line, relative to dash.php</em><br />
				<em><strong>Ubuntu users:</strong><br />touch /etc/sudoers.d/apache-rsync<br />chmod 0440 /etc/sudoers.d/apache-rsync<br />gedit /etc/sudoers.d/apache-rsync<br />www-data ALL=(ALL) NOPASSWD:/usr/bin/rsync</em>
			</span>
			<textarea data-bind="value: syncfolders"></textarea>
		</label>
		<label>
			<span>Specify URL paths to batch flatten. Leave empty to flatten current path in context<br /><em>Specify absolute paths (without host)</em></span>
			<textarea data-bind="value: batch"></textarea>
		</label>

		<h3>Inline parameters</h3>
		<p>Flattener supports inline parameters of format <var>%name</var> or <var>{%name}</var>, where <var>name</var> is pulled from parameters.</p>

		<h3>Using with Dispatch</h3>
		<p>Parameters can be passed in via Dispatch as query parameters:</p>
		<code>/-/Dispatch?e=NameOfEvent&token1=value1&token2=value2</code>
		<p>The results of the Flattener can be echoed if you specify the <var>echo=true</var> parameter when dispatching:</p>
			<code>/-/Dispatch?e=NameOfEvent&echo=true&token1=value1&token2=value2</code>
		<p>This is useful if you have a page that will be conditionally flattened if a query parameter exists.</p>

		<details>
			<summary>Toggle examples</summary>

			<h4>Example destination folder:</h4>
			<code>../../flat/{%whatever}</code>

			<h4>Example as-is sync folders:</h4>
			<code>../inc => ../../flat/{%thing}/inc</code>

			<h4>Example batch flatten:</h4>
			<code>/error.html
/landing.html => /index.html
/thanks.html?level={%level} => /level-{%level}/thank-you.html
</code>
		</details>
		<?php
	}
}