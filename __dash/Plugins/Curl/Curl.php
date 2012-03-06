<?php

namespace Plugins\Curl;

use Dash\Event;
use Dash\CommittableArrayObject;
use Plugins\AbstractCurl\AbstractCurl;

class Curl extends AbstractCurl
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
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

	public function addHeader( $header )
	{
		parent::addHeader( $header );
	}

	public function curl( $url )
	{
		// Promote curl method from protected to public
		return parent::curl( $url );
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><details>
			<summary>Toggle examples</summary>
			<p>cURL by path:
				<code>/-/Curl?path=/index.html</code>
			</p>
			<p>cURL by fully-qualified URL:
				<code>/-/Curl?url=http://www.website.com/index.html?qs=1</code>
			</p>
		</details>
		<?php
	}
}