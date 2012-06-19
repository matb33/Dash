<?php

namespace Plugins\URLReplace;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractCurl\AbstractCurl;

class URLReplace extends AbstractCurl
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

	public function run( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		$result = $this->curl( $url );

		if( $result[ "success" ] === true )
		{
			echo $this->replace( $result[ "content" ], $parameters );
		}
	}

	private function replace( $content, Array $parameters )
	{
		array_shift( $parameters );

		$path = "//" . implode( "/", $parameters );

		// html urls and hrefs
		$content = preg_replace( "#(href|src)=([\"'])(?!http|\\#|//)([^\"']+)([\"'])#", "$1=$2" . $path . "$3$2", $content );

		// css urls
		$content = preg_replace( "#url\(([\"']?)([^\"']*)([\"']?)\)#", "url($1" . $path . "$2$1)", $content );

		return $content;
	}

	protected function getURL( Array $parameters )
	{
		$path = "/" . str_replace( "@", "/", $parameters[ 0 ] );

		return "http://" . $_SERVER[ "HTTP_HOST" ] . $path . "?" . $_SERVER[ "QUERY_STRING" ];
	}
}