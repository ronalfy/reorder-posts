<?php
/**
 * User Preferences
 *
 * @package    ReorderPosts
 */

namespace MediaRon\ReorderPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User class
 *
 * @package    ReorderPosts
 */
class User {

	/**
	 * Get the user preferences
	 *
	 * @param int    $user_id The user ID.
	 * @param string $post_type The post type.
	 * @return array
	 */
	public static function get_preferences( int $user_id, string $post_type ): array {
		$user_preferences = get_user_meta( $user_id, 'reorder_user_preferences_' . $post_type, true );
		if ( ! $user_preferences ) {
			$user_preferences = self::get_defaults();
		}
		return $user_preferences;
	}

	/**
	 * Get the user defaults
	 *
	 * @return array
	 */
	private static function get_defaults(): array {
		return array(
			'posts_per_page' => 50,
			'post_status'    => array(
				'publish',
			),
		);
	}
}
