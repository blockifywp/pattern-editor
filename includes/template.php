<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function add_action;
use function add_filter;
use function Blockify\Theme\str_contains_any;
use function dirname;
use function do_blocks;
use function file_exists;
use function function_exists;
use function get_post_field;
use function get_stylesheet_directory;
use function get_template_directory;
use function get_the_ID;
use function in_array;
use function is_page;
use function is_singular;
use function locate_block_template;
use function ob_get_clean;
use function ob_start;
use function show_admin_bar;
use function str_contains;
use function WP_Filesystem;

//add_filter( 'template_include', NS . 'single_block_pattern_template' );
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
	$child  = get_stylesheet_directory() . '/templates/blank.html';
	$parent = get_template_directory() . '/templates/blank.html';
	$file   = file_exists( $child ) ? $child : $parent;

	if ( ! file_exists( $file ) ) {
		$dir = dirname( $file );

		if ( ! file_exists( $dir ) ) {
			mkdir( $dir, 0755, true );
		}

		$data = '<!-- wp:post-content {"layout":{"inherit":true}} /-->';

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$wp_filesystem->put_contents( $file, $data );
	}

	if ( is_singular( 'block_pattern' ) ) {
		$slug          = get_post_field( 'post_name', get_the_ID() );
		$template_slug = 'blank';

		if ( str_contains_any( $slug, 'page-' ) ) {
			$template_slug = 'full-width';
		}

		$template = locate_block_template( $file, $template_slug, [] );
	}

	return $template;
}

//add_filter( 'the_content', NS . 'render_auto_page_pattern' );
/**
 * Automatically display patterns for pages without content and matching slug.
 *
 * @since 1.0.0
 *
 * @param string $content Page content.
 *
 * @return string
 */
function render_auto_page_pattern( string $content ): string {

	if ( function_exists( 'Blockify\\Pro\\render_auto_page_pattern' ) ) {
		return $content;
	}

	if ( ! $content && is_page() ) {
		$page_slug = get_post_field( 'post_name', get_the_ID() );
		$file_name = get_stylesheet_directory() . "/patterns/page-$page_slug.php";

		if ( ! file_exists( $file_name ) ) {
			$file_name = get_template_directory() . "/patterns/default/page-$page_slug.php";
		}

		if ( file_exists( $file_name ) ) {
			ob_start();
			include $file_name;

			$content = ob_get_clean();
			$content = do_blocks( $content );
		}
	}

	return $content;
}

add_action( 'wp', NS . 'hide_admin_bar' );
/**
 * Hide admin bar on single block pattern.
 *
 * @since 1.0.0
 *
 * @return void
 */
function hide_admin_bar(): void {
	if ( is_singular( 'block_pattern' ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
		show_admin_bar( false );
	}
}
