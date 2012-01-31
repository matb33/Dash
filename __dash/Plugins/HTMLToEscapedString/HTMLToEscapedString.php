<?php

namespace Plugins\HTMLToEscapedString;

use Plugins\AbstractCurl\AbstractCurl;

class HTMLToEscapedString extends AbstractCurl
{
	public function run( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		$result = $this->curl( $url );

		if( $result[ "success" ] === true )
		{
			echo json_encode( str_replace( array( "\r\n", "\r", "\n", "\t" ), "", $result[ "content" ] ) );
		}
	}
}