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
class MN_Reorder {

	/**
	 * @var $post_type 
	 * @desc Post type to be reordered
	 * @access protected
	 */
	protected $post_type;
	
	/**
	 * @var $posts_per_page 
	 * @desc How many posts to show
	 * @access protected
	 */
	protected $posts_per_page;
	
	/**
	 * @var $offset 
	 * @desc How many posts to offset by
	 * @access protected
	 */
	protected $offset;

	/**
	 * @var $direction 
	 * @desc ASC or DESC
	 * @access protected
	 */
	protected $direction;

	/**
	 * @var $heading 
	 * @desc Admin page heading
	 * @access protected
	 */
	protected $heading;

	/**
	 * @var $initial 
	 * @desc HTML outputted at end of admin page
	 * @access protected
	 */
	protected $initial;

	/**
	 * @var $final 
	 * @desc HTML outputted at end of admin page
	 * @access protected
	 */
	protected $final;

	/**
	 * @var $post_statush
	 * @desc The post status of posts to be reordered
	 * @access protected
	 */
	protected $post_status;

	/**
	 * @var $menu_label 
	 * @desc Admin page menu label
	 * @access protected
	 */
	protected $menu_label;

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
			$num_pages = round($found_posts / $this->offset);
			$found_posts = (string)round( $num_pages * $this->posts_per_page );
		}
		return $found_posts;
	}

	/**
	 * Saving the post oder for later use
	 *
	 * @author Ryan Hellyer <ryan@metronet.no> and Ronald Huereca <ronald@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global object $wpdb  The primary global database object used internally by WordPress
	 */
	public function ajax_save_post_order() {
		global $wpdb;

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
		
		//Update post if passed
		if( $post_id > 0 && !isset( $_POST[ 'more_posts' ] ) ) {
			wp_update_post( array( 'ID' => $post_id, 'post_parent' => $post_parent, 'menu_order' => $post_menu_order ) );	
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
			'post__not_in' => $posts_to_exclude
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
					wp_update_post( array( 'ID' => $post->ID, 'menu_order' => $start, 'post_parent' => $post_parent ) );
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
				'edit_posts',                       // Capability
				'reorder-' . $post_type,            // Menu slug
				array( $this, 'sort_posts' )        // Callback function
			);
		}
		else {
			$hook = add_posts_page(
				$this->heading,                     // Page title (unneeded since specified directly)
				apply_filters( 'metronet_reorder_menu_label_' . $post_type, $this->menu_label ),                  // Menu title
				'edit_posts',                       // Capability
				'reorder-posts',                    // Menu slug
				array( $this, 'sort_posts' )        // Callback function
			);
		}
		add_action( 'admin_print_styles-' . $hook,  array( $this, 'print_styles'     ) );
		add_action( 'admin_print_scripts-' . $hook, array( $this, 'print_scripts'    ) );
	}
	
	/**
	* Post Row Output
	*
	* @author Ronald Huereca <ronald@metronet.no>
	* @since Reorder 2.0.2
	* @access private
	* @param stdclass $post object to post
	*/
	protected function output_row( $post ) {
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
				<div><?php the_title(); ?><?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG == true ) ? ' - Menu Order:' . absint( $post->menu_order ) : ''; ?><a href='#' style="float: right"><?php esc_html_e( 'Expand', 'metronet-reorder-posts' ); ?></a></div>
				<?php
			} else {
				?>
				<div><?php the_title(); ?><?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG == true ) ? ' - Menu Order:' . absint( $post->menu_order ) : ''; ?></div>
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
	 * HTML output
	 *
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @since Reorder 1.0
	 * @access public
	 * @global string $post_type
	 */
	public function sort_posts() {
		$has_posts = false;
		?>
		</style>
		<div class="wrap">
			<h2>
				<?php echo esc_html( $this->heading ); ?>
				<img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" id="loading-animation" />
			</h2>
			<?php
				$post_count_obj = wp_count_posts( $this->post_type );
				$post_count = isset( $post_count_obj->{$this->post_status} )  ?absint( $post_count_obj->{$this->post_status} ) : absint( $post_count_obj[ 'publish' ] );
				if ( $post_count >= 1000 ) {
					printf( '<div class="error"><p>%s</p></div>', sprintf( __( 'There are over %s posts found.  We do not recommend you sort these posts for performance reasons.', 'metronet_reorder_posts' ), number_format( $post_count ) ) );
				}
			?>
			<div id="reorder-error"></div>
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
				global $wp;
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
		/*die( '<pre>' . print_r( $post_query, true ) );
		if ( is_post_type_hierarchical( $this->post_type ) ) {
			$pages = get_pages( array( 
				'sort_column' => 'menu_order',
				'post_type' => $this->post_type,
			) );
			if( $pages ) {
				$has_posts = true;
				echo '<ul id="post-list">';
				//Get hiearchy of children/parents
				$top_level_pages = array();
				$children_pages = array();
				foreach( $pages as $page ) {
					if ( $page->post_parent == 0 ) {
						//Parent page
						$top_level_pages[] = $page;
					} else {
						$children_pages[ $page->post_parent ][] = $page;
					}
				} //end foreach
							 
				foreach( $top_level_pages as $page ) {
					$page_id = $page->ID;
					if ( isset( $children_pages[ $page_id ] ) && !empty( $children_pages[ $page_id ] ) ) {
						//If page has children, output page and its children
						$this->output_row_hierarchical( $page, $children_pages[ $page_id ], $children_pages );
					} else {
						$this->output_row( $page );
					}
				}
				echo '</ul>';	 
			}		 
		} else {
			//Output non hierarchical posts
			$post_query = new WP_Query(
				array(
					'post_type'      => $this->post_type,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => $this->order,
					'post_status'    => $this->post_status,
				)
			);
			$posts = $post_query->get_posts();
			if( $posts && !empty( $posts ) ) {
				$has_posts = true;
				echo '<ul id="post-list">';
				foreach( $posts as $post ) {
					$this->output_row( $post );
				} //end foreach
				echo '</ul>';
			}
		}
		if ( false === $has_posts ) {
			echo sprintf( '<h3>%s</h3>	', esc_html__( 'There is nothing to sort at this time', 'metronet-reorder-posts' ) );
		}*/
		echo esc_html( $this->final ); 
		?>
		</div><!-- .wrap -->
		<?php
	} //end sort_posts

}
