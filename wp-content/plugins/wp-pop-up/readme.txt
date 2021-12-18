=== WP Popup ===
Contributors: drywallbmb, rxnlabs, dannycorner, rpasillas
Tags: modal window, popup, lightbox
Requires at least: 4.3
Tested up to: 5.7.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Looking for a new way to entice your site visitors? WP Popup is the lightbox/popup plugin built with performance in mind.

== Description ==

WP Popup is a plugin for implementing whatever you want to call them — modals, lightboxes, overlays or popups — on your site. While it offers fine-tuned control over where and when the lightboxes display, it was developed with the goal of being simple and lightweight: WP Popup won't cause your site to take a big performance hit by loading lots of complicated and extraneous CSS and JavaScript.

WP Popup lets you use the standard WordPress post editor to build and configure your popups. In addition to full WYSIWYG editing of popup content, WP Popup gives you powerful control over what triggers the appearance of your lightbox. Triggers can be set so popups show:

* Immediately on page load
* After a configurable number of seconds
* After the page is scrolled a configurable number of pixels
* After the page is scrolled halfway or to the bottom
* After the user has spent a configurable number of minutes on the site
* After the user has visited a configurable number of pages over the past 90 days
* On page exit (Exit intent)

In addition to those sophisticated trigger controls, you also get options on each popup for:

* Mask background color: Choose an appropriate color and opacity to set as the background of the mask that covers your site.
* Background image: Make a richer, more visually engaging popup by using a photo or illustration that fills the inside of the popup.
* Background color: Choose an appropriate color and opacity to set as the background of the popup.
* Width control: Set a minimum and maximum width.
* Height control: Set minimum and maximum values along with pixels or percentages.
* Padding: Control the padding within your popup.
* Border: Add a border of any color, width and radius.
* Opacity: Adjust the opacity of the popup.
* Where to display: Choose whether to display on your site's homepage, on all pages, on all pages except the homepage, or specific pages.
* Scheduling: Configure whether users should see the popup just once, all the time, or periodically based on a schedule.
* Mobile control: Avoid hits to your SEO by suppressing your popups from appearing on mobile devices!
* Cookie identifier: Easily change how browsers know about this popup so you don't have to save a whole new popup after fixing a typo if you want your updated popup to appear again.

*Note: This plugin uses cookies, so if you're bound by the EU or other regulations requiring you notify users of such, be sure to do so if you've got WP Popup enabled.*

**Interested in other plugins from Cornershop Creative? We've made [these things](https://cornershopcreative.com/products).**

== Installation ==

1. Upload the `wp-popup` directory to your plugins directory (typically wp-content/plugins)
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit WP Popup > Create New Popup to begin setting up your first popup.

== Frequently Asked Questions ==

= I am switching from your Smart Popup, will my popups still work? =

Yes! Upon activating the plugin your previous popups will display in the WP Popups menu item and will continue working as expected.

= How many popups can I create? =

As many as you want, though only one will show on any given URL, unless the `wp_popup_display` filter is used to specify a popup to display via the theme.

= What happens if more than one popup is set to appear on a given page? =

To avoid annoying your site users with multiple popups, WP Popup will only display the most recent one, unless the `wp_popup_display` filter is used to specify a popup to display via the theme.

`
// Example wp_popup_filter, show popup 1 if user is logged in, popup 2 if user is not logged in
function theme_filter_popups( $default_display, $display, $popup_id ) {
  if ( is_user_logged_in() && 1 === $popup_id )  {
    return true;
  } elseif ( ! is_user_logged_in() && 2 === $popup_id ) {
    return true;
  }

  return null;
}
add_filter( 'wp_popup_display', 'theme_filter_popups', 10, 3 );
`

= What styling and animation options are there? =

WP Popup was written to be lean & mean. It offers minimal styling out-of-the-box (just a small close X in the upper right corner) and no animation controls, so that it doesn't bloat your site with unnecessary code for different themes & styles you're not actually using. Of course, you're free to use the WYSIWYG and graft on your own custom CSS to change the appearance however you want!

