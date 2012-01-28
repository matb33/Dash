<?php

namespace Dash;

interface SettingStorageInterface
{
	public function write();
	public function getPluginSettings();
}