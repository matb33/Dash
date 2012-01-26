<?php

namespace Plugins\McMillanFix;

use Plugins\Preparser\ContentEvent;

class McMillanFix extends \Dash\Plugin
{
	public function init()
	{
		$this->dispatcher->addListener( "PREPARSER", array( $this, "parse" ) );
	}

	public function parse( ContentEvent $event )
	{
		$content = $event->getContent();

		$content = preg_replace( "/([^~])McMillan/", "$1<span class=\"mcm\">M<sup>c</sup>Millan</span>", $content );
		$content = str_replace( "~McMillan", "McMillan", $content );

		$event->setContent( $content );
	}
}