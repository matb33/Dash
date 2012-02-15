<?php

namespace Plugins\Preparser;

use Dash\Event;
use Plugins\AbstractCurl\AbstractCurl;

class Preparser extends AbstractCurl
{
	const SUBREQ = "PREPARSER_SUBREQ";

	public function run( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		$url .= ( strpos( $url, "?" ) === false ? "?" : "&" ) . self::SUBREQ . "=1&REQUEST_URI=" . $_SERVER[ "REQUEST_URI" ];

		$content = $this->dispatchEvent( "Preparser.beforeCurl", NULL, $parameters );

		if( $content !== NULL )
		{
			echo $content;
		}
		else
		{
			$this->curlAndPreparse( $url );
		}
	}

	private function curlAndPreparse( $url )
	{
		$result = $this->curl( $url );

		if( $result[ "success" ] === true )
		{
			$this->repeatResponseHeaders( $result[ "header" ] );
			echo $this->dispatchEvent( "Preparser.afterCurl", $result[ "content" ] );
		}

		return $result[ "success" ];
	}

	public function renderSettings()
	{
		parent::renderSettings();

		?><p><em>Note: You must add/remove this block of Rewrite code to the .htaccess file to enable/disable the Preparser plugin:</em></p>
		<code>RewriteRule Preparser - [L]
RewriteCond %{QUERY_STRING} !<?php echo self::SUBREQ . "\n"; ?>
RewriteCond %{REQUEST_URI} !dash.php
RewriteRule ^(.*\.html)$ /-/Preparser?path=$1 [L,QSA]</code>
		<?php
	}
}