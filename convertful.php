<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Plugin Name: Convertful MailChimp Forms
 * Version: 1.2
 * Plugin URI: https://convertful.com/
 * Description: Acquire leads using smart targeted sign-up forms. Works with MailChimp and all other major email services
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
	if ( $hook !== 'tools_page_conv-settings' ) {
		return;
	}

	global $conv_uri, $conv_version;
	wp_enqueue_style( 'conv-main', $conv_uri . '/css/main.css', array(), $conv_version );
	wp_enqueue_script( 'conv-main', $conv_uri . '/js/main.js', array( 'jquery' ), $conv_version );
}

add_action( 'activated_plugin', 'conv_activated_plugin' );
function conv_activated_plugin( $plugin ) {
	global $conv_file;
	if ( $plugin !== plugin_basename( $conv_file ) ) {
		return;
	}
	// Taking into account promotional links
	$ref_data = get_transient( 'convertful-ref' );
	if ( $ref_data AND strpos( $ref_data, '|' ) !== FALSE ) {
		$ref_data = explode( '|', $ref_data );
		// Preventing violations with lifetime values
		if ( time() - intval( $ref_data[1] ) < DAY_IN_SECONDS ) {
			update_option( 'convertful_ref', $ref_data[0], FALSE );
		}
		delete_transient( 'convertful-ref' );
	}
	$owner_id = get_option( 'convertful_owner_id' );
	if ( $owner_id === FALSE ) {
		$redirect_location = admin_url( 'tools.php?page=conv-settings' );
		if ( wp_doing_ajax() ) {
			wp_send_json_success(
				array(
					'location' => $redirect_location,
				)
			);
		}
		wp_redirect( $redirect_location );
		exit;
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'conv_plugin_action_links' );
function conv_plugin_action_links( $links ) {
	return array_merge(
		array(
			'<a href="' . admin_url( 'tools.php?page=conv-settings' ) . '">' . __( 'Settings' ) . '</a>',
		), $links
	);
}


register_uninstall_hook( $conv_file, 'conv_uninstall' );
function conv_uninstall() {
	// Options cleanup
	foreach ( array( 'owner_id', 'site_id', 'website_id', 'token', 'ref' ) as $option_name ) {
		delete_option( 'optinguru_' . $option_name );
		delete_option( 'convertful_' . $option_name );
	}
}


add_action('wp_head','conv_variables');
function conv_variables()
{
	$tags = array();
	foreach (get_the_tags() as $tag){
		$tags[$tag->slug] = $tag->name;
	}
	$categories = array();
	foreach (get_the_category() as $category){
		$categories[$category->slug] = $category->name;
	}
	$user_meta = wp_get_current_user();
	$variables = array(
		'url' => admin_url('admin-ajax.php'),
		'tags' => $tags,
		'categories' => $categories,
		'user_roles' => ($user_meta instanceof WP_User)? $user_meta->roles: [],
		'type' => get_post_type(),
	);
	echo '<script type="text/javascript">window.conv_page_vars='.json_encode($variables).';</script>';
}

if( wp_doing_ajax() OR defined('DOING_AJAX') ){
	add_action('wp_ajax_conv_get_info', 'conv_get_info');
	add_action('wp_ajax_nopriv_conv_get_info', 'conv_get_info');

	function conv_get_info(){

		$tags = array();
		foreach (get_tags() as $tag){
			$tags[$tag->slug] = $tag->name;
		}

		$categories = array();
		foreach (get_categories() as $category){
			$categories[$category->slug] = $category->name;
		}

		wp_send_json_success([
			'tags' => $tags,
			'categories' => $categories,
		]);

		wp_die();
	}
}


