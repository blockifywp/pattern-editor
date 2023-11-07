<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function __;
use function class_exists;

if ( class_exists( 'WP_CLI' ) ) {
	\WP_CLI::add_command(
		'blockify export-patterns',
		static fn() => export_patterns_cli()
	);
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function export_patterns_cli(): void {
	$synced_patterns = get_reusable_blocks();

	foreach ( $synced_patterns as $synced_pattern ) {
		export_pattern( $synced_pattern->ID, $synced_pattern, true );

		\WP_CLI::success(
			__( 'Exported pattern: ', 'pattern-editor' ) . $synced_pattern->post_title
		);
	}
}
