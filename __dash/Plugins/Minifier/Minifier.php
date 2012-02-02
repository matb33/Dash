<?php

namespace Plugins\Minifier;

use ErrorException;

use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class Minifier extends AbstractShiftRefresh
{
	public function init()
	{
		if( $this->isShiftRefresh() )
		{
			$this->addListeners( array( $this, "minify" ) );
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

		if( ! isset( $data[ "configuration" ] ) ) $data[ "configuration" ] = "";

		?><div class="expando" title="Toggle advanced">
			<label>
				<span>Configuration:<br /><em>Paths are relative to dash.php</em></span>
				<textarea name="<?php echo $this->name; ?>[configuration]"><?php echo $data[ "configuration" ]; ?></textarea>
			</label>
		</div>
		<div class="expando" title="Toggle examples">
			<p>Example configuration:
			<code>../inc/cache/combined.css => ../inc/cache/combined.min.css
../inc/cache/combined.js => ../inc/cache/combined.min.js</code></p>
		</div>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "configuration" ] = $post[ $this->name ][ "configuration" ];

		$this->settings->set( $data );

		parent::updateSettings( $post );
	}
}