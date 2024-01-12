<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use stdClass;
use WP_Block_Patterns_Registry;
use WP_Post;
use function do_blocks;
use function filter_input;
use function get_template_directory;
use function locate_block_template;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;

add_filter( 'template_include', NS . 'single_block_pattern_template' );
/**
 * Filter pattern template.
 *
 * @since 0.4.0
 *
 * @param string $template Template slug.
 *
 * @return string
 */
function single_block_pattern_template( string $template ): string {
	$pattern_name = get_pattern_preview_name();

	if ( ! $pattern_name ) {
		return $template;
	}

	return locate_block_template(
		get_template_directory() . '/templates/blank.html',
		'blank',
		[]
	);
}

/**
 * Gets the pattern name from the query string.
 *
 * @since 0.4.0
 *
 * @return string
 */
function get_pattern_preview_name(): string {
	$page_id      = (int) filter_input( INPUT_GET, 'page_id', FILTER_SANITIZE_NUMBER_INT );
	$pattern_name = filter_input( INPUT_GET, 'pattern_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	if ( $page_id !== 9999 ) {
		return '';
	}

	return $pattern_name;
}

add_filter( 'the_posts', NS . 'block_pattern_preview', -100 );
/**
 * Generates dynamic block pattern previews without registering a CPT.
 *
 * @param array $posts Original posts object.
 *
 * @return array
 */
function block_pattern_preview( array $posts ): array {
	global $wp_query;

	static $cache = null;

	if ( ! is_null( $cache ) && $posts ) {
		return $posts;
	}

	$cache = true;
	$name  = get_pattern_preview_name();

	if ( ! $name ) {
		return $posts;
	}

	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $name );

	if ( ! $pattern ) {
		return $posts;
	}

	/* @var WP_Post $post Post object */
	$post                  = new stdClass();
	$post->post_author     = 1;
	$post->post_name       = $name;
	$post->guid            = home_url() . DS . 'page' . DS . $name;
	$post->post_title      = ucwords( str_replace( '-', ' ', $name ) );
	$post->post_content    = do_blocks( $pattern['content'] ?? '' );
	$post->ID              = -1;
	$post->post_type       = 'page';
	$post->post_status     = 'publish';
	$post->comment_status  = 'closed';
	$post->ping_status     = 'open';
	$post->comment_count   = 0;
	$post->post_date       = current_time( 'mysql' );
	$post->post_date_gmt   = current_time( 'mysql', 1 );
	$post->page_template   = 'blank';
	$posts                 = null;
	$posts[]               = $post;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	$wp_query->is_category = false;
	$wp_query->is_404      = false;

	add_filter( 'show_admin_bar', static fn(): bool => false );

	return $posts;
}

add_filter( 'pre_get_shortlink', NS . 'short_link_filter', 10, 2 );
/**
 * Short link filter.
 *
 * @param false|string $return Short-circuit return value.
 *
 * @return string
 */
function short_link_filter( $return ): string {
	$pattern_name = get_pattern_preview_name();

	if ( $pattern_name ) {
		$return = '';
	}

	return (string) $return;
}
