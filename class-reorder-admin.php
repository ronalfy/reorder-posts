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
 * @since 2.0.0
 */
class MN_Reorder_Admin {
	/**
	 * @var $instance 
	 * @desc Instance of the admin class
	 * @access private
	 */
	private static $instance = null;
	
	/**
	 * @var $options 
	 * @desc WordPress options for access within this class
	 * @access private
	 */
	private $options = false;

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
		//Filter to hide the admin panel options
		if ( !apply_filters( 'metronet_reorder_post_allow_admin', true ) ) return; //Use this filter if you want to disable the admin panel settings for this plugin, including disabling the menu order overrides
		
		//Initialize actions
		/* Dev note:  If you know what you're doing code-wise, add the menu_order in get_posts, pre_get_posts, or WP_Query, disable these filters, and/or disable the admin settings (see above). */
		add_filter( 'posts_orderby', array( $this, 'modify_menu_order_sql' ), 30, 2 );
		add_action( 'pre_get_posts', array( $this, 'modify_menu_order_pre' ), 30, 1 );
		
		//Admin Settings
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_admin_settings' ) );
		
		//Plugin settings
		add_filter( 'plugin_action_links_' . REORDER_BASENAME , array( $this, 'add_settings_link' ) );
	}
	
	//Singleton
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance
	
	/**
	 * Initialize options page
	 *
	 * Create plugin options page and callback
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see init
	 *
	 */	
	public function add_admin_menu() {
		add_options_page( _x( 'Reorder Posts', 'Plugin Name - Settings Page Title', 'metronet-reorder-posts' ), _x( 'Reorder Posts', 'Plugin Name - Menu Item', 'metronet-reorder-posts' ), 'manage_options', 'metronet-reorder-posts', array( $this, 'options_page' ) );
	}
	
	/**
	 * Add post types to be overridden using menu_order
	 *
	 * Output options for menu_order
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init_admin_settings
	 *
	 * @param array $args {	
	 		@type string $desc Description for the setting.
	 		
	 }
	 */
	public function add_settings_field_menu_order( $args = array() ) {
		//Get options/defaults
		$settings = $this->get_plugin_options();
		$settings_menu_order = isset( $settings[ 'menu_order' ] ) ? $settings[ 'menu_order' ] : array();
		$post_types = get_post_types( array(), 'object' );
		
		//Foreach loop to show de awesome post types
		foreach( $post_types as $post_type_name => $post_type ) {
			
			//Determine whether to show this post type (show_ui = true)
			$show_ui = (bool)isset( $post_type->show_ui ) ? $post_type->show_ui : false;
			if ( !$show_ui || 'attachment' === $post_type_name ) continue;
			
			//Get post type labels for checkbox
			$post_type_value = isset( $settings_post_types[ $post_type_name ] ) ? $settings_post_types[ $post_type_name ] : 'on';
			$post_type_label = isset( $post_type->label ) ? $post_type->label : $post_type_name;
			printf( '<div id="metronet-reorder-posts-%s">', esc_attr( $post_type_name ) );
			printf( '<h3>%s</h3>', esc_html( $post_type_label ) );
			
			//Get menu order arguments
			$menu_orderby = isset( $settings_menu_order[ $post_type_name ][ 'orderby' ] ) ? $settings_menu_order[ $post_type_name ][ 'orderby' ] : 'none';
			
			//Output Menu Order Arguments
			printf(  '<p>%s</p>', esc_html__( 'Sort by:', 'metronet-reorder-posts' ) );
			printf( '<select name="metronet-reorder-posts[menu_order][%1$s][orderby]">', esc_attr( $post_type_name ) );
			printf( '<option value="none" %s>%s</option>', selected( 'none', $menu_orderby, false ), esc_html__( 'None', 'metronet-reorder-posts' ) );
			printf( '<option value="menu_order" %s>%s</option>', selected( 'menu_order', $menu_orderby, false ), esc_html__( 'Menu Order', 'metronet-reorder-posts' ) );
			echo '</select>';
			
			//Get order arguments
			$menu_order = isset( $settings_menu_order[ $post_type_name ][ 'order' ] ) ? $settings_menu_order[ $post_type_name ][ 'order' ] : 'DESC'; //DESC is WP_Query default
			//Output Menu Order Arguments
			printf(  '<p>%s</p>', esc_html__( 'Sort Order:', 'metronet-reorder-posts' ) );
			printf( '<select name="metronet-reorder-posts[menu_order][%1$s][order]">', esc_attr( $post_type_name ) );
			printf( '<option value="ASC" %s>%s</option>', selected( 'ASC', $menu_order, false ), esc_html__( 'ASC', 'metronet-reorder-posts' ) );
			printf( '<option value="DESC" %s>%s</option>', selected( 'DESC', $menu_order, false ), esc_html__( 'DESC', 'metronet-reorder-posts' ) );
			echo '</select>';
			
			//Output closing div
			echo '</div>';
		}
	}
	
	/**
	 * Add post types to be reordered using the plugin
	 *
	 * Output checkboxes for post types
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init_admin_settings
	 *
	 * @param array $args {	
	 		@type string $desc Description for the setting.
	 		
	 }
	 */
	public function add_settings_field_post_types( $args = array() ) {
		//Get options/defaults
		$settings = $this->get_plugin_options();
		$settings_post_types = isset( $settings[ 'post_types' ] ) ? $settings[ 'post_types' ] : array();
		$post_types = get_post_types( array(), 'object' );
		
		//Foreach loop to show de awesome post types
		foreach( $post_types as $post_type_name => $post_type ) {
			
			//Determine whether to show this post type (show_ui = true)
			$show_ui = (bool)isset( $post_type->show_ui ) ? $post_type->show_ui : false;
			if ( !$show_ui || 'attachment' === $post_type_name ) continue;
			
			//Get post type labels for checkbox
			$post_type_value = isset( $settings_post_types[ $post_type_name ] ) ? $settings_post_types[ $post_type_name ] : 'on';
			$post_type_label = isset( $post_type->label ) ? $post_type->label : $post_type_name;
			$checked = '';
			if ( $post_type_value === 'on' ) {
				$checked = checked( true, true, false );
			} 
			
			//Output post type option
			printf( '<div><input type="hidden" name="metronet-reorder-posts[post_types][%1$s]" value="off" /> <input type="checkbox" name="metronet-reorder-posts[post_types][%1$s]" id="post_type_%1$s" value="on" %2$s /><label for="post_type_%1$s">&nbsp;%3$s</label></div>', esc_attr( $post_type_name ), $checked, esc_html( $post_type_label ) );
		}
	}
	
	public function add_settings_heading_menu_order() {
		printf( '<p>%s</p>', esc_html__( 'These are advanced options and you should only use these if you know what you are doing.  These can overwrite the menu orders of custom queries (using get_posts or WP_Query), custom post type archives, and even the order your blog posts display.', 'metronet-reorder-posts' ) );	
	}
	/**
	 * Add a settings link to the plugin's options.
	 *
	 * Add a settings link on the WordPress plugin's page.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see init
	 *
	 * @param array $links Array of plugin options
	 * @return array $links Array of plugin options
	 */
	public function add_settings_link( $links ) { 
		$settings_link = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=metronet-reorder-posts' ) ), _x( 'Settings', 'Plugin settings link on the plugins page', 'metronet-reorder-posts' ) ); 
			array_unshift($links, $settings_link); 
			return $links; 
	}
	
	/**
	 * Initialize and return plugin options.
	 *
	 * Return an array of plugin options.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @see init
	 *
	 * @return array Plugin options
	 */
	public function get_plugin_options() {
		if ( false === $this->options ) {
			$settings = get_option( 'metronet-reorder-posts' );	
		} else {
			$settings = $this->options;
		}
		
		if ( false === $settings || !is_array( $settings ) ) {
			$defaults = array(
				'post_types' => array(),
				'menu_order' => array(),
			);
			update_option( 'metronet-reorder-posts', $defaults );
			return $defaults;
		}
		$this->options = $settings;
		return $settings;
	}
	
	/**
	 * Initialize options 
	 *
	 * Initialize page settings, fields, and sections and their callbacks
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see init
	 *
	 */
	public function init_admin_settings() {
		register_setting( 'metronet-reorder-posts', 'metronet-reorder-posts', array( $this, 'sanitization' ) );
		
		add_settings_section( 'mn-reorder-post-types', _x( 'Enable Post Types', 'plugin settings heading' , 'metronet-reorder-posts' ), '__return_empty_string', 'metronet-reorder-posts' );
		
		add_settings_section( 'mn-reorder-menu-order', _x( 'Advanced', 'plugin settings heading' , 'metronet-reorder-posts' ), array( $this, 'add_settings_heading_menu_order' ), 'metronet-reorder-posts' );
		
		add_settings_field( 'mn-post-types', __( 'Post Types', 'metronet-reorder-posts' ), array( $this, 'add_settings_field_post_types' ), 'metronet-reorder-posts', 'mn-reorder-post-types', array( 'desc' => __( 'Select the post types you would like this plugin enabled for.', 'metronet-reorder-posts' ) ) );
		
		add_settings_field( 'mn-menu-order', __( 'Select the menu order arguments.', 'metronet-reorder-posts' ), array( $this, 'add_settings_field_menu_order' ), 'metronet-reorder-posts', 'mn-reorder-menu-order', array( 'desc' => __( 'Select the menu order for the post types.', 'metronet-reorder-posts' ) ) );
		
	}
	
	//Used for get_posts calls
	public function modify_menu_order_pre( $query ) {
		if( $query->is_main_query() || is_admin() || is_feed() ) return;
		
		if ( !apply_filters( 'metronet_reorder_allow_menu_order', true ) ) return; //Return false to disable this completely
		
		//Get main plugin options
		$plugin_options = $this->get_plugin_options();
		if ( !isset( $plugin_options[ 'menu_order' ] ) || !is_array( $plugin_options[ 'menu_order' ] ) || empty( $plugin_options[ 'menu_order' ] ) ) return;
		$menu_order = $plugin_options[ 'menu_order' ];
		
		//Get main post type
		$main_query = $query->query;
		$post_type = false;
		if( isset( $main_query[ 'post_type' ] ) ) {
			//Typically set on post type archives or custom WP_Query or get_posts instances
			$post_type = $main_query[ 'post_type' ];	
		}
		if ( !$post_type || is_array( $post_type ) ) return;
		
		//See if suppress filters is on (using get_posts) (if it's not, use modify_menu_order_sql for that)
		if ( !isset( $main_query[ 'suppress_filters' ] ) ) return;
		
		if ( !apply_filters( 'metronet_reorder_allow_menu_order_' . $post_type, true ) ) return; //Return false to disable this for each individual post type as opposed to using a global filter above
		
		//See if we're modifying the menu_order
		$menu_order_orderby = isset( $menu_order[ $post_type ][ 'orderby' ] ) ? $menu_order[ $post_type ][ 'orderby' ] : 'none';
		$menu_order_order = isset( $menu_order[ $post_type ][ 'order' ] ) ? $menu_order[ $post_type ][ 'order' ] : 'DESC';
		
		//Return if post type is not be ordered
		if ( $menu_order_orderby === 'none' ) return;
		
		//Return of $menu_order_order is invalid
		if ( $menu_order_order !== 'ASC' && $menu_order_order !== 'DESC' ) return;
		
		//Overwrite the orderby clause
		$query->set( 'orderby',  'menu_order' );
		$query->set( 'order', $menu_order_order );
	}
	
	//Used on homepage archives, post type archives, or custom WP_Query calls
	public function modify_menu_order_sql(  $sql_orderby, $query ) {
		if ( is_admin() || is_404() || is_feed() ) return $sql_orderby;
		
		if ( !apply_filters( 'metronet_reorder_allow_menu_order', true ) ) return $sql_orderby; //Return false to disable this completely
		
		//Get main plugin options
		$plugin_options = $this->get_plugin_options();
		if ( !isset( $plugin_options[ 'menu_order' ] ) || !is_array( $plugin_options[ 'menu_order' ] ) || empty( $plugin_options[ 'menu_order' ] ) ) return $sql_orderby;
		
		$menu_order = $plugin_options[ 'menu_order' ];
		
		$main_query = $query->query;
		
		//Get main post type - Try to get post_type first, if not, see if we're on the main page showing blog posts
		$post_type = false;
		if( isset( $main_query[ 'post_type' ] ) ) {
			//Typically set on post type archives or custom WP_Query or get_posts instances
			$post_type = $main_query[ 'post_type' ];	
		} elseif ( is_home() ) {
			$post_type = 'post';
		} 
		if ( !$post_type || is_array( $post_type ) ) return $sql_orderby;
		
		if ( !apply_filters( 'metronet_reorder_allow_menu_order_' . $post_type, true ) ) return $sql_orderby; //Return false to disable this for each individual post type as opposed to using a global filter above
		
		//See if we're modifying the menu_order
		$menu_order_orderby = isset( $menu_order[ $post_type ][ 'orderby' ] ) ? $menu_order[ $post_type ][ 'orderby' ] : 'none';
		$menu_order_order = isset( $menu_order[ $post_type ][ 'order' ] ) ? $menu_order[ $post_type ][ 'order' ] : 'DESC';
		
		//Return if post type is not be ordered
		if ( $menu_order_orderby === 'none' ) return $sql_orderby;
		
		//Return of $menu_order_order is invalid
		if ( $menu_order_order !== 'ASC' && $menu_order_order !== 'DESC' ) return $sql_orderby;
		
		//Overwrite the orderby clause
		global $wpdb;
		$sql_orderby = sprintf( '%s.menu_order %s', $wpdb->posts, $menu_order_order ); //for devs: no sanitization required as tablename is from $wpdb and $menu_order_order is set to match only ASC or DESC.

		//Return 
		return $sql_orderby;		
		
	}
	
	/**
	 * Output options page HTML.
	 *
	 * Output option page HTML and fields/sections.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see add_admin_menu
	 *
	 */
	public function options_page() {
	?>
	    <div class="wrap">
	        <h2><?php echo esc_html( _x( 'Reorder Posts', 'Plugin Name - Settings Page Title', 'metronet-reorder-posts' ) ); ?></h2>
	        <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="POST">
	            <?php settings_fields( 'metronet-reorder-posts' ); ?>
	            <?php do_settings_sections( 'metronet-reorder-posts' ); ?>
	            <?php submit_button(); ?>
	        </form>
	    </div>
    <?php
	}
	
	/**
	 * Sanitize options before they are saved.
	 *
	 * Sanitize and prepare error messages when saving options.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @see init_admin_settings
	 *
	 * @param array $input {
	 		@type array $post_types
	 		@type array $menu_order 
	 }
	 * @return array Sanitized array of options
	 */
	public function sanitization( $input = array() ) {
		//Get post type options
		$post_types = $input[ 'post_types' ];
		if ( !empty( $post_types ) ) {
			foreach( $post_types as $post_type_name => &$value ) {
				if ( $value !== 'on' ) {
					$value == 'off';	
				}
			}	
			$input[ 'post_types' ] = $post_types;
		}
		
		//Get menu order options
		$menu_order = $input[ 'menu_order' ];
		if ( !empty( $menu_order ) ) {
			foreach( $post_types as $post_type_name => &$values ) {
				$orderby = isset( $menu_order[ $post_type_name ][ 'orderby' ] ) ? $menu_order[ $post_type_name ] : 'none';
				if ( $orderby !== 'menu_order' ) {
					$menu_order[ $post_type_name ][ 'orderby' ] == 'none';
				}
				
				$order = isset( $menu_order[ $post_type_name ][ 'order' ] ) ? $menu_order[ $post_type_name ] : 'DESC';
				if ( $orderby !== 'ASC' ) {
					$menu_order[ $post_type_name ][ 'order' ] == 'DESC';	
				}
			}
			$input[ 'menu_order' ] = $menu_order;
			
		}
		return $input;
	}
	
} //end class Reorder_Admin

add_action( 'init', 'mn_reorder_admin_instantiate', 15 );
function mn_reorder_admin_instantiate() {
	MN_Reorder_Admin::get_instance();
}