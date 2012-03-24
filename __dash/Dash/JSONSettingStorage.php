<?php

namespace Dash;

class JSONSettingStorage implements SettingStorageInterface
{
	private $filename = NULL;
	private $pluginSettingsList = array();

	public function __construct( $filename = "../__dash.json" )
	{
		$this->filename = $filename;

		if( ! file_exists( $this->filename ) )
		{
			file_put_contents( $this->filename, "{}" );
		}

		$this->read();
	}

	public function read()
	{
		$allSettings = json_decode( file_get_contents( $this->filename ), true );

		$this->pluginSettingsList = array();

		foreach( $allSettings as $pluginName => $rawSettings )
		{
			$pluginSettings = new PluginSettings( $this );
			$pluginSettings->exchangeArray( $rawSettings );

			$this->pluginSettingsList[ $pluginName ] = $pluginSettings;
		}
	}

	public function write()
	{
		$allSettings = array();

		foreach( $this->pluginSettingsList as $pluginName => $pluginSettings )
		{
			$allSettings[ $pluginName ] = $pluginSettings->getArrayCopy();
		}

		file_put_contents( $this->filename, $this->jsonFormat( json_encode( $allSettings ) ) );
	}

	public function getPluginSettings( $pluginName )
	{
		if( ! isset( $this->pluginSettingsList[ $pluginName ] ) )
		{
			$this->pluginSettingsList[ $pluginName ] = new PluginSettings( $this );
		}

		return $this->pluginSettingsList[ $pluginName ];
	}

	// jsonFormat credit: http://www.php.net/manual/en/function.json-encode.php#80339
	private function jsonFormat( $json )
	{
		$tab = "	";
		$new_json = "";
		$indent_level = 0;
		$in_string = false;

		$json_obj = json_decode( $json );

		if( $json_obj === false ) return false;

		$json = json_encode( $json_obj );
		$len = strlen( $json );

		for( $c = 0; $c < $len; $c++ )
		{
			$char = $json[ $c ];

			switch( $char )
			{
				case '{':
				case '[':
					if( !$in_string )
					{
						$new_json .= $char . "\n" . str_repeat( $tab, $indent_level + 1 );
						$indent_level++;
					}
					else
					{
						$new_json .= $char;
					}
				break;

				case '}':
				case ']':
					if( !$in_string )
					{
						$indent_level--;
						$new_json .= "\n" . str_repeat( $tab, $indent_level ) . $char;
					}
					else
					{
						$new_json .= $char;
					}
				break;

				case ',':
					if( !$in_string )
					{
						$new_json .= ",\n" . str_repeat( $tab, $indent_level );
					}
					else
					{
						$new_json .= $char;
					}
				break;

				case ':':
					if( !$in_string )
					{
						$new_json .= ": ";
					}
					else
					{
						$new_json .= $char;
					}
				break;

				case '"':
					if( $c > 0 && $json[ $c - 1 ] != '\\' )
					{
						$in_string = !$in_string;
					}

				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}
}