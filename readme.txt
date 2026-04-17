=== Reorder Posts - Quick Post Type and Page Ordering ===
Contributors: ronalfy, ryanhellyer, scottbasgaard
Author URI: https://github.com/ronalfy/reorder-posts
Plugin URL: https://wordpress.org/plugins/metronet-reorder-posts/
Requires at Least: 6.5
Tested up to: 7.0
Tags: reorder, reorder posts, menu order, ordering, re-order
Stable tag: 2.6.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Quickly reorder posts, pages, and custom post types using an intuitive drag-and-drop interface. Reordering supports auto-sorting and nested pages.

== Description ==

Reorder Posts is a simple and flexible way to reorder posts, pages, and custom post types in WordPress using drag and drop. This plugin gives you full control over post ordering by leveraging WordPress's built-in `menu_order` functionality, while keeping the interface fast, and intuitive. For those not familiar with code, the admin settings allows for auto-sorting based on post type. For hierarchical post types, the plugin supports nested pages.

[youtube https://www.youtube.com/watch?v=qDa6Q6mDPbo]

=== Who is Reorder Posts For? ===

Reorder Posts is designed for developers and site builders who need precise control over how content is displayed. Whether you're organizing landing pages, structuring custom post type archives, or manually controlling query output, this plugin provides a reliable way to define ordering without relying on publish dates or titles.

Once activated, the plugin adds a "Reorder" submenu to supported post types in the WordPress dashboard. For custom post types that do not have an interface, you can click a shortcut to reorder the post type from within the admin settings. From there, you can drag and drop posts, pages, and post types into the exact order you need. Changes are saved automatically, so there's no need for a save button or extra clicks.

=== What Post Types and Queries Does Reordering Support? ===

This plugin works seamlessly with both standard posts, pages, and custom post types. It also supports hierarchical post types, allowing you to reorder and re-nest items like pages or nested content structures. This makes it especially useful for sites that rely on structured content, such as documentation, courses, or directories. For hidden post types (post types that expose no UI), you can reorder these post types from a shortcut within the Reorder Posts admin settings.

Because the plugin uses the native `menu_order` field, it integrates cleanly with WordPress queries. If your queries already support ordering by `menu_order`, your changes will appear instantly on the front-end. If not, the plugin includes advanced options in the Reorder admin settings to help override default query behavior when needed.

Reorder Posts does not auto-sort your post types on the front-end automatically unless explicitly enabled in the admin settings. Instead, it gives you the tools to define ordering while leaving implementation decisions in your hands. This makes it ideal for developers who want flexibility without unnecessary abstraction. The admin settings for auto-sorting are completely optional.

=== Choosing Which Post Types to Reorder ===

A dedicated settings panel allows you to control which post types support reordering. By default, common post types like posts and pages are enabled, while others can be toggled on as needed. If a post type is hidden, with no visibile editing interface, you can still reorder it by enabling it and clicking the Reorder shortcut next to the post type.

For more advanced use cases, developers can hook into filters to customize behavior programmatically. This includes defining which post types are reorderable or adjusting how ordering is applied across queries.

=== Reordering Categories, Terms, and Posts Within Categories ===

If you need to reorder posts within taxonomies such as categories or terms, companion add-ons are available to extend functionality even further.

* <a href="https://wordpress.org/plugins/reorder-by-term/">Reorder by Term</a> - Reorders posts/pages/post types by which category they are in.
* <a href="https://wordpress.org/plugins/reorder-terms/">Reorder Terms</a> - Reorders categories, terms, and any other public taxonomy.

=== Major Features ===

Key features include:

* Drag and drop post ordering in the WordPress admin
* Support for posts, pages, and custom post types
* Hierarchical post type support with support for nested pages
* Automatic saving with no manual update required
* Developer-friendly approach using `menu_order`
* Advanced settings for query overrides and post type auto-sorting

Reorder Posts is best suited for users who understand how WordPress queries work or are comfortable working with `WP_Query`, `get_posts`, or `pre_get_posts`. If you need full control over content ordering without sacrificing performance or simplicity, this plugin provides a clean and reliable solution.

=== Development ===

You are welcome to help us out and <a href="https://github.com/ronalfy/reorder-posts">contribute on GitHub</a>.

=== Credits ===

This plugin was originally developed for <a href="https://metronet.no/">Metronet AS in Norway</a>.

The plugin is now independently developed by <a href="https://geek.hellyer.kiwi/">Ryan Hellyer</a>, <a href="http://www.ronaldhuereca.com">Ronald Huereca</a> and <a href="http://scottbasgaard.com/">Scott Basgaard</a>.

== Installation ==

Either install the plugin via the WordPress admin panel, or ...

1. Upload `metronet-reorder-posts` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

For each post type, you will see a new "Reorder" submenu.  Simply navigate to "Reorder" to change the order of your post types. Changes are saved immediately, there is no need to click a save or update button.

By default, ordering is enabled for only posts and pages.  A settings panel is available for determining which post types to enable ordering for.

Advanced customization is allowed via hooks.  See the <a  href="https://github.com/ronalfy/reorder-posts#plugin-filters">Plugin Filters on GitHub</a>.

This tool allows you to easily reorder post types in the back-end of WordPress. Auto-sorting is available through the admin panel settings.

You'll want to make use of <a href="https://developer.wordpress.org/reference/classes/wp_query/">WP_Query</a>, <a href="https://developer.wordpress.org/reference/functions/get_posts/">get_posts</a>, or <a href="https://developer.wordpress.org/reference/hooks/pre_get_posts/">pre_get_posts</a> to modify query behavior on the front-end of your site.

Examples of each are on the respective pages above.  You are welcome to leave a support request if you need help with a query and we'll do our best to get back with you.

== Frequently Asked Questions ==

= Where's the settings page? =

You can find the settings under Settings → Reorder Posts. The plugin works out of the box with no configuration, but the settings allow you to control which post types are enabled and access advanced options such as auto-sorting post types on the frontend.


= Where is the "save" button when re-ordering? =

There isn’t one. Changes are saved automatically as you drag and drop items. This keeps the workflow fast and eliminates unnecessary steps.

If you are using nested pages or hierarchical post types, you can even drag pages from one area to another, and have it saved automatically.

= How does this plugin reorder posts in WordPress? =

The plugin updates the built-in `menu_order` field for each post. WordPress supports ordering by this field, which means you can control display order when your queries use `orderby => menu_order`.

= Do I need to modify my queries for this to work? =

In many cases, yes. Your queries need to support `menu_order` sorting. If they already do, your changes will appear immediately. If not, you may need to adjust your query arguments or use the plugin's advanced settings to override behavior.

If you are using query blocks or custom queries, just change the `orderby` parameter to work with "Menu Order."

= Does this work with custom post types? =

Yes. The plugin fully supports custom post types. You can enable or disable reordering per post type in the settings panel or via filters.

= Can I reorder hierarchical content like pages? =

Yes. The plugin supports hierarchical post types and allows you to re-nest items. This means you can change both order and parent-child relationships visually.

= Can I limit reordering to specific post types? =

Yes. You can either use the settings screen or apply a filter to define exactly which post types should support reordering.

Here's an example:

`<?php

add_filter( 'metronet_reorder_post_types', 'slug_set_reorder' );
function slug_set_reorder( $post_types ) {
	$post_types = array( 'my_custom_post_type', 'my_other_post_type' );
	return $post_types;
}

?>`

= Does this affect front-end display automatically or support auto-sorting? =

No, the plugin doesn't automatically enable itself to sort on the frontend. There are per-post-type settings for setting a global sort, but manually doing queries or using a query block is much more flexible.

= Is this plugin beginner-friendly? =

This plugin is best suited for developers or users familiar with WordPress queries. If you are not comfortable working with `menu_order` or query arguments, you may need some additional guidance to get the most out of it.

= Does it support large sites with many posts? =

Yes, but for very large datasets, pagination and performance considerations apply. We don't recommmend reordering post types that have over 1,000 items.

= Where can I get help or report issues? =

You can open a support request on WordPress.org or submit an issue on GitHub.

== Screenshots ==

1. Reorder items like posts, pages, and hierarchical post types with simple drag and drop.
2. Use the admin settings to enable post types to reorder, and perform any auto-sorting needed on the frontend.
3. Example Reorder menu showing up for pages.

== Changelog ==

= 2.6.0 =
* Released 2026-03-23
* Fix: Default post types that are enabled are only post and page. The rest should be opt-in.
* Fix: All post types were having menus registered, even when they weren't explicitly enabled.
* New: Reorder Posts shortcut shows up next to each post type in admin settings (if enabled).
* New: Post types with no top-level menu now are visible at the root level and can be accessed via the admin shortcuts.

= 2.5.3 =
* Released 2022-05-27
* Cache busting for people who are receiving script errors around Nested Sortable.

= 2.5.1 =
* Released 2022-02-14
* Fixing sortable script with newer versions of jQuery sortable.

= 2.5.0 =
* Released 2020-11-18
* Fixing sortable script with newer versions of jQuery.

= 2.4.1 =
* Released 2019-02-16
* Added query to reorder posts for an example
* Option to turn off query output

= 2.4.0 =
* Released 2016-08-14
* Major CSS overhaul inspired by the Nested Pages plugin
* The sorting is now mobile friendly

= 2.3.0 =
* Released 2016-08-12
* Added screen options to set the number of posts displayed

= 2.2.2 =
* Released 2015-12-04
* Fixed loading animation that displays out of nowhere

= 2.2.1 =
* Released 2015-11-09
* Fixed pagination issue

= 2.2.0 =
* Released 2015-11-1
* Loading animation now shows inline

= 2.1.5 =

* Released 2015-10-02
* Fixing paging offset error in the backend.

= 2.1.4 =
* Updated 2015-08-20 for WordPress 4.3
* Released 2015-04-24
* Added cache-busting when re-ordering
* Added German translation
* Ensuring WordPress 4.2 compatibility

= 2.1.2 =
* Released 2015-01-28
* Removed developer notice from Reorder pages

= 2.1.1 =
* Released 2015-01-21
* Fixed pagination issue
* Improved Reorder save query performance significantly

= 2.1.0 =
* Released 2015-01-19
* Added add-on support
* Make sure reordering can only be done by those with edit_pages privileges
* Added pagination for performance reasons
* Optimized queries for performance reasons
* Added warning message for those with a lot of posts

= 2.0.2 =
* Released 2014-12-26
* Bug fix:  Saving admin panel settings resulted in a variety of PHP offset error messages.
* Bug fix:  Querying multiple post types resulted in PHP illegal offset error messages.

= 2.0.1 =
* Released 2014-12-23
* Altered contributor documentation.
* Adding filters for determining where the Reorder sub-menu will show up.
* Sub-menu headings now reflect the post type that is being re-ordered.
* Fixed bug in display when there are no post types to re-order.
* Changed class names to be more unique.

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

== Upgrade Notice ==

= 2.6.0 =
New: shortcuts to reorder each post type are present in the admin settings. Fix: Post types only load when explicitly enabled for performance (defaults are post and page).