<?php

declare( strict_types=1 );

namespace Blockify\Blocks;

use WP_Block;
use function add_action;
use function esc_html__;
use function register_custom_block;
use function render_block_core_template_part;
use function sprintf;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\register_template_part_block' );
/**
 * Registers the Template Part block.
 *
 * @since 0.2.0
 *
 * @return void
 */
function register_template_part_block() {
	register_custom_block(
		'blockify/template-part',
		[
			'file'            => null,
			'title'           => esc_html__( 'Template Part', 'blockify-pro' ),
			'description'     => esc_html__( 'Edit the different global regions of your site, like the header, footer or sidebar.', 'blockify-pro' ),
			'category'        => 'pattern-editor',
			'text_domain'     => 'pattern-editor',
			'keywords'        => [
				esc_html__( 'Header', 'blockify-pro' ),
				esc_html__( 'Footer', 'blockify-pro' ),
			],
			'icon'            => [
				'src' => get_template_part_icon(),
			],
			'post_types'      => [ 'wp_block' ],
			'render_callback' => __NAMESPACE__ . '\\render_template_part_block',
			'panels'          => [
				'settings' => [
					'title'       => esc_html__( 'Template Part Settings', 'blockify-pro' ),
					'initialOpen' => true,
				],
			],
			'attributes'      => [
				'slug'      => [
					'type'    => 'string',
					'default' => '',
					'control' => 'select',
					'label'   => esc_html__( 'Template Part', 'blockify-pro' ),
					'options' => [
						[
							'label' => esc_html__( 'Select template part', 'blockify-pro' ),
							'value' => '',
						],
						[
							'label' => esc_html__( 'Header', 'blockify-pro' ),
							'value' => 'header',
						],
						[
							'label' => esc_html__( 'Footer', 'blockify-pro' ),
							'value' => 'footer',
						],
					],
				],
				'tagName'   => [
					'type'      => 'string',
					'value'     => '{attributes.slug}',
					'control'   => 'text',
					'inputType' => 'hidden',
				],
				'className' => [
					'type'      => 'string',
					'value'     => 'site-{attributes.slug}',
					'control'   => 'text',
					'inputType' => 'hidden',
				],
				'style'     => [
					'type' => 'object',
				],
			],
		]
	);
}

/**
 * Renders the Template Part block.
 *
 * @since 1.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string
 */
function render_template_part_block( array $attributes, string $content, WP_Block $block ): string {
	$slug = $attributes['slug'] ?? '';

	if ( ! $slug ) {
		$html = sprintf(
			'<div class="wp-block-blockify-template-part"><p>%s</p></div>',
			esc_html__( 'Please select a template part.', 'blockify-pro' )
		);
	} else {
		$html = render_block_core_template_part( $attributes );
	}

	return $html;
}

/**
 * Returns the icon for the Template Part block.
 *
 * @since 0.2.0
 *
 * @return string
 */
function get_template_part_icon(): string {
	return <<<HTML
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
<path d="M18.5 10.5H10v8h8a.5.5 0 0 0 .5-.5v-7.5zm-10 0h-3V18a.5.5 0 0 0 .5.5h2.5v-8zM6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
</svg>
HTML;
}
