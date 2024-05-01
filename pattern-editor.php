<?php
/**
 * Plugin Name: Pattern Editor
 * Plugin URI: https://blockifywp.com/pattern-editor
 * Author: Blockify
 * Author URI: https://blockifywp.com/
 * Version: 0.2.0
 * License: GPLv2-or-later
 * Requires WP: 6.3
 * Requires PHP: 7.4
 * Tested up to: 6.5
 * Text Domain: pattern-editor
 * Description: Import, export and edit block patterns in the Site Editor and save directly to your theme.
 */

namespace Blockify\PatternEditor;

use function add_action;
use function dirname;
use function glob;
use function load_plugin_textdomain;
use function plugin_basename;
use function version_compare;
use const PHP_VERSION;

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
 * Setup Pattern Editor.
 *
 * @return void
 */
function setup(): void {
	$files = [
		DIR . 'vendor/autoload.php',
		...glob( DIR . 'includes/*.php' ),
		...glob( DIR . 'includes/blocks/*.php' ),
	];

	foreach ( $files as $file ) {
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
