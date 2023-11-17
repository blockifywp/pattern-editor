<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Block_Patterns_Registry;
use WP_CLI;
use WP_Post;
use WP_Screen;
use function __;
use function add_action;
use function add_query_arg;
use function admin_url;
use function apply_filters;
use function array_merge;
use function basename;
use function esc_html_e;
use function esc_url_raw;
use function explode;
use function file_exists;
use function filemtime;
use function flush_rewrite_rules;
use function get_current_user_id;
use function get_home_url;
use function get_page_by_path;
use function get_post;
use function get_stylesheet;
use function get_stylesheet_directory;
use function home_url;
use function in_array;
use function is_array;
use function preg_match_all;
use function register_post_type;
use function rest_url;
use function sanitize_text_field;
use function sanitize_title_with_dashes;
use function str_contains;
use function str_replace;
use function trailingslashit;
use function trim;
use function ucwords;
use function wp_create_nonce;
use function wp_enqueue_script;
use function WP_Filesystem;
use function wp_localize_script;
use function wp_mkdir_p;
use function wp_nonce_url;
use function wp_parse_url;
use function wp_safe_redirect;
use function wp_slash;
use function wp_unslash;

add_action( 'admin_post_blockify_export_patterns', NS . 'export_patterns' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function export_patterns(): void {
	$synced_patterns = get_reusable_blocks();

	foreach ( $synced_patterns as $synced_pattern ) {
		export_pattern( $synced_pattern->ID, $synced_pattern, true );
	}

	patterns_redirect( [
		'action' => 'blockify_export_patterns',
	] );
}

/**
 * Returns memoized array of all nav menus.
 *
 * @since 1.0.0
 *
 * @return array
 */
function get_nav_menus(): array {
	static $nav_menus = [];

	if ( ! empty( $nav_menus ) ) {
		return $nav_menus;
	}

	$nav_menus = get_posts(
		[
			'post_type'      => 'wp_navigation',
			'posts_per_page' => 100,
		]
	);

	return $nav_menus;
}

/**
 * Returns patterns redirect URL.
 *
 * @since 1.0.0
 *
 * @param array $extra Extra query args (optional).
 *
 * @return void
 */
function patterns_redirect( array $extra = [] ): void {
	$url = add_query_arg(
		array_merge(
			[
				'post_type' => 'wp_block',
				'orderby'   => 'title',
				'order'     => 'ASC',
			],
			$extra
		),
		admin_url( 'edit.php' )
	);

	$action = $extra['action'] ?? '';

	if ( $action ) {
		wp_safe_redirect( wp_nonce_url( $url, $action ) );
	} else {
		wp_safe_redirect( $url );
	}

	exit;
}

/**
 * Removes nav menu references from pattern content.
 *
 * @since 0.0.1
 *
 * @param string $html The HTML content.
 *
 * @return string
 */
function replace_nav_menu_refs( string $html = '' ): string {
	$nav_menus = get_nav_menus();

	foreach ( $nav_menus as $nav_menu ) {
		$html = str_replace(
			'"ref":' . $nav_menu->ID,
			'"ref":""',
			$html
		);
	}

	return $html;
}

/**
 * Replace reusable blocks with patterns.
 *
 * @since 0.0.1
 *
 * @param string $html The HTML content.
 *
 * @return string
 */
function replace_reusable_blocks( string $html = '' ): string {
	if ( ! $html ) {
		return $html;
	}

	$reusable_blocks = get_reusable_blocks();

	foreach ( $reusable_blocks as $reusable_block ) {
		$id   = $reusable_block->ID;
		$slug = sanitize_title_with_dashes( $reusable_block->post_title ?? '' );

		$html = str_replace(
			'<!-- wp:block {"ref":' . $id . '} /-->',
			'<!-- wp:pattern {"slug":"' . $slug . '"} /-->',
			$html
		);
	}

	return $html;
}

/**
 * Replaces Blockify post content blocks with core.
 *
 * @since 1.0.0
 *
 * @param string $html The HTML content.
 *
 * @return string
 */
function replace_post_content_blocks( string $html = '' ): string {
	return str_replace(
		'wp:blockify/post-content',
		'wp:post-content',
		$html
	);
}

/**
 * Replaces image paths with theme URI.
 *
 * @since 1.0.0
 *
 * @param string $content_dir The content directory.
 *
 * @param string $html        The HTML content.
 *
 * @return string
 */
function replace_image_paths( string $html, string $content_dir ): string {
	$regex       = "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
	$types       = [ 'jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'mov', 'svg', 'webm' ];
	$upload_dir  = wp_upload_dir();
	$content_dir = trailingslashit( $content_dir );
	$stylesheet  = get_stylesheet();
	$setting     = apply_filters( 'blockify_image_export_dir', "themes/$stylesheet/assets" );

	// Remove trailing slashes.
	$setting   = implode( DS, explode( DS, $setting ) );
	$asset_dir = $content_dir . $setting . DS;

	preg_match_all( $regex, $html, $matches );

	if ( ! isset( $matches[0] ) || ! is_array( $matches[0] ) ) {
		return $html;
	}

	foreach ( $matches[0] as $url ) {
		$basename = basename( $url );

		if ( ! str_contains( $basename, '.' ) ) {
			continue;
		}

		[ $file, $type ] = explode( '.', basename( $url ) );

		if ( ! in_array( $type, $types, true ) ) {
			continue;
		}

		// Limit to current site.
		$host = wp_parse_url( get_home_url() )['host'] ?? '';

		if ( ! str_contains( $url, $host ) ) {
			continue;
		}

		$original = str_replace(
			$upload_dir['baseurl'],
			$upload_dir['basedir'],
			$url
		);

		if ( ! file_exists( $original ) ) {
			continue;
		}

		if ( $type === 'svg' ) {
			$sub_dir = 'svg';
		} else {
			if ( $type === 'mp4' || $type === 'mov' ) {
				$sub_dir = 'video';
			} else {
				if ( $type === 'gif' ) {
					$sub_dir = 'gif';
				} else {
					$sub_dir = 'img';
				}
			}
		}

		$new_dir = $asset_dir . $sub_dir . DS;

		if ( ! file_exists( $new_dir ) ) {
			wp_mkdir_p( $new_dir );
		}

		$new = $new_dir . $basename;

		copy( $original, $new );

		$html = str_replace( $url, $new, trim( $html ) );
	}

	$html = str_replace(
		$asset_dir,
		'<?php echo content_url( "/' . $setting . '/" ) ?>',
		$html
	);

	$html = str_replace(
		get_stylesheet_directory_uri(),
		'<?php echo get_stylesheet_directory_uri() ?>',
		$html
	);

	$html = str_replace(
		home_url(),
		'<?php echo home_url() ?>',
		$html
	);

	return $html;
}

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

add_action( 'admin_post_blockify_import_patterns', NS . 'import_patterns', 11 );
/**
 * Imports all registered patterns as posts.
 *
 * @since 1.0.0
 *
 * @return void
 */
function import_patterns(): void {
	$registered = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
	$stylesheet = get_stylesheet();

	foreach ( $registered as $pattern ) {
		$theme = $pattern['theme'] ?? null;

		if ( $theme !== $stylesheet ) {
			continue;
		}

		$category = $pattern['categories'][0] ?? 'uncategorized';

		//if ( in_array( $category, [ 'template', 'page' ] ) ) {
		//	continue;
		//}

		$args = [
			'post_name'    => $pattern['slug'],
			'post_title'   => ucwords( $category ) . ' ' . $pattern['title'],
			'post_content' => $pattern['content'],
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		];

		$id = $pattern['ID'] ?? null;

		if ( $id ?? null ) {
			$existing = get_post( $id );

			if ( $existing ) {
				$args['ID'] = $id;
			} else {
				$args['import_id'] = $id;
			}
		}

		if ( get_page_by_path( $pattern['slug'], OBJECT, 'wp_block' ) ) {
			continue;
		}

		wp_insert_post( wp_slash( $args ) );
	}

	flush_rewrite_rules();

	patterns_redirect( [
		'action' => 'blockify_import_patterns',
	] );
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

add_action( 'admin_notices', NS . 'pattern_import_success_notice' );
/**
 * Admin notice for pattern import.
 *
 * @since 1.0.0
 *
 * @return void
 */
function pattern_import_success_notice(): void {
	$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, 'blockify_import_patterns' ) ) {
		return;
	}

	$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ?? '' ) );

	if ( $post_type !== 'wp_block' ) {
		return;
	}

	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<?php esc_html_e( 'Patterns successfully imported.', 'pattern-editor' ); ?>
		</p>
	</div>
	<?php
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

