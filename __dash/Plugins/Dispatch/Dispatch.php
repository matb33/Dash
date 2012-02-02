<?php

namespace Plugins\Dispatch;

use Dash\Event;

class Dispatch extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		$eventName = array_shift( $parameters );

		if( strlen( $eventName ) > 0 )
		{
			$eventParameters = new Event( $parameters );
			$this->dispatcher->dispatch( $eventName, $eventParameters );
		}
	}
}