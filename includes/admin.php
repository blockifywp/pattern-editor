<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Post;
use WP_Screen;
use function __;
use function add_action;
use function admin_url;
use function esc_url_raw;
use function explode;
use function filemtime;
use function get_current_user_id;
use function get_stylesheet;
use function get_stylesheet_directory;
use function register_post_type;
use function rest_url;
use function sanitize_text_field;
use function wp_create_nonce;
use function wp_enqueue_script;
use function wp_localize_script;
use function wp_unslash;

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

	if ( ( $current_screen->post_type ?? '' ) !== 'wp_block' ) {
		return;
	}

	if ( $current_screen->base !== 'edit' ) {
		return;
	}

	$handle = 'blockify-patterns';
	$uri    = get_plugin_uri();
	$dir    = DIR;

	wp_enqueue_script(
		$handle,
		$uri . 'build/patterns.js',
		[
			'wp-i18n',
		],
		filemtime( $dir . 'build/patterns.js' ),
		true
	);

	wp_localize_script(
		$handle,
		'blockifyPatterns',
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

add_action( 'admin_post_blockify_delete_patterns', NS . 'delete_patterns', 11 );
/**
 * Deletes all registered patterns as posts.
 *
 * @since 1.0.0
 *
 * @return void
 */
function delete_patterns(): void {
	$posts = get_posts(
		[
			'post_type'      => 'wp_block',
			'posts_per_page' => -1,
		]
	);

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	patterns_redirect( [
		'action' => 'blockify_delete_patterns',
	] );
}

add_action( 'current_screen', NS . 'sort_patterns_redirect' );
/**
 * Redirects the wp_block post type to the desired URL.
 *
 * @since 1.0.0
 *
 * @param WP_Screen $current_screen The current screen object.
 *
 * @return void
 */
function sort_patterns_redirect( WP_Screen $current_screen ): void {
	if ( ( $current_screen->post_type ?? '' ) !== 'wp_block' ) {
		return;
	}

	if ( $current_screen->base !== 'edit' ) {
		return;
	}

	$order_by    = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? '' ) );
	$post_status = sanitize_text_field( wp_unslash( $_GET['post_status'] ?? '' ) );

	if ( ! $order_by && $post_status !== 'trash' ) {
		patterns_redirect();
	}
}

add_filter( 'manage_wp_block_posts_columns', NS . 'add_pattern_category_column' );
/**
 * Adds a column to the wp_block post type.
 *
 * @since 1.0.0
 *
 * @param array $columns Array of columns.
 *
 * @return array
 */
function add_pattern_category_column( array $columns ): array {
	unset ( $columns['date'] );

	$columns['category'] = __( 'Category', 'wp-patterns' );
	$columns['date']     = __( 'Date', 'wp-patterns' );

	return $columns;
}

add_action( 'manage_wp_block_posts_custom_column', NS . 'add_pattern_category_column_title', 10, 2 );
/**
 * Adds data to the custom column.
 *
 * @since 1.0.0
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 *
 * @return void
 */
function add_pattern_category_column_title( string $column, int $post_id ) {
	if ( $column === 'category' ) {
		$title    = get_the_title( $post_id );
		$explode  = explode( ' ', $title );
		$category = $explode[0] ?? '';

		echo $category;
	}
}

add_filter( 'post_row_actions', NS . 'add_quick_edit_button', 10, 2 );
/**
 * Re-adds quick edit to post type.
 *
 * @since 1.0.0
 *
 * @param array   $actions Array of actions.
 * @param WP_Post $post    Post object.
 *
 * @return array
 */
function add_quick_edit_button( array $actions, WP_Post $post ) {
	if ( $post->post_type === 'wp_block' ) {
		$actions['inline hide-if-no-js'] = sprintf(
			'<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $post->post_title ) ),
			__( 'Quick&nbsp;Edit' )
		);
	}
	return $actions;
}

add_action( 'current_screen', NS . 'export_pattern_cpt' );
/**
 * Enables pattern post type on export screen.
 *
 * @since 1.0.0
 *
 * @param WP_Screen $screen Screen object.
 *
 * @return void
 */
function export_pattern_cpt( WP_Screen $screen ): void {
	if ( 'export' !== $screen->id ) {
		return;
	}

	register_post_type(
		'wp_block',
		[
			'labels'                => [
				'name'               => 'Patterns',
				'singular_name'      => 'Pattern',
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New Pattern',
				'edit_item'          => 'Edit Pattern',
				'new_item'           => 'New Pattern',
				'view_item'          => 'View Pattern',
				'search_items'       => 'Search Patterns',
				'not_found'          => 'No Patterns found',
				'not_found_in_trash' => 'No Patterns found in Trash',
				'parent_item_colon'  => 'Parent Pattern:',
				'menu_name'          => 'Patterns',
			],
			'public'                => false,
			'rest_base'             => 'blocks',
			'rest_controller_class' => 'WP_REST_Blocks_Controller',
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'show_in_admin_bar'     => true,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-layout',
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'supports'              => [
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
				'post-formats',
			],
			'rewrite'               => false,
			'show_in_rest'          => true,
		]
	);
}
