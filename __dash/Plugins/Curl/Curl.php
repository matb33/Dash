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
		$result = $this->curl( $event->getParameters() );
		$content = $event->getContent() . $result[ "content" ];
		$event->setContent( $content );
	}

	public function run( Array $parameters )
	{
		$result = $this->curl( $parameters );

		if( $result[ "success" ] === true )
		{
			echo $result[ "content" ];
		}
	}

	protected function curl( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		return parent::curl( $url );
	}

	protected function getURL( Array $parameters )
	{
		array_pop( $parameters );

		$path = "/" . implode( "/", $parameters );
		return "http://" . $_SERVER[ "HTTP_HOST" ] . $path . "?" . $_SERVER[ "QUERY_STRING" ];
	}
}