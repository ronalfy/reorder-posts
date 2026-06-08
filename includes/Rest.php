<?php
/**
 * REST API
 *
 * @package    ReorderPosts
 */

namespace MediaRon\ReorderPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API
 *
 * @package    ReorderPosts
 */
class Rest {

	/**
	 * Initialize the REST routes
	 */
	public function init_routes() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Register the REST routes
	 */
	public function rest_api_init() {
		register_rest_route(
			'reorder-posts/v1',
			'/posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_others_posts' );
				},
				'args'                => array(
					'post_type'      => array(
						'type'     => 'string',
						'required' => true,
					),
					'post_status'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'posts_per_page' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'offset'         => array(
						'type'     => 'integer',
						'required' => true,
					),
					'order'          => array(
						'type'     => 'string',
						'required' => true,
					),
					'hierarchical'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'parent_id'      => array(
						'type'    => 'integer',
						'default' => null,
					),
				),
			)
		);
	}

	/**
	 * Normalize post_status from request (array or comma-separated string).
	 *
	 * @param mixed $post_status Raw post_status param.
	 *
	 * @return array
	 */
	private function normalize_post_status( $post_status ) {
		if ( is_string( $post_status ) ) {
			return array_filter( array_map( 'trim', explode( ',', $post_status ) ) );
		}
		if ( is_array( $post_status ) ) {
			return $post_status;
		}
		return array( 'publish' );
	}

	/**
	 * Filterable batch size for has_children lookups and query caps.
	 *
	 * @param string $post_type Post type slug.
	 * @param array  $context   Optional context (e.g. parent post IDs).
	 *
	 * @return int
	 */
	private function get_query_batch_limit( $post_type, $context = array() ) {
		/**
		 * Filterable batch size for has_children lookups and query caps.
		 *
		 * @param int    $limit     The limit.
		 * @param string $post_type Post type slug.
		 * @param array  $context   Optional context (e.g. parent post IDs).
		 *
		 * @return int
		 */
		$limit = (int) apply_filters( 'reorder_posts_query_batch_limit', 500, $post_type, $context );

		return max( 1, $limit );
	}

	/**
	 * Cap posts_per_page requests to a filterable maximum.
	 *
	 * @param int    $posts_per_page Requested page size.
	 * @param string $post_type      Post type slug.
	 *
	 * @return int
	 */
	private function cap_posts_per_page( $posts_per_page, $post_type ) {
		$max = $this->get_query_batch_limit( $post_type, array( 'cap' => 'posts_per_page' ) );

		return min( absint( $posts_per_page ), $max );
	}

	/**
	 * Get parent IDs (from a candidate list) that have at least one child post.
	 *
	 * Uses DISTINCT post_parent queries in filterable batches — no unbounded -1 queries.
	 *
	 * @param string $post_type   Post type.
	 * @param array  $post_status Post statuses.
	 * @param array  $post_ids    Parent post IDs.
	 *
	 * @return array<int, bool> Map of post ID => has_children.
	 */
	private function get_has_children_map( $post_type, $post_status, $post_ids ) {
		$post_ids = array_values( array_filter( array_map( 'absint', $post_ids ) ) );
		$map      = array_fill_keys( $post_ids, false );

		if ( empty( $post_ids ) ) {
			return $map;
		}

		$batch_limit = $this->get_query_batch_limit( $post_type, $post_ids );

		foreach ( array_chunk( $post_ids, $batch_limit ) as $parent_id_chunk ) {
			$parents_with_children = $this->query_parent_ids_with_children(
				$post_type,
				$post_status,
				$parent_id_chunk
			);

			foreach ( $parents_with_children as $parent_id ) {
				if ( isset( $map[ $parent_id ] ) ) {
					$map[ $parent_id ] = true;
				}
			}
		}

		return $map;
	}

	/**
	 * Query which parent IDs from a list have at least one direct child.
	 *
	 * @param string $post_type   Post type slug.
	 * @param array  $post_status Post statuses.
	 * @param array  $parent_ids  Parent post IDs to check.
	 *
	 * @return int[] Parent IDs that have children.
	 */
	private function query_parent_ids_with_children( $post_type, $post_status, $parent_ids ) {
		global $wpdb;

		$parent_ids  = array_values( array_filter( array_map( 'absint', $parent_ids ) ) );
		$post_status = array_values( array_filter( array_map( 'sanitize_key', (array) $post_status ) ) );

		if ( empty( $parent_ids ) || empty( $post_status ) ) {
			return array();
		}

		$parent_placeholders = implode( ',', array_fill( 0, count( $parent_ids ), '%d' ) );
		$status_placeholders = implode( ',', array_fill( 0, count( $post_status ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders assembled above.
		$sql = "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_parent IN ($parent_placeholders) AND post_type = %s AND post_status IN ($status_placeholders)";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared on next line.
		$prepared = $wpdb->prepare( $sql, array_merge( $parent_ids, array( $post_type ), $post_status ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL prepared above.
		$results = $wpdb->get_col( $prepared );

		return array_map( 'absint', array_filter( $results ) );
	}

	/**
	 * Build a consistent REST response payload.
	 *
	 * @param array $posts          Post rows.
	 * @param int   $found_posts      Total matching posts.
	 * @param int   $offset           Request offset.
	 * @param int   $posts_per_page   Posts per page.
	 *
	 * @return array
	 */
	private function build_response( $posts, $found_posts, $offset, $posts_per_page ) {
		$count = count( $posts );
		return array(
			'posts'      => $posts,
			'total'      => (int) $found_posts,
			'offset'     => $offset + $count,
			'more_posts' => ( $offset + $posts_per_page ) < (int) $found_posts,
		);
	}

	/**
	 * Get the posts
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response The response object.
	 */
	public function get_posts( \WP_REST_Request $request ) {
		$post_type      = $request->get_param( 'post_type' );
		$post_status    = $this->normalize_post_status( $request->get_param( 'post_status' ) );
		$posts_per_page = absint( $request->get_param( 'posts_per_page' ) );
		$offset         = absint( $request->get_param( 'offset' ) );
		$order          = $request->get_param( 'order' );
		$hierarchical   = filter_var( $request->get_param( 'hierarchical' ), FILTER_VALIDATE_BOOLEAN );
		$parent_id      = $request->get_param( 'parent_id' );

		if ( $posts_per_page < 1 ) {
			$posts_per_page = 50;
		}

		$posts_per_page = $this->cap_posts_per_page( $posts_per_page, $post_type );

		$query_args = array(
			'post_type'              => $post_type,
			'post_status'            => $post_status,
			'posts_per_page'         => $posts_per_page,
			'offset'                 => $offset,
			'order'                  => $order,
			'suppress_filters'       => true,
			'orderby'                => 'menu_order title',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( $hierarchical ) {
			$query_args['post_parent'] = null !== $parent_id ? absint( $parent_id ) : 0;
		}

		$posts = new \WP_Query( $query_args );

		if ( ! $posts->have_posts() ) {
			return rest_ensure_response(
				$this->build_response( array(), $posts->found_posts, $offset, $posts_per_page )
			);
		}

		$post_ids = wp_list_pluck( $posts->posts, 'ID' );
		$has_children_map = $this->get_has_children_map( $post_type, $post_status, $post_ids );

		$response = array();
		foreach ( $posts->posts as $post ) {
			$response[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'url'          => get_permalink( $post->ID ),
				'menu_order'   => $post->menu_order,
				'parent'       => $post->post_parent,
				'has_children' => ! empty( $has_children_map[ $post->ID ] ),
			);
		}

		return rest_ensure_response(
			$this->build_response( $response, $posts->found_posts, $offset, $posts_per_page )
		);
	} //end get_posts
}//end class
