<?php

namespace Dash;

class EventConfigCollection extends CommittableArrayObject
{
	public function getArrayCopy()
	{
		$export = array();
		$copy = parent::getArrayCopy();

		foreach( $copy as $eventConfig )
		{
			$export[] = $eventConfig->getArrayCopy();
		}

		return $export;
	}

	public function exchangeArray( Array $import )
	{
		$new = array();

		foreach( $import as $entry )
		{
			$new[] = $this->getEventConfig( $entry );
		}

		parent::exchangeArray( $new );
	}

	public function getEventConfig( Array $import )
	{
		$eventConfig = new EventConfig( $this );
		$eventConfig->exchangeArray( $import );

		return $eventConfig;
	}
}