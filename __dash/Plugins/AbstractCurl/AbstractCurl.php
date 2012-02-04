<?php

namespace Plugins\AbstractCurl;

abstract class AbstractCurl extends \Dash\Plugin
{
	protected function curl( $url )
	{
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_COOKIESESSION, false );
		curl_setopt( $ch, CURLOPT_FAILONERROR, false );
		curl_setopt( $ch, CURLOPT_ENCODING, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->getRequestHeaders() );
		curl_setopt( $ch, CURLOPT_URL, $url );

		$rawContents = curl_exec( $ch );
		$info = curl_getinfo( $ch );
		$error = curl_error( $ch );
		curl_close( $ch );

		if( $error === "" )
		{
			if( $rawContents === false ) $rawContents = "";

			$header = ( string )substr( $rawContents, 0, $info[ "header_size" ] );
			$content = ( string )substr( $rawContents, $info[ "header_size" ] );

			return array( "success" => true, "header" => $header, "content" => $content );
		}

		return array( "success" => false, "error" => $error );
	}

	protected function getRequestHeaders()
	{
		$requestHeaders = apache_request_headers();
		$headers = array();

		foreach( $requestHeaders as $key => $value )
		{
			$headers[] = $key . ": " . $value;
		}

		return $headers;
	}

	protected function repeatResponseHeaders( $header )
	{
		$headers = explode( "\n", $header );

		foreach( $headers as $line )
		{
			// Only repeating content-type, otherwise we get some caching issues
			if( strpos( strtolower( $line ), "content-type" ) !== false )
			{
				header( $line );
			}
		}
	}

	protected function getURL( Array $parameters )
	{
		$path = "/" . implode( "/", $parameters );
		$path = "/" . str_replace( "@", "/", $path );

		return "http://" . $_SERVER[ "HTTP_HOST" ] . $path . "?" . $_SERVER[ "QUERY_STRING" ];
	}
}