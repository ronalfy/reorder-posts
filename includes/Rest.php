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
						'type'     => 'array',
						'required' => true,
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
				),
			)
		);
	}

	/**
	 * Get the posts
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response The response object.s
	 */
	public function get_posts( \WP_REST_Request $request ) {
		$post_type      = $request->get_param( 'post_type' );
		$post_status    = $request->get_param( 'post_status' );
		$posts_per_page = absint( $request->get_param( 'posts_per_page' ) );
		$offset         = absint( $request->get_param( 'offset' ) );
		$order          = $request->get_param( 'order' );
		$posts          = new \WP_Query(
			array(
				'post_type'        => $post_type,
				'post_status'      => $post_status,
				'posts_per_page'   => $posts_per_page,
				'offset'           => $offset,
				'order'            => $order,
				'suppress_filters' => true,
			)
		);
		if ( ! $posts->have_posts() ) {
			return rest_ensure_response( array() );
		}

		$response = array();
		foreach ( $posts->posts as $post ) {
			$response[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'url'   => get_permalink( $post->ID ),
			);
		}
		return rest_ensure_response( array( 'posts' => $response ) );
	}
}
