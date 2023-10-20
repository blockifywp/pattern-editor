<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function add_filter;

//add_filter( 'block_type_metadata', NS . 'filter_metadata_registration' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $metadata Array of block metadata.
 *
 * @return array
 */
function filter_metadata_registration( array $metadata ): array {
	if ( ( $metadata['name'] ?? '' ) === 'core/pattern' ) {
		$metadata['inserter'] = true;
	}

	return $metadata;
}
