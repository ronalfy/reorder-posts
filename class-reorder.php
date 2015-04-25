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
 * Based on work by Scott Basgaard and Ronald Huereca
 * 
 * To use this class, simply instantiate it using an argument to set the post type as follows:
 * new MN_Reorder( array( 'post_type' => 'post', 'order'=> 'ASC' ) );
 * 
 * @copyright Copyright (c), Metronet
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryan@metronet.no>
 * @since 1.0
 */
final class MN_Reorder {

	/**
	 * @var $post_type 
	 * @desc Post type to be reordered
	 * @access private
	 */
	private $post_type;
	
	/**
	 * @var $posts_per_page 
	 * @desc How many posts to show
	 * @access private
	 */
	private $posts_per_page;
	
	/**
	 * @var $offset 
	 * @desc How many posts to offset by
	 * @access private
	 */
	private $offset;

	/**
	 * @var $heading 
	 * @desc Admin page heading
	 * @access private
	 */
	private $heading;

	/**
	 * @var $initial 
	 * @desc HTML outputted at end of admin page
	 * @access private
	 */
	private $initial;

	/**
	 * @var $final 
	 * @desc HTML outputted at end of admin page
	 * @access private
	 */
	private $final;

	/**
	 * @var $post_statush
	 * @desc The post status of posts to be reordered
	 * @access private
	 */
	private $post_status;

	/**
	 * @var $menu_label 
	 * @desc Admin page menu label
	 * @access private
	 */
	private $menu_label;
	
	/**
	 * @var $order 
	 * @desc ASC or DESC
	 * @access private
	 */
	private $order;
	
	/**
	 * @var $reorder_page 
	 * @desc Where the reorder interface is being added
	 * @access private
	 */
	private $reorder_page = '';
	
	/**
	 * Get method for post status
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @returns string $post_status Post Status of Posts
	 */
	public function get_post_status() {
		return $this->post_status;	
	}
	
	/**
	 * Get method for post order
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @returns string $order Order of posts (ASC or DESC)
	 */
	public function get_post_order() {
		return $this->order;	
	}
	
	/**
	 * Get method for posts per page
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @returns int $posts_per_page How many posts to display
	 */
	public function get_posts_per_page() {
		return $this->posts_per_page;	
	}
	
	/**
	 * Get method for post offset used in pagination
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @returns int $offset Offset of posts
	 */
	public function get_offset() {
		return $this->offset;	
	}
	
	/**
	 * Class constructor
	 * 
	 * Sets definitions
	 * Adds methods to appropriate hooks
	 * 
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @param array $args    If not set, then uses $defaults instead
	 */
	public function __construct( $args = array() ) {

		// Parse arguments
		$defaults = array(
			'post_type'   => 'post',                     // Setting the post type to be reordered
			'order'       => 'ASC',                      // Setting the order of the posts
			'heading'     => __( 'Reorder', 'metronet-reorder-posts' ), // Default text for heading
			'initial'     => '',                         // Initial text displayed before sorting code
			'final'       => '',                         // Initial text displayed before sorting code
			'post_status' => 'publish',                  // Post status of posts to be reordered
			'menu_label'  => __( 'Reorder', 'metronet-reorder-posts' ), //Menu label for the post type
			'offset' => 48,
			'posts_per_page' => 50
		);
		$args = wp_parse_args( $args, $defaults );

		// Set variables
		$this->post_type   = $args[ 'post_type' ];
		$this->order       = $args[ 'order' ];;
		$this->heading     = $args[ 'heading' ];
		$this->initial     = $args[ 'initial' ];
		$this->final       = $args[ 'final' ];
		$this->menu_label  = $args[ 'menu_label' ];
		$this->post_status = $args[ 'post_status' ];
		
		//Get offset and posts_per_page
		$this->posts_per_page = absint( $args[ 'posts_per_page' ] ); //todo - filterable?
		$this->offset = absint( $args[ 'offset' ] ); //todo - filterable?
		if ( $this->offset > $this->posts_per_page ) {
			$this->offset = $this->posts_per_page;	
		}
		
		// Add actions
		add_action( 'wp_ajax_post_sort',   array( $this, 'ajax_save_post_order'  ) );
		add_action( 'admin_menu',          array( $this, 'enable_post_sort' ), 10, 'page' );
		add_action( 'metronet_reorder_posts_interface_' . $this->post_type, array( $this, 'output_interface' ) );
	}
	/**
	 * Adjust the found posts for the offset
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @returns int $found_posts Number of posts
	 */
	public function adjust_offset_pagination( $found_posts, $query ) {
		//This sometimes will have a bug of showing an extra page, but it doesn't break anything, so leaving it for now.
		if( $found_posts > $this->posts_per_page ) {
			$num_pages = $found_posts / $this->offset;
			$found_posts = (string)round( $num_pages * $this->posts_per_page );
		}
		return $found_posts;
	}

