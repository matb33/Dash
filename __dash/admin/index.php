<?php

if( ! isset( $loader ) ) die( "Try accessing the admin interface by using the /-/ path" );

if( isset( $_SERVER[ "HTTP_X_REQUESTED_WITH" ] ) )
{
	$pluginName = $_REQUEST[ "name" ];
	$settings = json_decode( $_REQUEST[ "settings" ], true );

	try
	{
		$instance = $pluginManager->getPluginInstance( $pluginName );
		$instance->updateSettings( $settings );

		header( "HTTP/1.0 200 OK" );
		?>{}
		<?php
	}
	catch( \ErrorException $e )
	{
		header( "HTTP/1.0 404 Plugin Not Found" );
		?><h1>404 Plugin Not Found</h1>
		<p>The plugin <?php echo $pluginName; ?> does not exist.</p>
		<?php
	}

	exit();
}

$pluginNames = $pluginManager->getPluginListFromFileSystem();

// Scripts and styles are generally included via PHP to avoid URL complication issues with regards to local development environments.

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Dash Admin</title>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript">
			<?php include "jquery.tmpl.js"; ?>
		</script>
		<script type="text/javascript">
			<?php include "knockout-2.0.0.js"; ?>
		</script>
		<script type="text/javascript">
			<?php include "ko-extensions.js"; ?>
		</script>
		<script type="text/javascript">

			window.DASH = {};
			window.DASH.viewModel = {};
			window.DASH.sync = function( name, viewModel, callback ) {
				viewModel.saving( true );
				viewModel.message( "Saving..." );

				var context = this;
				var data = { "name": name, "settings": ko.toJSON( viewModel ) };

				$.ajax({
					"url": "./",
					"dataType": "json",
					"type": "POST",
					"processData": true,
					"crossDomain": false,
					"context": context,
					"data": data
				}).always( function( data, textStatus, jqXHR ) {
					viewModel.saving( false );
					if( textStatus === "error" ) {
						viewModel.message( "Error while saving: " + data.statusText );
					} else {
						viewModel.message( "Save successful." );
					}
					if( typeof callback === "function" ) callback.apply( context, arguments );
				});
			};

			<?php
			foreach( $pluginNames as $pluginName )
			{
				$instance = $pluginManager->getPluginInstance( $pluginName );

				echo $instance->viewModel; ?> = {
					save: function() {
						window.DASH.sync( "<?php echo $instance->name; ?>", <?php echo $instance->viewModel; ?> );
					},
					message: ko.delayedRevertObservable( "", 2500, window.DASH.messageTimeout_<?php echo $pluginName; ?> ),
					saving: ko.observable( false )
				};
				<?php
			}
			?>

			// Details/Summary fallback
			jQuery( document ).ready( function( $ ) {
				if( !( "open" in document.createElement( "details" ) ) ) {
					document.documentElement.className += " no-details";
				}
				$( ".no-details summary" ).live( "click", function() {
					$( this ).closest( "details" ).each( function() {
						var $this = $( this );

						if( $this.attr( "open" ) ) {
							$this.removeAttr( "open" );
						} else {
							$this.attr( "open", "open" );
						}

						$this.children().not( "summary" ).toggle()
					});
				});
			});

		</script>
		<style type="text/css">
			<?php include "admin.css"; ?>
		</style>
	</head>
	<body>
		<h1><em>Dash</em>: The development-side plugin framework</h1>
		<h2>Plugin Configuration</h2>

		<form method="post" action="">
			<div class="plugins">
			<?php

				foreach( $pluginNames as $pluginName )
				{
					$instance = $pluginManager->getPluginInstance( $pluginName );

					?><div class="plugin" data-bind="css: { 'is-enabled': <?php echo $instance->viewModel; ?>.enabled, 'is-disabled': !<?php echo $instance->viewModel; ?>.enabled() }">
						<details>
							<summary><?php echo $pluginName; ?></summary>
							<div class="settings">
								<?php $instance->renderSettings(); ?>
							</div>
							<div class="sync">
								<button class="save" data-bind="click: <?php echo $instance->viewModel; ?>.save, disable: <?php echo $instance->viewModel; ?>.saving">Save changes</button>
								<div class="message" data-bind="text: <?php echo $instance->viewModel; ?>.message, css: { 'is-enabled': <?php echo $instance->viewModel; ?>.message }"></div>
							</div>
						</details>
					</div>
					<?php
				}

			?></div>
		</form>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				ko.applyBindings( DASH.viewModel );
			});
		</script>
	</body>
</html>