<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Plugin Name: Convertful MailChimp Forms
 * Version: 1.0
 * Plugin URI: https://convertful.com/
 * Description: Convert visitors to subscribers with targeted pop-ups, sideboxes, bars and inlines. Works with MailChimp, ConvertKit, AWeber, GetResponse & others
 * Author: Convertful
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: convertful
 */

// Global variables for plugin usage (global declaration is needed here for WP CLI compatibility)
global $conv_file, $conv_dir, $conv_uri, $conv_version, $conv_domain;
//$conv_domain = 'http://convertful.local';
$conv_domain = 'https://app.convertful.com';
$conv_file = __FILE__;
$conv_dir = plugin_dir_path( __FILE__ );
$conv_uri = plugins_url( '', __FILE__ );
$conv_version = preg_match( '~Version\: ([^\n]+)~', file_get_contents( __FILE__, NULL, NULL, 82, 150 ), $conv_matches ) ? $conv_matches[1] : FALSE;
unset( $conv_matches );

add_action( 'init', 'conv_init' );
function conv_init() {
	if ( get_option( 'optinguru_owner_id' ) ) {
		update_option( 'convertful_owner_id', get_option( 'optinguru_owner_id' ), TRUE );
		update_option( 'convertful_site_id', get_option( 'optinguru_site_id', get_option( 'optinguru_website_id' ) ), FALSE );
		update_option( 'convertful_token', get_option( 'optinguru_token' ), FALSE );
	}
	$owner_id = get_option( 'convertful_owner_id' );
	if ( ! is_admin() AND $owner_id !== FALSE ) {
		add_action( 'wp_enqueue_scripts', 'conv_enqueue_scripts' );
		add_filter( 'script_loader_tag', 'conv_script_loader_tag', 10, 2 );
	}
}

if ( is_admin() ) {
	require $conv_dir . 'functions/admin_pages.php';
}

// Shortcodes
require $conv_dir . 'functions/shortcodes.php';

function conv_enqueue_scripts() {
	global $conv_domain, $conv_version;
	wp_enqueue_script( 'convertful-api', $conv_domain . '/Convertful.js', array(), $conv_version, TRUE );
}

function conv_script_loader_tag( $tag, $handle ) {
	if ( $handle !== 'convertful-api' ) {
		return $tag;
	}
	global $conv_domain;

	return '<script type="text/javascript" id="convertful-api" src="' . $conv_domain . '/Convertful.js" data-owner="' . get_option( 'convertful_owner_id' ) . '" async="async"></script>';
}

add_action( 'admin_enqueue_scripts', 'conv_admin_enqueue_scripts' );
function conv_admin_enqueue_scripts( $hook ) {
	if ( $hook !== 'tools_page_og-settings' ) {
		return;
	}

	global $conv_uri, $conv_version;
	wp_enqueue_style( 'conv-main', $conv_uri . '/css/main.css', array(), $conv_version );
	wp_enqueue_script( 'conv-main', $conv_uri . '/js/main.js', array( 'jquery' ), $conv_version );
}

add_action( 'activated_plugin', 'conv_activated_plugin' );
function conv_activated_plugin( $plugin ) {
	global $conv_file;
	if ( $plugin === plugin_basename( $conv_file ) ) {
		$owner_id = get_option( 'convertful_owner_id' );
		if ( $owner_id === FALSE ) {
			wp_redirect( admin_url( 'tools.php?page=og-settings' ) );
			exit;
		}
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'conv_plugin_action_links' );
function conv_plugin_action_links( $links ) {
	return array_merge(
		array(
			'<a href="' . admin_url( 'tools.php?page=og-settings' ) . '">' . __( 'Settings' ) . '</a>',
		), $links
	);
}


register_uninstall_hook( $conv_file, 'conv_uninstall' );
function conv_uninstall() {
	// Options cleanup
	foreach ( array( 'owner_id', 'site_id', 'website_id', 'token' ) as $option_name ) {
		delete_option( 'convertful_' . $option_name );
	}
}