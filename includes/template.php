<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function add_filter;
use function dirname;
use function do_blocks;
use function file_exists;
use function get_post_field;
use function get_stylesheet_directory;
use function get_template_directory;
use function get_the_ID;
use function is_page;
use function is_singular;
use function locate_block_template;
use function ob_get_clean;
use function ob_start;
use function WP_Filesystem;

add_filter( 'template_include', NS . 'single_block_pattern_template' );
/**
 * Filter pattern template.
 *
 * @param string $template Template slug.
 *
 * @since 0.4.0
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
		$template = locate_block_template( $file, 'blank', [] );
	}

	return $template;
}

add_filter( 'the_content', NS . 'render_auto_page_pattern' );
/**
 * Automatically display patterns for pages without content and matching slug.
 *
 * @param string $content Page content.
 *
 * @since 1.0.0
 *
 * @return string
 */
function render_auto_page_pattern( string $content ): string {
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
