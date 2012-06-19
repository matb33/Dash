<?php

namespace Plugins\AbstractCurl;

abstract class AbstractCurl extends \Dash\Plugin
{
	private $additionalHeaders = array();

	protected function curl( $url )
	{
		set_time_limit( 0 );
	
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

		$headers = array_merge( $headers, $this->additionalHeaders );

		return $headers;
	}

	protected function repeatResponseHeaders( $header )
	{
		$headers = explode( "\n", $header );

		foreach( $headers as $index => $line )
		{
			// Only repeating first header and content-type, otherwise we get some caching issues
			if( $index === 0 || strpos( strtolower( $line ), "content-type" ) !== false )
			{
				header( $line );
			}
		}
	}

	protected function addHeader( $header )
	{
		$this->additionalHeaders[] = $header;
	}

	protected function getURL( Array $parameters )
	{
		if( isset( $parameters[ "url" ] ) )
		{
			return $parameters[ "url" ];
		}
		else
		{
			$path = ltrim( $parameters[ "path" ], "/" );
			unset( $parameters[ "path" ] );

			$query = http_build_query( $parameters );

			return "http://" . $_SERVER[ "HTTP_HOST" ] . "/" . $path . ( strlen( $query ) > 0 ? "?" . $query : "" );
		}
	}
}