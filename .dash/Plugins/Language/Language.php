<?php

namespace Plugins\Language;

use Dash\Event;
use Dash\CommittableArrayObject;

class Language extends \Dash\Plugin
{
	private $currentLang;

	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$validLanguagesRaw = $settings->offsetGet( "languages" );
		$validLanguages = explode( ",", $validLanguagesRaw );
		$validLanguagesRegExp = implode( "|", $validLanguages );
		$readGetLang = $settings->offsetGet( "read_get_lang" );

		$this->currentLang = $settings->offsetGet( "default_lang" );

		if( $readGetLang && isset( $_GET[ "lang" ] ) )
		{
			if( in_array( $_GET[ "lang" ], $validLanguages ) )
			{
				$this->currentLang = $_GET[ "lang" ];
			}
		}

		$content = $event->getContent();
		$content = $this->parseTokens( $content );
		$event->setContent( $content );
	}

	private function parseTokens( $content, $offset = 0, $prevMatches = array(), $depth = 0 )
	{
		if( $depth >= 50 )
		{
			die( "Too much recursion. You're probably missing a closing {{/lang}} token." );
		}

		$iterationCount = 0;

		while( preg_match( "|\{\{([/]?)lang[ ]?([a-z-]*)\}\}|ms", $content, $matches, PREG_OFFSET_CAPTURE, $offset ) > 0 )
		{
			$isEndToken = $matches[ 1 ][ 0 ] === "/";
			$isStartToken = !$isEndToken;

			$tokenLength = strlen( $matches[ 0 ][ 0 ] );
			$posBeforeToken = $matches[ 0 ][ 1 ];
			$posAfterToken = $posBeforeToken + $tokenLength;

			if( $isStartToken )
			{
				// Look for another token
				$content = $this->parseTokens( $content, $posAfterToken, $matches, $depth + 1 );
			}
			else
			{
				$thisLang = $prevMatches[ 2 ][ 0 ];
				$keepClip = $thisLang === $this->currentLang;

				$startTokenLength = strlen( $prevMatches[ 0 ][ 0 ] );
				$posBeforeStartToken = $prevMatches[ 0 ][ 1 ];
				$posAfterStartToken = $posBeforeStartToken + $startTokenLength;

				$posBeforeEndToken = $posBeforeToken;
				$posAfterEndToken = $posAfterToken;

				if( $keepClip )
				{
					$innerClip = substr( $content, $posAfterStartToken, $posBeforeEndToken - $posAfterStartToken );
					$content = substr_replace( $content, $innerClip, $posBeforeStartToken, $posAfterEndToken - $posBeforeStartToken );
					$offset = $posBeforeStartToken;
				}
				else
				{
					$content = substr_replace( $content, "", $posBeforeStartToken, $posAfterEndToken - $posBeforeStartToken );
					$offset = $posBeforeStartToken;
				}

				break;
			}

			$iterationCount++;

			if( $iterationCount > 10000 )
			{
				die( "Too many tokens (> 10000)" );
			}
		}

		return $content;
	}

	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "languages" ) ) $settings->offsetSet( "languages", "en,fr" );
		if( ! $settings->offsetExists( "default_lang" ) ) $settings->offsetSet( "default_lang", "en" );
		if( ! $settings->offsetExists( "read_get_lang" ) ) $settings->offsetSet( "read_get_lang", true );

		?>languages: ko.observable( <?php echo json_encode( $settings->offsetGet( "languages" ) ); ?> ),
		default_lang: ko.observable( <?php echo json_encode( $settings->offsetGet( "default_lang" ) ); ?> ),
		read_get_lang: ko.observable( <?php echo json_encode( $settings->offsetGet( "read_get_lang" ) ? true : false ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Valid languages (comma-separated):</span>
			<input type="text" data-bind="value: languages"></textarea>
		</label>
		<label>
			<span>Default language:</span>
			<input type="text" data-bind="value: default_lang"></textarea>
		</label>
		<label>
			<input type="checkbox" data-bind="checked: read_get_lang" /> Read lang from GET (authoritative)
		</label>
		<details>
			<summary>Toggle examples</summary>
			<p>Example languages: <strong>en,fr</strong></p>
			<p>Example default language: <strong>en</strong></p>
		</details>
		<?php
	}
}