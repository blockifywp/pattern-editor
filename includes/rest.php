<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Post;
use WP_REST_Request;
use WP_REST_Server;
use function __;
use function add_action;
use function current_user_can;
use function register_rest_route;
use function wp_send_json_error;

add_action( 'rest_api_init', NS . 'register_rest_routes' );
/**
 * Registers REST routes.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_rest_routes(): void {
	register_rest_route(
		'blockify/v1',
		'/export-pattern/',
		[
			'permission_callback' => static fn() => current_user_can( 'manage_options' ),
			'callback'            => static fn( WP_REST_Request $request ): array => export_pattern_rest( $request ),
			'methods'             => WP_REST_Server::ALLMETHODS,
			[
				'args' => [
					'id'      => [
						'type'     => 'string',
						'required' => true,
					],
					'slug'    => [
						'type'     => 'string',
						'required' => true,
					],
					'content' => [
						'type'     => 'string',
						'required' => true,
					],
					'title'   => [
						'type'     => 'string',
						'required' => true,
					],
				],
			],
		]
	);
}

/**
 * Handle custom api key endpoint.
 *
 * @since 1.0.0
 *
 * @param WP_REST_Request $request Request object.
 *
 * @return void
 */
function export_pattern_rest( WP_REST_Request $request ): void {
	$post_id      = $request->get_param( 'id' );
	$post_name    = $request->get_param( 'slug' );
	$post_content = $request->get_param( 'content' );
	$post_title   = $request->get_param( 'title' );

	if ( ! $post_id ) {
		wp_send_json_error(
			[
				'message' => __( 'Pattern ID is required.', 'blockify-pattern-editor' ),
			]
		);
	}

	if ( ! $post_name ) {
		wp_send_json_error(
			[
				'message' => __( 'Pattern slug is required.', 'blockify-pattern-editor' ),
			]
		);
	}

	if ( ! $post_content ) {
		wp_send_json_error(
			[
				'message' => __( 'Pattern content is required.', 'blockify-pattern-editor' ),
			]
		);
	}

	if ( ! $post_title ) {
		wp_send_json_error(
			[
				'message' => __( 'Pattern title is required.', 'blockify-pattern-editor' ),
			]
		);
	}

	$post = new WP_Post( (object) [
		'post_content' => $post_content,
		'post_name'    => $post_name,
		'post_title'   => $post_title,
		'post_status'  => 'publish',
	] );

	$exported = export_pattern( $post_id, $post, true );

	if ( ! $exported ) {
		wp_send_json_error(
			[
				'message' => __( 'Pattern export failed.', 'blockify-pattern-editor' ),
			]
		);
	}

	wp_send_json_success(
		[
			'message' => __( 'Pattern exported successfully.', 'blockify-pattern-editor' ),
		]
	);
}
