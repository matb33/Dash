<?php

namespace Plugins\Typography;

use Plugins\Preparser\ContentEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Typography extends \Dash\Plugin
{
	public function init()
	{
		$this->dispatcher->addListener( "PREPARSER", array( $this, "parse" ) );
	}

	public function parse( ContentEvent $event )
	{
		require_once "php-typography/php-typography.php";

		$typo = new \phpTypography();
		$typo->set_style_ampersands( false );
		$typo->set_style_caps( false );
		$typo->set_style_numbers( false );
		$typo->set_style_initial_quotes( false );

		$content = $event->getContent();
		$content = $typo->process( $content );
		$event->setContent( $content );
	}
}