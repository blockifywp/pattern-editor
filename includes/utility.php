<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

/**
 * Returns part of string between two strings.
 *
 * @since 0.0.1
 *
 * @param string $start  Start string.
 * @param string $end    End string.
 * @param string $string String content.
 * @param bool   $omit   Omit start and end.
 *
 * @return string
 */
function str_between( string $start, string $end, string $string, bool $omit = false ): string {
	$string = ' ' . $string;
	$ini    = strpos( $string, $start );

	if ( $ini === 0 ) {
		return '';
	}

	$ini += strlen( $start );

	if ( strlen( $string ) < $ini ) {
		$ini = 0;
	}

	$len    = strpos( $string, $end, $ini ) - $ini;
	$string = $start . substr( $string, $ini, $len ) . $end;

	if ( $omit ) {
		$string = str_replace( [ $start, $end ], '', $string );
	}

	return $string;
}
