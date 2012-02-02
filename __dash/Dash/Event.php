<?php

namespace Dash;

class Event extends \Symfony\Component\EventDispatcher\Event
{
	private $parameters;
	private $content;

	public function __construct( Array $parameters, $content = NULL )
	{
		$this->setParameters( $parameters );
		$this->setContent( $content );
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function setParameters( $parameters )
	{
		$this->parameters = $parameters;
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