<?php
/*
Plugin Name: Reorder Posts
Plugin URI: https://wordpress.org/plugins/metronet-reorder-posts/
Description: Easily reorder posts and pages in WordPress
Version: 2.1.4
Author: Ryan Hellyer, Ronald Huereca, Scott Basgaard
Author URI: https://github.com/ronalfy/reorder-posts
Text Domain: metronet-reorder-posts
Domain Path: /languages

------------------------------------------------------------------------
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/


/**
 * Do not continue processing since file was called directly
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
if ( !defined( 'ABSPATH' ) )
	die( 'Eh! What you doin in here?' );

/**
 * Load classes
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
require( 'class-reorder.php' );
require( 'class-reorder-admin.php' );

/**
 * Define constants
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
define( 'REORDER_ALLOW_ADDONS', true ); //Show support for add-ons
define( 'REORDER_DIR', rtrim( plugin_dir_path(__FILE__), '/' ) ); // Plugin folder DIR
define( 'REORDER_URL', rtrim( plugin_dir_url(__FILE__), '/' ) ); // Plugin folder URL
define( 'REORDER_BASENAME', plugin_basename(__FILE__) ); //Plugin basename

/**
 * Instantiate admin panel
 * Iterate through each specified post type and instantiate it's organiser
 * 
 * @since 1.0
 * @author Ryan Hellyer <ryan@metronet.no>
 */
add_action( 'wp_loaded', 'mn_reorder_posts_init', 100 ); //Load low priority in init for other plugins to generate their post types
function mn_reorder_posts_init() {
	global $mn_reorder_instances;
	$post_types = get_post_types( array(), 'names' );
	
	//Get plugin options for post types and exclude as necessary
	$plugin_options = get_option( 'metronet-reorder-posts', false );
	if ( $plugin_options && isset( $plugin_options[ 'post_types' ] ) && is_array( $plugin_options[ 'post_types' ] )  ) {
		foreach( $plugin_options[ 'post_types' ]  as $post_type => $value ) {
			if( $value === 'off' ) {
				unset( $post_types[ $post_type ] );	
			}
		}
	}
	
	// Add filter to allow users to control which post-types the plugin is used with via their theme
	$post_types = array_unique( apply_filters( 'metronet_reorder_post_types', $post_types ) );
	do_action( 'metronet_reorder_post_types_loaded', $post_types );
		
	foreach ( $post_types as $post_type ) {
		//Generate heading
		$post_type_object = get_post_type_object( $post_type );
		$post_type_label = isset( $post_type_object->label ) ? $post_type_object->label : __( 'Posts', 'metronet-reorder-posts' );
		$heading = sprintf( __( 'Reorder %s', 'metronet-reorder-posts' ), $post_type_label );
		
		// Instantiate new reordering
		$mn_reorder_args = array(
			'post_type'   => $post_type,
			'order'       => 'ASC',
			'heading'     => $heading,
			'final'       => '',
			'initial'     => '',
			'menu_label'  => __( 'Reorder', 'metronet-reorder-posts' ),
			'post_status' => 'publish',
		);
	
		$mn_reorder_instances[ $post_type ] = new MN_Reorder(
			$mn_reorder_args
		);
	}
} //end mt_reorder_posts_init

add_action( 'plugins_loaded', 'mn_reorder_init_language' );
function mn_reorder_init_language() {
	//* Localization Code */
	load_plugin_textdomain( 'metronet-reorder-posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/* Global variable for storing class instances */
global $mn_reorder_instances;
$mn_reorder_instances = array();

