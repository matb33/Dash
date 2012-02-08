<?php

namespace Dash;

use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class Plugin
{
	protected $manager;
	protected $dispatcher;
	protected $settings;

	public $name;
	public $viewModel;

	public function __construct()
	{
		$this->name = basename( get_called_class() );
		$this->viewModel = "DASH.viewModel." . $this->name;
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
		$settings = $this->settings->get();

		if( ! isset( $settings[ "enabled" ] ) ) $settings[ "enabled" ] = false;
		if( ! isset( $settings[ "events" ] ) ) $settings[ "events" ] = array( "default" => "0" );

		?><script type="text/javascript">
			<?php echo $this->viewModel; ?>.enabled = ko.observable( <?php echo $settings[ "enabled" ] ? "true" : "false"; ?> );
			<?php echo $this->viewModel; ?>.events = ko.observable( <?php echo json_encode( $this->arrayEventsToRaw( $settings[ "events" ] ) ); ?> );
			<?php echo $this->viewModel; ?>.enabled.subscribe( function( value ) {
				DASH.sync( "<?php echo $this->name; ?>", <?php echo $this->viewModel; ?> );
			});
		</script>

		<!-- ko with: <?php echo $this->viewModel; ?> -->
		<label class="enabled">
			<input type="checkbox" data-bind="checked: enabled" /> <span>Check to enable</span>
		</label>
		<label class="events">
			<span>Event config:<br /><em>Format: eventName[:priority],eventName[:priority] (example: bleh:10,blah:20,other)</em></span>
			<input type="text" data-bind="value: events" />
		</label>
		<!-- /ko -->
		<?php
	}

	public function updateSettings( Array $newSettings )
	{
		$settings = $this->settings->get();

		$settings[ "enabled" ] = isset( $newSettings[ "enabled" ] ) && $newSettings[ "enabled" ] === true;

		if( isset( $newSettings[ "events" ] ) )
		{
			if( ! isset( $settings[ "events" ] ) ) $settings[ "events" ] = array();
			$settings[ "events" ] = $this->rawEventsToArray( $settings[ "events" ], $newSettings[ "events" ] );
		}

		$this->settings->set( $settings );
		$this->settings->commit();
	}

	protected function addListeners( Array $callback )
	{
		$settings = $this->settings->get();

		if( isset( $settings[ "events" ] ) && is_array( $settings[ "events" ] ) )
		{
			foreach( $settings[ "events" ] as $eventName => $eventPriority )
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