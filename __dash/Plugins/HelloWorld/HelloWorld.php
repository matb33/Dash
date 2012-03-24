<?php

namespace Plugins\HelloWorld;

use Dash\CommittableArrayObject;

class HelloWorld extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		$settings = $this->settings->get();

		echo "Hello " . ( isset( $settings[ "who" ] ) && strlen( $settings[ "who" ] ) > 0 ? $settings[ "who" ] : "world" ) . "!";

		if( count( $parameters ) )
		{
			echo " [parameters: " . implode( ", ", $parameters ) . "]";
		}
	}

	public function renderCommonObservables( CommittableArrayObject $settings )
	{
		parent::renderCommonObservables( $settings );

		if( ! $settings->offsetExists( "who" ) ) $settings->offsetSet( "who", "" );

		?>who: ko.observable( <?php echo json_encode( $settings->offsetGet( "who" ) ); ?> ),
		<?php
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><label>
			<span>Hello who?</span>
			<input type="text" data-bind="value: who" />
		</label>
		<?php
	}
}