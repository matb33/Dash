<?php

namespace Plugins\URLReplace;

use Dash\Event;
use Plugins\AbstractCurl\AbstractCurl;

class URLReplace extends AbstractCurl
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event )
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
		$host = $parameters[ 1 ];

		// html urls and hrefs
		$content = preg_replace( "#(href|src)=([\"'])(?!http|\\#|//)([^\"']+)([\"'])#", "$1=$2//" . $host . "$3$2", $content );

		// css urls
		$content = preg_replace( "#url\(([\"']?)([^\"']*)([\"']?)\)#", "url($1//" . $host . "$2$1)", $content );

		return $content;
	}

	protected function getURL( Array $parameters )
	{
		array_pop( $parameters );

		$path = "/" . implode( "/", $parameters );
		return "http://" . $_SERVER[ "HTTP_HOST" ] . $path . "?" . $_SERVER[ "QUERY_STRING" ];
	}
}