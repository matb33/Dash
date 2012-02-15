<?php

namespace Plugins\Flattener;

use ErrorException;

use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;
use Plugins\Curl\Curl;
use Dash\Event;

class Flattener extends AbstractShiftRefresh
{
	private $curl;

	const SUBREQ = "FLATTENER_SUBREQ";

	public function init()
	{
		// Until we get Traits in PHP 5.4, we'll create a private instance of the Curl plugin
		$this->curl = new Curl();

		if( ! $this->isFlattenerSubRequest() )
		{
			if( $this->isShiftRefresh() )
			{
				$this->addListeners( array( $this, "flatten" ) );
			}
		}
	}

	public function run( Array $parameters )
	{
		if( ! $this->isFlattenerSubRequest() )
		{
			header( "Content-type: text/plain" );

			// Batch functionality

			$settings = $this->settings->get();

			$batch = $settings[ "batch" ];
			$batch = str_replace( "\r\n", "\n", $batch );

			$parts = $parameters[ "p" ];

			$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings[ "flatoutputfolder" ] );
			$outputFolder = $this->parseTokens( $outputFolder, $parts );

			$inputRelativeURLs = explode( "\n", $batch );
			$inputRelativeURLs = $this->dispatchEvent( "Flattener.inputRelativeURLs", $inputRelativeURLs );

			foreach( $inputRelativeURLs as $rawInputRelativeURL )
			{
				$inputRelativeURL = trim( $rawInputRelativeURL );

				if( strlen( $inputRelativeURL ) > 0 )
				{
					$inputRelativeURL = $this->parseTokens( $inputRelativeURL, $parts );
					$qsConjunction = ( strpos( $inputRelativeURL, "?" ) === false ? "?" : "&" );
					$outputFile = parse_url( $inputRelativeURL, PHP_URL_PATH );

					$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . $qsConjunction . self::SUBREQ . "=1";
					$outputFile = $outputFolder . $outputFile;

					echo "flatten url: " . $inputURL . ", path: " . $outputFile . " ";

					try
					{
						$this->fetchAndWrite( $inputURL, $outputFile );
						echo "[OK]";
					}
					catch( ErrorException $e )
					{
						echo "[ERR]";
					}

					echo "\n";
				}
			}

			$this->syncFolders( $outputFolder, $parts, true );
		}
	}

	public function flatten( Event $event )
	{
		// Single functionality

		$settings = $this->settings->get();

		$inputRelativeURL = $_SERVER[ "REDIRECT_DOCUMENT_URI" ];

		$outputFolder = $this->dispatchEvent( "Flattener.outputFolder", $settings[ "flatoutputfolder" ] );
		$outputFolder = $this->removeTokens( $outputFolder );
		$outputFolder = $this->collapseSlashes( $outputFolder );

		$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . "?" . self::SUBREQ . "=1";
		$outputFile = $outputFolder . $inputRelativeURL;

		$this->fetchAndWrite( $inputURL, $outputFile );

		$this->syncFolders( $outputFolder, $event->getParameters(), false );
	}

	private function fetchAndWrite( $url, $outFile )
	{
		$outPath = dirname( $outFile );

		if( ! file_exists( $outPath ) )
		{
			mkdir( $outPath, 0777, true );
		}

		$result = $this->curl->curl( $url );

		if( $result[ "success" ] === true && strpos( $result[ "header" ], "404" ) === false )
		{
			$contents = $result[ "content" ];
			file_put_contents( $outFile, $contents );
		}
		else
		{
			throw new ErrorException( "Invalid input URL: " . $url );
		}
	}

	private function syncFolders( $outputFolder, $parts, $debug = false )
	{
		$settings = $this->settings->get();

		$syncFolders = $settings[ "syncfolders" ];
		$syncFolders = str_replace( "\r\n", "\n", $syncFolders );
		$syncFolders = explode( "\n", $syncFolders );
		$syncFolders = $this->dispatchEvent( "Flattener.syncFolders", $syncFolders );

		foreach( $syncFolders as $configLine )
		{
			$rawFolders = explode( "=>", $configLine );
			$syncFolder = $this->parseTokens( trim( $rawFolders[0] ), $parts );
			$syncDestination = $this->parseTokens( trim( $rawFolders[1] ), $parts );

			$realSyncFolder = realpath( $syncFolder );

			if( $realSyncFolder !== false )
			{
				if( ! file_exists( $syncDestination ) )
				{
					if( $debug ) echo "mkdir " . $syncDestination . "\n";
					mkdir( $syncDestination, 0777, true );
				}

				$realSyncDestination = realpath( $syncDestination );

				$command = "robocopy \"" . $realSyncFolder . "\" \"" . $realSyncDestination . "\" /PURGE /S /XD .svn";

				if( $debug ) echo $command . "\n";
				exec( $command );
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

		return $content;
	}

	private function removeTokens( $content )
	{
		// remove all %1, %2, %3 tokens
		return preg_replace( "/%\d+/", "", $content );
	}

	private function collapseSlashes( $content )
	{
		return preg_replace( "#([/\\\])[/\\\]+#", "\1", $content );
	}

	public function renderSettings()
	{
		parent::renderSettings();

		$settings = $this->settings->get();

		if( ! isset( $settings[ "flatoutputfolder" ] ) ) $settings[ "flatoutputfolder" ] = "";
		if( ! isset( $settings[ "syncfolders" ] ) ) $settings[ "syncfolders" ] = "";
		if( ! isset( $settings[ "batch" ] ) ) $settings[ "batch" ] = "";

		?><script type="text/javascript">
			<?php echo $this->viewModel; ?>.flatoutputfolder = ko.observable( <?php echo json_encode( $settings[ "flatoutputfolder" ] ); ?> );
			<?php echo $this->viewModel; ?>.syncfolders = ko.observable( <?php echo json_encode( $settings[ "syncfolders" ] ); ?> );
			<?php echo $this->viewModel; ?>.batch = ko.observable( <?php echo json_encode( $settings[ "batch" ] ); ?> );
		</script>

		<!-- ko with: <?php echo $this->viewModel; ?> -->
		<details>
			<summary>Toggle advanced</summary>
			<label>
				<span>Destination folder for flattened files<br /><em>Relative to dash.php<br />%1, %2, %3 etc act as parameter tokens</em></span>
				<input type="text" data-bind="value: flatoutputfolder" />
			</label>
			<label>
				<span>Folders to be sync'd as-is (using robocopy)<br /><em>Specify one per line, relative to dash.php</em></span>
				<textarea data-bind="value: syncfolders"></textarea>
			</label>
			<label>
				<span>Registered files for batch flatten (optional)<br /><em>Specify absolute paths (without host)</em></span>
				<textarea data-bind="value: batch"></textarea>
			</label>
		</details>
		<details>
			<summary>Toggle examples</summary>
			<p>Example destination folder: <code>../../flat</code></p>
			<p>Example as-is sync folders: <code>../inc</code></p>
			<p>Example batch flatten:
			<code>/about.html
/careers.html
/contact.html
/error.html
/index.html
/people.html
/subscribe.html
/terms.html
/work.html</code></p>
			<p>Example run usage with token parts (token parts are replaced with %1, %2, %3, etc):
			<code>/-/Flattener?p[]=token1&p[]=token2</code></p>
		</details>
		<!-- /ko -->
		<?php
	}

	public function updateSettings( Array $newSettings )
	{
		$settings = $this->settings->get();

		$settings[ "flatoutputfolder" ] = $newSettings[ "flatoutputfolder" ];
		$settings[ "syncfolders" ] = $newSettings[ "syncfolders" ];
		$settings[ "batch" ] = $newSettings[ "batch" ];

		$this->settings->set( $settings );

		parent::updateSettings( $newSettings );
	}

	private function isFlattenerSubRequest()
	{
		return strpos( $_SERVER[ "REQUEST_URI" ], self::SUBREQ ) !== false;
	}
}