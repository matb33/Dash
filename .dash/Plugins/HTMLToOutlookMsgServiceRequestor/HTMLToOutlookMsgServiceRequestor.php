<?php

namespace Plugins\HTMLToOutlookMsgServiceRequestor;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class HTMLToOutlookMsgServiceRequestor extends AbstractShiftRefresh
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
			$serviceURL = $settings->offsetGet( "serviceurl" );

			$html = $event->getContent();
			$msgContent = $this->convert( $html, $serviceURL );
			$event->setContent( $msgContent );
		}
	}

	private function convert( $html, $serviceURL )
	{
		set_time_limit( 0 );

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_COOKIESESSION, false );
		curl_setopt( $ch, CURLOPT_FAILONERROR, false );
		curl_setopt( $ch, CURLOPT_ENCODING, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_URL, $serviceURL );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $html );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"Content-Length: " . strlen( $html ),
			"Content-Type: text/plain"
		));

		$content = curl_exec( $ch );
		$error = curl_error( $ch );
		curl_close( $ch );

		if( $error === "" )
		{
			return $content;
		}

		return "Error: " . $error;
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "serviceurl" ) ) $settings->offsetSet( "serviceurl", "http://localhost:52169/" );

		?>serviceurl: ko.observable( <?php echo json_encode( $settings->offsetGet( "serviceurl" ) ); ?> )
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Service URL:</span>
			<input type="text" data-bind="value: serviceurl" />
		</label>
		<details>
			<summary>Toggle examples</summary>
			<h3>Service URL:</h3>
			<code>http://localhost:52169/</code>
		</details>
		<?php
	}
}