<?php

namespace Plugins\McMillanFix;

use Dash\Event;
use Dash\CommittableArrayObject;

class McMillanFix extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$content = $event->getContent();

		$content = preg_replace( "/([^~])McMillan/", "$1<span class=\"mcm\">M<sup>c</sup>Millan</span>", $content );
		$content = str_replace( "~McMillan", "McMillan", $content );

		$event->setContent( $content );
	}
}