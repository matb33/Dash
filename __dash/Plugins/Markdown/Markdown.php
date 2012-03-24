<?php

namespace Plugins\Markdown;

use Dash\Event;
use Dash\CommittableArrayObject;

class Markdown extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function run( Array $parameters )
	{
		$path = $parameters[ "file" ];
		$basePath = dirname( $_SERVER[ "REDIRECT_SCRIPT_FILENAME" ] );

		if( ( $contents = file_get_contents( $basePath . DIRECTORY_SEPARATOR . $path ) ) !== false )
		{
			echo self::parse( $contents );
		}
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$markerStart = preg_quote( $settings->offsetGet( "marker_start" ), "/" );
		$markerEnd = preg_quote( $settings->offsetGet( "marker_end" ), "/" );

		$content = $event->getContent();

		$parsedContent = preg_replace_callback( "/^(\t*){$markerStart}(.*?){$markerEnd}/ms", function( $matches )
		{
			$tabs = $matches[ 1 ];
			$tabCount = substr_count( $tabs, "\t" );
			$text = $matches[ 2 ];
			$text = preg_replace( "/^[\t]{" . $tabCount . "}/m", "", $text );

			return Markdown::parse( $text );
		}, $content );

		$event->setContent( $parsedContent );
	}

	public static function parse( $content )
	{
		require_once __DIR__ . "/PHP Markdown 1.0.1o/markdown.php";
		return Markdown( $content );
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><details>
			<summary>Toggle examples</summary>
			<p>Example run usage:
			<code>/-/Markdown?file=readme.md</code></p>
		</details>
		<?php
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "marker_start" ) ) $settings->offsetSet( "marker_start", "{markdown}" );
		if( ! $settings->offsetExists( "marker_end" ) ) $settings->offsetSet( "marker_end", "{/markdown}" );

		?>marker_start: ko.observable( <?php echo json_encode( $settings->offsetGet( "marker_start" ) ); ?> ),
		marker_end: ko.observable( <?php echo json_encode( $settings->offsetGet( "marker_end" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Start marker:</span>
			<input type="text" data-bind="value: marker_start"></textarea>
		</label>
		<label>
			<span>End marker:</span>
			<input type="text" data-bind="value: marker_end"></textarea>
		</label>
		<details>
			<summary>Toggle examples</summary>
			<p>Example start marker: <strong>{markdown}</strong></p>
			<p>Example end marker: <strong>{/markdown}</strong></p>
		</details>
		<?php
	}
}