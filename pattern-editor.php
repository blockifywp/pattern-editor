<?php
/**
 * Plugin Name:  Pattern Editor
 * Plugin URI:   https://blockifywp.com/pattern-editor
 * Description:  Import, export and edit patterns in the block editor and save directly to your theme.
 * Author:       Blockify
 * Author URI:   https://blockifywp.com/
 * Version:      0.0.3
 * License:      GPLv2-or-Later
 * Requires WP:  6.1
 * Requires PHP: 7.4
 * Text Domain:  pattern-editor
 */

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use const PHP_VERSION;
use function add_action;
use function dirname;
use function is_readable;
use function plugin_basename;
use function version_compare;

const NS   = __NAMESPACE__ . '\\';
const DS   = DIRECTORY_SEPARATOR;
const DIR  = __DIR__ . DS;
const FILE = __FILE__;

if ( ! version_compare( '7.4.0', PHP_VERSION, '<=' ) ) {
	return;
}

add_action( 'plugins_loaded', NS . 'load_textdomain' );
/**
 * Load textdomain.
 *
 * @since 0.0.1
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain(
		'pattern-editor',
		false,
		dirname( plugin_basename( FILE ) ) . '/languages'
	);
}

add_action( 'after_setup_theme', NS . 'setup', 9 );
/**
 * Load plugin files.
 *
 * @since 0.0.1
 *
 * @return void
 */
function setup(): void {
	$files = [
		'utility',
		'admin',
		'cpt',
		'export',
		'import',
		'template',
	];

	foreach ( $files as $file ) {
		$path = DIR . 'includes/' . $file . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
