*Dash*: a development-side plugin framework
===========================================

What is *Dash*?
---------------

*Dash* is plugin-based framework meant to run development-side in order to help generate high-quality static sites.  The plugin architecture allows drop-in functionality, with a web-based administration panel to enable plugins and modify their settings (or edit the settings yourself--it's a JSON file).

*Dash* is not meant to run on a production server. Plugins, such as Flattener, produce files suitable for production use.

*Dash* is meant to work with Apache and mod_rewrite.

Why *Dash*?
-----------

*Dash* was written to formalize what used to be a set of disjointed scripts whose main purpose were to prepare a dynamically constructed website (think SSI includes) into a static version.  Some of these scripts also performed combining and minification tasks on CSS and JavaScript assets.

*Dash* provides a clean and simple way to add and create plugins that help developers put together quality static websites.  For instance, a Markdown plugin exists that can easily parse a markdown file such as this one into HTML.