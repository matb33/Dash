<?php

namespace Plugins\Curl;

use Dash\Event;
use Plugins\AbstractCurl\AbstractCurl;

class Curl extends AbstractCurl
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event )
	{
		$url = $this->getURL( $event->getParameters() );
		$result = $this->curl( $url );
		$content = $event->getContent() . $result[ "content" ];
		$event->setContent( $content );
	}

	public function run( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		$result = $this->curl( $url );

		if( $result[ "success" ] === true )
		{
			echo $result[ "content" ];
		}
	}

	public function curl( $url )
	{
		// Promote curl method from protected to public
		return parent::curl( $url );
	}

	public function getURL( Array $parameters )
	{
		array_pop( $parameters );

		$path = "/" . implode( "/", $parameters );
		return "http://" . $_SERVER[ "HTTP_HOST" ] . $path . "?" . $_SERVER[ "QUERY_STRING" ];
	}
}