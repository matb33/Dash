<?php

namespace Plugins\FlattenerBatchFilter;

use Dash\Event;
use Dash\CommittableArrayObject;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use IteratorIterator;

class FlattenerBatchFilter extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$source = $settings->offsetGet( "source" );
		$isRecursive = $settings->offsetGet( "recursive" );
		$includeFilter = $settings->offsetGet( "includefilter" );
		$excludeFilter = $settings->offsetGet( "excludefilter" );

		$fileList = $this->getFileList( $source, $isRecursive, $includeFilter, $excludeFilter );

		$event->setContent( implode( "\n", $fileList ) );
	}

	private function getFileList( $source, $isRecursive, $includeFilter = "", $excludeFilter = "" )
	{
		$source = realpath( $source );
		$fileList = array();

	    if( $source !== false )
	    {
		    $source = str_replace( '\\', '/', $source );

		    if( $isRecursive )
		    {
	        	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
	        }
	        else
	        {
	        	$files = new IteratorIterator( new FilesystemIterator( $source, FilesystemIterator::SKIP_DOTS ) );
	        }

	        foreach( $files as $file )
	        {
	            $file = str_replace( '\\', '/', realpath( $file ) );

	            if( is_file( $file ) === true )
	            {
		            $include = true;

		            if( strlen( $includeFilter ) > 0 ) $include = preg_match( $includeFilter, $file ) > 0;
		            if( strlen( $excludeFilter ) > 0 ) $include = !( preg_match( $excludeFilter, $file ) > 0 );

		            if( $include )
		            {
		            	$path = str_replace( $source, "", $file );
			            $path = str_replace( '\\', '/', $path );
		                $fileList[] = $path;
		            }
		        }
	        }
	    }

	    return $fileList;
	}


	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "source" ) ) $settings->offsetSet( "source", "../" );
		if( ! $settings->offsetExists( "includefilter" ) ) $settings->offsetSet( "includefilter", '/\\.html$/' );
		if( ! $settings->offsetExists( "excludefilter" ) ) $settings->offsetSet( "excludefilter", '/partials\\//' );
		if( ! $settings->offsetExists( "recursive" ) ) $settings->offsetSet( "recursive", false );

		?>source: ko.observable( <?php echo json_encode( $settings->offsetGet( "source" ) ); ?> ),
		includefilter: ko.observable( <?php echo json_encode( $settings->offsetGet( "includefilter" ) ); ?> ),
		excludefilter: ko.observable( <?php echo json_encode( $settings->offsetGet( "excludefilter" ) ); ?> ),
		recursive: ko.observable( <?php echo json_encode( $settings->offsetGet( "recursive" ) ? true : false ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Base Folder:<br /><em>Relative to dash.php</em></span>
			<input type="text" data-bind="value: source" />
		</label>
		<label>
			<span>Include Filter:</span>
			<input type="text" data-bind="value: includefilter" />
		</label>
		<label>
			<span>Exclude Filter:</span>
			<input type="text" data-bind="value: excludefilter" />
		</label>
		<label>
			<input type="checkbox" data-bind="checked: recursive" /> <span>Recursive</span>
		</label>
		<details>
			<summary>Toggle examples</summary>
			<h3>Base Folder:</h3>
			<code>../</code>
			<h3>Include Filter:</h3>
			<code>/\.html$/</code>
			<h3>Exclude Filter:</h3>
			<code>/partials\//</code>
		</details>
		<?php
	}
}