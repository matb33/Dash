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
					var title = $( this ).attr( "title" );
					var caption = title !== undefined ? title : "Toggle";
					var expandButton = $( "<button class='expand'>" + caption + "</button>" );
					expandButton.data( "dash_linked_expando", $( this ) );
					expandButton.insertBefore( $( this ) );

					expandButton.click( function()
					{
						var expando = $( this ).data( "dash_linked_expando" );
						expando.slideToggle( "fast" );

						return false;
					});
				});

				var onEnabledCheckboxClick = function()
				{
					var $plugin = $( this ).closest( ".plugin" );

					if( $( this ).is( ":checked" ) )
					{
						$plugin.addClass( "is-enabled" );
						$plugin.removeClass( "is-disabled" );
					}
					else
					{
						$plugin.removeClass( "is-enabled" );
						$plugin.addClass( "is-disabled" );
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
			body, button {
				font: 100%/133% Georgia, Arial, Helvetica, sans-serif;
			}

			body {
				padding: 20px;
				background-color: #f0f0f0;
			}

			ul {
				padding-left: 0px;
				margin-left: 16px;
			}

			button {
				cursor: pointer;
			}

			.plugins {
				margin: 30px 0px;
				overflow: hidden;
			}

			.plugin {
				position: relative;
				float: left;
				width: 50%;
			}

			.plugin:nth-child(2n+1) {
				clear: left;
			}

			fieldset {
				background-color: #fff;
				padding: 15px;
				margin: 0px 20px 20px 0px;
				border-radius: 10px;
				border: 0px solid #000;
				border-width: 2px;
			}

			.is-disabled fieldset {
				background-color: #f0f0f0;
				border-color: #888;
			}

			.is-disabled * {
				color: #888;
			}

			.is-disabled button {
				background: none;
				background-color: #ccc;
			}

			fieldset legend {
				font-weight: bold;
				font-size: 150%;
				margin: 0px;
				padding: 0px;
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
				position: absolute;
				top: 20px;
				right: 30px;
				font-weight: normal;
				margin-bottom: 10px;
			}

			.is-enabled .enabled {
				color: #000;
				font-style: italic;
				background-color: #ff0;
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
				margin-bottom: 10px;
			}

			textarea {
				white-space: nowrap;
				height: 300px;
			}

			textarea,
			input[type='text'] {
				width: 98%;
				font-family: Courier New;
			}

			button {
				color: #ffffff;
				padding: 5px 10px;
				background: -moz-linear-gradient( top, #42aaff 0%, #003366);
				background: -webkit-gradient( linear, left top, left bottom, from(#42aaff), to(#003366));
				border-radius: 10px;
				border: 1px solid #003366;
				box-shadow: 0px 1px 3px rgba(000,000,000,0.5), inset 0px 0px 1px rgba(255,255,255,0.5);
				text-shadow: 0px -1px 0px rgba(000,000,000,0.7), 0px 1px 0px rgba(255,255,255,0.3);
			}

			button.save {
				font-size: 150%;
				color: #fff;
				padding: 10px 20px;
				background: -moz-linear-gradient( top, #fff3db 0%, #ffc821 25%, #ff3c00);
				background: -webkit-gradient( linear, left top, left bottom, from(#fff3db), color-stop(0.25, #ffc821), to(#ff3c00));
				border-radius: 10px;
				border: 1px solid #b85f00;
				box-shadow: 0px 1px 3px rgba(000,000,000,0.5), inset 0px -1px 0px rgba(255,255,255,0.7);
				text-shadow: 0px -1px 1px rgba(000,000,000,0.2), 0px 1px 0px rgba(255,255,255,0.3);
			}

		</style>
	</head>
	<body>
		<h1><em>Dash</em>: The development-side plugin framework</h1>
		<h2>Administration Panel</h2>
		<p>The plugins listed below are available for configuration:</p>

		<form method="post" action="">
			<button name="submit" class="save">Save Settings</button>
			<div class="plugins">
				<?php

				foreach( $pluginNames as $pluginName )
				{
					?><div class="plugin">
						<fieldset>
							<legend><?php echo $pluginName; ?></legend>
							<?php

								$instance = $pluginManager->getPluginInstance( $pluginName );
								$instance->renderSettings();

							?>
						</fieldset>
					</div>
					<?php
				}

			?></div>
			<button name="submit" class="save">Save Settings</button>
		</form>
	</body>
</html>