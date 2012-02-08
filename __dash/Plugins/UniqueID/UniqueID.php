<?php

namespace Plugins\UniqueID;

use Dash\Event;

class UniqueID extends \Dash\Plugin
{
	private $uniqueIDFile = NULL;

	public function init()
	{
		$this->uniqueIDFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "Dash_Plugins_UniqueID.txt";

		$this->addListeners( array( $this, "callback" ) );
	}

	public function run( Array $parameters )
	{
		if( count( $parameters ) >= 1 && strtolower( $parameters[ 0 ] ) === "reset" )
		{
			echo $this->resetID();
		}
		else
		{
			echo $this->getID();
		}
	}

	public function callback( Event $event )
	{
		$this->resetID();
	}

	private function resetID()
	{
		$this->setID( uniqid( "v", false ) );

		return $this->getID();
	}

	private function getID()
	{
		return trim( file_get_contents( $this->uniqueIDFile ) );
	}

	private function setID( $id )
	{
		file_put_contents( $this->uniqueIDFile, $id );
	}
}