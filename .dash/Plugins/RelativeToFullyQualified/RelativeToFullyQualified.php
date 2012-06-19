<?php

namespace Plugins\RelativeToFullyQualified;

use Dash\Event;
use Dash\CommittableArrayObject;

class RelativeToFullyQualified extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$content = $this->replace( $event->getContent(), $event->getParameters() );
		$event->setContent( $content );
	}

	private function replace( $content, Array $parameters )
	{
		require_once "url_to_absolute.php";
		$url = $this->getUrl( $parameters );

		// html srcs and hrefs
		$content = preg_replace_callback( "#(href|src)=([\"'])(?!http|\\#|//)([^\"']+)([\"'])#", function( $matches ) use ( $url ) {
			$attrib = $matches[ 1 ];
			$quote = $matches[ 2 ];
			$newUrl = url_to_absolute( $url, $matches[ 3 ] );

			return $attrib . "=" . $quote . $newUrl . $quote;
		}, $content );

		// css urls
		$content = preg_replace_callback( "#url\(([\"']?)([^\"']*)([\"']?)\)#", function( $matches ) use ( $url ) {
			$quote = $matches[ 1 ];
			$value = $matches[ 2 ];
			$newUrl = url_to_absolute( $url, $value );

			return "url(" . $quote . $newUrl . $quote . ")";
		}, $content );

		return $content;
	}

	protected function getURL( Array $parameters )
	{
		$baseUrl = $parameters[ "url" ];
		if( substr( $baseUrl , -1) == "/" )
		{
			$baseUrl = dirname( $baseUrl );
		}

		return $baseUrl;
	}
}