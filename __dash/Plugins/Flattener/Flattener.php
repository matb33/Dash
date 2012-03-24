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

			$batch = $this->dispatchEvent( "Flattener.batch", $settings->offsetGet( "batch" ) );
			$batch = str_replace( "\r\n", "\n", $batch );

			$parts = isset( $parameters[ "p" ] ) ? $parameters[ "p" ] : array();

			$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings->offsetGet( "flatoutputfolder" ) );
			$outputFolder = $this->parseTokens( $outputFolder, $parts );

			$inputRelativeURLs = explode( "\n", $batch );
			$inputRelativeURLs = $this->dispatchEvent( "Flattener.inputRelativeURLs", $inputRelativeURLs );

			$this->batchFlatten( $inputRelativeURLs, $outputFolder, $parts, true );
			$this->syncFolders( $settings, $outputFolder, $parts, true );

			$this->dispatchEvent( "Flattener.allComplete", NULL, array( "outputFolder" => $outputFolder, "batch" => $batch, "inputRelativeURLs" => $inputRelativeURLs ) );
		}
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		if( ! $this->isFlattenerSubRequest() )
		{
			if( $this->testShiftRefresh( $settings ) )
			{
				$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings->offsetGet( "flatoutputfolder" ) );
				$outputFolder = $this->removeTokens( $outputFolder );

				$batch = $this->dispatchEvent( "Flattener.batch", $settings->offsetGet( "batch" ) );
				$batch = trim( str_replace( "\r\n", "\n", $batch ) );

				$inputRelativeURL = $inputRelativeURLs = NULL;

				if( strlen( $batch ) > 0 )
				{
					$inputRelativeURLs = explode( "\n", $batch );
					$inputRelativeURLs = $this->dispatchEvent( "Flattener.inputRelativeURLs", $inputRelativeURLs );

					$this->batchFlatten( $inputRelativeURLs, $outputFolder );
					$this->syncFolders( $settings, $outputFolder );
				}
				else
				{
					$inputRelativeURL = $_SERVER[ "REDIRECT_DOCUMENT_URI" ];
					$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . "?" . self::SUBREQ . "=1";
					$outputFile = $outputFolder . $inputRelativeURL;

					$this->fetchAndWrite( $inputURL, $outputFile );
					$this->syncFolders( $settings, $outputFolder );
				}

				$this->dispatchEvent( "Flattener.allComplete", NULL, array( "outputFolder" => $outputFolder, "batch" => $batch, "inputRelativeURLs" => $inputRelativeURLs, "inputRelativeURL" => $inputRelativeURL ) );
			}
		}
	}

	private function batchFlatten( Array $inputRelativeURLs, $outputFolder, Array $parts = array(), $echo = false )
	{
		foreach( $inputRelativeURLs as $rawInputRelativeURLSet )
		{
			$rawInputRelativeURL = $this->parseTokens( trim( $rawInputRelativeURLSet ), $parts );

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
					if( $echo ) echo "[ERR]";
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
			mkdir( $outPath, 0777, true );
		}

		$this->curl->addHeader( "Cache-Control: no-cache" );
		$result = $this->curl->curl( $url );

		if( $result[ "success" ] === true && strpos( $result[ "header" ], "404" ) === false )
		{
			$content = $result[ "content" ];
			$content = $this->dispatchEvent( "Flattener.curlContent", $content, array( "url" => $url, "outPath" => $outPath, "outFile" => $outFile ) );
			if( $content !== NULL ) file_put_contents( $outFile, $content );
			$this->dispatchEvent( "Flattener.curlComplete", NULL, array( "url" => $url, "outPath" => $outPath, "outFile" => $outFile ) );
		}
		else
		{
			throw new ErrorException( "Invalid input URL: " . $url );
		}
	}

	private function syncFolders( CommittableArrayObject $settings, $outputFolder, Array $parts = array(), $echo = false )
	{
		$syncFolders = $settings->offsetGet( "syncfolders" );
		$syncFolders = str_replace( "\r\n", "\n", $syncFolders );
		$syncFolders = explode( "\n", $syncFolders );
		$syncFolders = $this->dispatchEvent( "Flattener.syncFolders", $syncFolders );

		foreach( $syncFolders as $configLine )
		{
			$rawFolders = explode( "=>", $configLine );
			$syncFolder = $this->parseTokens( trim( $rawFolders[0] ), $parts );
			$syncDestination = $this->parseTokens( trim( $rawFolders[1] ), $parts );

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
					mkdir( $syncDestination, 0777, true );
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
						case "Windows":
							$command = "robocopy \"" . $realSyncInput . "\" \"" . $realSyncDestination . "\" /PURGE /S /NJH /NJS /XD .svn";
						break;
						case "Linux":
							$command = "sudo rsync -vram --delete \"" . $realSyncInput . "/\" \"" . $realSyncDestination . "/\"";
						break;
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

	private function parseTokens( $content, Array $parts )
	{
		// replace %1, %2, %3 in content with equivalent in parameters
		foreach( $parts as $index => $parameter )
		{
			$content = str_replace( "%" . ( $index + 1 ), $parameter, $content );
		}

		$content = $this->collapseSlashes( $content );

		return $content;
	}

	private function removeTokens( $content )
	{
		// remove all %1, %2, %3 tokens
		$content = preg_replace( "/%\d+/", "", $content );
		$content = $this->collapseSlashes( $content );

		return $content;
	}

	private function collapseSlashes( $content )
	{
		return preg_replace( "#([/\\\])[/\\\]+#", "\1", $content );
	}

	private function isFlattenerSubRequest()
	{
		return strpos( $_SERVER[ "REQUEST_URI" ], self::SUBREQ ) !== false;
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

		?><p>These settings apply when running manually: <code>/-/Flattener?p[]=token1&p[]=token2</code></p>
		<label>
			<span>Destination folder for flattened files<br /><em>Relative to dash.php<br />%1, %2, %3 etc act as parameter tokens</em></span>
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
			<span>Registered URL paths for batch flatten<br /><em>Specify absolute paths (without host)</em></span>
			<textarea data-bind="value: batch"></textarea>
		</label>
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
			<p>Example destination folder: <code>../../flat/%1</code></p>
			<p>Example as-is sync folders: <code>../inc => ../../flat/%1/inc
../pub => ../../flat/%1/public</code></p>
			<p>Example batch flatten:
			<code>/about.html
/careers.html
/contact.html
/error.html
/index.html
/people.html
/subscribe.html => /subscription.html
/terms.html
/work.html</code></p>
			<p>Example run usage with token parts (token parts are replaced with %1, %2, %3, etc):
			<code>/-/Flattener?p[]=token1&p[]=token2</code></p>
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
			<span>Destination folder for flattened file<br /><em>Relative to dash.php</em></span>
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
		<details>
			<summary>Toggle examples</summary>
			<p>Example destination folder: <code>../../flat</code></p>
			<p>Example as-is sync folders: <code>../inc => ../../flat/inc</code></p>
			<p>Example batch flatten:
			<code>/error.html
/landing.html => /index.html
</code></p>
		</details>
		<?php
	}
}