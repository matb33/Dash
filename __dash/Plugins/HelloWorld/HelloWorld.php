<?php

namespace Plugins\HelloWorld;

class HelloWorld extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		$data = $this->settings->get();

		echo "Hello " . ( isset( $data[ "who" ] ) && strlen( $data[ "who" ] ) > 0 ? $data[ "who" ] : "world" ) . "!";

		if( count( $parameters ) )
		{
			echo " [parameters: " . implode( ", ", $parameters ) . "]";
		}
	}

	public function renderSettings()
	{
		parent::renderSettings();

		$data = $this->settings->get();

		?><div class="expando">
			<label>
				<span>Hello who?</span>
				<input type="text" name="<?php echo $this->pluginName; ?>[who]" value="<?php echo isset( $data[ "who" ] ) ? $data[ "who" ] : ""; ?>" />
			</label>
		</div>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "who" ] = $post[ $this->pluginName ][ "who" ];

		$this->settings->set( $data );

		parent::updateSettings( $post );
	}
}