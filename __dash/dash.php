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

$pluginManager = new Dash\PluginManager(
	new Symfony\Component\EventDispatcher\EventDispatcher(),
	new Dash\JSONSettingStorage()
);

//=================================
// Parse raw request and either
// a) Run the specified plugin
// b) Show admin interface
//=================================

$pluginName = trim( $_SERVER[ "PATH_INFO" ], "/" );

if( strlen( $pluginName ) > 0 )
{
	$pluginManager->runPlugin( $pluginName, $_GET );
}
else
{
	require_once "admin/index.php";
}