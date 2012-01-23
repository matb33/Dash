<?php

namespace Dash;

class PluginSettings
{
	private $settingStorage = NULL;
	private $settings = array();

	public function __construct( SettingStorage $settingStorage, $isEnabled = false )
	{
		$this->settingStorage = $settingStorage;
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
		$this->settingStorage->write();
	}
}