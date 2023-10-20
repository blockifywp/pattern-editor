<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function __;
use function add_action;
use function add_filter;
use function add_theme_page;
use function admin_url;
use function esc_url;
use function esc_url_raw;
use function filemtime;
use function get_current_user_id;
use function get_stylesheet;
use function get_stylesheet_directory;
use function home_url;
use function register_meta;
use function rest_url;
use function wp_create_nonce;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;

add_action( 'admin_enqueue_scripts', NS . 'enqueue_pattern_admin' );
/**
 * Enqueues editor pattern styles.
 *
 * @since 0.0.1
 *
 * @return void
 */
function enqueue_pattern_admin(): void {
	$current_screen = get_current_screen();

	if ( ( $current_screen->post_type ?? '' ) !== 'block_pattern' ) {
		return;
	}

	if ( $current_screen->base !== 'edit' ) {
		return;
	}

	wp_enqueue_style(
		'blockify-pattern-editor',
		plugin_dir_url( FILE ) . 'assets/css/admin.css',
		[],
		filemtime( DIR . 'assets/css/admin.css' )
	);

	wp_enqueue_script(
		'blockify-pattern-editor',
		plugin_dir_url( FILE ) . 'assets/js/admin.js',
		[
			'wp-i18n',
		],
		filemtime( DIR . 'assets/js/admin.js' ),
		true
	);

	wp_localize_script(
		'blockify-pattern-editor',
		'blockifyPatternEditor',
		[
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'restUrl'       => esc_url_raw( rest_url() ),
			'adminUrl'      => esc_url_raw( admin_url() ),
			'currentUser'   => get_current_user_id(),
			'stylesheet'    => get_stylesheet(),
			'stylesheetDir' => get_stylesheet_directory(),
			'isChildTheme'  => is_child_theme(),
		]
	);
}

add_filter( 'manage_block_pattern_posts_columns', NS . 'set_custom_edit_book_columns' );
/**
 * Adds preview column to patterns list screen.
 *
 * @param array $columns The columns.
 *
 * @since 0.0.1
 *
 * @return array
 */
function set_custom_edit_book_columns( array $columns ): array {
	$columns['preview'] = __( 'Preview', 'pattern-editor' );

	return $columns;
}

add_action( 'manage_block_pattern_posts_custom_column', NS . 'pattern_preview_column', 10, 2 );
/**
 * Adds pattern iframe preview to admin columns.
 *
 * @param string $column  The column.
 * @param int    $post_id The post ID.
 *
 * @since 0.0.1
 *
 * @return void
 */
function pattern_preview_column( string $column, int $post_id ): void {
	$post_name = get_post_field( 'post_name', $post_id );
	$url       = home_url() . '/patterns/' . $post_name;

	switch ( $column ) {
		case 'preview':
			echo '<div class="pattern-preview"><iframe loading="lazy" scrolling="no" src=\'' . esc_url( $url ) . '\' seamless></iframe></div>';
			break;
	}
}

add_action( 'rest_api_init', NS . 'register_pattern_user_meta' );
/**
 * Register user meta for pattern display.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_pattern_user_meta(): void {
	register_meta(
		'user',
		'blockify_show_patterns',
		[
			'description'  => __( 'Show patterns by default.', 'pattern-editor' ),
			'type'         => 'string',
			'show_in_rest' => true,
			'single'       => true,
		]
	);
}

add_filter( 'admin_body_class', NS . 'add_show_patterns_body_class' );
/**
 * Conditionally  add show patterns class by default.
 *
 * @param string $classes The body classes.
 *
 * @since 1.0.0
 *
 * @return string
 */
function add_show_patterns_body_class( string $classes ): string {
	$show_patterns = get_user_option( 'blockify_show_patterns', get_current_user_id() );

	if ( $show_patterns ) {
		$classes .= ' show-patterns';
	}

	return $classes;
}

add_action( 'admin_menu', NS . 'add_quick_links' );
/**
 * Add theme menu links to admin.
 *
 * @return void
 */
function add_quick_links(): void {
	$links = [
		[
			'label' => __( 'Patterns', 'blockify' ),
			'url'   => admin_url( 'edit.php?post_type=block_pattern&orderby=title&order=asc' ),
		],
		[
			'label' => __( 'Pattern Categories', 'blockify' ),
			'url'   => admin_url( 'edit-tags.php?taxonomy=pattern_category' ),
		],
		[
			'label' => __( 'Add Pattern', 'blockify' ),
			'url'   => admin_url( 'post-new.php?post_type=block_pattern' ),
		],
	];

	foreach ( $links as $link ) {
		add_theme_page(
			$link['label'],
			$link['label'],
			'manage_options',
			$link['url']
		);
	}
}
