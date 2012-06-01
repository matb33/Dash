<?php

namespace Plugins\Preparser;

use Dash\Event;
use Plugins\AbstractCurl\AbstractCurl;

class Preparser extends AbstractCurl
{
	const SUBREQ = "PREPARSER_SUBREQ";

	public function run( Array $parameters )
	{
		$content = $this->dispatchEvent( "Preparser.beforeCurl", NULL, $parameters );

		if( $content !== NULL )
		{
			echo $content;
		}
		else
		{
			$url = $this->getURL( $parameters );
			$url .= ( strpos( $url, "?" ) === false ? "?" : "&" ) . self::SUBREQ . "=1&REQUEST_URI=" . $_SERVER[ "REQUEST_URI" ];

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

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><p><em>Note: You must add/remove this block of Rewrite code to the .htaccess file to enable/disable the Preparser plugin:</em></p>
		<code>RewriteRule Preparser - [L]
RewriteCond %{QUERY_STRING} !<?php echo self::SUBREQ . "\n"; ?>
RewriteCond %{REQUEST_URI} !dash.php
RewriteRule ^(.*\.html)$ /-/Preparser?path=$1 [L,QSA]</code>
		<h3>Events you can listen to:</h3>
		<ul>
			<li><strong>Preparser.beforeCurl</strong> : Fires before cURL request. Set content to non-NULL value to echo content and prevent cURL.</li>
			<li><strong>Preparser.afterCurl</strong> : Fires after cURL request, allowing you to modify retrieved content. This is the main event hook you are probably looking to use.</li>
		</ul>
		<?php
	}
}