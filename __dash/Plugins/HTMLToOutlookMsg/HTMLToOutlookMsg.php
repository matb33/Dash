<?php

namespace Plugins\HTMLToOutlookMsg;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class HTMLToOutlookMsg extends AbstractShiftRefresh
{
	private $curl;

	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		if( $this->testShiftRefresh( $settings ) )
		{
			$converterExe = trim( $settings->offsetGet( "converterexe" ) );

			$html = $event->getContent();

			if( substr( $converterExe, -1 ) === "-" )
			{
				$msg = $this->convertUsingStandardStreams( $html, $converterExe );
			}
			else
			{
				$msg = $this->convertUsingTempFiles( $html, $converterExe );
			}

			$event->setContent( $msg );
		}
	}

	private function convertUsingTempFiles( $html, $converterExe )
	{
		$inputFile = tempnam( sys_get_temp_dir(), "dash" ) . ".html";
		$outputFile = tempnam( sys_get_temp_dir(), "dash" ) . ".msg";

		file_put_contents( $inputFile, $html );

		$command = "{$converterExe} \"{$inputFile}\" \"{$outputFile}\"";
		exec( $command );

		$msg = file_get_contents( $outputFile );

		unlink( $inputFile );
		unlink( $outputFile );

		return $msg;
	}

	private function convertUsingStandardStreams( $html, $converterExe )
	{
		// Currently doesn't work, hangs on stream_get_contents
		set_time_limit( 5 );

		$descriptorSpec = array(
		   0 => array( "pipe", "r" ),  // stdin is a pipe that the child will read from
		   1 => array( "pipe", "w" ),  // stdout is a pipe that the child will write to
		   2 => array( "pipe", "w" )
		);

		$process = proc_open( $converterExe, $descriptorSpec, $pipes );
		stream_set_blocking( $pipes[ 2 ], 0 );

		if( is_resource( $process ) )
		{
		    // $pipes now looks like this:
		    // 0 => writeable handle connected to child stdin
		    // 1 => readable handle connected to child stdout

		    fwrite( $pipes[ 0 ], $html );
		    fclose( $pipes[ 0 ] );

		    $msg = stream_get_contents( $pipes[ 1 ] );
		    fclose( $pipes[ 1 ] );

		    $returnValue = proc_close( $process );

		    return $msg;
		}

		return NULL;
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "converterexe" ) ) $settings->offsetSet( "converterexe", "C:\\web\\services\\webtools\\code\\HTMLToOutlookMsg\\HTMLToOutlookMsg\\bin\\Release\\HTMLToOutlookMsg.exe -" );

		?>converterexe: ko.observable( <?php echo json_encode( $settings->offsetGet( "converterexe" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Converter EXE:</span>
			<input type="text" data-bind="value: converterexe" />
		</label>
		<details>
			<summary>Toggle examples</summary>
			<h3>Converter EXE:</h3>
			<code>C:\web\services\webtools\code\HTMLToOutlookMsg\HTMLToOutlookMsg\bin\Release\HTMLToOutlookMsg.exe -</code>
		</details>
		<?php
	}
}