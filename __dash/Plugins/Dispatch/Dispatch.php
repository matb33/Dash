<?php

namespace Plugins\Dispatch;

use Dash\Event;

class Dispatch extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		$eventName = $parameters[ "e" ];

		if( strlen( $eventName ) > 0 )
		{
			$event = new Event( $eventName, $parameters );
			$this->dispatcher->dispatch( $eventName, $event );
		}
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><details>
			<summary>Toggle examples</summary>
			<code>/-/Dispatch?e=NameOfEvent</code>
		</details>
		<?php
	}
}