<?php

namespace Dash;

class PluginSettings
{
	private $storage = NULL;
	private $settings = array();

	public function __construct( SettingStorageInterface $storage, $isEnabled = false )
	{
		$this->storage = $storage;
		$this->settings[ "enabled" ] = $isEnabled;
	}

	public function isEnabled()
	{
		return $this->settings[ "enabled" ];
	}

	public function get()
	{
		return $this->settings;
	}

	public function set( $settings )
	{
		$this->settings = $settings;
	}

	public function commit()
	{
		$this->storage->write();
	}
}