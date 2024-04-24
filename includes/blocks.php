<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function add_action;
use function register_block_type;
use function register_custom_post_type;

add_action( 'init', NS . 'register_blocks' );
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
