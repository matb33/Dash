<?php

namespace Plugins\Minifier;

use ErrorException;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class Minifier extends AbstractShiftRefresh
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		if( $this->testShiftRefresh( $settings ) )
		{
			$config = $settings->offsetGet( "configuration" );
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
				// $t1 = microtime( true );
				require_once "minify-2.1.5/min/lib/Minify/CSS/Compressor.php";
				$result = \Minify_CSS_Compressor::process( file_get_contents( $inputFile ) );
				file_put_contents( $outputFile, $result );
				// exec( __DIR__ . DIRECTORY_SEPARATOR . "AjaxMin.exe -CSS -clobber:true " . $inputFile . " -o " . $outputFile );
				// echo ( microtime( true ) - $t1 ) . " seconds elapsed on CSS min.";
			break;

			case "js":
			default:
				// $t1 = microtime( true );
				require_once "minify-2.1.5/min/lib/JSMinPlus.php";
				$result = \JSMinPlus::minify( file_get_contents( $inputFile ) );
				file_put_contents( $outputFile, $result );
				// exec( __DIR__ . DIRECTORY_SEPARATOR . "AjaxMin.exe -JS -clobber:true -term " . $inputFile . " -o " . $outputFile );
				// echo ( microtime( true ) - $t1 ) . " seconds elapsed on JS min.";
		}
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "configuration" ) ) $settings->offsetSet( "configuration", "" );

		?>configuration: ko.observable( <?php echo json_encode( $settings->offsetGet( "configuration" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Configuration:<br /><em>Paths are relative to dash.php</em></span>
			<textarea data-bind="value: configuration"></textarea>
		</label>
		<details>
			<summary>Toggle examples</summary>
			<p>Example configuration:
			<code>../inc/cache/combined.css => ../inc/cache/combined.min.css
../inc/cache/combined.js => ../inc/cache/combined.min.js</code></p>
		</details>
		<?php
	}
}