	/**
	 * Saving the post oder for later use
	 *
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since Reorder 1.0
	 * @access public
	 * @global object $wpdb  The primary global database object used internally by WordPress
	 */
	public function ajax_save_post_order() {
		global $wpdb;
		
		if ( !current_user_can( 'edit_pages' ) ) die( '' );
		// Verify nonce value, for security purposes
		if ( !wp_verify_nonce( $_POST['nonce'], 'sortnonce' ) ) die( '' );
		
		//Get Ajax Vars
		$post_parent = isset( $_POST[ 'post_parent' ] ) ? absint( $_POST[ 'post_parent' ] ) : 0;
		$menu_order_start = isset( $_POST[ 'start' ] ) ? absint( $_POST[ 'start' ] ) : 0;
		$post_id = isset( $_POST[ 'post_id' ] ) ? absint( $_POST[ 'post_id' ] ) : 0;
		$post_menu_order = isset( $_POST[ 'menu_order' ] ) ? absint( $_POST[ 'menu_order' ] ) : 0;
		$posts_to_exclude = isset( $_POST[ 'excluded' ] ) ? array_filter( $_POST[ 'excluded' ], 'absint' ) : array();
		$post_type = isset( $_POST[ 'post_type' ] ) ? sanitize_text_field( $_POST[ 'post_type' ] ) : false;
		
		if ( !$post_type ) die( '' );
		
		//Performance
		remove_action( 'pre_post_update', 'wp_save_post_revision' );
		
		//Build Initial Return 
		$return = array();
		$return[ 'more_posts' ] = false;
		$return[ 'action' ] = 'post_sort';
		$return[ 'post_parent' ] = $post_parent;
		$return[ 'nonce' ] = sanitize_text_field( $_POST[ 'nonce' ] );
		$return[ 'post_id'] = $post_id;
		$return[ 'menu_order' ] = $post_menu_order;
		$return[ 'post_type' ] = $post_type;
		
		//Update post if passed - Should run only on beginning of first iteration
		if( $post_id > 0 && !isset( $_POST[ 'more_posts' ] ) ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => $post_menu_order, 'post_parent' => $post_parent ), array( 'ID' => $post_id )
			);
			clean_post_cache( $post_id );
			$posts_to_exclude[] = $post_id;
		}
		
		//Build Query
		$query_args = array(
			'post_type' => $post_type,
			'orderby' => 'menu_order title',
			'order' => $this->order,
			'posts_per_page' => 50,
			'suppress_filters' => true,
			'ignore_sticky_posts' => true,
			'post_status' => $this->post_status,
			'post_parent' => $post_parent,
			'post__not_in' => $posts_to_exclude,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false
		);
		$posts = new WP_Query( $query_args );
		
		$start = $menu_order_start;
		if ( $posts->have_posts() ) {
			foreach( $posts->posts as $post ) {
				//Increment start if matches menu_order and there is a post to change
				if ( $start == $post_menu_order && $post_id > 0 ) {
					$start++;	
				}
				
				if ( $post_id != $post->ID ) {
					//Update post and counts
					$wpdb->update(
						$wpdb->posts,
						array( 'menu_order' => $start, 'post_parent' => $post_parent ),
						array( 'ID'         => $post->ID )
					);
					clean_post_cache( $post );
				}
				$posts_to_exclude[] = $post->ID;
				$start++;
			}
			$return[ 'excluded' ] = $posts_to_exclude;
			$return[ 'start' ] = $start;
			if ( $posts->max_num_pages > 1 ) {
				$return[ 'more_posts' ] = true;	
			} else {
				$return[ 'more_posts' ] = false;	
			}
			die( json_encode( $return ) );
		} else {
			die( json_encode( $return ) );
		}
	} //end ajax_save_post_order

	/**
	 * Print styles to admin page
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global string $pagenow Used internally by WordPress to designate what the current page is in the admin panel
	 */
	public function print_styles() {
		wp_enqueue_style( 'reorderpages_style', REORDER_URL . '/css/admin.css' );
	}

	/**
	 * Print scripts to admin page
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global string $pagenow Used internally by WordPress to designate what the current page is in the admin panel
	 */
	public function print_scripts() {
		wp_register_script( 'reorder_nested', REORDER_URL . '/scripts/jquery.mjs.nestedSortable.js', array( 'jquery-ui-sortable' ), '1.3.5', true );
		wp_enqueue_script( 'reorder_posts', REORDER_URL . '/scripts/sort.js', array( 'reorder_nested' ) );
		wp_localize_script( 'reorder_posts', 'reorder_posts', array(
			'action' => 'post_sort',
			'expand' => esc_js( __( 'Expand', 'metronet-reorder-posts' ) ),
			'collapse' => esc_js( __( 'Collapse', 'metronet-reorder-posts' ) ),
			'sortnonce' =>  wp_create_nonce( 'sortnonce' ),
			'hierarchical' => is_post_type_hierarchical( $this->post_type ) ? 'true' : 'false',
		) );
	}

	/**
	 * Add submenu
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 */
	public function enable_post_sort() {
		$post_type = $this->post_type;
		if ( 'post' != $post_type ) {
			$menu_location = apply_filters( 'metronet_reorder_menu_location_' . $post_type, 'edit.php?post_type=' . $post_type, $post_type );
			$hook = add_submenu_page(
				$menu_location, // Parent slug
				$this->heading,                     // Page title (unneeded since specified directly)
				apply_filters( 'metronet_reorder_menu_label_' . $post_type, $this->menu_label , $post_type ),                  // Menu title
				'edit_pages',                       // Capability
				'reorder-' . $post_type,            // Menu slug
				array( $this, 'sort_posts' )        // Callback function
			);
			$this->reorder_page = add_query_arg( array( 'page' => 'reorder-' . $post_type ), admin_url( $menu_location ) );
		}
		else {
			$hook = add_posts_page(
				$this->heading,                     // Page title (unneeded since specified directly)
				apply_filters( 'metronet_reorder_menu_label_' . $post_type, $this->menu_label ),                  // Menu title
				'edit_pages',                       // Capability
				'reorder-posts',                    // Menu slug
				array( $this, 'sort_posts' )        // Callback function
			);
			$this->reorder_page = add_query_arg( array( 'page' => 'reorder-posts' ), admin_url( 'edit.php' ) );
		}
		do_action( 'metronet_reorder_posts_add_menu_' . $post_type, $hook ); //Allow other plugin authors to add scripts/styles to our menu items
		do_action( 'metronet_reorder_menu_url_' . $post_type, $this->reorder_page );
		add_action( 'admin_print_styles-' . $hook,  array( $this, 'print_styles'     ) );
		add_action( 'admin_print_scripts-' . $hook, array( $this, 'print_scripts'    ) );
	}
	
	/**
	 * Output the main Reorder Interface
	 *
	 * @author Ryan Hellyer <ryan@metronet.no> and Ronald Huereca <ronalfy@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @global string $post_type
	 */
	public function output_interface() {
		echo '<br />';
		$post_count_obj = wp_count_posts( $this->post_type );
		$post_count = isset( $post_count_obj->{$this->post_status} )  ?absint( $post_count_obj->{$this->post_status} ) : absint( $post_count_obj[ 'publish' ] );
		if ( $post_count >= 1000 ) {
			printf( '<div class="error"><p>%s</p></div>', sprintf( __( 'There are over %s posts found.  We do not recommend you sort these posts for performance reasons.', 'metronet_reorder_posts' ), number_format( $post_count ) ) );
		}
		?>
		<div id="reorder-error"></div>
		<div><img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" id="loading-animation" /></div>
		<?php echo esc_html( $this->initial ); ?>
		<?php
		//Output non hierarchical posts
		$page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 0;
		if ( $page == 0 ) {
			$offset = 0;	
		} elseif ( $page > 1 ) {
			$offset = $this->offset * ( $page - 1 );
		}
		printf( '<input type="hidden" id="reorder-offset" value="%s" />', absint( $offset ) );
		add_filter( 'found_posts', array( $this, 'adjust_offset_pagination' ), 10, 2 );
		$post_query = new WP_Query(
			array(
				'post_type'      => $this->post_type,
				'posts_per_page' => $this->posts_per_page,
				'orderby'        => 'menu_order title',
				'order'          => $this->order,
				'post_status'    => $this->post_status,
				'post_parent' => 0,
				'offset' => $offset
			)
		);
		remove_filter( 'found_posts', array( $this, 'adjust_offset_pagination' ), 10, 2 );
		if( $post_query->have_posts() ) {
			echo '<ul id="post-list">';
			while( $post_query->have_posts() ) {
				global $post;
				$post_query->the_post();
				$this->output_row( $post );	
			}
			echo '</ul><!-- #post-list -->';
			
			//Show pagination links
			if( $post_query->max_num_pages > 1 ) {
				echo '<div id="reorder-pagination">';
				$current_url = add_query_arg( array( 'paged' => '%#%' ) );
				$pagination_args = array(
					'base' => $current_url,
					'total' => $post_query->max_num_pages,
					'current' => ( $page == 0 ) ? 1 : $page
				);
				echo paginate_links( $pagination_args );
				echo '</div>';
			}
		} else {
			echo sprintf( '<h3>%s</h3>	', esc_html__( 'There is nothing to sort at this time', 'metronet-reorder-posts' ) );	
		}
		echo esc_html( $this->final ); 	
	}
	/**
	* Post Row Output
	*
	* @author Ronald Huereca <ronalfy@gmail.com>
	* @since Reorder 2.1.0
	* @access private
	* @param stdclass $post object to post
	*/
	private function output_row( $post ) {
		global $post;
		setup_postdata( $post );
		?>
		<li id="list_<?php the_id(); ?>" data-id="<?php the_id(); ?>" data-menu-order="<?php echo absint( $post->menu_order ); ?>" data-parent="<?php echo absint( $post->post_parent ); ?>" data-post-type="<?php echo esc_attr( $post->post_type ); ?>">
			<?php
			//Get the children
			$args = array(
				'post_type' => $this->post_type,
				'post_status' => $this->post_status,
				'posts_per_page' => 100, /*hope there's never more than 100 children*/
				'post_parent' => get_the_ID(),
				'orderby'        => 'menu_order',
				'order'          => $this->order,
			);
			$children = new WP_Query( $args );
			//Output parent title
			if( $children->have_posts() ) {
				?>
				<div><?php the_title(); ?><?php echo ( defined( 'REORDER_DEBUG' ) && REORDER_DEBUG == true ) ? ' - Menu Order:' . absint( $post->menu_order ) : ''; ?><a href='#' style="float: right"><?php esc_html_e( 'Expand', 'metronet-reorder-posts' ); ?></a></div>
				<?php
			} else {
				?>
				<div><?php the_title(); ?><?php echo ( defined( 'REORDER_DEBUG' ) && REORDER_DEBUG == true ) ? ' - Menu Order:' . absint( $post->menu_order ) : ''; ?></div>
				<?php
			}
			
			if( $children->have_posts() ) {
				echo '<ul class="children">';
				while( $children->have_posts() ) {
					global $post;
					$children->the_post();
					$this->output_row( $post );
				}
				echo '</ul>';
			}	
			?>
		</li>
		<?php
	} //end output_row
	
	/**
	 * Initial HTML output
	 *
	 * @author Ryan Hellyer <ryan@metronet.no> and Ronald Huereca <ronalfy@gmail.com>
	 * @since Reorder 2.1.0
	 * @access public
	 * @global string $post_type
	 */
	public function sort_posts() {
		//Dev note - Settings API not used here because there are no options to save.
		?>
		<div class="wrap">
			<h2>
				<?php echo esc_html( $this->heading ); ?>
			</h2>
			<?php
			$tabs = 
			array(
				array(
					'url' => $this->reorder_page /* URL to the tab */,
					'label' => $this->heading,
					'get' => 'main' /*$_GET variable*/,
					'action' => 'metronet_reorder_posts_interface_' . $this->post_type /* action variable in do_action */
				)
			);
			$tabs = apply_filters( 'metronet_reorder_posts_tabs_' . $this->post_type, (array)$tabs );
			$tabs_count = count( $tabs );
			
			//Output tabs
			$tab_html = '';
			if ( $tabs && !empty( $tabs ) )  {
				$tab_html .=  '<h2 class="nav-tab-wrapper">';
				$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'main';
				$do_action = false;
				foreach( $tabs as $tab ) {
					$classes = array( 'nav-tab' );
					$tab_get = isset( $tab[ 'get' ] ) ? $tab[ 'get' ] : '';
					if ( $active_tab == $tab_get ) {
						$classes[] = 'nav-tab-active';
						$do_action = isset( $tab[ 'action' ] ) ? $tab[ 'action' ] : false;
					}
					$tab_url = isset( $tab[ 'url' ] ) ? $tab[ 'url' ] : '';
					$tab_label = isset( $tab[ 'label' ] ) ? $tab[ 'label' ] : ''; 
					$tab_html .= sprintf( '<a href="%s" class="%s">%s</a>', esc_url( $tab_url ), esc_attr( implode( ' ', $classes ) ), esc_html( $tab[ 'label' ] ) );
				}
				$tab_html .= '</h2>';
				if ( $tabs_count > 1 ) {
					echo $tab_html;	
				}
				if ( $do_action ) {
					do_action( $do_action );	
				}
			}	
			?>
		</div><!-- .wrap -->
		<?php
	} //end sort_posts

}
