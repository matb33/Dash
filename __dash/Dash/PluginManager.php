<?php

namespace Dash;

use ErrorException;
use ReflectionClass;

use Symfony\Component\EventDispatcher\EventDispatcher;

class PluginManager
{
	private $dispatcher = NULL;
	private $settingStorage = NULL;
	private $basePath = NULL;
	private $pluginList = array();

	public function __construct( EventDispatcher $dispatcher, SettingStorage $settingStorage, $basePath = "Plugins" )
	{
		$this->dispatcher = $dispatcher;
		$this->settingStorage = $settingStorage;
		$this->basePath = $basePath;

		$this->instantiatePlugins();
		$this->initPlugins();
	}

	private function instantiatePlugins()
	{
		$this->pluginList = array();

		$pluginNames = $this->getPluginListFromFileSystem();

		foreach( $pluginNames as $pluginName )
		{
			if( $this->settingStorage->getPluginSettings( $pluginName )->isEnabled() )
			{
				$this->getPluginInstance( $pluginName );
			}
		}
	}

	private function initPlugins()
	{
		foreach( $this->pluginList as $pluginName => $plugin )
		{
			$plugin->init();
		}
	}

	private function getFullyQualifiedPluginClassName( $pluginName )
	{
		return "\\" . $this->basePath . "\\" . $pluginName . "\\" . $pluginName;
	}

	private function getPluginFolderList()
	{
		return glob( $this->basePath . "/*", GLOB_ONLYDIR );
	}

	public function getPluginListFromFileSystem( $allowAbstract = false )
	{
		$folders = $this->getPluginFolderList();
		$list = array();

		foreach( $folders as $folder )
		{
			$pluginName = basename( $folder );

			$class = new ReflectionClass( $this->getFullyQualifiedPluginClassName( $pluginName ) );
			$isAbstract = $class->isAbstract();

			if( ! $isAbstract || $isAbstract && $allowAbstract )
			{
				$list[] = $pluginName;
			}
		}

		return $list;
	}

	public function getPluginInstance( $pluginName )
	{
		if( ! isset( $this->pluginList[ $pluginName ] ) )
		{
			$this->pluginList[ $pluginName ] = $this->createPluginInstance( $pluginName );
		}

		return $this->pluginList[ $pluginName ];
	}

	public function createPluginInstance( $pluginName )
	{
		$fullyQualifiedClassName = $this->getFullyQualifiedPluginClassName( $pluginName );

		if( class_exists( $fullyQualifiedClassName ) )
		{
			if( in_array( "Dash\\Plugin", class_parents( $fullyQualifiedClassName ) ) )
			{
				$instance = new $fullyQualifiedClassName;
				$instance->setPluginManager( $this );
				$instance->setEventDispatcher( $this->dispatcher );
				$instance->setPluginSettings( $this->settingStorage->getPluginSettings( $pluginName ) );
			}
			else
			{
				throw new ErrorException( "Plugin class " . $fullyQualifiedClassName . " does not extend Dash\\Plugin" );
			}
		}
		else
		{
			throw new ErrorException( "Plugin class not found: " . $fullyQualifiedClassName );
		}

		return $instance;
	}

	public function runPlugin( $pluginName, Array $parameters )
	{
		if( $this->pluginIsEnabled( $pluginName ) )
		{
			$plugin = $this->getPluginInstance( $pluginName );
			$plugin->run( $parameters );
		}
	}

	public function pluginExists( $pluginName )
	{
		$allPlugins = $this->getPluginListFromFileSystem( true );

		return in_array( $pluginName, $allPlugins );
	}

	public function pluginIsEnabled( $pluginName )
	{
		return isset( $this->pluginList[ $pluginName ] );
	}
}