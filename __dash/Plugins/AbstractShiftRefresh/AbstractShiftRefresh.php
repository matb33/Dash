<?php

namespace Plugins\AbstractShiftRefresh;

abstract class AbstractShiftRefresh extends \Dash\Plugin
{
	protected function isShiftRefresh()
	{
		$data = $this->settings->get();
		return ( ! $data[ "onshiftrefresh" ] || $data[ "onshiftrefresh" ] && $this->isShiftRefreshSentInHeaders() );
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

		$data = $this->settings->get();

		if( ! isset( $data[ "onshiftrefresh" ] ) ) $data[ "onshiftrefresh" ] = true;

		?><label>
			<input type="checkbox" name="<?php echo $this->name; ?>[onshiftrefresh]"<?php echo $data[ "onshiftrefresh" ] ? ' checked="checked"' : ""; ?>  />
			<span>Check to run only on Shift+Refresh (Ctrl+Refresh on some browsers). Unchecked will always run.</span>
		</label>
		<?php
	}

	public function updateSettings( Array $post )
	{
		$data = $this->settings->get();

		$data[ "onshiftrefresh" ] = isset( $post[ $this->name ][ "onshiftrefresh" ] );

		$this->settings->set( $data );

		parent::updateSettings( $post );
	}
}