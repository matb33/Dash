<?php

namespace Plugins\Preparser;

class Preparser extends \Dash\Plugin
{
	const SUBREQ = "PREPARSER_SUBREQ";

	public function run( Array $parameters )
	{
		$this->preparse( $this->getURL( $parameters ) );
	}

	private function preparse( $url )
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

			$this->repeatResponseHeaders( $header );

			$event = new ContentEvent( $content );
			$this->dispatcher->dispatch( "PREPARSER", $event );
			echo $event->getContent();

			return true;
		}

		return false;
	}

	private function getRequestHeaders()
	{
		$requestHeaders = apache_request_headers();
		$headers = array();

		foreach( $requestHeaders as $key => $value )
		{
			$headers[] = $key . ": " . $value;
		}

		return $headers;
	}

	private function repeatResponseHeaders( $header )
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

	private function getURL( Array $parameters )
	{
		$path = "/" . implode( "/", $parameters );
		return "http://" . $_SERVER[ "HTTP_HOST" ] . $path . "?" . self::SUBREQ . "=1&" . $_SERVER[ "QUERY_STRING" ];
	}

	public function renderSettings()
	{
		parent::renderSettings();

		?><p><em>Note: You must add/remove this block of Rewrite code to the .htaccess file to enable/disable the Preparser plugin:</em></p>
		<code>RewriteRule Preparser - [L]
RewriteCond %{QUERY_STRING} !<?php echo self::SUBREQ . "\n"; ?>
RewriteRule ^(.*\.html)$ /-/Preparser/$1 [L,QSA]</code>
		<?php
	}
}