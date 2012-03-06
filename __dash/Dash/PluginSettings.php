<?php

namespace Dash;

class PluginSettings implements CommittableInterface
{
	private $settingStorage = NULL;

	private $commonSettings = NULL;
	private $eventConfigCollection = NULL;

	public function __construct( SettingStorageInterface $settingStorage )
	{
		$this->settingStorage = $settingStorage;

		$this->exchangeArray( array() );
	}

	public function commit()
	{
		$this->settingStorage->write();
	}

	public function isEnabled()
	{
		return $this->commonSettings->offsetExists( "enabled" ) && $this->commonSettings->offsetGet( "enabled" );
	}

	public function getCommonSettings()
	{
		return $this->commonSettings;
	}

	public function getEventConfigCollection()
	{
		return $this->eventConfigCollection;
	}

	public function getArrayCopy()
	{
		$export = array();
		$export[ "common" ] = $this->commonSettings;
		$export[ "events" ] = $this->eventConfigCollection->getArrayCopy();

		return $export;
	}

	public function exchangeArray( Array $import )
	{
		$this->commonSettings = new CommittableArrayObject( $this );
		if( array_key_exists( "common", $import ) )
		{
			$this->commonSettings->exchangeArray( $import[ "common" ] );
		}

		$this->eventConfigCollection = new EventConfigCollection( $this );
		if( array_key_exists( "events", $import ) )
		{
			$this->eventConfigCollection->exchangeArray( ( array )$import[ "events" ] );
		}
	}

	//===========================================

	public function set( $settings )
	{
		throw new \Exception( "Oops, stop using set! Migrate to setCommon/setByEvent" );
	}

	public function get()
	{
		throw new \Exception( "Oops, stop using get! Migrate to getCommon/getByEvent" );
	}
}