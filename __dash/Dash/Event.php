<?php

namespace Dash;

class Event extends \Symfony\Component\EventDispatcher\Event
{
	private $name;
	private $parameters;
	private $content;

	public function __construct( $name, Array $parameters = array(), $content = NULL )
	{
		$this->setName( $name );
		$this->setParameters( $parameters );
		$this->setContent( $content );
	}

	public function getName()
	{
		return $this->name;
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function setName( $name )
	{
		$this->name = $name;
	}

	public function setParameters( $parameters )
	{
		$this->parameters = $parameters;
	}

	public function setContent( $content )
	{
		$this->content = $content;
	}
}