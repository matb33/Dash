<?php

namespace Plugins\FileWriter;

use Dash\Event;
use Dash\CommittableArrayObject;

class FileWriter extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$paramKeyForFile = $settings->offsetGet( "paramkeyforfile" );
		$pattern = $settings->offsetGet( "pattern" );
		$replacement = $settings->offsetGet( "replacement" );
		$eventName = $settings->offsetGet( "eventname" );

		$content = $this->dispatchEvent( $eventName, $event->getContent() );

		$params = $event->getParameters();
		$originalFile = $params[ $paramKeyForFile ];
		$newFile = preg_replace( $pattern, $replacement, $originalFile );

		file_put_contents( $newFile, $content );
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "paramkeyforfile" ) ) $settings->offsetSet( "paramkeyforfile", "outFile" );
		if( ! $settings->offsetExists( "pattern" ) ) $settings->offsetSet( "pattern", '/(.*)\.(.+)$/' );
		if( ! $settings->offsetExists( "replacement" ) ) $settings->offsetSet( "replacement", '$1-copy.$2' );
		if( ! $settings->offsetExists( "eventname" ) ) $settings->offsetSet( "eventname", "FileWriter.content" );

		?>paramkeyforfile: ko.observable( <?php echo json_encode( $settings->offsetGet( "paramkeyforfile" ) ); ?> ),
		pattern: ko.observable( <?php echo json_encode( $settings->offsetGet( "pattern" ) ); ?> ),
		replacement: ko.observable( <?php echo json_encode( $settings->offsetGet( "replacement" ) ); ?> ),
		eventname: ko.observable( <?php echo json_encode( $settings->offsetGet( "eventname" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Parameter Key:<br /><em>Used as source string to determine output filename</em></span>
			<input type="text" data-bind="value: paramkeyforfile" />
		</label>
		<label>
			<span>Pattern:</span>
			<input type="text" data-bind="value: pattern" />
		</label>
		<label>
			<span>Replacement:</span>
			<input type="text" data-bind="value: replacement" />
		</label>
		<label>
			<span>Chain Event Name:</span>
			<input type="text" data-bind="value: eventname" />
		</label>
		<details>
			<summary>Toggle examples</summary>
			<h3>Parameter Key for File:</h3>
			<code>outFile</code>
			<h3>Pattern:</h3>
			<code>/(.*)\.(.+)$/</code>
			<h3>Replacement:</h3>
			<code>$1.new</code>
			<h3>Chain Event Name:</h3>
			<code>FileWriter.content</code>
		</details>
		<?php
	}
}