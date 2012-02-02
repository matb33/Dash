<?php

namespace Plugins\JSONEncode;

use Dash\Event;
use Plugins\AbstractCurl\AbstractCurl;

class JSONEncode extends AbstractCurl
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event )
	{
		$content = $this->encode( $event->getContent() );
		$event->setContent( $content );
	}

	public function run( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		$result = $this->curl( $url );

		if( $result[ "success" ] === true )
		{
			echo $this->encode( $result[ "content" ] );
		}
	}

	private function encode( $content )
	{
		return json_encode( $content );
	}
}