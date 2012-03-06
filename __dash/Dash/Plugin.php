<?php

namespace Dash;

use ArrayObject;

use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class Plugin
{
	public $name;

	protected $manager;
	protected $dispatcher;

	private $settings;
	private $viewModel;

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

	public function getCommonSettings()
	{
		return $this->settings->getCommonSettings();
	}

	public function getEventConfigCollection()
	{
		return $this->settings->getEventConfigCollection();
	}

	public function getCommittableArrayObject()
	{
		return new CommittableArrayObject( $this->settings );
	}

	public function getViewModelName()
	{
		return $this->viewModel;
	}

	protected function dispatchEvent( $eventName, $content = NULL, Array $parameters = array() )
	{
		$event = new Event( $eventName, $parameters, $content );
		$this->dispatcher->dispatch( $eventName, $event );
		return $event->getContent();
	}

	public function init()
	{
		// No init code defined in base plugin
	}

	public function run( Array $parameters )
	{
		// No run code defined in base plugin
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		// No event observables defined in base plugin
	}

	public function renderCommonObservables( CommittableArrayObject $settings )
	{
		if( ! $settings->offsetExists( "enabled" ) ) $settings->offsetSet( "enabled", false );

		?>enabled: ko.observable( <?php echo json_encode( $settings->offsetGet( "enabled" ) ? true : false ); ?> ),
		<?php
	}

	public function renderCommonSettings()
	{
		?><script type="text/javascript">
			<?php echo $this->getViewModelName(); ?>.settings.common.enabled.subscribe( function( value ) {
				<?php echo $this->getViewModelName(); ?>.save();
			});
		</script>

		<label class="enabled">
			<input type="checkbox" data-bind="checked: enabled" /> <span data-bind="visible: enabled">Uncheck to disable</span><span data-bind="visible: !enabled()">Check to enable</span>
		</label>
		<?php
	}

	public function renderEventSettings()
	{
		// No default event settings
	}

	protected function addListeners( Array $callback )
	{
		$eventConfigCollection = $this->getEventConfigCollection();

		foreach( $eventConfigCollection as $eventConfig )
		{
			$this->dispatcher->addListener(
				$eventConfig->getName(),
				function( Event $event ) use( $callback, $eventConfig )
				{
					call_user_func_array( $callback, array( $event, $eventConfig->getSettings() ) );
				},
				$eventConfig->getPriority()
			 );
		}
	}
}