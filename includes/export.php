<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Post;
use function add_action;
use function apply_filters;
use function basename;
use function explode;
use function file_exists;
use function flush_rewrite_rules;
use function get_home_url;
use function get_stylesheet;
use function home_url;
use function in_array;
use function is_array;
use function preg_match_all;
use function sanitize_title_with_dashes;
use function str_contains;
use function str_replace;
use function trailingslashit;
use function trim;
use function ucwords;
use function WP_Filesystem;
use function wp_mkdir_p;
use function wp_parse_url;

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
function replace_template_blocks( string $html = '' ): string {
	return str_replace(
		[
			'wp:blockify/template-part',
			'wp:blockify/pattern',
			'wp:blockify/post-content',
			'"constrained":true',
		],
		[
			'wp:template-part',
			'wp:pattern',
			'wp:post-content',
			'"layout":{"type":"constrained"}',
		],
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

add_action( 'save_post_wp_block', NS . 'export_pattern', 10, 3 );
/**
 * Handles export pattern request.
 *
 * @since 0.0.1
 *
 * @param ?WP_Post $post    The post object.
 * @param bool     $update  Whether this is an existing post being updated or
 *                          not.
 *
 * @param int      $post_ID The post ID.
 *
 * @return int
 */
function export_pattern( int $post_ID, ?WP_Post $post, bool $update ): int {
	if ( ! $update ) {
		return $post_ID;
	}

	if ( ! $post ) {
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
	$content     = replace_template_blocks( $content );
	$content     = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content );
	$content     = apply_filters( 'blockify_pattern_export_content', $content, $post, $category );

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

	$pattern_dir = get_pattern_dir( $post, $content );

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
