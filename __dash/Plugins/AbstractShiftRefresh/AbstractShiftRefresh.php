<?php

namespace Plugins\AbstractShiftRefresh;

use Dash\Event;
use Dash\CommittableArrayObject;

abstract class AbstractShiftRefresh extends \Dash\Plugin
{
	protected function testShiftRefresh( CommittableArrayObject $settings = NULL )
	{
		if( $settings === NULL )
		{
			$settings = $this->getCommonSettings();
		}

		$isOnShiftRefresh = $settings->offsetExists( "onshiftrefresh" ) && $settings->offsetGet( "onshiftrefresh" );

		return ( ! $isOnShiftRefresh || $isOnShiftRefresh && $this->isShiftRefreshSentInHeaders() );
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

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "onshiftrefresh" ) ) $settings->offsetSet( "onshiftrefresh", true );

		?>onshiftrefresh: ko.observable( <?php echo json_encode( $settings->offsetGet( "onshiftrefresh" ) ? true : false ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<input type="checkbox" data-bind="checked: onshiftrefresh" />
			<span>Check to run only on Shift+Refresh (Ctrl+Refresh on some browsers). Unchecked will always run.</span>
		</label>
		<?php
	}
}