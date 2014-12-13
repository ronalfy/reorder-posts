=== Metronet Reorder Posts ===
Contributors: ryanhellyer, metronet, ronalfy
Author URI: http://metronet.no/
Plugin URL: http://metronet.no/
Requires at Least: 3.7
Tested up to: 4.1
Tags: reorder, re-order, posts, wordpress, post-type, ajax, admin, hierarchical, menu_order, ordering
Stable tag: 1.0.6

A simple and easy way to reorder your custom post-type posts in WordPress.

== Description ==

A simple and easy way to reorder your custom post-type posts in WordPress. Adds drag and drop functionality for post ordering in the WordPress admin panel. Works with custom post-types and regular posts.

== Installation ==

Either install the plugin via the WordPress admin panel, or ... 

1. Upload `metronet-reorder-posts` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

There are no configuration options in this plugin. Simply navigate to "Posts" > "Reorder" to change the order of your posts. Changes are saved immediately, there is no need to click a save or update button. Additional links automatically appear for custom post-types.

== Frequently Asked Questions ==

= Where's the settings page? =

There is no settings page per se. The plugin adds an ordering page as a submenu under each post type.

= Where is the "save" button? =

There isn't one. The changes are saved automatically.

= Can I use this on a single post type? =

We have attempted to make the class as user-friendly and adaptable as possible. You can either extract the "Reorder" class from the plugin and use it directly. Or alternatively, you can filter the post-types via your custom plugin or theme.

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

Probably, but we only support the latest version of WordPress.

== Screenshots ==

1. Metronet Reorder Posts allows you to easily drag and drop posts to change their order

== Changelog ==

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
