<?php

namespace Plugins\Zip;

use Dash\Event;
use Dash\CommittableArrayObject;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Zip extends \Dash\Plugin
{
	public function init()
	{
		$this->addListeners( array( $this, "callback" ) );
	}

	public function callback( Event $event, CommittableArrayObject $settings )
	{
		$paramKeyForInput = $settings->offsetGet( "paramkeyforinput" );
		$patternForInput = $settings->offsetGet( "patternforinput" );
		$replacementForInput = $settings->offsetGet( "replacementforinput" );
		$paramKeyForOutput = $settings->offsetGet( "paramkeyforoutput" );
		$patternForOutput = $settings->offsetGet( "patternforoutput" );
		$replacementForOutput = $settings->offsetGet( "replacementforoutput" );
		$eventNameBefore = $settings->offsetGet( "eventnamebefore" );
		$eventNameAfter = $settings->offsetGet( "eventnameafter" );
		$includeFilter = $settings->offsetGet( "includefilter" );
		$excludeFilter = $settings->offsetGet( "excludefilter" );

		$event->setContent( $this->dispatchEvent( $eventNameBefore, $event->getContent(), $event->getParameters() ) );

		$params = $event->getParameters();
		$inputFolder = preg_replace( $patternForInput, $replacementForInput, $params[ $paramKeyForInput ] );
		$outputFile = preg_replace( $patternForOutput, $replacementForOutput, $params[ $paramKeyForOutput ] );

		$this->zip( $inputFolder, $outputFile, $includeFilter, $excludeFilter );

		$event->setContent( $this->dispatchEvent( $eventNameAfter, $event->getContent(), $event->getParameters() ) );
	}

	private function zip( $source, $destination, $includeFilter = "", $excludeFilter = "" )
	{
	    if( ! extension_loaded( "zip" ) || ! file_exists( $source ) )
	    {
	        return false;
	    }

	    $zip = new ZipArchive();

	    if( ! $zip->open( $destination, ZIPARCHIVE::OVERWRITE ) )
	    {
	        return false;
	    }

	    $source = str_replace( '\\', '/', realpath( $source ) );

	    if( is_dir( $source ) === true )
	    {
	        $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );

	        foreach( $files as $file )
	        {
	            $file = str_replace( '\\', '/', realpath( $file ) );

	            $include = true;

	            if( strlen( $includeFilter ) > 0 ) $include = preg_match( $includeFilter, $file ) > 0;
	            if( strlen( $excludeFilter ) > 0 ) $include = !( preg_match( $excludeFilter, $file ) > 0 );

	            if( $include )
	            {
		            if( is_dir( $file ) === true )
		            {
		                $zip->addEmptyDir( str_replace( $source . '/', '', $file . '/' ) );
		            }
		            else if( is_file( $file ) === true )
		            {
		                $zip->addFromString( str_replace( $source . '/', '', $file ), file_get_contents( $file ) );
		            }
		        }
	        }
	    }
	    else if( is_file( $source ) === true )
	    {
	        $zip->addFromString( basename( $source ), file_get_contents( $source ) );
	    }

	    return $zip->close();
	}


	public function renderEventObservables( CommittableArrayObject $settings )
	{
		parent::renderEventObservables( $settings );

		if( ! $settings->offsetExists( "paramkeyforinput" ) ) $settings->offsetSet( "paramkeyforinput", "outputFolder" );
		if( ! $settings->offsetExists( "patternforinput" ) ) $settings->offsetSet( "patternforinput", '/^(.+)$/' );
		if( ! $settings->offsetExists( "replacementforinput" ) ) $settings->offsetSet( "replacementforinput", '$1' );
		if( ! $settings->offsetExists( "paramkeyforoutput" ) ) $settings->offsetSet( "paramkeyforoutput", "outputFolder" );
		if( ! $settings->offsetExists( "patternforoutput" ) ) $settings->offsetSet( "patternforoutput", '/(.*)(\/|\\\\)(.+)$/' );
		if( ! $settings->offsetExists( "replacementforoutput" ) ) $settings->offsetSet( "replacementforoutput", '$1$2$3/$3.zip' );
		if( ! $settings->offsetExists( "eventnamebefore" ) ) $settings->offsetSet( "eventnamebefore", "Zip.before" );
		if( ! $settings->offsetExists( "eventnameafter" ) ) $settings->offsetSet( "eventnameafter", "Zip.after" );
		if( ! $settings->offsetExists( "includefilter" ) ) $settings->offsetSet( "includefilter", '/.*/' );
		if( ! $settings->offsetExists( "excludefilter" ) ) $settings->offsetSet( "excludefilter", '/\\.zip$/' );

		?>paramkeyforinput: ko.observable( <?php echo json_encode( $settings->offsetGet( "paramkeyforinput" ) ); ?> ),
		patternforinput: ko.observable( <?php echo json_encode( $settings->offsetGet( "patternforinput" ) ); ?> ),
		replacementforinput: ko.observable( <?php echo json_encode( $settings->offsetGet( "replacementforinput" ) ); ?> ),
		paramkeyforoutput: ko.observable( <?php echo json_encode( $settings->offsetGet( "paramkeyforoutput" ) ); ?> ),
		patternforoutput: ko.observable( <?php echo json_encode( $settings->offsetGet( "patternforoutput" ) ); ?> ),
		replacementforoutput: ko.observable( <?php echo json_encode( $settings->offsetGet( "replacementforoutput" ) ); ?> ),
		eventnamebefore: ko.observable( <?php echo json_encode( $settings->offsetGet( "eventnamebefore" ) ); ?> ),
		eventnameafter: ko.observable( <?php echo json_encode( $settings->offsetGet( "eventnameafter" ) ); ?> ),
		includefilter: ko.observable( <?php echo json_encode( $settings->offsetGet( "includefilter" ) ); ?> ),
		excludefilter: ko.observable( <?php echo json_encode( $settings->offsetGet( "excludefilter" ) ); ?> ),
		<?php
	}

	public function renderEventSettings()
	{
		parent::renderEventSettings();

		?><label>
			<span>Parameter Key (input):<br /><em>Used as source string to determine input folder</em></span>
			<input type="text" data-bind="value: paramkeyforinput" />
		</label>
		<label>
			<span>Pattern (input):</span>
			<input type="text" data-bind="value: patternforinput" />
		</label>
		<label>
			<span>Replacement (input):</span>
			<input type="text" data-bind="value: replacementforinput" />
		</label>
		<label>
			<span>Parameter Key (output):<br /><em>Used as source string to determine output filename for zip archive</em></span>
			<input type="text" data-bind="value: paramkeyforoutput" />
		</label>
		<label>
			<span>Pattern (output):</span>
			<input type="text" data-bind="value: patternforoutput" />
		</label>
		<label>
			<span>Replacement (output):</span>
			<input type="text" data-bind="value: replacementforoutput" />
		</label>
		<label>
			<span>Event Name (before):</span>
			<input type="text" data-bind="value: eventnamebefore" />
		</label>
		<label>
			<span>Event Name (after):</span>
			<input type="text" data-bind="value: eventnameafter" />
		</label>
		<label>
			<span>Include Filter:</span>
			<input type="text" data-bind="value: includefilter" />
		</label>
		<label>
			<span>Exclude Filter:</span>
			<input type="text" data-bind="value: excludefilter" />
		</label>
		<details>
			<summary>Toggle examples</summary>
			<h3>Parameter Key (input):</h3>
			<code>outputFolder</code>
			<h3>Pattern (input):</h3>
			<code>/^(.+)$/</code>
			<h3>Replacement (input):</h3>
			<code>$1</code>
			<h3>Parameter Key (output):</h3>
			<code>outputFolder</code>
			<h3>Pattern (output):</h3>
			<code>/(.*)(\/|\\)(.+)$/</code>
			<h3>Replacement (output):</h3>
			<code>$1$2$3/$3.zip</code>
			<h3>Event Name (before):</h3>
			<code>Zip.before</code>
			<h3>Event Name (after):</h3>
			<code>Zip.after</code>
			<h3>Include Filter:</h3>
			<code>/.+/</code>
			<h3>Exclude Filter:</h3>
			<code>/\.svn|\.tmp/</code>
		</details>
		<?php
	}
}