<?php

namespace Plugins\Preparser;

use Symfony\Component\EventDispatcher\Event;

class ContentEvent extends Event
{
	private $content;

	public function __construct( $content )
	{
		$this->setContent( $content );
	}

	public function getContent()
	{
		return $this->content;
	}

	public function setContent( $content )
	{
		$this->content = $content;
	}
}