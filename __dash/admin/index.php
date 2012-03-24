<?php

namespace Dash;

if( ! isset( $loader ) ) die( "Try accessing the admin interface by using the /-/ path" );

if( isset( $_SERVER[ "HTTP_X_REQUESTED_WITH" ] ) )
{
	$pluginName = $_REQUEST[ "name" ];
	$payload = json_decode( $_REQUEST[ "payload" ], true );

	try
	{
		$settings = $payload[ "settings" ];
		$instance = $pluginManager->getPluginInstance( $pluginName );

		//================================
		// Update common settings
		//================================

		$commonSettings = $instance->getCommonSettings();
		$commonSettings->exchangeArray( ( array )$settings[ "common" ] );
		$commonSettings->commit();

		//================================
		// Update existing event settings
		//================================

		$eventConfigCollection = $instance->getEventConfigCollection();

		foreach( $eventConfigCollection as $index => $eventConfig )
		{
			if( array_key_exists( $index, $settings[ "events" ] ) )
			{
				$eventConfig->exchangeArray( ( array )$settings[ "events" ][ $index ] );
			}
			else
			{
				unset( $eventConfigCollection[ $index ] );
			}
		}

		//================================
		// Insert new event settings
		//================================

		$newSettings = array_slice( $settings[ "events" ], count( $eventConfigCollection ) );

		foreach( $newSettings as $settingsArray )
		{
			$newEventConfig = $eventConfigCollection->getEventConfig( ( array )$settingsArray );
			$eventConfigCollection->append( $newEventConfig );
		}

		//================================
		// Commit all event settings
		//================================

		$eventConfigCollection->commit();

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

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Dash Admin</title>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript" src="//github.com/downloads/SteveSanderson/knockout/jquery.tmpl.js"></script>
		<script type="text/javascript" src="//github.com/SteveSanderson/knockout/raw/master/build/output/knockout-latest.js"></script>
		<script type="text/javascript">
			( function( $, ko ) {
				ko.delayedRevertObservable = function( restValue, revertDelay, timeoutVar ) {
					var observable = ko.observable( restValue );
					return ko.dependentObservable({
						read: function() {
							return ko.utils.unwrapObservable( observable );
						},
						write: function( temporaryValue ) {
							observable( temporaryValue );
							if( temporaryValue !== restValue ) {
								window.clearTimeout( timeoutVar );
								timeoutVar = window.setTimeout( function() { observable( restValue ); }, revertDelay );
							}
						}
					});
				};
			})( window.jQuery, window.ko );

			window.DASH = {
				viewModel: {},
				sync: function( name, viewModel, callback ) {
					viewModel.saving( true );
					viewModel.message( "Saving..." );

					var context = this;
					var data = { "name": name, "payload": ko.toJSON( viewModel ) };

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
				}
			};

			<?php
			// Bootstrap plugin data:
			foreach( $pluginNames as $pluginName )
			{
				$instance = $pluginManager->getPluginInstance( $pluginName );
				$eventConfigCollection = $instance->getEventConfigCollection();

				echo $instance->getViewModelName(); ?> = {
					save: function() {
						window.DASH.sync( "<?php echo $instance->name; ?>", <?php echo $instance->getViewModelName(); ?> );
					},
					saving: ko.observable( false ),
					message: ko.delayedRevertObservable( "", 2500, window.DASH.messageTimeout_<?php echo $pluginName; ?> ),
					addEvent: function( data, event ) {
						<?php echo $instance->getViewModelName(); ?>.settings.events.push( {
							name: ko.observable( "NEW" ),
							priority: ko.observable( "0" ),
							index: <?php echo $instance->getViewModelName(); ?>.settings.events().length,
							settings: {<?php
								$tempEmptyCommittable = $instance->getCommittableArrayObject();
								$instance->renderEventObservables( $tempEmptyCommittable );
								unset( $tempEmptyCommittable );
							?>}
						});

						$( event.target ).closest( "ul.tabs" ).find( "li:last-child" ).prev().find( "button" ).trigger( "click" );
					},
					delEvent: function( data, event ) {
						$( event.target ).closest( ".plugin" ).find( "ul.tabs li.common button" ).trigger( "click" );
						<?php echo $instance->getViewModelName(); ?>.settings.events.remove( data );
					}
				};
				<?php echo $instance->getViewModelName(); ?>.settings = {
					common: {<?php $instance->renderCommonObservables( $instance->getCommonSettings() ); ?>},
					events: ko.observableArray( [] )
				};
				<?php

				foreach( $eventConfigCollection as $index => $eventConfig )
				{
					echo $instance->getViewModelName(); ?>.settings.events.push( {
						name: ko.observable( <?php echo json_encode( $eventConfig->getName() ); ?> ),
						priority: ko.observable( <?php echo json_encode( $eventConfig->getPriority() ); ?> ),
						index: <?php echo $instance->getViewModelName(); ?>.settings.events().length,
						settings: {<?php $instance->renderEventObservables( $eventConfig->getSettings() ); ?>}
					});
					<?php
				}
			}
			?>

			jQuery( document ).ready( function( $ ) {
				// Details/Summary fallback
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

				// Basic tab/pane system
				$( "[data-tab]" ).live( "click", function() {
					var $this = $( this );
					var $pane = $this.closest( ".plugin" ).find( "[data-pane='" + $this.attr( "data-tab" ) + "']" );

					$this.addClass( "target" ).closest( "ul" ).children( "[data-tab]" ).not( $this ).removeClass( "target" );
					$pane.addClass( "target" ).closest( "ul" ).children( "[data-pane]" ).not( $pane ).removeClass( "target" );

					return false;
				});
			});

		</script>
		<style type="text/css">
			<?php
				// CSS included via PHP to avoid URL complication issues with regards to local development environments:
				include "admin.css";
			?>
		</style>
	</head>
	<body>
		<h1><em>Dash</em>: Plug-in Configuration</h1>

		<form method="post" action="">
			<div class="plugins">
			<?php

				foreach( $pluginNames as $pluginName )
				{
					$instance = $pluginManager->getPluginInstance( $pluginName );
					$eventConfigCollection = $instance->getEventConfigCollection();

					?><div class="plugin" data-bind="css: { 'is-enabled': <?php echo $instance->getViewModelName(); ?>.settings.common.enabled, 'is-disabled': !<?php echo $instance->getViewModelName(); ?>.settings.common.enabled() }">
						<details>
							<summary><?php echo $pluginName; ?></summary>
							<ul class="tabs">
								<li data-tab="<?php echo $instance->name; ?>-common" class="common target"><button><em>Common</em></button></li>

								<!-- ko foreach: <?php echo $instance->getViewModelName(); ?>.settings.events -->
									<li data-bind="attr: { 'data-tab': '<?php echo $instance->name; ?>-' + index }" class="event">
										<button><strong data-bind="text: name"></strong> : <span data-bind="text: priority"></span></button>
									</li>
								<!-- /ko -->

								<li data-bind="click: <?php echo $instance->getViewModelName(); ?>.addEvent"><button>+</button></li>
							</ul>
							<ul class="settings">
								<!-- ko with: <?php echo $instance->getViewModelName(); ?>.settings.common -->
									<li data-pane="<?php echo $instance->name; ?>-common" class="common target">
										<?php $instance->renderCommonSettings(); ?>
									</li>
								<!-- /ko -->

								<!-- ko foreach: <?php echo $instance->getViewModelName(); ?>.settings.events -->
									<li data-bind="attr: { 'data-pane': '<?php echo $instance->name; ?>-' + index }" class="event">
										<label class="event name">
											<span>Event name:</span>
											<input type="text" data-bind="value: name, valueUpdate: 'afterkeydown'" />
										</label>
										<label class="event priority">
											<span>Priority:</span>
											<input type="text" data-bind="value: priority, valueUpdate: 'afterkeydown'" />
										</label>
										<label class="event delete">
											<button data-bind="click: <?php echo $instance->getViewModelName(); ?>.delEvent">X</button>
										</label>
										<!-- ko with: settings -->
											<?php $instance->renderEventSettings(); ?>
										<!-- /ko -->
									</li>
								<!-- /ko -->
							</ul>
							<div class="sync">
								<button class="save" data-bind="click: <?php echo $instance->getViewModelName(); ?>.save, disable: <?php echo $instance->getViewModelName(); ?>.saving">Save changes</button>
								<div class="message" data-bind="text: <?php echo $instance->getViewModelName(); ?>.message, css: { 'is-enabled': <?php echo $instance->getViewModelName(); ?>.message }"></div>
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