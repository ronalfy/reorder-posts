=== Metronet Reorder Posts ===
Contributors: ryanhellyer, metronet, ronalfy
Author URI: http://metronet.no/
Plugin URL: http://metronet.no/
Requires at Least: 3.7
Tested up to: 4.1
Tags: reorder, re-order, posts, wordpress, post-type, ajax, admin, hierarchical, menu_order, ordering
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple and easy way to reorder your custom post-type posts in WordPress.

== Description ==

A simple and easy way to reorder your custom post-type posts in WordPress. Adds drag and drop functionality for post ordering in the WordPress admin panel. Works with custom post-types and regular posts.

A settings panel is available for determining which post types to enable ordering for.  Advanced options allow you to change the menu order for post types.

== Installation ==

Either install the plugin via the WordPress admin panel, or ... 

1. Upload `metronet-reorder-posts` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

For each post type, you will see a new "Reorder" submenu.  Simply navigate to "Reorder" to change the order of your post types. Changes are saved immediately, there is no need to click a save or update button.  

By default, ordering is enabled for all post types.  A settings panel is available for determining which post types to enable ordering for.  Advanced options allow you to change the menu order for post types.

== Frequently Asked Questions ==

= Where's the settings page? =

The settings are located under Settings->Reorder Posts.

= Where is the "save" button when re-ordering? =

There isn't one. The changes are saved automatically.

= Do I need to add custom code to get this to work? =

Yes, and no.  There are many ways to retrieve posts using the WordPress API, and if the code has a `menu_order` sort property, the changes should be reflected immediately.

Often, however, there is no `menu_order` argument.  In the plugin's settings, there is an "Advanced" section which will attempt to override the `menu_order` property.  Please use this with caution. 

= Can I use this on a single post type? =

You are able to override the post types used via a filter (see below) or navigate to the plugin's settings and enable which post types you would like to use.

`<?php

add_filter( 'metronet_reorder_post_types', 'slug_set_reorder' );
function slug_set_reorder( $post_types ) {
	$post_types = array( 'my_custom_post_type', 'my_other_post_type' );
	return $post_types;
}

?>`

= Does the plugin work with hierarchical post types? =

Yes, but be wary that the plugin now allows you to re-nest hierarchical items easily.

= Does it work in older versions of WordPress? =

This plugin requires WordPress 3.7 or above.  We urge you, however, to always use the latest version of WordPress.

== Screenshots ==

1. Metronet Reorder Posts allows you to easily drag and drop posts to change their order

== Changelog ==

= 2.0.0 =
* Released 2014-12-12 
* Added settings panel for enabling/disabling the Reorder plugin for post types.
* Added advanced settings for overriding the menu order of post types.
* Added internationalization capabilities. 
* Slightly adjusted the styles of the Reordering interface.

= 1.0.6 =
* Updated 2014-12-11 - Ensuring WordPress 4.1 compatibility
* Released 2013-07-19
* Added new filter for editing the post-types supported
* Thanks to mathielo for the suggestion and code contribution.

= 1.0.5 =
* Released 2012-08-09
* Added expand/collapse section for nested post types
* Added better page detection for scripts and styles

= 1.0.4 =
* Released 2012-07-11
* Added support for hierarchical post types

= 1.0.3 =
* Released 2012-05-09
* Updated screenshot
* Corrected function prefix
* Additional: changed readme.txt (didn't bump version number)

= 1.0.2 =
* Released 2012-05-09
* Added ability to post type of posts to be reordered
* Fixed bug in initial order

= 1.0.1 =
* Added ability to change menu name via class argument
* Removed support for non-hierarchical post-types

= 1.0 =
* Initial plugin release
