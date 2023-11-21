<?php

namespace Blockify\PatternEditor;

use WP_CLI;
use WP_Post;
use function add_query_arg;
use function admin_url;
use function apply_filters;
use function array_merge;
use function dirname;
use function esc_html;
use function get_post;
use function get_stylesheet;
use function is_null;
use function plugin_dir_url;
use function trailingslashit;
use function wp_nonce_url;
use function wp_safe_redirect;

/**
 * Returns URI to theme directory.
 *
 * @since 1.0.0
 *
 * @return string
 */
function get_plugin_uri(): string {
	static $uri = null;

	if ( is_null( $uri ) ) {
		$uri = plugin_dir_url( FILE );
		$uri = trailingslashit( $uri );
	}

	return $uri;
}

/**
 * Returns path to `wp-content` directory.
 *
 * @since 1.0.0
 *
 * @return string
 */
function get_content_dir(): string {
	return trailingslashit( dirname( get_template_directory(), 2 ) );
}

/**
 * Returns path to pattern directory.
 *
 * @since 1.0.0
 *
 * @param WP_Post|null $post Post object (optional).
 *
 * @return string
 */
function get_pattern_dir( WP_Post $post = null ): string {
	$post        = $post ?? get_post() ?? null;
	$stylesheet  = get_stylesheet();
	$default_dir = get_content_dir() . "themes/$stylesheet/patterns";

	/**
	 * Filters the pattern directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $filtered_dir Filtered pattern directory.
	 * @param ?WP_Post $post         Post object (optional).
	 */
	$filtered_dir = apply_filters( 'blockify_pattern_export_dir', $default_dir, $post );

	return esc_html( trailingslashit( $filtered_dir ) );
}

/**
 * Returns memoized array of all reusable blocks.
 *
 * @since 1.0.0
 *
 * @return array
 */
function get_reusable_blocks(): array {
	static $reusable_blocks = [];

	if ( ! empty( $reusable_blocks ) ) {
		return $reusable_blocks;
	}

	$reusable_blocks = get_posts(
		[
			'post_type'      => 'wp_block',
			'posts_per_page' => -1,
		]
	);

	return $reusable_blocks;
}

/**
 * Handles pattern redirect.
 *
 * @since 1.0.0
 *
 * @param array $extra Extra query args (optional).
 *
 * @return void
 */
function patterns_redirect( array $extra = [] ): void {
	$url = add_query_arg(
		array_merge(
			[
				'post_type' => 'wp_block',
				'orderby'   => 'title',
				'order'     => 'ASC',
			],
			$extra
		),
		admin_url( 'edit.php' )
	);

	$action = $extra['action'] ?? '';

	if ( $action ) {
		wp_safe_redirect( wp_nonce_url( $url, $action ) );
	} else {
		wp_safe_redirect( $url );
	}

	exit;
}
