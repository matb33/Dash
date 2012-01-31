<?php

namespace Plugins\Combiner;

use ErrorException;

use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class Combiner extends AbstractShiftRefresh
{
	public function init()
	{
		if( $this->isShiftRefresh() )
		{
			$this->dispatcher->addListener( "BOF", array( $this, "combine" ), 30 );
		}
	}

	public function combine()
	{
		$data = $this->settings->get();
		$config = $data[ "configuration" ];
		$config = str_replace( "\r\n", "\n", $config );
		$basePath = dirname( $_SERVER[ "SCRIPT_FILENAME" ] );

		$sets = explode( "\n\n", trim( $config ) );

		foreach( $sets as $set )
		{
			$params = explode( "=", trim( $set ), 2 );

			if( count( $params ) === 2 )
			{
				$rawInputFiles = trim( $params[ 0 ] );
				$targetFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $params[ 1 ] ) );

				$inputFiles = explode( "+", $rawInputFiles );
				$contents = "";

				foreach( $inputFiles as $rawInputFile )
				{
					$inputFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $rawInputFile ) );
					$realInputFile = realpath( $inputFile );

					if( $realInputFile !== false )
					{
						$contents = ( $contents . file_get_contents( $realInputFile ) . PHP_EOL . PHP_EOL );
					}
					else
					{
						throw new ErrorException( "Invalid input file: " . $inputFile );
					}
				}

				if( strlen( $contents ) > 0 )
				{
					file_put_contents( $targetFile, $contents );
				}
			}
			else
			{
				throw new ErrorException( "Invalid configuration set, no equal sign found." );
			}
		}
	}

	public function renderSettings()
	{
		parent::renderSettings();

		$data = $this->settings->get();

		if( ! isset( $data[ "configuration" ] ) ) $data[ "configuration" ] = "";

		?><div class="expando" title="Toggle advanced">
			<label>
				<span>Configuration:</span>
				<textarea name="<?php echo $this->name; ?>[configuration]"><?php echo $data[ "configuration" ]; ?></textarea>
			</label>
		</div>
		<div class="expando" title="Toggle examples">
			<p>Example configuration:

			<code>../inc/styles/reset.css
+ ../inc/fonts/universltstd/stylesheet.css
+ ../inc/styles/mixins.less.css
+ ../inc/styles/typography.less.css
+ ../inc/styles/app.less.css
+ ../inc/styles/tiles.less.css
+ ../inc/styles/print.less.css
+ ../inc/styles/work-tiles.less.css
= ../inc/cache/combined.less.css

../inc/scripts/common.js
+ ../inc/scripts/app.js
= ../inc/cache/combined.js</code></p>
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