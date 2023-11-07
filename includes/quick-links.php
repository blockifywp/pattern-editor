<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function add_action;
use function add_menu_page;
use function add_submenu_page;
use function admin_url;
use function defined;
use function get_stylesheet;
use const BLOCKIFY_DEV;

add_action( 'admin_menu', NS . 'add_quick_links' );
/**
 * Add theme menu links to admin.
 *
 * @return void
 */
function add_quick_links(): void {

	if ( ! defined( 'BLOCKIFY_DEV' ) || ! BLOCKIFY_DEV ) {
		$links[] = [
			'label' => __( 'Patterns', 'blockify' ),
			'url'   => '',
		];
	}

	add_menu_page(
		__( 'Patterns', 'blockify' ),
		__( 'Patterns', 'blockify' ),
		'manage_options',
		admin_url( 'edit.php?post_type=wp_block&orderby=title&order=ASC' ),
		'',
		'dashicons-layout',
		30
	);

	$stylesheet = get_stylesheet();

	$links = [
		[
			'label' => __( 'Add New', 'blockify' ),
			'url'   => admin_url( 'post-new.php?post_type=wp_block' ),
		],
		[
			'label' => __( 'Header', 'blockify' ),
			'url'   => admin_url( "site-editor.php?postType=wp_template_part&postId=$stylesheet%2F%2Fheader&canvas=edit" ),
		],
		[
			'label' => __( 'Footer', 'blockify' ),
			'url'   => admin_url( "site-editor.php?postType=wp_template_part&postId=$stylesheet%2F%2Ffooter&canvas=edit" ),
		],
		[
			'label' => __( 'Templates', 'blockify' ),
			'url'   => admin_url( 'site-editor.php?postType=wp_template' ),
		],
		[
			'label' => __( 'Navigation', 'blockify' ),
			'url'   => admin_url( 'site-editor.php?path=%2Fnavigation' ),
		],
	];

	foreach ( $links as $link ) {
		add_submenu_page(
			admin_url( 'edit.php?post_type=wp_block&orderby=title&order=ASC' ),
			$link['label'],
			$link['label'],
			'manage_options',
			$link['url'],
			''
		);
	}
}
