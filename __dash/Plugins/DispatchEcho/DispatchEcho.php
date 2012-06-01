<?php

namespace Plugins\DispatchEcho;

use Dash\Event;

class DispatchEcho extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		$eventName = $parameters[ "e" ];

		if( strlen( $eventName ) > 0 )
		{
			$event = new Event( $eventName, $parameters );
			$this->dispatcher->dispatch( $eventName, $event );
			echo $event->getContent();
		}
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><details>
			<summary>Toggle examples</summary>
			<code>/-/DispatchEcho?e=NameOfEvent</code>
		</details>
		<?php
	}
}