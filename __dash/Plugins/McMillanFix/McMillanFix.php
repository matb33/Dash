<?php

namespace Plugins\McMillanFix;

use Dash\Event;

class McMillanFix extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "parse" ) );
	}

	public function parse( Event $event )
	{
		$content = $event->getContent();

		$content = preg_replace( "/([^~])McMillan/", "$1<span class=\"mcm\">M<sup>c</sup>Millan</span>", $content );
		$content = str_replace( "~McMillan", "McMillan", $content );

		$event->setContent( $content );
	}
}