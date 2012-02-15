<?php

namespace Plugins\Markdown;

class Markdown extends \Dash\Plugin
{
	public function run( Array $parameters )
	{
		require_once "PHP Markdown 1.0.1o/markdown.php";

		$path = $parameters[ "file" ];
		$basePath = dirname( $_SERVER[ "REDIRECT_SCRIPT_FILENAME" ] );

		if( ( $contents = file_get_contents( $basePath . DIRECTORY_SEPARATOR . $path ) ) !== false )
		{
			echo Markdown( $contents );
		}
	}

	public function renderSettings()
	{
		parent::renderSettings();

		?><details>
			<summary>Toggle examples</summary>
			<p>Example run usage:
			<code>/-/Markdown?file=readme.md</code></p>
		</details>
		<?php
	}
}