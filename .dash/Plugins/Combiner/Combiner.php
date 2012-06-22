<?php

namespace Plugins\Combiner;

use ErrorException;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;
use Plugins\Curl\Curl;

class Combiner extends AbstractShiftRefresh
{
	private $curl;

	public function init()
	{
		// Until we get Traits in PHP 5.4, we'll create a private instance of the Curl plugin
		$this->curl = new Curl();

		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		if( $this->testShiftRefresh( $settings ) )
		{
			$config = $settings->offsetGet( "configuration" );
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
						$inputContents = "";

						if( strpos( $rawInputFile, "//" ) )
						{
							// Read from URL
							$url = trim( str_replace( ":///", "://" . $_SERVER[ "HTTP_HOST" ] . "/", $rawInputFile ) );
							$result = $this->curl->curl( $url );

							if( $result[ "success" ] === true )
							{
								$inputContents = $result[ "content" ];
							}
							else
							{
								throw new ErrorException( "Invalid input URL: " . $url );
							}
						}
						else
						{
							// Read from filesystem
							$inputFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $rawInputFile ) );
							$realInputFile = realpath( $inputFile );

							if( $realInputFile !== false )
							{
								$inputContents = $this->fileGetContents( $realInputFile );
							}
							else
							{
								throw new ErrorException( "Invalid input file: " . $inputFile );
							}
						}

						$contents = ( $contents . $inputContents . PHP_EOL . PHP_EOL );
					}

					if( strlen( $contents ) > 0 )
					{
						file_put_contents( $targetFile, $contents );
						chmod( $targetFile, 0777 );
					}
				}
				else
				{
					throw new ErrorException( "Invalid configuration set, no equal sign found." );
				}
			}
		}
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

			<code>../inc/styles/reset.css
+ ../inc/fonts/universltstd/stylesheet.css
+ ../inc/styles/mixins.less.css
+ ../inc/styles/typography.less.css
+ http:///inc/styles/get_this_file_using_curl.css
+ ../inc/styles/app.less.css
= ../inc/cache/combined.less.css

../inc/scripts/common.js
+ http:///inc/scripts/get_this_file_using_curl.js
+ ../inc/scripts/app.js
= ../inc/cache/combined.js</code></p>
		</details>
		<?php
	}
}