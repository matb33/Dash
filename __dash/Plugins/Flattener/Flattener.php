<?php

namespace Plugins\Flattener;

use ErrorException;

use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;
use Dash\Event;

class Flattener extends AbstractShiftRefresh
{
	const SUBREQ = "FLATTENER_SUBREQ";

	public function init()
	{
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

			$inputRelativeURLs = explode( "\n", $batch );
			$outputFolder = $this->parseTokens( $settings[ "flatoutputfolder" ], $parameters );

			foreach( $inputRelativeURLs as $rawInputRelativeURL )
			{
				$inputRelativeURL = trim( $rawInputRelativeURL );

				if( strlen( $inputRelativeURL ) > 0 )
				{
					$inputRelativeURL = $this->parseTokens( $inputRelativeURL, $parameters );
					$qsConjunction = ( strpos( $inputRelativeURL, "?" ) === false ? "?" : "&" );
					$outputFile = parse_url( $inputRelativeURL, PHP_URL_PATH );

					$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . $qsConjunction . self::SUBREQ . "=1";
					$outputFile = $outputFolder . $outputFile;

					echo "flatten url: " . $inputURL . ", path: " . $outputFile . "\n";
					$this->fetchAndWrite( $inputURL, $outputFile );
				}
			}

			$this->syncFolders( $outputFolder, $parameters, true );
		}
	}

	public function flatten( Event $event )
	{
		// Single functionality

		$settings = $this->settings->get();

		$inputRelativeURL = $_SERVER[ "REDIRECT_DOCUMENT_URI" ];

		$outputFolder = $settings[ "flatoutputfolder" ];
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

		$contents = $this->fileGetContents( $url );

		file_put_contents( $outFile, $contents );
	}

	private function fileGetContents( $filename )
	{
		try
		{
			$contents = file_get_contents( $filename );
		}
		catch( \Exception $e )
		{
			$contents = "";
		}

		return mb_convert_encoding( $contents, "UTF-8", mb_detect_encoding( $contents, "UTF-8, ISO-8859-1", true ) );
	}

	private function syncFolders( $outputFolder, $parameters, $debug = false )
	{
		$settings = $this->settings->get();

		$syncFolders = $settings[ "syncfolders" ];
		$syncFolders = str_replace( "\r\n", "\n", $syncFolders );
		$syncFolders = explode( "\n", $syncFolders );

		foreach( $syncFolders as $configLine )
		{
			$rawFolders = explode( "=>", $configLine );
			$syncFolder = $this->parseTokens( trim( $rawFolders[0] ), $parameters );
			$syncDestination = $this->parseTokens( trim( $rawFolders[1] ), $parameters );

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

	private function parseTokens( $content, Array $parameters )
	{
		// replace %1, %2, %3 in content with equivalent in parameters
		foreach( $parameters as $index => $parameter )
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