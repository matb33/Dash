<?php

namespace Plugins\Minifier;

use ErrorException;

use Plugins\AbstractShiftRefresh\AbstractShiftRefresh;

class Minifier extends AbstractShiftRefresh
{
	public function init()
	{
		if( $this->isShiftRefresh() )
		{
			$this->addListeners( array( $this, "minify" ) );
		}
	}

	public function minify()
	{
		$settings = $this->settings->get();
		$config = $settings[ "configuration" ];
		$config = str_replace( "\r\n", "\n", $config );
		$basePath = dirname( $_SERVER[ "SCRIPT_FILENAME" ] );

		$sets = explode( "\n", trim( $config ) );

		foreach( $sets as $set )
		{
			$params = explode( "=>", trim( $set ), 2 );

			if( count( $params ) === 2 )
			{
				$inputFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $params[ 0 ] ) );
				$realInputFile = realpath( $inputFile );

				if( $realInputFile !== false )
				{
					$targetFile = str_replace( array( "/", "\\" ), DIRECTORY_SEPARATOR, $basePath . DIRECTORY_SEPARATOR . trim( $params[ 1 ] ) );

					$this->ajaxmin( $realInputFile, $targetFile );
				}
				else
				{
					throw new ErrorException( "Invalid input file: " . $inputFile );
				}
			}
			else
			{
				throw new ErrorException( "Invalid configuration set, no equal arrow =&gt; found." );
			}
		}
	}

	private function ajaxmin( $inputFile, $outputFile )
	{
		if( strpos( strtolower( $outputFile ), ".css" ) !== false )
		{
			$type = "css";
		}
		else
		{
			$type = "js";
		}

		switch( $type )
		{
			case "css":
				exec( __DIR__ . DIRECTORY_SEPARATOR . "AjaxMin.exe -CSS -clobber:true " . $inputFile . " -o " . $outputFile );
			break;

			case "js":
			default:
				exec( __DIR__ . DIRECTORY_SEPARATOR . "AjaxMin.exe -JS -clobber:true -term " . $inputFile . " -o " . $outputFile );
		}
	}

	public function renderSettings()
	{
		parent::renderSettings();

		$settings = $this->settings->get();

		if( ! isset( $settings[ "configuration" ] ) ) $settings[ "configuration" ] = "";

		?><script type="text/javascript">
			<?php echo $this->viewModel; ?>.configuration = ko.observable( <?php echo json_encode( $settings[ "configuration" ] ); ?> );
		</script>

		<!-- ko with: <?php echo $this->viewModel; ?> -->
		<details>
			<summary>Toggle advanced</summary>
			<label>
				<span>Configuration:<br /><em>Paths are relative to dash.php</em></span>
				<textarea data-bind="value: configuration"></textarea>
			</label>
		</details>
		<details>
			<summary>Toggle examples</summary>
			<p>Example configuration:
			<code>../inc/cache/combined.css => ../inc/cache/combined.min.css
../inc/cache/combined.js => ../inc/cache/combined.min.js</code></p>
		</details>
		<!-- /ko -->
		<?php
	}

	public function updateSettings( Array $newSettings )
	{
		$settings = $this->settings->get();

		$settings[ "configuration" ] = $newSettings[ "configuration" ];

		$this->settings->set( $settings );

		parent::updateSettings( $newSettings );
	}
}