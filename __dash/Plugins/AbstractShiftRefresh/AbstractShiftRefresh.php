<?php

namespace Plugins\AbstractShiftRefresh;

abstract class AbstractShiftRefresh extends \Dash\Plugin
{
	protected function isShiftRefresh()
	{
		$settings = $this->settings->get();
		return ( ! $settings[ "onshiftrefresh" ] || $settings[ "onshiftrefresh" ] && $this->isShiftRefreshSentInHeaders() );
	}

	private function isShiftRefreshSentInHeaders()
	{
		$headers = apache_request_headers();

		foreach( $headers as $key => $value )
		{
			if( strtolower( $key ) == "cache-control" && strtolower( $value ) == "no-cache" ) return true;
		}

		return false;
	}

	public function renderSettings()
	{
		parent::renderSettings();

		$settings = $this->settings->get();

		if( ! isset( $settings[ "onshiftrefresh" ] ) ) $settings[ "onshiftrefresh" ] = true;

		?><script type="text/javascript">
			<?php echo $this->viewModel; ?>.onshiftrefresh = ko.observable( <?php echo $settings[ "onshiftrefresh" ] ? "true" : "false"; ?> );
		</script>

		<!-- ko with: <?php echo $this->viewModel; ?> -->
		<label>
			<input type="checkbox" data-bind="checked: onshiftrefresh" />
			<span>Check to run only on Shift+Refresh (Ctrl+Refresh on some browsers). Unchecked will always run.</span>
		</label>
		<!-- /ko -->
		<?php
	}

	public function updateSettings( Array $newSettings )
	{
		$settings = $this->settings->get();

		$settings[ "onshiftrefresh" ] = isset( $newSettings[ "onshiftrefresh" ] ) && $newSettings[ "onshiftrefresh" ] === true;

		$this->settings->set( $settings );

		parent::updateSettings( $newSettings );
	}
}