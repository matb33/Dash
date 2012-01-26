<?php

//=================================
// Initialize class loading
//=================================

require_once "Symfony/Component/ClassLoader/UniversalClassLoader.php";

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces( array( "Dash" => __DIR__, "Plugins" => __DIR__, "Symfony" => __DIR__ ) );
$loader->register();

//=================================
// Load Dash core classes
//=================================

$dispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
$settingStorage = new Dash\SettingStorage();
$pluginManager = new Dash\PluginManager( $dispatcher, $settingStorage );

//=================================
// Parse raw request and either
// a) Run the specified plugin
// b) Show admin interface
//=================================

$rawParameters = ltrim( $_SERVER[ "PATH_INFO" ], "/" );
$parameters = strlen( $rawParameters ) > 0 ? explode( "/", $rawParameters ) : array();

if( count( $parameters ) > 0 )
{
	$pluginName = array_shift( $parameters );
	$pluginManager->runPlugin( $pluginName, $parameters );
}
else
{
	require_once "admin.php";
}