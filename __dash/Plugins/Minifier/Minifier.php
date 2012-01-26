<?php

/*

Minifier expects a specific configuration.
Paths are relative to dash.php.

Example configuration:

../inc/cache/combined.css => ../inc/cache/combined.min.css
../inc/cache/combined.js => ../inc/cache/combined.min.js

*/

namespace Plugins\Minifier;

use ErrorException;

class Minifier extends \Dash\Plugin
{
	public function init()
	{
		$data = $this->settings->get();

		if( ! $data[ "onshiftrefresh" ] || $data[ "onshiftrefresh" ] && $this->isShiftRefresh() )
		{
			$this->dispatcher->addListener( "BOF", array( $this, "minify" ), 10 );
		}
	}

	public function minify()
	{
		$data = $this->settings->get();
		$config = $data[ "configuration" ];
		$config = str_replace( "\r\n", "\n", $config );
		$basePath = dirname( $_SERVER[ "SCRIPT_FILENAME" ] );

		$sets = explode( "\n", trim( $config ) );

		foreach( $sets as $set )
		{
			$params = explode( "=>", trim( $set ), 2 );

			if( count( $params ) === 2 )
			{
				$inputFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $params[ 0 ] ) );
				$realInputFile = realpath( $inputFile );

				if( $realInputFile !== false )
				{
					$targetFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $params[ 1 ] ) );

					$this->ajaxmin( $realInputFile, $targetFile );
				}
				else
				{
					throw new ErrorException( "Invalid input file: " . $inputFile );
				}
			}
			else
			{
				throw new ErrorException( "Invalid configuration set, no equal arrow =&gt; found." );
			}
		}
	}

	private function ajaxmin( $inputFile, $outputFile )
	{
		if( strpos( strtolower( $outputFile ), ".css" ) !== false )
		{
			$type = "css";
		}
		else
		{
			$type = "js";
		}

		switch( $type )
		{
			case "css":
				exec( __DIR__ . DIRECTORY_SEPARATOR . "AjaxMin.exe -CSS -clobber:true " . $inputFile . " -o " . $outputFile );
			break;

			case "js":
			default:
				exec( __DIR__ . DIRECTORY_SEPARATOR . "AjaxMin.exe -JS -clobber:true -term " . $inputFile . " -o " . $outputFile );
		}
	}

	public function renderSettings()
	{
		parent::renderSettings();

		$data = $this->settings->get();

		if( ! isset( $data[ "onshiftrefresh" ] ) ) $data[ "onshiftrefresh" ] = false;
		if( ! isset( $data[ "configuration" ] ) ) $data[ "configuration" ] = "";

		?><div class="expando">
			<label>
				<input type="checkbox" name="<?php echo $this->name; ?>[onshiftrefresh]"<?php echo $data[ "onshiftrefresh" ] ? ' checked="checked"' : ""; ?>  />
				<span>Check to run only on Shift+Refresh (Ctrl+Refresh on some browsers). Unchecked will always run.</span>
			</label>
			<label>
				<span>Configuration:</span>
				<textarea name="<?php echo $this->name; ?>[configuration]"><?php echo $data[ "configuration" ]; ?></textarea>
			</label>
		</div>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "onshiftrefresh" ] = isset( $post[ $this->name ][ "onshiftrefresh" ] );
		$data[ "configuration" ] = $post[ $this->name ][ "configuration" ];

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
}