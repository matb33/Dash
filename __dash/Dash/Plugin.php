<?php

namespace Dash;

use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class Plugin
{
	protected $manager;
	protected $dispatcher;
	protected $settings;
	protected $name;

	public function __construct()
	{
		$this->name = basename( get_called_class() );
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
			<input type="checkbox" name="<?php echo $this->name; ?>[enabled]" value="1"<?php echo $data[ "enabled" ] ? ' checked="checked"' : ""; ?> /><span>Check to enable</span>
		</label>
		<label class="events">
			<span>Event config:<br /><em>Format: eventName[:priority],eventName[:priority] (example: bleh:10,blah:20,other)</em></span>
			<input type="text" name="<?php echo $this->name; ?>[events]" value="<?php echo isset( $data[ "events" ] ) ? $this->arrayEventsToRaw( $data[ "events" ] ) : "default:0"; ?>" />
		</label>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "enabled" ] = isset( $post[ $this->name ][ "enabled" ] ) && $post[ $this->name ][ "enabled" ] === "1";

		if( isset( $post[ $this->name ][ "events" ] ) )
		{
			if( ! isset( $data[ "events" ] ) )
			{
				$data[ "events" ] = array();
			}

			$data[ "events" ] = $this->rawEventsToArray( $data[ "events" ], $post[ $this->name ][ "events" ] );
		}

		$this->settings->set( $data );
		$this->settings->commit();
	}

	protected function addListeners( Array $callback )
	{
		$data = $this->settings->get();

		if( isset( $data[ "events" ] ) && is_array( $data[ "events" ] ) )
		{
			foreach( $data[ "events" ] as $eventName => $eventPriority )
			{
				$this->dispatcher->addListener( $eventName, $callback, $eventPriority );
			}
		}
	}

	private function rawEventsToArray( Array $existingEvents, $rawEvents )
	{
		$existingEvents = array();

		if( strlen( $rawEvents ) > 0 )
		{
			$events = explode( ",", $rawEvents );

			foreach( $events as $rawEvent )
			{
				$event = explode( ":", $rawEvent );

				$eventName = $event[ 0 ];
				$eventPriority = isset( $event[ 1 ] ) ? $event[ 1 ] : 0;

				$existingEvents[ $eventName ] = $eventPriority;
			}
		}

		return $existingEvents;
	}

	private function arrayEventsToRaw( Array $arrayEvents )
	{
		$rawEvents = array();

		foreach( $arrayEvents as $eventName => $eventPriority )
		{
			$rawEvents[] = $eventName . ":" . $eventPriority;
		}

		return implode( ",", $rawEvents );
	}
}