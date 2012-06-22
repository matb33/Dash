<?php

namespace Plugins\PregReplace;

use Dash\Event;
use Dash\CommittableArrayObject;

class PregReplace extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function run( Array $parameters )
	{
		$pattern = $parameters[ "pattern" ];
		$replacement = $parameters[ "replacement" ];
		$subject = $parameters[ "subject" ];
		$limit = isset( $parameters[ "limit" ] ) ? $parameters[ "limit" ] : -1;

		$subject = $this->dispatchEvent( "PregReplace.beforeReplace", $subject, $parameters );
		$subject = preg_replace( $pattern, $replacement, $subject, $limit, $count );
		$subject = $this->dispatchEvent( "PregReplace.afterReplace", $subject, array_merge( $parameters, array( "count" => $count ) ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$pattern = $settings->offsetGet( "pattern" );
		$replacement = $settings->offsetGet( "replacement" );
		$limit = $settings->offsetGet( "limit" );

		$subject = $event->getContent();
		$parameters = $event->getParameters();

		// Parse final used vars for inline variables, matching against parameters
		$pattern = $this->parseInlineVariables( $pattern, $parameters );
		$replacement = $this->parseInlineVariables( $replacement, $parameters );
		$limit = $this->parseInlineVariables( $limit, $parameters );

		$subject = $this->dispatchEvent( "PregReplace.beforeReplace", $subject, array_merge( $settings->getArrayCopy(), $parameters ) );
		$subject = preg_replace( $pattern, $replacement, $subject, $limit, $count );
		$subject = $this->dispatchEvent( "PregReplace.afterReplace", $subject, array_merge( $settings->getArrayCopy(), $parameters, array( "count" => $count ) ) );

		$event->setContent( $subject );
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><h3>Syntax:</h3>
		<code>/-/PregReplace?pattern=|abc|&replacement=def&subject=abc&limit=1</code>
		<h3>Events you can listen to:</h3>
		<ul>
			<li><strong>PregReplace.beforeReplace</strong> : Allows you to modify the subject before the replace occurs, and can read parameters.</li>
			<li><strong>PregReplace.afterReplace</strong> : Allows you to modify the subject after the replace occurred, and can read parameters, including the count parameter.</li>
		</ul>
		<details>
			<summary>Toggle examples</summary>
			<code>/-/PregReplace?pattern=|abc|&replacement=def&subject=abc</code>
		</details>
		<?php
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "pattern" ) ) $settings->offsetSet( "pattern", "" );
		if( ! $settings->offsetExists( "replacement" ) ) $settings->offsetSet( "replacement", "" );
		if( ! $settings->offsetExists( "limit" ) ) $settings->offsetSet( "limit", "-1" );

		?>pattern: ko.observable( <?php echo json_encode( $settings->offsetGet( "pattern" ) ); ?> ),
		replacement: ko.observable( <?php echo json_encode( $settings->offsetGet( "replacement" ) ); ?> ),
		limit: ko.observable( <?php echo json_encode( $settings->offsetGet( "limit" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Pattern:</span>
			<input type="text" data-bind="value: pattern" />
		</label>
		<label>
			<span>Replacement:</span>
			<input type="text" data-bind="value: replacement" />
		</label>
		<label>
			<span>Limit:</span>
			<input type="text" data-bind="value: limit" />
		</label>
		<h3>Events you can listen to:</h3>
		<ul>
			<li><strong>PregReplace.beforeReplace</strong> : Allows you to modify the subject before the replace occurs, and can read parameters and event settings.</li>
			<li><strong>PregReplace.afterReplace</strong> : Allows you to modify the subject after the replace occurred, and can read parameters and event settings, including the count parameter.</li>
		</ul>
		<h3>Inline parameters</h3>
		<p>PregReplace supports inline parameters of format <var>%name</var> or <var>{%name}</var>, where <var>name</var> is pulled from parameters.</p>
		<details>
			<summary>Toggle examples</summary>

			<h4>Pattern:</h4>
			<code>/\/.htm$/</code>
			<h4>Replacement:</h4>
			<code>.html</code>
			<h4>Limit:</h4>
			<code>5</code>
			<hr />

			<h4>Pattern:</h4>
			<code>/\n$/</code>
			<h4>Replacement:</h4>
			<code>?lang={%lang}\n</code>
			<h4>Limit:</h4>
			<code>-1</code>
		</details>
		<?php
	}
}