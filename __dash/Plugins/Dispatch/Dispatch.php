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
			$event = new Event( $parameters );
			$this->dispatcher->dispatch( $eventName, $event );
		}
	}

	public function renderSettings()
	{
		parent::renderSettings();

		?><details>
			<summary>Toggle examples</summary>
			<code>/-/Dispatch?e=NameOfEvent</code>
		</details>
		<?php
	}
}