add_filter( 'save_post_wp_block', NS . 'export_pattern', 11, 3 );
/**
 * Handles export pattern request.
 *
 * @since 0.0.1
 *
 * @param WP_Post $post    The post object.
 * @param bool    $update  Whether this is an existing post being updated or
 *                         not.
 *
 * @param int     $post_ID The post ID.
 *
 * @return int
 */
function export_pattern( int $post_ID, WP_Post $post, bool $update ): int {

	if ( ! $update ) {
		return $post_ID;
	}

	if ( $post->post_status !== 'publish' ) {
		return $post_ID;
	}

	$slug = sanitize_title_with_dashes( $post->post_title ?? '' );

	if ( ! $slug ) {
		return $post_ID;
	}

	$explode  = explode( '-', $slug );
	$category = $explode[0] ?? null;

	if ( ! $category ) {
		return $post_ID;
	}

	$name = str_replace( [ $category . '-' ], '', $slug );

	$content_dir = get_content_dir();
	$content     = $post->post_content ?? '';
	$content     = replace_image_paths( $content, $content_dir );
	$content     = replace_nav_menu_refs( $content );
	$content     = replace_reusable_blocks( $content );
	$content     = replace_post_content_blocks( $content );
	$content     = apply_filters( 'blockify_pattern_export_content', $content, $post, $category );
	$content     = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content );

	$block_types = '';

	if ( $category === 'page' ) {
		$block_types .= 'core/post-content,';
	}

	if ( $category === 'header' ) {
		$block_types .= 'core/template-part/header,';
	}

	if ( $category === 'footer' ) {
		$block_types .= 'core/template-part/footer,';
	}

	if ( $block_types ) {
		$block_types = 'Block Types: ' . rtrim( $block_types, ',' );
	}

	$pattern_dir = get_pattern_dir( $post );

	if ( ! file_exists( $pattern_dir ) ) {
		wp_mkdir_p( $pattern_dir );
	}

	if ( ! file_exists( $pattern_dir . $category ) ) {
		wp_mkdir_p( $pattern_dir . $category );
	}

	global $wp_filesystem;

	if ( ! $wp_filesystem ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	$title = ucwords( str_replace( '-', ' ', $category . ' ' . $name ) );

	$header_comment = <<<EOF
<?php
/**
 * Title: $title
 * Slug: $name
 * Categories: $category
EOF;

	if ( $block_types ) {
		$header_comment .= "\n * $block_types";
	}

	if ( $post_ID ) {
		//$header_comment .= "\n * ID: $post_ID";
	}

	if ( $category === 'template' ) {
		$header_comment .= "\n * Template Types: $slug";
		$header_comment .= "\n * Inserter: false";
	}

	$header_comment .= "\n */\n";
	$header_comment .= "?>\n";

	$wp_filesystem->put_contents(
		$pattern_dir . $category . DS . $name . '.php',
		$header_comment . $content
	);

	flush_rewrite_rules();

	return $post_ID;
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

add_filter( 'post_row_actions', NS . 'add_quick_edit_back', 10, 2 );
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
function add_quick_edit_back( array $actions, WP_Post $post ) {

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
