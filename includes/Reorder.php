<?php
/**
 * Reorder posts
 *
 * @package    ReorderPosts
 */

namespace MediaRon\ReorderPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reorder posts
 *
 * @package    ReorderPosts
 */
class Reorder {

	/**
	 * The post type to be reordered
	 *
	 * @var string
	 */
	private string $post_type;

	/**
	 * The posts per page.
	 *
	 * @var int
	 */
	private int $posts_per_page;

	/**
	 * How many posts to offset by
	 *
	 * @var int
	 */
	private int $offset;

	/**
	 * Admin page heading
	 *
	 * @var string
	 */
	private string $heading;

	/**
	 * HTML outputted at start of admin page
	 *
	 * @var string
	 */
	private string $initial;

	/**
	 * HTML outputted at end of admin page
	 *
	 * @var string
	 */
	private string $final;

	/**
	 * Post status of posts to be reordered
	 *
	 * @var array
	 */
	private array $post_status;

	/**
	 * Admin page menu label
	 *
	 * @var string
	 */
	private string $menu_label;

	/**
	 * Order of posts (ASC or DESC)
	 *
	 * @var string
	 */
	private string $order;

	/**
	 * Where the reorder interface is being added
	 *
	 * @var string
	 */
	private string $reorder_page = '';

	/**
	 * Get method for post status
	 *
	 * @return array
	 */
	public function get_post_status(): array {
		return $this->post_status;
	}

	/**
	 * Get method for post order
	 *
	 * @return string
	 */
	public function get_post_order(): string {
		return $this->order;
	}

	/**
	 * Get method for posts per page
	 *
	 * @return int
	 */
	public function get_posts_per_page(): int {
		return $this->posts_per_page;
	}

