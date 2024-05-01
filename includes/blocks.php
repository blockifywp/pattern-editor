<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Block_Editor_Context;
use function add_filter;
use function register_block_type;
use function register_custom_post_type;

// add_action( 'init', NS . 'register_blocks' );
/**
 * Register blocks
 */
function register_blocks(): void {

	if ( ! function_exists( 'register_custom_post_type' ) ) {
		return;
	}

	register_custom_post_type( 'pattern-template', [
		'template'      => [
			[
				'blockify/pattern-canvas',
			],
		],
		'template_lock' => 'all',
	] );

	$blocks = [
		'pattern-template',
		'pattern-canvas',
		'pattern-frame',
	];

	$dir = DIR . '/build/blocks/';

	foreach ( $blocks as $block ) {
		register_block_type( $dir . $block );
	}
}

add_filter( 'block_categories_all', __NAMESPACE__ . '\\register_block_category', 10, 2 );
/**
 * Add block category
 *
 * @param array                   $categories Block categories.
 * @param WP_Block_Editor_Context $editor     Editor name.
 *
 * @return array
 */
function register_block_category( array $categories, WP_Block_Editor_Context $editor ): array {
	$categories[] = [
		'slug'  => 'pattern-editor',
		'title' => esc_html__( 'Pattern Editor', 'pattern-editor' ),
	];

	return $categories;
}
