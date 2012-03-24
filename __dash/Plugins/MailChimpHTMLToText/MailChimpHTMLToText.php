<?php

namespace Plugins\MailChimpHTMLToText;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class MailChimpHTMLToText extends AbstractShiftRefresh
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
			$inputName = $settings->offsetGet( "inputname" );
			$scrapeTokenStart = $settings->offsetGet( "scrapetokenstart" );
			$scrapeTokenEnd = $settings->offsetGet( "scrapetokenend" );

			$html = $event->getContent();
			$text = $this->convert( $html, $serviceURL, $inputName, $scrapeTokenStart, $scrapeTokenEnd );
			$event->setContent( $text );
		}
	}

	private function convert( $html, $serviceURL, $inputName, $scrapeTokenStart, $scrapeTokenEnd )
	{
		set_time_limit( 0 );
	
		$fields = array( $inputName => $html );
		$postFields = http_build_query( $fields );

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
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $postFields );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"Content-Length: " . strlen( $postFields )
		));

		$content = curl_exec( $ch );
		$error = curl_error( $ch );
		curl_close( $ch );

		if( $error === "" )
		{
			$tokenStart = preg_quote( $scrapeTokenStart, "/" );
			$tokenEnd = preg_quote( $scrapeTokenEnd, "/" );

			preg_match( "/{$tokenStart}(.*?){$tokenEnd}/ms", $content, $matches );

			if( count( $matches ) )
			{
				$text = trim( $matches[ 1 ] );
				return $text;
			}
		}

		return "Error converting.";
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "serviceurl" ) ) $settings->offsetSet( "serviceurl", "http://beaker.mailchimp.com/html-to-text" );
		if( ! $settings->offsetExists( "inputname" ) ) $settings->offsetSet( "inputname", "html" );
		if( ! $settings->offsetExists( "scrapetokenstart" ) ) $settings->offsetSet( "scrapetokenstart", '<textarea name="text" cols="100" rows="12">' );
		if( ! $settings->offsetExists( "scrapetokenend" ) ) $settings->offsetSet( "scrapetokenend", '</textarea>' );

		?>serviceurl: ko.observable( <?php echo json_encode( $settings->offsetGet( "serviceurl" ) ); ?> ),
		inputname: ko.observable( <?php echo json_encode( $settings->offsetGet( "inputname" ) ); ?> ),
		scrapetokenstart: ko.observable( <?php echo json_encode( $settings->offsetGet( "scrapetokenstart" ) ); ?> ),
		scrapetokenend: ko.observable( <?php echo json_encode( $settings->offsetGet( "scrapetokenend" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Service URL:</span>
			<input type="text" data-bind="value: serviceurl" />
		</label>
		<label>
			<span>Input Name:</span>
			<input type="text" data-bind="value: inputname" />
		</label>
		<label>
			<span>Scrape Token Start:</span>
			<input type="text" data-bind="value: scrapetokenstart" />
		</label>
		<label>
			<span>Scrape Token End:</span>
			<input type="text" data-bind="value: scrapetokenend" />
		</label>
		<details>
			<summary>Toggle examples</summary>
			<h3>Service URL:</h3>
			<code>http://beaker.mailchimp.com/html-to-text</code>
			<h3>Input Name:</h3>
			<code>html</code>
			<h3>Scrape Token Start:</h3>
			<code>&lt;textarea name="text" cols="100" rows="12"&gt;</code>
			<h3>Scrape Token End:</h3>
			<code>&lt;/textarea&gt;</code>
		</details>
		<?php
	}
}