	/**
	 * Get method for post offset used in pagination
	 *
	 * @return int
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
	 * @param array $args    If not set, then uses $defaults instead.
	 */
	public function __construct( array $args = array() ) {

		// Get posts per page.
		$user_id        = get_current_user_id();
		$posts_per_page = User::get_preferences( $user_id, $args['post_type'] )['posts_per_page'];
		if ( ! is_numeric( $posts_per_page ) ) {
			$posts_per_page = 50;
		}

		// Make sure post_status is an array.
		if ( ! is_array( $args['post_status'] ) ) {
			$args['post_status'] = array( $args['post_status'] );
		}

		// Parse arguments.
		$defaults = array(
			'post_type'      => 'post',
			'order'          => 'ASC',
			'heading'        => __( 'Reorder', 'metronet-reorder-posts' ),
			'initial'        => '',
			'final'          => '',
			'post_status'    => array( 'publish' ),
			'menu_label'     => esc_html__( 'Reorder', 'metronet-reorder-posts' ),
			'posts_per_page' => $posts_per_page,
		);
		$args     = wp_parse_args( $args, $defaults );

		// Set variables.
		$this->post_type = $args['post_type'];
		$this->order     = $args['order'];

		$this->heading     = $args['heading'];
		$this->initial     = $args['initial'];
		$this->final       = $args['final'];
		$this->menu_label  = $args['menu_label'];
		$this->post_status = $args['post_status'];

		// Get offset and posts_per_page.
		$this->posts_per_page = absint( $args['posts_per_page'] );

		add_action( 'wp_ajax_reorder_sort_' . $this->post_type, array( $this, 'ajax_save_post_order' ) );
		add_action( 'admin_menu', array( $this, 'enable_post_sort' ), 10, 'page' );
		add_action( 'metronet_reorder_posts_interface_' . $this->post_type, array( $this, 'output_interface' ) );
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

		if ( ! current_user_can( 'edit_pages' ) ) {
			die( '' );
		}
		// Verify nonce value, for security purposes
		if ( ! wp_verify_nonce( $_POST['nonce'], 'sortnonce' ) ) {
			die( '' );
		}

		// Get Ajax Vars
		$post_parent      = isset( $_POST['post_parent'] ) ? absint( $_POST['post_parent'] ) : 0;
		$menu_order_start = isset( $_POST['start'] ) ? absint( $_POST['start'] ) : 0;
		$post_id          = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$post_menu_order  = isset( $_POST['menu_order'] ) ? absint( $_POST['menu_order'] ) : 0;
		$posts_to_exclude = isset( $_POST['excluded'] ) ? array_filter( $_POST['excluded'], 'absint' ) : array();
		$post_type        = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : false;

		if ( ! $post_type ) {
			die( '' );
		}

		// Performance
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		// Build Initial Return
		$return                = array();
		$return['more_posts']  = false;
		$return['action']      = 'post_sort';
		$return['post_parent'] = $post_parent;
		$return['nonce']       = sanitize_text_field( $_POST['nonce'] );
		$return['post_id']     = $post_id;
		$return['menu_order']  = $post_menu_order;
		$return['post_type']   = $post_type;

		// Update post if passed - Should run only on beginning of first iteration
		if ( $post_id > 0 && ! isset( $_POST['more_posts'] ) ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'menu_order'  => $post_menu_order,
					'post_parent' => $post_parent,
				),
				array( 'ID' => $post_id )
			);
			clean_post_cache( $post_id );
			$posts_to_exclude[] = $post_id;
		}

		// Build Query
		$query_args = array(
			'post_type'              => $post_type,
			'orderby'                => 'menu_order title',
			'order'                  => $this->order,
			'posts_per_page'         => 50,
			'suppress_filters'       => true,
			'ignore_sticky_posts'    => true,
			'post_status'            => $this->post_status,
			'post_parent'            => $post_parent,
			'post__not_in'           => $posts_to_exclude,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);
		$posts      = new WP_Query( $query_args );

		$start = $menu_order_start;
		if ( $posts->have_posts() ) {
			foreach ( $posts->posts as $post ) {
				// Increment start if matches menu_order and there is a post to change
				if ( $start == $post_menu_order && $post_id > 0 ) {
					++$start;
				}

				if ( $post_id != $post->ID ) {
					// Update post and counts
					$wpdb->update(
						$wpdb->posts,
						array(
							'menu_order'  => $start,
							'post_parent' => $post_parent,
						),
						array( 'ID' => $post->ID )
					);
					clean_post_cache( $post );
				}
				$posts_to_exclude[] = $post->ID;
				++$start;
			}
			$return['excluded'] = $posts_to_exclude;
			$return['start']    = $start;
			if ( $posts->max_num_pages > 1 ) {
				$return['more_posts'] = true;
			} else {
				$return['more_posts'] = false;
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
		wp_enqueue_style( 'reorderpages_style', REORDER_URL . '/css/admin.css', array(), '20160813' );
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

		$deps = require_once REORDER_DIR . '/dist/js/admin-reorder-posts.asset.php';
		wp_enqueue_script( 'dlx-reorder-posts', REORDER_URL . '/dist/js/admin-reorder-posts.js', $deps['dependencies'], $deps['version'], true );
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
		if ( 'post' !== $post_type ) {
			$menu_location  = apply_filters( 'metronet_reorder_menu_location_' . $post_type, 'edit.php?post_type=' . $post_type, $post_type );
			$post_type_args = get_post_type_object( $post_type );
			// IF show_ui is false, add it to a hidden parent menu.
			if ( ! $post_type_args->show_ui || false === $post_type_args->show_in_menu || false === $post_type_args->public ) {
				$menu_location = '';
				$hook          = add_submenu_page(
					'',
					apply_filters( 'metronet_reorder_menu_label_' . $post_type, esc_html__( 'Reorder', 'metronet-reorder-posts' ) . ' ' . esc_html( $post_type_args->label ), $post_type ),
					'',
					'edit_pages',
					'reorder-' . $post_type,
					array( $this, 'sort_posts' )
				);
			} else {
				$hook = add_submenu_page(
					$menu_location,
					$this->heading,
					apply_filters( 'metronet_reorder_menu_label_' . $post_type, $this->menu_label, $post_type ),
					'edit_pages',
					'reorder-' . $post_type,
					array( $this, 'sort_posts' )
				);
			}
			$this->reorder_page = add_query_arg( array( 'page' => 'reorder-' . $post_type ), admin_url( $menu_location ) );
		} else {
			$hook               = add_posts_page(
				$this->heading,
				apply_filters( 'metronet_reorder_menu_label_' . $post_type, $this->menu_label ),
				'edit_pages',
				'reorder-posts',
				array( $this, 'sort_posts' )
			);
			$this->reorder_page = add_query_arg( array( 'page' => 'reorder-posts' ), admin_url( 'edit.php' ) );
		}
		do_action( 'metronet_reorder_posts_add_menu_' . $post_type, $hook );
		do_action( 'metronet_reorder_menu_url_' . $post_type, $this->reorder_page );
		add_action( 'admin_print_styles-' . $hook, array( $this, 'print_styles' ) );
		add_action( 'admin_print_scripts-' . $hook, array( $this, 'print_scripts' ) );
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
		$post_count       = Functions::get_post_count( $this->post_type, $this->post_status );
		$user_preferences = User::get_preferences( get_current_user_id(), $this->post_type );
		if ( $post_count >= 1000 ) {
			printf( '<div class="error"><p>%s</p></div>', sprintf( __( 'There are over %s posts found.  We do not recommend you sort these posts for performance reasons.', 'metronet_reorder_posts' ), number_format( $post_count ) ) );
		}
		?>
		<div id="reorder-error"></div>
		<div><img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" id="loading-animation" /></div>
		<div id="reorder-posts-interface" data-posts-per-page="<?php echo esc_attr( $user_preferences['posts_per_page'] ); ?>" data-debug="false" data-post-type="<?php echo esc_attr( $this->post_type ); ?>" data-nonce="<?php echo esc_html( wp_create_nonce( 'reorder-sort-nonce-' . $this->post_type ) ); ?>" data-hierarchical="<?php echo esc_attr( is_post_type_hierarchical( $this->post_type ) ? 'true' : 'false' ); ?>" data-post-status="<?php echo esc_attr( implode( ',', $user_preferences['post_status'] ) ); ?>"></div>
		<?php echo esc_html( $this->initial ); ?>
		<?php
		echo esc_html( $this->final );
		$options = get_option( 'metronet-reorder-posts' );

		if ( ! isset( $options['show_query'] ) || 'on' === $options['show_query'] ) :
			printf( '<h3>%s</h3>', esc_html__( 'Reorder Posts Query', 'metronet-reorder-posts' ) );
			printf( '<p>%s</p>', esc_html__( 'You will need custom code to reorder posts.  Here are some example query arguments for getting your content.', 'metronet-reorder-posts' ) );
			$query = "
\$query = array(
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'post_status' => 'publish',
	'post_type' => '{$this->post_type}',
	'posts_per_page' => {$this->posts_per_page}
);
\$posts = get_posts( \$query );
if( ! empty( \$posts ) ) {
     echo '<ul>';
     foreach( \$posts as \$post ) {
          printf( '<li><a href=\"%s\">%s</a></li>', esc_url( get_permalink( \$post->ID ) ), esc_html( \$post->post_title ) );
    }
    echo '</ul>';
}
";
			printf( '<blockquote><pre><code>%s</code></pre></blockquote>', esc_html( print_r( $query, true ) ) );
		endif;
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
			// Get the children
			$args     = array(
				'post_type'      => $this->post_type,
				'post_status'    => $this->post_status,
				'posts_per_page' => 100, /*hope there's never more than 100 children*/
				'post_parent'    => get_the_ID(),
				'orderby'        => 'menu_order',
				'order'          => $this->order,
			);
			$children = new WP_Query( $args );
			// Output parent title
			if ( $children->have_posts() ) {
				?>
				<div class="row">
					<div class="expand row-action">
						<span class="dashicons dashicons-arrow-right"></span>
					</div><!-- .row-action -->
					<div class="row-content">
						<?php the_title(); ?><?php echo ( defined( 'REORDER_DEBUG' ) && REORDER_DEBUG == true ) ? ' - Menu Order:' . absint( $post->menu_order ) : ''; ?>
					</div><!-- .row-content -->
				</div><!-- .row -->
				<?php
			} else {
				?>
				<div class="row">
					<?php
					$is_hierarchical = true;
					if ( is_post_type_hierarchical( $post->post_type ) ) {
						?>
						<div class="row-action">
						</div><!-- .row-action -->
						<?php
					} else {
						$is_hierarchical = false;
					}
					?>
					<div class="row-content <?php echo $is_hierarchical ? '' : 'non-hierarchical'; ?>">
						<?php the_title(); ?><?php echo ( defined( 'REORDER_DEBUG' ) && REORDER_DEBUG == true ) ? ' - Menu Order:' . absint( $post->menu_order ) : ''; ?>
					</div><!-- .row-content -->
				</div><!-- .row -->
				<?php
			}

			if ( $children->have_posts() ) {
				echo '<ul class="children">';
				while ( $children->have_posts() ) {
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
		// Dev note - Settings API not used here because there are no options to save.
		?>
		<div class="wrap">
			<h2>
				<?php echo esc_html( $this->heading ); ?>
			</h2>
			<?php
			$tabs       =
			array(
				array(
					'url'    => $this->reorder_page, /* URL to the tab */
					'label'  => $this->heading,
					'get'    => 'main', /*$_GET variable*/
					'action' => 'metronet_reorder_posts_interface_' . $this->post_type, /* action variable in do_action */
				),
			);
			$tabs       = apply_filters( 'metronet_reorder_posts_tabs_' . $this->post_type, (array) $tabs );
			$tabs_count = count( $tabs );

			$tab_html = '';
			if ( $tabs && ! empty( $tabs ) ) {
				$tab_html  .= '<h2 class="nav-tab-wrapper">';
				$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'main';
				$do_action  = false;
				foreach ( $tabs as $tab ) {
					$classes = array( 'nav-tab' );
					$tab_get = isset( $tab['get'] ) ? $tab['get'] : '';
					if ( $active_tab == $tab_get ) {
						$classes[] = 'nav-tab-active';
						$do_action = isset( $tab['action'] ) ? $tab['action'] : false;
					}
					$tab_url   = isset( $tab['url'] ) ? $tab['url'] : '';
					$tab_label = isset( $tab['label'] ) ? $tab['label'] : '';
					$tab_html .= sprintf( '<a href="%s" class="%s">%s</a>', esc_url( $tab_url ), esc_attr( implode( ' ', $classes ) ), esc_html( $tab['label'] ) );
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
