<?php
/**
Plugin Name: WP Pop-up
Description: Show a highly-configurable popup for your pages to encourage donations, actions. etc.
Version: 1.2.4
Author: Cornershop Creative
Author URI: https://cornershopcreative.com
License: GPLv2 or later
Text Domain: wp-popup
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not allowed' );
}


/**
 * Load Plugin Version Number
 *
 * @since 1.1.6
 */
if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$wp_popup_plugin_data = get_plugin_data( __FILE__, true );
define( 'WP_POPUP_VERSION', $wp_popup_plugin_data['Version'] );
// remove from global so we don't pollute global
unset( $wp_popup_plugin_data );

require_once dirname( __FILE__ ) . '/classes/class-wp-popup.php';

require_once dirname( __FILE__ ) . '/classes/class-wp-popup-activate.php';

/**
 * Perform any activation functions
 *
 * @since 1.1
 */
register_activation_hook( __FILE__, array( 'WP_Popup_Activate', 'activate' ) );


/**
 * Kick things off by hooking into `plugins_loaded`
 */
function run_wp_popup() {
	$wp_popup_plugin = new WP_Popup();
	$wp_popup_plugin->init();
}
add_action( 'plugins_loaded', 'run_wp_popup' );
