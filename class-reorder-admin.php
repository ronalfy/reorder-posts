<?php
/**
 * Reorder posts
 * 
 * @package    WordPress
 * @subpackage Metronet Reorder Posts plugin
 */


/**
 * Reorder posts
 * Adds drag and drop editor for reordering WordPress posts
 * 
 * This class is self-instantiated and should not be used directly
 * 
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ronald Huereca
 * @since 1.0
 */
class Reorder_Admin {
	/**
	 * @var $instance 
	 * @desc Instance of the admin class
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Class constructor
	 * 
	 * Sets definitions
	 * Adds methods to appropriate hooks
	 * 
	 * @author Ronald Huereca
	 * @since Reorder 1.1
	 * @access private
	 */
	private function __construct( ) {
	}
	
	//Singleton
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance
	
} //end class Reorder_Admin

add_action( 'init', 'mn_reorder_admin_instantiate', 15 );
function mn_reorder_admin_instantiate() {
	Reorder_Admin::get_instance();
}
