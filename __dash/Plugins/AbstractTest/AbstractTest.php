<?php

namespace Plugins\AbstractTest;

abstract class AbstractTest extends \Dash\Plugin
{
	// This plugin should NOT show up in the Admin panel.
	// This allows us to install plugins that are meant to provide base/shared functionality for other plugins.
}