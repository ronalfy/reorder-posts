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
class Reorder_Admin {
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
		if ( !apply_filters( 'metronet_reorder_post_show_admin', true ) ) return;
		
		//Initialize actions
		add_action( 'pre_get_posts', array( $this, 'modify_menu_order' ) );
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
			printf( '<div><input type="hidden" name="metronet-reorder-posts[post_types][%1$s]" value="off" /> <input type="checkbox" name="metronet-reorder-posts[post_types][%1$s]" id="post_type_%1$s" value="on" %2$s /><label for="post_type_%1$s">&nbsp;%3$s</label></div>', esc_attr( $post_type_name ), $checked, $post_type_label );
		}
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
		
		add_settings_section( 'mn-reorder-menu-order', _x( 'Advanced', 'plugin settings heading' , 'metronet-reorder-posts' ), '__return_empty_string', 'metronet-reorder-posts' );
		
		add_settings_field( 'mn-post-types', __( 'Post Types', 'metronet-reorder-posts' ), array( $this, 'add_settings_field_post_types' ), 'metronet-reorder-posts', 'mn-reorder-post-types', array( 'desc' => __( 'Select the post types you would like this plugin enabled for.', 'metronet-reorder-posts' ) ) );
		
		//add_settings_field( 'mn-menu-order', __( 'Select the menu order arguments.', 'metronet-reorder-posts' ), array( $this, 'add_settings_field_menu_order' ), 'metronet-reorder-posts', 'mn-reorder-menu-order', array( 'desc' => __( 'Select the menu order for the post types.', 'metronet-reorder-posts' ) ) );
		
	}
	
	public function modify_menu_order( $query ) {
		
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
	 		@type string $js_content Content to be parsed via Javascript.  Default 'entry-content'.
	
	 		@type string $twitter Twitter username.  Default ''.
	 		@type bool $show_twitter Whether to show twitter share option.  Default true.
	 		@type bool $show_facebook Whether to show facebook share option.  Default true
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
				
		return $input;
		$output = get_option( 'highlight-and-share' );
		
		//Check if settings are being initialized for the first time
		if ( false === $output ) {
			//No settings have been saved yet and we're being supplied with defaults
			foreach( $input as $key => &$value ) {
				if ( is_bool( $value ) ) continue;
				$value = sanitize_text_field( $value );
			}	
			return $input;
		}
		//Settings are being saved.  Update.
		foreach( $input as $key => $value ) {
			if ( 'twitter' == $key ) {
				$twitter_username = $input[ $key ];
				if ( !preg_match( '/^[a-zA-Z0-9_]{1,15}$/', $twitter_username ) ) {
					add_settings_error( 'highlight-and-share', 'invalid_twitter', _x( 'You have entered an incorrect Twitter username', 'Twitter username error', 'highlight-and-share' ) );
				} else {
					$output[ $key ] = sanitize_text_field( $value );
				}
			} elseif( 'js_content' == $key ) {
				$js_content = trim( $value );				
				if( empty( $js_content ) || preg_match( '/[-_0-9a-zA-Z]+(,[-_0-9a-zA-Z]+)*$/', $js_content ) ) {
					$output[ $key ] = sanitize_text_field( $js_content );
				} else {
					add_settings_error( 'highlight-and-share', 'invalid_twitter', _x( 'You must enter valid comma-separated values for the content.', 'Invalid comma-separated values', 'highlight-and-share' ) );
				}
			} elseif( ( 'show_twitter' || 'show_facebook' || 'enable_content' || 'enable_excerpt' ) == $key ) {
				if ( $input[ $key ] == 'on' ) {
					$output[ $key ] = true;	
				} else {
					$output[ $key ] = false;
				}
			}
		}
		return $output;
	}
	
} //end class Reorder_Admin

add_action( 'init', 'mn_reorder_admin_instantiate', 15 );
function mn_reorder_admin_instantiate() {
	Reorder_Admin::get_instance();
}
