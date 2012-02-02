<?php

namespace Plugins\Flattener;

use ErrorException;

use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

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

			$data = $this->settings->get();

			$batch = $data[ "batch" ];
			$batch = str_replace( "\r\n", "\n", $batch );

			$inputRelativeURLs = explode( "\n", $batch );
			$outputFolder = $this->parseTokens( $data[ "flatoutputfolder" ], $parameters );

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

			$this->syncFolders( $outputFolder, true );
		}
	}

	public function flatten()
	{
		// Single functionality

		$data = $this->settings->get();

		$inputRelativeURL = $_SERVER[ "REDIRECT_DOCUMENT_URI" ];

		$outputFolder = $data[ "flatoutputfolder" ];
		$outputFolder = $this->removeTokens( $outputFolder );
		$outputFolder = $this->collapseSlashes( $outputFolder );

		$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . "?" . self::SUBREQ . "=1";
		$outputFile = $outputFolder . $inputRelativeURL;

		$this->fetchAndWrite( $inputURL, $outputFile );

		$this->syncFolders( $outputFolder );
	}

	private function fetchAndWrite( $url, $outFile )
	{
		$outPath = dirname( $outFile );

		if( ! file_exists( $outPath ) )
		{
			mkdir( $outPath, 0777, true );
		}

		$contents = file_get_contents( $url );

		file_put_contents( $outFile, $contents );
	}

	private function syncFolders( $outputFolder, $debug = false )
	{
		$data = $this->settings->get();

		$syncFolders = $data[ "syncfolders" ];
		$syncFolders = str_replace( "\r\n", "\n", $syncFolders );
		$syncFolders = explode( "\n", $syncFolders );

		foreach( $syncFolders as $rawSyncFolder )
		{
			$syncFolder = trim( $rawSyncFolder );
			$realSyncFolder = realpath( $syncFolder );

			if( $realSyncFolder !== false )
			{
				$incOutPath = $outputFolder . DIRECTORY_SEPARATOR . basename( $realSyncFolder );

				if( ! file_exists( $incOutPath ) )
				{
					if( $debug ) echo "mkdir " . $incOutPath . "\n";
					mkdir( $incOutPath, 0777, true );
				}

				$command = "robocopy \"" . $realSyncFolder . "\" \"" . realpath( $incOutPath ) . "\" /PURGE /S /XD .svn";

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

		$data = $this->settings->get();

		if( ! isset( $data[ "flatoutputfolder" ] ) ) $data[ "flatoutputfolder" ] = "";
		if( ! isset( $data[ "syncfolders" ] ) ) $data[ "syncfolders" ] = "";
		if( ! isset( $data[ "batch" ] ) ) $data[ "batch" ] = "";

		?><div class="expando" title="Toggle advanced">
			<label>
				<span>Destination folder for flattened files<br /><em>Relative to dash.php<br />%1, %2, %3 etc act as parameter tokens</em></span>
				<input type="text" name="<?php echo $this->name; ?>[flatoutputfolder]" value="<?php echo $data[ "flatoutputfolder" ]; ?>">
			</label>
			<label>
				<span>Folders to be sync'd as-is (using robocopy)<br /><em>Specify one per line, relative to dash.php</em></span>
				<textarea name="<?php echo $this->name; ?>[syncfolders]"><?php echo $data[ "syncfolders" ]; ?></textarea>
			</label>
			<label>
				<span>Registered files for batch flatten (optional)<br /><em>Specify absolute paths (without host)</em></span>
				<textarea name="<?php echo $this->name; ?>[batch]"><?php echo $data[ "batch" ]; ?></textarea>
			</label>
		</div>
		<div class="expando" title="Toggle examples">
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
		</div>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "flatoutputfolder" ] = $post[ $this->name ][ "flatoutputfolder" ];
		$data[ "syncfolders" ] = $post[ $this->name ][ "syncfolders" ];
		$data[ "batch" ] = $post[ $this->name ][ "batch" ];

		$this->settings->set( $data );

		parent::updateSettings( $post );
	}

	private function isFlattenerSubRequest()
	{
		return strpos( $_SERVER[ "REQUEST_URI" ], self::SUBREQ ) !== false;
	}
}