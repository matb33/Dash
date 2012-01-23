<?php

namespace Dash;

use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class Plugin
{
	protected $manager;
	protected $dispatcher;
	protected $settings;
	protected $pluginName;

	public function __construct()
	{
		$this->pluginName = basename( get_called_class() );
	}

	public function setPluginManager( PluginManager $pluginManager )
	{
		$this->manager = $pluginManager;
	}

	public function setEventDispatcher( EventDispatcher $dispatcher )
	{
		$this->dispatcher = $dispatcher;
	}

	public function setPluginSettings( PluginSettings $pluginSettings )
	{
		$this->settings = $pluginSettings;
	}

	public function init()
	{
		// No init code defined in base plugin
	}

	public function run( Array $parameters )
	{
		// No run code defined in base plugin
	}

	public function renderSettings()
	{
		$data = $this->settings->get();

		?><label class="enabled">
			<input type="checkbox" name="<?php echo $this->pluginName; ?>[enabled]" value="1"<?php echo $data[ "enabled" ] ? ' checked="checked"' : ""; ?> /><span>Check to enable</span>
		</label>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "enabled" ] = isset( $post[ $this->pluginName ][ "enabled" ] ) && $post[ $this->pluginName ][ "enabled" ] === "1";

		$this->settings->set( $data );
		$this->settings->commit();
	}
}