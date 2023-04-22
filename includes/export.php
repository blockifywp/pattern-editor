<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Post;
use function __;
use function add_action;
use function add_filter;
use function admin_url;
use function apply_filters;
use function basename;
use function dirname;
use function esc_html_e;
use function explode;
use function file_exists;
use function flush_rewrite_rules;
use function get_home_url;
use function get_stylesheet;
use function get_template_directory;
use function home_url;
use function in_array;
use function is_array;
use function preg_match_all;
use function sanitize_text_field;
use function str_contains;
use function str_replace;
use function trailingslashit;
use function trim;
use function WP_Filesystem;
use function wp_mkdir_p;
use function wp_nonce_url;
use function wp_parse_url;
use function wp_safe_redirect;
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
	$block_patterns = get_posts(
		[
			'post_type'      => 'block_pattern',
			'posts_per_page' => - 1,
		]
	);

	foreach ( $block_patterns as $block_pattern ) {
		export_pattern( $block_pattern->ID, $block_pattern, true );
	}

	$action = 'blockify_export_patterns';

	wp_safe_redirect(
		wp_nonce_url(
			admin_url( "edit.php?post_type=block_pattern&=$action=true" ),
			$action,
		)
	);
}

add_action( 'admin_notices', NS . 'pattern_export_success_notice' );
/**
 * Admin notice for pattern export.
 *
 * @since 1.0.0
 *
 * @return void
 */
function pattern_export_success_notice(): void {
	$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, 'blockify_export_patterns' ) ) {
		return;
	}

	$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ?? '' ) );

	if ( $post_type !== 'block_pattern' ) {
		return;
	}

	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<?php esc_html_e( 'Patterns successfully exported.', 'pattern-editor' ); ?>
		</p>
	</div>
	<?php
}

add_filter( 'save_post_block_pattern', NS . 'export_pattern', 10, 3 );
/**
 * Handles export pattern request.
 *
 * @param int     $post_ID The post ID.
 * @param WP_Post $post    The post object.
 * @param bool    $update  Whether this is an existing post being updated or not.
 *
 * @since 0.0.1
 *
 * @return integer
 */
function export_pattern( int $post_ID, WP_Post $post, bool $update ): int {
	if ( ! $update ) {
		return $post_ID;
	}

	if ( $post->post_status === 'trash' ) {
		return $post_ID;
	}

	$categories = get_the_terms( $post_ID, 'pattern_category' ) ?? [];
	$category   = $categories[0]->slug ?? null;

	if ( ! $category ) {
		$category = explode( '-', $post->post_name )[0] ?? null;
	}

	if ( ! $category ) {
		return $post_ID;
	}

	$stylesheet  = get_stylesheet();
	$default_dir = $stylesheet === 'blockify' ? 'themes/blockify/patterns/default' : "themes/$stylesheet/patterns";
	$content_dir = dirname( get_template_directory(), 2 );
	$default_dir = apply_filters( 'blockify_pattern_export_dir', $default_dir );
	$pattern_dir = $content_dir . DS . trailingslashit( $default_dir );
	$content     = replace_nav_menu_refs( $post->post_content );
	$content     = replace_image_paths( $content, $content_dir );
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

	$data = <<<EOF
<?php
/**
 * Title: $post->post_title
 * Slug: $post->post_name
 * Categories: $category
 * $block_types
 */
?>
$content
EOF;

	if ( ! file_exists( $pattern_dir ) ) {
		wp_mkdir_p( $pattern_dir );
	}

	global $wp_filesystem;

	if ( ! $wp_filesystem ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	$wp_filesystem->put_contents(
		$pattern_dir . $post->post_name . '.php',
		$data
	);

	flush_rewrite_rules();

	return $post_ID;
}

/**
 * Removes nav menu references from pattern content.
 *
 * @param string $html The HTML content.
 *
 * @since 0.0.1
 *
 * @return string
 */
function replace_nav_menu_refs( string $html ): string {
	$ref = str_between(
		'"ref":',
		',',
		$html
	);

	if ( $ref ) {
		$html = str_replace( $ref, '', $html );
	}

	return $html;
}

/**
 * Replaces image paths with theme URI.
 *
 * @param string $html        The HTML content.
 * @param string $content_dir The content directory.
 *
 * @since 1.0.0
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
		} elseif ( $type === 'mp4' || $type === 'mov' ) {
			$sub_dir = 'video';
		} elseif ( $type === 'gif' ) {
			$sub_dir = 'gif';
		} else {
			$sub_dir = 'img';
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
