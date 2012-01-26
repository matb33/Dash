<?php

/*

LESSCompiler expects a specific configuration.
Paths are relative to dash.php.

Example configuration:

../inc/cache/combined.less.css => ../inc/cache/combined.css
../inc/styles/ultra-narrow.less.css => ../inc/cache/ultra-narrow.css
../inc/styles/narrow.less.css => ../inc/cache/narrow.css
../inc/styles/wide.less.css => ../inc/cache/wide.css

*/

namespace Plugins\LESSCompiler;

use ErrorException;

class LESSCompiler extends \Dash\Plugin
{
	public function init()
	{
		$data = $this->settings->get();

		if( ! $data[ "onshiftrefresh" ] || $data[ "onshiftrefresh" ] && $this->isShiftRefresh() )
		{
			$this->dispatcher->addListener( "BOF", array( $this, "compile" ), 20 );
		}
	}

	public function compile()
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

	private function less( $in, $out )
	{
		$command = "cscript //nologo \"" . __DIR__ . DIRECTORY_SEPARATOR . "lessc.wsf\" \"" . $in . "\" \"" . $out . "\"";

		$outputArray = NULL;
		$errors = array();

		exec( $command . " 2>&1", $outputArray );

		if( is_array( $outputArray ) )
		{
			$output = implode( "\n", $outputArray );

			if( strpos( $output, "ERR" ) !== false )
			{
				$errors[] = array(
					"command" => $command,
					"in" => $in,
					"out" => $out,
					"output" => $output
				);
			}
		}

		if( count( $errors ) )
		{
			$this->displayErrors( $errors );
			return false;
		}

		return true;
	}

	private function displayErrors( Array $errors )
	{
		?><div xmlns="http://www.w3.org/1999/xhtml">
		<?php

			foreach( $errors as $info )
			{
				?><fieldset style="position: absolute; top: 0px; left: 0px; z-index: 99999; border: 3px dashed red; background-color: #fff; color: #000; margin: 10px; padding: 10px; box-shadow: 0px 0px 50px #000;">
					<legend style="font-weight: bold; background-color: #fff; font-size: 30px; padding: 5px;">LESS to CSS compilation error</legend>
					<ul style="margin-top: 0px; margin-bottom: 0px;">
						<li><strong>IN: </strong><?php echo $info[ "in" ]; ?></li>
						<li><strong>OUT: </strong><?php echo $info[ "out" ]; ?></li>
					</ul>
					<pre style="margin: 0px; padding: 10px; border: 1px dotted #333; background-color: #fff9da; font-size: 13px;"><?php echo $info[ "output" ]; ?></pre>
				</fieldset>
				<?php
			}

		?></div>
		<?php
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