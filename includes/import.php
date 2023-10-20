<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function add_action;
use function admin_url;
use function basename;
use function esc_html_e;
use function explode;
use function flush_rewrite_rules;
use function get_page_by_path;
use function get_stylesheet_directory;
use function glob;
use function ob_get_clean;
use function ob_start;
use function sanitize_text_field;
use function str_replace;
use function term_exists;
use function ucwords;
use function wp_insert_term;
use function wp_nonce_url;
use function wp_safe_redirect;
use function wp_unslash;

add_action( 'admin_post_blockify_import_patterns', NS . 'import_patterns', 11 );
/**
 * Imports all registered patterns as posts.
 *
 * @since 1.0.0
 *
 * @return void
 */
function import_patterns(): void {
	$patterns = [
		...glob( get_stylesheet_directory() . '/patterns/*.php' ),
		...glob( get_stylesheet_directory() . '/patterns/**/*.php' ),
	];

	foreach ( $patterns as $pattern ) {
		$name     = basename( $pattern, '.php' );
		$title    = ucwords( str_replace( [ '-', '_' ], ' ', $name ) );
		$category = explode( '-', $name )[0] ?? null;

		if ( ! $category || $category === 'template' ) {
			continue;
		}

		ob_start();
		include $pattern;
		$content = ob_get_clean();

		$args = [
			'post_name'    => $name,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'block_pattern',
			'tax_input'    => [
				'pattern_category' => [ $category ],
			],
		];

		if ( get_page_by_path( $name, OBJECT, 'block_pattern' ) ) {
			continue;
		}

		if ( ! term_exists( $category ) ) {
			wp_insert_term(
				ucwords( str_replace( '-', ' ', $category ) ),
				'pattern_category',
				[
					'slug' => $category,
				]
			);
		}

		wp_insert_post( $args );
	}

	flush_rewrite_rules();

	wp_safe_redirect(
		wp_nonce_url(
			admin_url( 'edit.php?post_type=block_pattern' ),
			'blockify_import_patterns'
		)
	);
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
	$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, 'blockify_import_patterns' ) ) {
		return;
	}

	$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ?? '' ) );

	if ( $post_type !== 'block_pattern' ) {
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

