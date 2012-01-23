<?php

if( ! isset( $loader ) ) die( "Try accessing the admin interface by using the /-/ path" );

$pluginNames = $pluginManager->getPluginListFromFileSystem();

if( isset( $_POST[ "submit" ] ) )
{
	foreach( $pluginNames as $pluginName )
	{
		$instance = $pluginManager->getPluginInstance( $pluginName );
		$instance->updateSettings( $_POST );
	}
}

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Dash Admin</title>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ )
			{
				$( ".expando" ).each( function()
				{
					var expandButton = $( "<button class='expand'>toggle advanced</button>" );
					expandButton.insertBefore( $( this ) );

					expandButton.click( function()
					{
						var expando = $( this ).siblings( ".expando" );
						expando.slideToggle();

						return false;
					});
				});

				var onEnabledCheckboxClick = function()
				{
					var $enabledElement = $( this ).closest( ".enabled" );

					if( $( this ).is( ":checked" ) )
					{
						$enabledElement.addClass( "checked" );
					}
					else
					{
						$enabledElement.removeClass( "checked" );
					}
				};

				var $enabledCheckbox = $( ".enabled input[type='checkbox']" );
				$enabledCheckbox.click( onEnabledCheckboxClick );

				$enabledCheckbox.each( function()
				{
					onEnabledCheckboxClick.call( this );
				});
			});
		</script>
		<style type="text/css">
			.plugins {
				border: 1px solid #888;
				padding: 10px;
				margin: 10px 0px;
				background-color: #f0f0f0;
				overflow: hidden;
			}

			fieldset {
				background-color: #fff;
				margin-bottom: 10px;
			}

			fieldset legend {
				font-weight: bold;
			}

			label {
				display: block;
				clear: both;
				margin-bottom: 10px;
				overflow: hidden;
			}

			label > span {
				font-weight: bold;
				display: block;
			}

			label > input[type='checkbox' ] ~ span {
				font-weight: normal;
				display: inline;
			}

			label > span > em {
				font-weight: normal;
			}

			.enabled {
				background-color: #f88;
				color: #633;
				border: 1px dotted #888;
				padding: 5px;
				width: 130px;
				margin-bottom: 10px;
			}

			.enabled.checked {
				background-color: #8f8;
				color: #000;
			}

			button.expand {
				margin-bottom: 5px;
				cursor: pointer;
			}

			.expando {
				display: none;
			}

			code {
				display: block;
				white-space: pre;
				border: 1px dashed #888;
				background-color: #f0f0f0;
				padding: 10px;
			}

			textarea {
				white-space: nowrap;
				height: 300px;
			}

			textarea,
			input[type='text'] {
				width: 400px;
				font-family: Courier New;
			}
		</style>
	</head>
	<body>
		<h1><em>Dash</em>: The development-side plugin framework</h1>
		<h2>Administration Panel</h2>
		<p>The plugins listed below are available for configuration:</p>

		<form method="post" action="">
			<input type="submit" name="submit" value="Save Settings" />
			<div class="plugins">
				<?php

				foreach( $pluginNames as $pluginName )
				{
					?><fieldset>
						<legend><?php echo $pluginName; ?></legend>
						<?php

							$instance = $pluginManager->getPluginInstance( $pluginName );
							$instance->renderSettings();

						?>
					</fieldset>
					<?php
				}

			?></div>
			<input type="submit" name="submit" value="Save Settings" />
		</form>
	</body>
</html>