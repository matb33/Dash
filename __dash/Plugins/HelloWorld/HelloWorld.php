<?php

namespace Plugins\HelloWorld;

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

	public function renderSettings()
	{
		parent::renderSettings();

		$settings = $this->settings->get();

		if( ! isset( $settings[ "who" ] ) ) $settings[ "who" ] = "";

		?><script type="text/javascript">
			<?php echo $this->viewModel; ?>.who = ko.observable( <?php echo json_encode( $settings[ "who" ] ); ?> );
		</script>

		<!-- ko with: <?php echo $this->viewModel; ?> -->
		<details>
			<summary>Toggle advanced</summary>
			<label>
				<span>Hello who?</span>
				<input type="text" data-bind="value: who" />
			</label>
		</details>
		<!-- /ko -->
		<?php
	}

	public function updateSettings( Array $newSettings )
	{
		$settings = $this->settings->get();

		$settings[ "who" ] = $newSettings[ "who" ];

		$this->settings->set( $settings );

		parent::updateSettings( $newSettings );
	}
}