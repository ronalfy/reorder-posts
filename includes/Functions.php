<?php
/**
 * Helper functions for the plugin.
 *
 * @package ReorderPosts
 */

namespace MediaRon\ReorderPosts;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access.' );
}

/**
 * Class Functions
 */
class Functions {

	/**
	 * Checks if the plugin is on a multisite install.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network_admin Check if in network admin.
	 *
	 * @return true if multisite, false if not.
	 */
	/**
	 * Checks if the plugin is installed on a multisite install.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network_admin Check if in network admin.
	 *
	 * @return true if multisite, false if not.
	 */
	public static function is_multisite( $network_admin = false ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}
		if ( $network_admin ) {
			if ( is_network_admin() ) {
				if ( is_plugin_active_for_network( REORDER_BASENAME ) ) {
					return true;
				}
			} else {
				return false;
			}
		}
		if ( is_multisite() && is_plugin_active_for_network( REORDER_BASENAME ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the REST URL for a given network.
	 *
	 * @param string $path The path to the REST URL.
	 *
	 * @return string The REST URL.
	 */
	public static function get_rest_url( $path = '' ) {
		if ( self::is_multisite() ) {
			$blog_id = get_current_blog_id();
			return get_rest_url( $blog_id, $path );
		}
		return get_rest_url( null, $path );
	}

	/**
	 * Get the post count for a given post type and post status.
	 *
	 * @param string $post_type The post type.
	 * @param array  $post_status The post status.
	 *
	 * @return int The post count.
	 */
	public static function get_post_count( $post_type, $post_status ) {
		$post_count_obj = wp_count_posts( $post_type );
		if ( ! is_array( $post_count_obj ) ) {
			return 0;
		}
		$post_count = 0;
		foreach ( $post_status as $status ) {
			$post_count += isset( $post_count_obj[ $status ] ) ? absint( $post_count_obj[ $status ] ) : 0;
		}
		return $post_count;
	}

	/**
	 * Get the current admin tab.
	 *
	 * @return null|string Current admin tab.
	 */
	public static function get_admin_tab() {
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_SPECIAL_CHARS );
		if ( $tab && is_string( $tab ) ) {
			return sanitize_text_field( sanitize_title( $tab ) );
		}
		return null;
	}

	/**
	 * Checks to see if an asset is activated or not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path to the asset.
	 * @param string $type Type to check if it is activated or not.
	 *
	 * @return bool true if activated, false if not.
	 */
	public static function is_activated( $path, $type = 'plugin' ) {

		// Gets all active plugins on the current site.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( is_multisite() ) {
			if ( is_plugin_active_for_network( $path ) ) {
				return true;
			}
		}
		if ( is_plugin_active( $path ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Take a _ separated field and convert to camelcase.
	 *
	 * @param array $fields Array of fields to convert to camelcase.
	 *
	 * @return string camelCased field.
	 */
	public static function to_camelcase_recursive( array $fields ) {
		foreach ( $fields as $key => $value ) {
			if ( is_numeric( $key ) || is_bool( $key ) ) {
				continue;
			}
			// Store old key.
			$old_key = $key;
			if ( is_array( $value ) ) {
				$value = self::to_camelcase_recursive( $value );
			}
			$key = self::to_camelcase( $key );
			if ( $key !== $old_key ) {
				unset( $fields[ $old_key ] );
			}
			$fields[ $key ] = $value;
		}
		return $fields;
	}

	/**
	 * Take a _ separated field and convert to camelcase.
	 *
	 * @param string $field Field to convert to camelcase.
	 *
	 * @return string camelCased field.
	 */
	public static function to_camelcase( string $field ) {
		return str_replace( '_', '', lcfirst( ucwords( $field, '_' ) ) );
	}

	/**
	 * Take a camelcase field and converts it to underline case.
	 *
	 * @param string $field Field to convert to camelcase.
	 *
	 * @return string $field Field name in camelCase..
	 */
	public static function to_underlines( string $field ) {
		$regex = '/([a-z])([A-Z])/';
		if ( preg_match( $regex, $field ) ) {
			$field = strtolower( preg_replace( $regex, '$1_$2', $field ) );
		}
		return $field;
	}

	/**
	 * Take a camelcase key and converts it to underline case.
	 *
	 * @param array $fields Array of fields to convert to underline case.
	 *
	 * @return array $fields Array of fields in underline case.
	 */
	public static function to_underlines_recursive( array $fields ) {
		foreach ( $fields as $key => $value ) {
			if ( is_numeric( $key ) || is_bool( $key ) ) {
				continue;
			}
			// Store old key.
			$old_key = $key;

			// Convert key to underline case.
			$key            = self::to_underlines( $key );
			$fields[ $key ] = $value;

			// Unset old key if it has changed.
			if ( $key !== $old_key ) {
				unset( $fields[ $old_key ] );
			}

			// Recursively convert array values to underline case.
			if ( is_array( $value ) ) {
				$fields[ $key ] = self::to_underlines_recursive( $value );
			}
		}
		return $fields;
	}

	/**
	 * Array data that must be sanitized.
	 *
	 * @param array $data Data to be sanitized.
	 * @param bool  $strict Whether to strictly sanitize text values with esc_attr(). Default is false.
	 *
	 * @return array Sanitized data.
	 */
	public static function sanitize_array_recursive( array $data, $strict = false ) {
		$sanitized_data = array();
		foreach ( $data as $key => $value ) {
			$key = esc_attr( $key );
			if ( '0' === $value ) {
				$value = 0;
			}
			if ( 'true' === $value ) {
				$value = true;
			} elseif ( 'false' === $value ) {
				$value = false;
			}
			if ( is_array( $value ) ) {
				$value                  = self::sanitize_array_recursive( $value );
				$sanitized_data[ $key ] = $value;
				continue;
			}
			if ( is_bool( $value ) ) {
				$sanitized_data[ $key ] = (bool) $value;
				continue;
			}
			if ( is_int( $value ) ) {
				$sanitized_data[ $key ] = (int) $value;
				continue;
			}
			if ( is_string( $value ) ) {
				$sanitized_data[ $key ] = sanitize_text_field( wp_unslash( $value ) );
				if ( $strict ) {
					$sanitized_data[ $key ] = esc_attr( wp_unslash( $value ) );
				}
				continue;
			}
		}
		return $sanitized_data;
	}

	/**
	 * Get a value from an array.
	 *
	 * @param array  $array_to_check The array to get the value from.
	 * @param string $key The key to get the value from.
	 * @param mixed  $default_value The default value to return if the key is not found. Default is null.
	 *
	 * @return mixed The value from the array.
	 */
	public static function get_ar( array $array_to_check, string $key, $default_value = null ) {
		if ( ! is_array( $array_to_check ) && ! ( is_object( $array_to_check ) && $array_to_check instanceof ArrayAccess ) ) {
			return $default_value;
		}

		if ( isset( $array_to_check[ $key ] ) ) {
			$value = $array_to_check[ $key ];
		} else {
			$value = '';
		}

		return empty( $value ) && null !== $default_value ? $default_value : $value;
	}

	/**
	 * Get a value from an array by path.
	 *
	 * @param array  $array_to_check The array to get the value from.
	 * @param string $path The path to get the value from. Can include paths separated by slashes.
	 * @param mixed  $default_value The default value to return if the key is not found. Default is null.
	 *
	 * @return mixed The value from the array.
	 */
	public static function get_ars( array $array_to_check, string $path, $default_value = null ) {
		if ( ! is_array( $array_to_check ) && ! ( is_object( $array_to_check ) && $array_to_check instanceof ArrayAccess ) ) {
			return $default_value;
		}

		$names = explode( '/', $path );
		$val   = $array_to_check;
		foreach ( $names as $current_name ) {
			$val = self::get_ar( $val, $current_name, $default_value );
		}

		return $val;
	}

	/**
	 * Get the admin capability for the plugin.
	 *
	 * @return string The admin capability.
	 */
	public static function get_admin_capability() {
		$capability = 'manage_options';
		if ( self::is_multisite() ) {
			$capability = 'manage_network';
		}
		/**
		 * Filter the admin capability for the plugin.
		 *
		 * @param string $capability The admin capability.
		 *
		 * @return string The admin capability.
		 */
		return apply_filters( 'reorder_posts_admin_capability', $capability );
	}

	/**
	 * Get the plugin directory for a path.
	 *
	 * @param string $path The path to the file.
	 *
	 * @return string The new path.
	 */
	public static function get_plugin_dir( $path = '' ) {
		$dir = rtrim( plugin_dir_path( REORDER_FILE ), '/' );
		if ( ! empty( $path ) && is_string( $path ) ) {
			$dir .= '/' . ltrim( $path, '/' );
		}
		return $dir;
	}

	/**
	 * Return a plugin URL path.
	 *
	 * @param string $path Path to the file.
	 *
	 * @return string URL to to the file.
	 */
	public static function get_plugin_url( $path = '' ) {
		$dir = rtrim( plugin_dir_url( REORDER_FILE ), '/' );
		if ( ! empty( $path ) && is_string( $path ) ) {
			$dir .= '/' . ltrim( $path, '/' );
		}
		return $dir;
	}

	/**
	 * Gets the highest priority for a filter.
	 *
	 * @param int $subtract The amount to subtract from the high priority.
	 *
	 * @return int priority.
	 */
	public static function get_highest_priority( $subtract = 0 ) {
		$highest_priority = PHP_INT_MAX;
		$subtract         = absint( $subtract );
		if ( 0 === $subtract ) {
			--$highest_priority;
		} else {
			$highest_priority = absint( $highest_priority - $subtract );
		}
		return $highest_priority;
	}
}
