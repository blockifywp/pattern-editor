<?php

declare( strict_types=1 );

namespace Blockify\PatternEditor;

use function __;
use function add_action;
use function function_exists;
use function remove_filter;

add_action( 'init', NS . 'register_pattern_cpt' );
/**
 * Registers block pattern custom post type.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_pattern_cpt() : void {
	$labels = [
		'name'                  => _x( 'Block Patterns', 'Block Pattern General Name', 'pattern-editor' ),
		'singular_name'         => _x( 'Block Pattern', 'Block Pattern Singular Name', 'pattern-editor' ),
		'menu_name'             => __( 'Block Patterns', 'pattern-editor' ),
		'name_admin_bar'        => __( 'Block Pattern', 'pattern-editor' ),
		'archives'              => __( 'Item Archives', 'pattern-editor' ),
		'attributes'            => __( 'Item Attributes', 'pattern-editor' ),
		'parent_item_colon'     => __( 'Parent Item:', 'pattern-editor' ),
		'all_items'             => __( 'All Items', 'pattern-editor' ),
		'add_new_item'          => __( 'Add New Item', 'pattern-editor' ),
		'add_new'               => __( 'Add New', 'pattern-editor' ),
		'new_item'              => __( 'New Item', 'pattern-editor' ),
		'edit_item'             => __( 'Edit Item', 'pattern-editor' ),
		'update_item'           => __( 'Update Item', 'pattern-editor' ),
		'view_item'             => __( 'View Item', 'pattern-editor' ),
		'view_items'            => __( 'View Items', 'pattern-editor' ),
		'search_items'          => __( 'Search Item', 'pattern-editor' ),
		'not_found'             => __( 'Not found', 'pattern-editor' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'pattern-editor' ),
		'featured_image'        => __( 'Featured Image', 'pattern-editor' ),
		'set_featured_image'    => __( 'Set featured image', 'pattern-editor' ),
		'remove_featured_image' => __( 'Remove featured image', 'pattern-editor' ),
		'use_featured_image'    => __( 'Use as featured image', 'pattern-editor' ),
		'insert_into_item'      => __( 'Place into item', 'pattern-editor' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'pattern-editor' ),
		'items_list'            => __( 'Items list', 'pattern-editor' ),
		'items_list_navigation' => __( 'Items list navigation', 'pattern-editor' ),
		'filter_items_list'     => __( 'Filter items list', 'pattern-editor' ),
	];

	$args = [
		'label'               => __( 'Block Pattern', 'pattern-editor' ),
		'description'         => __( 'Block Pattern Description', 'pattern-editor' ),
		'labels'              => $labels,
		'supports'            => [ 'title', 'editor' ],
		'taxonomies'          => [ 'pattern_category' ],
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => false,
		'menu_position'       => 5,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'show_in_rest'        => true,
		'capability_type'     => 'page',
	];

	register_post_type( 'block_pattern', $args );
}

add_action( 'init', NS . 'register_pattern_category_taxonomy' );
/**
 * Registers block pattern category taxonomy.
 *
 * @since 0.0.1
 *
 * @return void
 */
function register_pattern_category_taxonomy() : void {
	$labels = [
		'name'                       => _x( 'Pattern Category', 'Pattern Category General Name', 'pattern-editor' ),
		'singular_name'              => _x( 'Pattern Category', 'Pattern Category Singular Name', 'pattern-editor' ),
		'menu_name'                  => __( 'Pattern Category', 'pattern-editor' ),
		'all_items'                  => __( 'All Items', 'pattern-editor' ),
		'parent_item'                => __( 'Parent Item', 'pattern-editor' ),
		'parent_item_colon'          => __( 'Parent Item:', 'pattern-editor' ),
		'new_item_name'              => __( 'New Item Name', 'pattern-editor' ),
		'add_new_item'               => __( 'Add New Item', 'pattern-editor' ),
		'edit_item'                  => __( 'Edit Item', 'pattern-editor' ),
		'update_item'                => __( 'Update Item', 'pattern-editor' ),
		'view_item'                  => __( 'View Item', 'pattern-editor' ),
		'separate_items_with_commas' => __( 'Separate items with commas', 'pattern-editor' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'pattern-editor' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'pattern-editor' ),
		'popular_items'              => __( 'Popular Items', 'pattern-editor' ),
		'search_items'               => __( 'Search Items', 'pattern-editor' ),
		'not_found'                  => __( 'Not Found', 'pattern-editor' ),
		'no_terms'                   => __( 'No items', 'pattern-editor' ),
		'items_list'                 => __( 'Items list', 'pattern-editor' ),
		'items_list_navigation'      => __( 'Items list navigation', 'pattern-editor' ),
	];

	$args = [
		'labels'            => $labels,
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'show_in_rest'      => true,
	];

	register_taxonomy( 'pattern_category', [ 'block_pattern' ], $args );
}

// Gutenberg compat.
if ( function_exists( 'filter_block_pattern_response' ) ) {
	remove_filter( 'rest_prepare_block_pattern', 'filter_block_pattern_response' );
}
