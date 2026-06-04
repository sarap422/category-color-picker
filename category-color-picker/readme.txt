=== Category Color Picker ===
Contributors: sarap422
Tags: category, color, picker, noindex, styling, css
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a color picker to WordPress categories and reflect category colors in post listings and links. Includes per-category noindex settings.

== Description ==

Category Color Picker lets you assign a color to each WordPress category and automatically applies that color to post listings and category links on the front end.

= Main features =

* Adds a color picker to the category edit screen
* Automatically reflects the chosen color on the front end
* Automatic text color adjustment based on relative luminance
* Customizable CSS selectors
* Adds "Color", "ID" and "Noindex" columns to the category list
* Sortable by the ID column
* Per-category noindex setting (applies to the category archive, single posts in that category, and tag/date archives that contain those posts)
* JSON export / import of categories (including color and noindex settings)

= Compatible plugins and themes =

* VK All in One Expansion Unit
* Content Views
* General WordPress themes
* Any element via custom selectors

= Usage =

1. Activate the plugin
2. Go to Posts > Categories and edit a category
3. Pick a color with the color picker
4. Optionally enable the "Noindex" checkbox
5. Optionally customize selectors under Settings > Category Color

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/category-color-picker` directory
2. Activate the plugin through the Plugins menu in WordPress
3. Set the color and noindex for each category under Posts > Categories

== Frequently Asked Questions ==

= Does it work with any theme? =

Yes. It works with any theme that uses the standard WordPress category output. With custom selectors, it can also support specific themes and plugins.

= Can it replace VK All in One Expansion Unit? =

Yes. It can be used as an alternative to the category color feature of VK All in One Expansion Unit, with more flexible selector settings.

= Can I customize the selectors? =

Yes. You can freely set CSS selectors under Settings > Category Color.

= Which pages does noindex apply to? =

It outputs `<meta name="robots" content="noindex" />` on the category archive page, single posts belonging to that category, and tag/date archive pages that contain posts in the target category.

= Where can I check the noindex setting? =

You can check it in the "Noindex" column of the category list. Categories with the setting enabled show "noindex" in red.

== Screenshots ==

1. Color picker and noindex setting on the category edit screen
2. Color, ID and Noindex columns in the category list
3. Selector settings screen
4. Category colors reflected on the front end

== Changelog ==

= 1.2.0 =
* Added JSON export / import for categories (buttons placed at the top right of the category list screen)
* Export covers all categories (name, slug, parent, description, color, noindex)
* Import resolves categories by slug; for existing categories only color and noindex are overwritten, missing categories are created
* Separated logic into includes/ (pure logic is unit tested)

= 1.1.0 =
* Added "ID" and "Noindex" columns to the category list
* Reordered columns to Name, Slug, Color, Description, Count, ID, Noindex
* Made the ID column sortable
* Added noindex feature (outputs meta robots noindex per category)

= 1.0.6 =
* Excluded tags from the target

= 1.0.5 =
* Switched to wp_enqueue_style() for CSS output (Plugin Check compliance)
* Adjusted the text color luminance threshold to 0.6 for better readability
* Translated plugin descriptions and messages into Japanese
* Code optimization and compliance with WordPress standards

= 1.0.4 =
* Bug fixes and stability improvements
* Improved CSS selector handling
* Improved error handling

= 1.0.3 =
* Initial release
* Color picker integration
* Automatic text color adjustment
* Customizable CSS selectors
