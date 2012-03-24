<?php

namespace Plugins\UniqueID;

use Dash\Event;
use Dash\CommittableArrayObject;

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
		$command = isset( $parameters[ "c" ] ) ? $parameters[ "c" ] : "";

		switch( $command )
		{
			case "reset":
				echo $this->resetID();
			break;

			case "get":
			default:
				echo $this->getID();
		}
	}

	public function callback( Event $event, CommittableArrayObject $settings )
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
		if( ! file_exists( $this->uniqueIDFile ) )
		{
			return $this->resetID();
		}
		else
		{
			return trim( file_get_contents( $this->uniqueIDFile ) );
		}
	}

	private function setID( $id )
	{
		file_put_contents( $this->uniqueIDFile, $id );
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><details>
			<summary>Toggle examples</summary>
			<p>Example run usage to reset the global unique ID:
				<code>/-/UniqueID?c=reset</code>
				<em>Note that a reset action occurs if UniqueID is set to listen to an event that is fired.</em>
			</p>
			<p>Example run usage to get the global unique ID:
				<code>/-/UniqueID?c=get</code> or simply
				<code>/-/UniqueID</code>
			</p>
		</details>
		<?php
	}
}