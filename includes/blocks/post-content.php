<?php

declare( strict_types=1 );

namespace Blockify\Blocks;

use Blockify\Dom\DOM;
use Blockify\Utilities\Block;
use WP_Block;
use function add_action;
use function esc_html__;
use function explode;
use function implode;
use function is_singular;
use function register_custom_block;
use function render_block_core_post_content;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_post_content_block' );
/**
 * Registers the Post Content block.
 *
 * @since 0.2.0
 *
 * @return void
 */
function register_post_content_block() {
	register_custom_block(
		'blockify/post-content',
		[
			'title'           => esc_html__( 'Content', 'pattern-editor' ),
			'description'     => esc_html__( 'Displays the contents of a post or page.', 'pattern-editor' ),
			'category'        => 'pattern-editor',
			'text_domain'     => 'pattern-editor',
			'keywords'        => [
				esc_html__( 'Content', 'pattern-editor' ),
				esc_html__( 'Post', 'pattern-editor' ),
			],
			'icon'            => [
				'src' => get_post_content_icon(),
			],
			'supports'        => [
				'align' => true,
			],
			'panels'          => [
				'settings' => [
					'title'       => esc_html__( 'Template Part Settings', 'blockify-pro' ),
					'initialOpen' => true,
				],
			],
			'attributes'      => [
				'style'       => [
					'type' => 'object',
				],
				'constrained' => [
					'type'    => 'boolean',
					'default' => false,
					'control' => 'toggle',
					'label'   => esc_html__( 'Inner blocks use content width
', 'pattern-editor' ),
					'help'    => esc_html__( 'Nested blocks use content width with options for full and wide widths.', 'pattern-editor' ),
				],
			],
			'render_callback' => __NAMESPACE__ . '\\render_post_content_block',
		]
	);
}

/**
 * Renders the Post Content block.
 *
 * @since 0.2.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string
 */
function render_post_content_block( array $attributes, string $content, WP_Block $block ): string {
	$paragraphs   = [];
	$paragraphs[] = esc_html__( 'This is the Content block, it will display all the blocks in any single post or page.', 'pattern-editor' );
	$paragraphs[] = esc_html__( 'That might be a simple arrangement like consecutive paragraphs in a blog post, or a more elaborate composition that includes image galleries, videos, tables, columns, and any other block types.', 'pattern-editor' );
	$paragraphs[] = esc_html__( 'If there are any Custom Post Types registered at your site, the Content block can display the contents of those entries as well.', 'pattern-editor' );

	$placeholder = '';

	foreach ( $paragraphs as $paragraph ) {
		$placeholder .= '<p>' . $paragraph . '</p>';
	}

	if ( Block::is_rendering_preview() ) {
		return $placeholder;
	}

	if ( ! isset( $block->context['postId'] ) && is_singular() ) {
		$block->context['postId'] = get_the_ID();
	}

	$content = render_block_core_post_content( $attributes, $content, $block );

	$dom = DOM::create( $content );
	$div = DOM::get_element( 'div', $dom );

	if ( $div ) {
		$div_classes = explode( ' ', $div->getAttribute( 'class' ) );

		if ( $attributes['constrained'] ?? '' ) {
			$div_classes[] = 'is-layout-constrained';
		}

		$div->setAttribute( 'class', implode( ' ', $div_classes ) );
	}

	return $dom->saveHTML();
}

/**
 * Returns the post content icon.
 *
 * @since 0.2.0
 *
 * @return string
 */
function get_post_content_icon(): string {
	return <<<HTML
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
<path d="M4 6h12V4.5H4V6Zm16 4.5H4V9h16v1.5ZM4 15h16v-1.5H4V15Zm0 4.5h16V18H4v1.5Z"/>
</svg>
HTML;
}
