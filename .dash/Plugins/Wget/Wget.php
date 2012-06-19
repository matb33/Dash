<?php

namespace Plugins\Wget;

use Dash\Event;
use Dash\CommittableArrayObject;

class Wget extends \Dash\Plugin
{
	// TODO insufficient testing
	// may not work correctly

	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$url = $this->getURL( $event->getParameters() );
		$result = $this->wget( $url, $settings->getArrayCopy() );

		$event->setContent( $result[ "content" ] );
	}

	public function run( Array $parameters )
	{
		$url = $this->getURL( $parameters );
		$result = $this->wget( $url, $parameters );

		if( $result[ "success" ] === true )
		{
			echo $result[ "content" ];
		}
	}

	private function wget( $url, $settings )
	{
		set_time_limit( 0 );

		$content = "";
		$command = $this->buildExecCommand( $url, $settings );

		$fp = popen( $command . " 2>&1", "r" );

		while( !feof( $fp ) )
		{
			$content .= fread( $fp, 1024 );
		}

		fclose( $fp );

		return array( "success" => true, "content" => $content );
	}

	private function buildExecCommand( $url, $settings )
	{
		switch( PHP_OS )
		{
			case "WIN32":
			case "WINNT":
			case "Windows":
				$wgetCmd[] = dirname(__FILE__) . "/wget.exe";
				break;

			default:
				$wgetCmd[] = "wget";
		}

		$wgetCmd[] = $settings[ "converturls" ] ? "--convert-links" : "";
		$wgetCmd[] = "-qO-";
		$wgetCmd[] = $url;

		return implode( " ", $wgetCmd );
	}

	protected function getURL( Array $parameters )
	{
		if( isset( $parameters[ "url" ] ) )
		{
			return $parameters[ "url" ];
		}
		else
		{
			$path = ltrim( $parameters[ "path" ], "/" );
			unset( $parameters[ "path" ] );

			$query = http_build_query( $parameters );

			return "http://" . $_SERVER[ "HTTP_HOST" ] . "/" . $path . ( strlen( $query ) > 0 ? "?" . $query : "" );
		}
	}

	public function renderCommonSettings()
	{
		parent::renderCommonSettings();

		?><details>
			<summary>Toggle examples</summary>
			<p>Wget by path:
				<code>/-/Wget?path=/index.html</code>
			</p>
			<p>Wget by fully-qualified URL:
				<code>/-/Wget?url=http://www.website.com/index.html?qs=1</code>
			</p>
		</details>
		<?php
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "converturls" ) ) $settings->offsetSet( "converturls", false );

		?>converturls: ko.observable( <?php echo json_encode( $settings->offsetGet( "converturls" ) ? true : false ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<input type="checkbox" data-bind="checked: converturls" /> Check to convert relative URLs to absolute URLs
		</label>
		<?php
	}
}