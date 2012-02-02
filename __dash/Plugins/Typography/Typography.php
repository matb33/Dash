<?php

namespace Plugins\Typography;

use Dash\Event;

class Typography extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "parse" ) );
	}

	public function parse( Event $event )
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