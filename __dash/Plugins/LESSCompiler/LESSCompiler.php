<?php

namespace Plugins\LESSCompiler;

use Exception;
use ErrorException;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class LESSCompiler extends AbstractShiftRefresh
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function run( Array $parameters )
	{
		$inputFile = $parameters[ "file" ];
		$realInputFile = realpath( $inputFile );

		if( $realInputFile !== false )
		{
			echo $this->less( $realInputFile );
		}
		else
		{
			throw new ErrorException( "Invalid input file: " . $inputFile );
		}
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

						$this->less( $realInputFile, $targetFile );
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

	private function less( $in, $out = NULL )
	{
		require_once "lessphp/lessc.inc.php";

		try
		{
			if( $out !== NULL )
			{
				\lessc::ccompile( $in, $out );
			}
			else
			{
				$less = new \lessc( $in );
				$output = $less->parse();
				return $output;
			}
		}
		catch( Exception $ex )
		{
			$this->displayError( $in, $out, $ex->getMessage() );
			return false;
		}
	}

	private function displayError( $in, $out, $error )
	{
		?><div xmlns="http://www.w3.org/1999/xhtml">
			<fieldset style="position: absolute; top: 0px; left: 0px; z-index: 99999; border: 3px dashed red; background-color: #fff; color: #000; margin: 10px; padding: 10px; box-shadow: 0px 0px 50px #000;">
				<legend style="font-weight: bold; background-color: #fff; font-size: 30px; padding: 5px;">LESS to CSS compilation error</legend>
				<ul style="margin-top: 0px; margin-bottom: 0px;">
					<li><strong>IN: </strong><?php echo $in; ?></li>
					<li><strong>OUT: </strong><?php echo $out; ?></li>
				</ul>
				<pre style="margin: 0px; padding: 10px; border: 1px dotted #333; background-color: #fff9da; font-size: 13px;"><?php echo $error; ?></pre>
			</fieldset>
		</div>
		<?php
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
			<span>Configuration:</span>
			<textarea data-bind="value: configuration"></textarea>
		</label>
		<details>
			<summary>Toggle examples</summary>
			<p>Example configuration:
			<code>../inc/cache/combined.less.css => ../inc/cache/combined.css
../inc/styles/ultra-narrow.less.css => ../inc/cache/ultra-narrow.css
../inc/styles/narrow.less.css => ../inc/cache/narrow.css
../inc/styles/wide.less.css => ../inc/cache/wide.css</code></p>
			<p>Example run usage:
			<code>/-/LESSCompiler?file=../inc/styles/style.less.css</code></p>
		</details>
		<?php
	}
}