<?php

namespace Plugins\DispatchEcho;

use Dash\Event;

class DispatchEcho extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		$eventName = array_shift( $parameters );

		if( strlen( $eventName ) > 0 )
		{
			$event = new Event( $parameters );
			$this->dispatcher->dispatch( $eventName, $event );
			echo $event->getContent();
		}
	}
}