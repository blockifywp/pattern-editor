<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use WP_Block_Patterns_Registry;
use function add_action;
use function esc_html_e;
use function flush_rewrite_rules;
use function get_page_by_path;
use function get_post;
use function get_stylesheet;
use function sanitize_text_field;
use function str_replace;
use function ucwords;
use function wp_slash;
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
	$registered = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
	$stylesheet = get_stylesheet();

	foreach ( $registered as $pattern ) {
		$theme = $pattern['theme'] ?? null;

		if ( $theme !== $stylesheet ) {
			continue;
		}

		$category       = $pattern['categories'][0] ?? 'uncategorized';
		$category_title = ucwords( $category );
		$post_title     = str_replace( $category_title . ' ', '', $pattern['title'] );

		if ( $category === 'cta' ) {
			$category_title = 'CTA';
		}

		if ( $category === 'faq' ) {
			$category_title = 'FAQ';
		}
		
		$args = [
			'post_name'    => str_replace( $category, '-', $pattern['slug'] ),
			'post_title'   => $category_title . ' ' . $post_title,
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
