<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor\Blocks;

use Blockify\Dom\DOM;
use Blockify\Utilities\Str;
use WP_Block;
use WP_Block_Patterns_Registry;
use function add_action;
use function admin_url;
use function do_blocks;
use function esc_attr;
use function filter_input;
use function get_page_by_path;
use function implode;
use function register_custom_block;
use function str_contains;
use function str_replace;
use function wp_safe_redirect;
use const Blockify\PatternEditor\DIR;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;
use const INPUT_GET;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_pattern_block' );
/**
 * Registers the Pattern block.
 *
 * @since 0.2.0
 *
 * @return void
 */
function register_pattern_block(): void {
	register_custom_block(
		'blockify/pattern',
		[
			'file'            => DIR . 'build/blocks/pattern',
			'post_types'      => [ 'wp_block' ],
			'render_callback' => __NAMESPACE__ . '\\render_pattern_block',
		]
	);
}

/**
 * Renders the pattern block.
 *
 * @since 0.2.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block object.
 *
 * @return string
 */
function render_pattern_block( array $attributes, string $content, WP_Block $block ): string {
	$slug = esc_attr( $attributes['slug'] ?? '' );

	if ( ! $slug ) {
		return $content;
	}

	$patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();

	foreach ( $patterns as $pattern ) {
		if ( $pattern['slug'] !== $slug ) {
			continue;
		}

		$content = do_blocks( $pattern['content'] ?? '' );

		break;
	}

	$use_iframe = $attributes['iframe'] ?? false;
	$is_page    = str_contains( $slug, 'page-' );

	if ( $use_iframe ) {

		if ( $is_page ) {
			$header = '<header class="has-position-absolute" style="inset:0 0 auto">' . do_blocks( '<!-- wp:pattern {"slug":"header-default"} /-->' ) . '</header>';
			$footer = '<footer class="alignfull">' . do_blocks( '<!-- wp:pattern {"slug":"footer-default"} /-->' ) . '</footer>';

			$content = $header . $content . $footer;
		}

		$content = str_replace(
			'is-stacked-on-mobile',
			'is-not-stacked-on-mobile',
			$content
		);

		$dom               = DOM::create( '' );
		$container         = DOM::create_element( 'div', $dom );
		$container_classes = [
			'blockify-pattern',
		];

		if ( $is_page ) {
			$container_classes[] = 'is-page-pattern';
		}

		$container->setAttribute( 'class', esc_attr( implode( ' ', $container_classes ) ) );

		$iframe = DOM::create_element( 'div', $dom );
		$iframe->setAttribute( 'class', 'blockify-pattern-iframe' );
		$height = esc_attr( $attributes['height'] ?? '' );

		if ( $height ) {
			$iframe->setAttribute( 'style', 'height:' . $height . ';' );
		}

		$content_dom = DOM::create( "<div>$content</div>" );
		$imported    = $dom->importNode( $content_dom->documentElement, true );

		$iframe->appendChild( $imported );
		$container->appendChild( $iframe );

		$content = $dom->saveHTML( $container );
	}

	return $content;
}

add_action( 'admin_init', __NAMESPACE__ . '\\redirect_edit_link' );
/**
 * Redirects the edit pattern link to the block editor.
 *
 * @since 0.2.0
 *
 * @return void
 */
function redirect_edit_link(): void {
	$edit_pattern = filter_input( INPUT_GET, 'edit_pattern', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	if ( ! $edit_pattern ) {
		return;
	}

	$post = get_page_by_path( $edit_pattern, OBJECT, 'wp_block' );

	if ( ! $post ) {
		$title = Str::title_case( $edit_pattern );

		$posts = get_posts( [
			'title'     => $title,
			'post_type' => 'wp_block',
		] );

		$post = $posts[0] ?? null;

		if ( ! $post ) {
			return;
		}
	}

	wp_safe_redirect( admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) );

	exit;
}

