<?php

namespace Dash;

class EventConfig implements ChainingCommittableInterface, CommittableInterface
{
	private $committable = NULL;

	private $name = NULL;
	private $priority = NULL;
	private $settings = NULL;

	public function __construct( CommittableInterface $committable )
	{
		$this->committable = $committable;
		$this->settings = new CommittableArrayObject( $this );
	}

	public function commit()
	{
		$this->committable->commit();
	}

	public function getName()
	{
		return $this->name;
	}

	public function getPriority()
	{
		return $this->priority;
	}

	public function getSettings()
	{
		return $this->settings;
	}

	public function getClassName()
	{
		$name = $this->getName();
		$name = strtolower( $name );
		$name = str_replace( array( "_", " " ), "-", $name );
		$name = preg_replace( "/[^a-z0-9-]+/", "", $name );

		return $name;
	}

	public function getArrayCopy()
	{
		$export = array();
		$export[ "name" ] = $this->getName();
		$export[ "priority" ] = $this->getPriority();
		$export[ "settings" ] = $this->getSettings()->getArrayCopy();

		return $export;
	}

	public function exchangeArray( Array $import )
	{
		$this->name = $import[ "name" ];
		$this->priority = ( int )$import[ "priority" ];

		if( array_key_exists( "settings", $import ) )
		{
			$this->settings->exchangeArray( $import[ "settings" ] );
		}
	}
}