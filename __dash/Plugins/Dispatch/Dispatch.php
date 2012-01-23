<?php

namespace Plugins\Dispatch;

class Dispatch extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		foreach( $parameters as $eventName )
		{
			if( strlen( $eventName ) > 0 )
			{
				$this->dispatcher->dispatch( $eventName );
			}
		}
	}
}