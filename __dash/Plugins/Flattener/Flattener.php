<?php

/*

Flattener expects a specific configuration for batch config.
Paths are relative to dash.php (except for batch config).

Example batch config:

/about.html
/careers.html
/contact.html
/error.html
/index.html
/people.html
/subscribe.html
/terms.html
/work.html

*/

namespace Plugins\Flattener;

use ErrorException;

use Symfony\Component\EventDispatcher\EventDispatcher;

class Flattener extends \Dash\Plugin
{
	const SUBREQ = "FLATTENER_SUBREQ";
	const EVENT = "EOF";

	public function init()
	{
		if( ! $this->isFlattenerSubRequest() )
		{
			$data = $this->settings->get();

			if( ! $data[ "onshiftrefresh" ] || $data[ "onshiftrefresh" ] && $this->isShiftRefresh() )
			{
				$this->dispatcher->addListener( self::EVENT, array( $this, "flatten" ) );
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

			foreach( $inputRelativeURLs as $rawInputRelativeURL )
			{
				$inputRelativeURL = trim( $rawInputRelativeURL );

				if( strlen( $inputRelativeURL ) > 0 )
				{
					$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . "?" . self::SUBREQ . "=1";
					$outputFile = $data[ "flatoutputfolder" ] . $inputRelativeURL;

					echo "flatten url: " . $inputURL . ", path: " . $outputFile . "\n";
					$this->fetchAndWrite( $inputURL, $outputFile );
				}
			}

			$this->syncFolders( true );
		}
	}

	public function flatten()
	{
		// Single functionality

		$data = $this->settings->get();

		$inputRelativeURL = $_SERVER[ "REDIRECT_DOCUMENT_URI" ];

		$inputURL = "http://" . $_SERVER[ "HTTP_HOST" ] . $inputRelativeURL . "?" . self::SUBREQ . "=1";
		$outputFile = $data[ "flatoutputfolder" ] . $inputRelativeURL;

		$this->fetchAndWrite( $inputURL, $outputFile );

		$this->syncFolders();
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

	private function syncFolders( $debug = false )
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
				$incOutPath = $data[ "flatoutputfolder" ] . DIRECTORY_SEPARATOR . basename( $realSyncFolder );

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

	public function renderSettings()
	{
		parent::renderSettings();

		$data = $this->settings->get();

		if( ! isset( $data[ "onshiftrefresh" ] ) ) $data[ "onshiftrefresh" ] = false;
		if( ! isset( $data[ "flatoutputfolder" ] ) ) $data[ "flatoutputfolder" ] = "";
		if( ! isset( $data[ "syncfolders" ] ) ) $data[ "syncfolders" ] = "";
		if( ! isset( $data[ "batch" ] ) ) $data[ "batch" ] = "";

		?><p>This plugin listens to the <?php echo self::EVENT; ?> event.</p>
		<div class="expando">
			<label>
				<input type="checkbox" name="<?php echo $this->name; ?>[onshiftrefresh]"<?php echo $data[ "onshiftrefresh" ] ? ' checked="checked"' : ""; ?>  />
				<span>Check to run only on Shift+Refresh (Ctrl+Refresh on some browsers). Unchecked will always run.</span>
			</label>
			<label>
				<span>Destination folder for flattened files<br /><em>Relative to dash.php</em></span>
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
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "onshiftrefresh" ] = isset( $post[ $this->name ][ "onshiftrefresh" ] );
		$data[ "flatoutputfolder" ] = $post[ $this->name ][ "flatoutputfolder" ];
		$data[ "syncfolders" ] = $post[ $this->name ][ "syncfolders" ];
		$data[ "batch" ] = $post[ $this->name ][ "batch" ];

		$this->settings->set( $data );

		parent::updateSettings( $post );
	}

	private function isShiftRefresh()
	{
		$headers = apache_request_headers();

		foreach( $headers as $key => $value )
		{
			if( strtolower( $key ) == "cache-control" && strtolower( $value ) == "no-cache" ) return true;
			if( strtolower( $key ) == "pragma" && strtolower( $value ) == "no-cache" ) return true;
		}

		return false;
	}

	private function isFlattenerSubRequest()
	{
		return strpos( $_SERVER[ "REQUEST_URI" ], self::SUBREQ ) !== false;
	}
}