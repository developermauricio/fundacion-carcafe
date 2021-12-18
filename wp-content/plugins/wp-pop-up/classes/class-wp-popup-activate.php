<?php
/**
 * Perform a database update to migrate `smart_overlay` posts types to `wp_popup` post types.
 * PHP Version 5
 *
 * @since 1.1
 * @package WP_Popup
 * @author Cornershop Creative <devs@cshp.co>
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not allowed' );
}

/**
 * Perform all of the plugin activation functions
 *
 * Class WP_Popup_Activate
 */
class WP_Popup_Activate {

	/**
	 * On activation, update any old smart_overlay post types to work with this plugin
	 */
	public static function activate() {
		if ( self::check_if_smart_overlays_exist() ) {
			self::update_smart_overlay_posts();
			self::update_smart_overlay_metas();
			if ( class_exists( 'FLBuilderModel' ) ) {
				self::beaver_flush_rewrites();
			}
		}
	}

	/**
	 * Check if there are old smart_overlay post types
	 *
	 * @return bool
	 */
	private static function check_if_smart_overlays_exist() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'smart_overlay'", OBJECT );

		if ( empty( $results ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Update smart_overlay post types to wp_popup
	 */
	private static function update_smart_overlay_posts() {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'posts',
			array(
				'post_type' => 'wp_popup',
			),
			array(
				'post_type' => 'smart_overlay',
			),
			array(
				'%s',
			)
		);
	}

	/**
	 * Update all of the smart_overlay meta_keys to wp_popup meta_keys
	 *
	 * @link https://gist.github.com/zanematthew/3199265
	 */
	private static function update_smart_overlay_metas() {
		global $wpdb;

		$old_prefix = 'smart_overlay_';
		$new_prefix = 'wp_popup_';

		$old_keys = array(
			'overlay_identifier',
			'display_lightbox_on',
			'suppress',
			'trigger',
			'max_height',
			'min_height',
			'disable_on_mobile',
			'bg_image_id',
			'bg_image',
			'trigger_amount',
			'max_width',
		);

		foreach ( $old_keys as $key ) {
			// Used by the $wpdb->update() as the replacement data
			$new_postmeta_row = array(
				'meta_key' => $new_prefix . $key,
			);

			// Used by the $wpdb->update() as the data to replace
			$old_postmeta_row = array(
				'meta_key' => $old_prefix . $key,
			);

			// In this plugin, the `overlay_identifier` meta_key from Smart Overlay got changed to be `identifier`
			if ( 'overlay_identifier' === $key ) {
				$new_postmeta_row = array(
					'meta_key' => $new_prefix . 'identifier',
				);
			}

			// Perform the db update
			$wpdb->update(
				$wpdb->prefix . 'postmeta',
				$new_postmeta_row,
				$old_postmeta_row,
				array( '%s' )
			);
		}//end foreach
	}

	/**
	 * Add for compatibility with Beaver Builder since we need a wp_popup post type to be publicly queryable
	 * to be editable with Beaver Builder. Popups are now publicly queryable since version 1.1.6.
	 *
	 * @since 1.1.6
	 */
	public static function beaver_flush_rewrites() {
		flush_rewrite_rules();
	}
}