= Will this work with Pagebuilder plugins? =

WP Popup has been tested with the following pagebuilders: Elementor, Beaver Builder, and WPBakery/Visual Composer. WP Popup may work with other pagebuilders but has not been tested for compatibility. While WP Popup may work with pagebuilder plugins, it was not designed as a pagebuilder add-on, so we cannot guarantee 100% compatibility.

== Changelog ==
= 1.2.4 =
* Add new setting for maximizing the Z-Index value.
* Add new Advanced settings panel and move some settings into it.
* Fix bug "undefined" displays in the class names when the "light" Close Button Theme is selected.
* Fix bug where newest popup was not displaying if there were multiples set on same post.

= 1.2.3 =
* New filter, wp_popup_display, to pick which popup to show. This allows themes to assign multiple popups to a post and then use the new filter to determine which popup to display.
* Enable the block editor.
* Remove the plugin sidebar from the wp-popup post types.
* Fix bug where having Beaver Builder installed prevented popup's content from displaying.
* Fix PHP 8 Notice with CMB2, update CMB2.

= 1.2.2 =
* Bugfix to no longer require fields that are hidden in the WP admin.
* Accessibility improvements addressing issues with tabindex, CSS outlines, and aria attribute placement.
* Removed problematic content filtering when running on a site using Elementor.
* The behavior of the 'Disable "the_content" filters' checkbox has changed: when this box is checked in a popup's settings, WP Popup will use its own filter hook on popup content, called `wp_popup_content`, instead of changing which callbacks are bound to `the_content`. This fixes potential PHP errors that could have occurred on sites using Fusion Builder, and possibly other plugins as well.
* Added aria roles for increased a11y; fixed errant PHP notice.

= 1.2.1 =
* Update the disabling of the block editor. The hook that allowed post types to disable the block editor was updated in WordPress version 5.

= 1.2.0 =
* WP Popups can now be used from within posts or pages. This provides a capability to override certain settings and display the Popup in the post/page single view.
* Options were added for close buttons including light/dark, X vs. "close", and an option to click the overlay to close the popup.
* Impressions and conversions from WP Popup can now be tracked in Google Analytics and defined in custom events.
* Start and end dates can now be added to popups.
* A checkbox to disable/enable third party 'the_content' hooks from running on the WP Popups content was added. This helps with plugins that inject code into content, stopping the plugins from doing the same on the popup content.
* Preview displays for WP Popup content were improved.

= 1.1.6 =
* Added the ability to use WP Popup with Beaver Builder. Edit popups with Beaver Builder.

= 1.1.5 =
* Fix bug undefined function get_plugin_data().

= 1.1.4 =
* Fix bug where popups would not work on Elementor pages. The popups would show the post content (including Elementor styles) in the popup instead of showing the popup's content.

= 1.1.3 =
* Fix minor styling issue with spinning loading gif in the admin.

= 1.1.2 =
* New option: Select posts to display popups on.

= 1.1.1 =
* Update bundled version of CMB2 to version 2.6.0.
* Fix PHP Notice regarding undefined property modal_outer_has_set_style_properties.

= 1.1 =
* Add activation functions that migrate Smart Popups to work with this plugin.

= 1.0 =
* Rename plugin from Smart Overlay to Smart Popup
* Re-Arranged options into groups, styles for the popup inside, styles for the popup outside and display options.
* New option: Background Color for outer mask.
* New option: Background color for inner popup.
* New option: Max Height
* New option: Min Height
* New option: Padding
* New option: Borders
* New option: Opacity
* Disable Gutenberg editor for popups.

= 0.8.1 =
* Bugfixes for overlay styling and file inclusion.

= 0.8 =
* Refactoring entire codebase to be object-oriented in preparation for future features; no other functional changes.

= 0.7 =
* Refactoring mobile check to occur on front-end rather than with wp_is_mobile() to get around caching issues.
* Updating Featherlight library from 1.2.3 to 1.7.8.

= 0.6 =
* Initial public release.
