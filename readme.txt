=== Pattern Editor ===
Contributors: blockifywp
Donate link: https://blockifywp.com/
Tags: pattern, editor, block, gutenberg, block editor, blockify
Requires at least: 6.3
Tested up to: 6.5
Stable tag: 0.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import, export and edit patterns with the Block Pattern custom post type with additional settings and UI features that makes working with WordPress block patterns easier and faster. This plugin has minimal settings, and when active, will automatically export patterns to the active themes patterns directory on save. Perfect for block theme developers and designers.

== Description ==

Pattern Editor is a plugin to help you create and edit patterns for WordPress block themes. It automatically exports patterns on save as PHP files to the active themes `patterns` directory.

It works with the built-in `wp_block` post type and adds some minimal UI features to make working with patterns easier. The new Block Pattern editor UI in the Site Editor is supported, and the `wp_block` post type screen is exposed in the admin menu for quick access.

= Features =

* Exports assets (images, SVGs, videos) to the active theme's `assets` directory.
* Pattern front end previews.
* Export patterns as PHP files.
* Import patterns from PHP files.
* Edit patterns in the block editor.
* Can export to category subdirectories or a single directory.
* Supports the new Block Pattern editor UI in the Site Editor.

= Filters =

`blockify_pattern_export_use_category_dirs` - Enable or disable the use of category directories for pattern exports. Default is `true`. If enabled, patterns will be exported to `patterns/{category}/{pattern-name}.php`. If disabled, patterns will be exported to `patterns/{category}-{pattern-name}.php`.

`blockify_image_export_dir` - Set the directory where images and assets are exported to. Default is `themes/$stylesheet/assets`. Images are saved to the `img` subdirectory, SVGs to the `svg` subdirectory, and videos to the `video` subdirectory.

`blockify_pattern_export_content` - Allows you to modify the content of the exported pattern file. The first parameter is the content of the pattern file, the second parameter is the pattern post object, and the third parameter is the pattern category slug.

More documentation coming soon.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'Debug Toolkit'
3. Activate Debug Toolkit from your Plugins page.

== Frequently Asked Questions ==

= How does this plugin work? =

Pattern Editor automatically exports patterns on save as PHP files to the active themes `patterns` directory. It works with the built-in `wp_block` post type and adds some minimal UI features to make working with patterns easier.

= Who is this plugin for? =

This plugin is designed to be used by developers and theme authors.

== Screenshots ==

1. Block Patterns post type edit screen
2. Site Editor block pattern screen
3. Edit pattern screen

== ChangeLog ==

= 0.2.0 - 1 May 2024 =

* Add: Template part block
* Add: Static pattern block
* Add: Post content block

= 0.1.1 - 29 March 2024 =

* Remove: Blocks (temporarily)

= 0.1.0 - 29 March 2024 =

* Update: Stable version
* Add: Escape exported URLs
* Add: Use category dirs filter
* Add: Basic pattern canvas implementation
* Fix: Import pattern category title acronyms

= 0.0.3 - 2 April 2023 =

* Update: Stable version

= 0.0.2 - 21 March 2023 =

* Remove: Navigation menu ref attribute

= 0.0.1 - 21 March 2023 =

* Initial release
