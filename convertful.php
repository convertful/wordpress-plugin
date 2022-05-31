<?php defined( 'ABSPATH' ) or die( 'This script cannot be accessed directly.' );

/**
 * Plugin Name: Convertful - Your Ultimate On-Site Conversion Tool
 * Version: 2.5
 * Plugin URI: https://convertful.com/
 * Description: All the modern on-site conversion solutions, natively integrates with all modern Email Marketing
 * Platforms. Author: Convertful Author URI: https://convertful.com License: GPLv2 or later License URI:
 * http://www.gnu.org/licenses/gpl-2.0.html Text Domain: convertful
 */

// Global variables for plugin usage (global declaration is needed here for WP CLI compatibility)
global $conv_file, $conv_dir, $conv_uri, $conv_version, $conv_config;
$conv_file    = __FILE__;
$conv_dir     = plugin_dir_path( __FILE__ );
$conv_uri     = plugins_url( '', __FILE__ );
$conv_version = preg_match( '~Version: ([^\n]+)~', file_get_contents( __FILE__, NULL, NULL, 82, 150 ), $conv_matches ) ? $conv_matches[1] : FALSE;
unset( $conv_matches );

if ( file_exists( $conv_dir . 'config.php' ) ) {
	$conv_config = require $conv_dir . 'config.php';
}

add_action( 'init', 'conv_init' );
if ( is_admin() ) {
	require $conv_dir . 'functions/admin_pages.php';
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'conv_plugin_action_links' );

// Shortcodes
require $conv_dir . 'functions/shortcodes.php';
register_uninstall_hook( $conv_file, 'conv_uninstall' );

add_action( 'rest_api_init', function () {
	register_rest_route( 'convertful/v2', '/complete_authorization/', array(
		'methods'             => 'POST',
		'callback'            => 'conv_complete_authorization',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'convertful/v2', '/get_info/', array(
		'methods'             => 'POST',
		'callback'            => 'conv_get_info',
		'permission_callback' => '__return_true',
	) );
} );

function conv_init() {
	global $conv_dir;

	if ( get_option( 'optinguru_owner_id' ) ) {
		update_option( 'conv_owner_id', get_option( 'optinguru_owner_id' ), TRUE );
		update_option( 'conv_site_id', get_option( 'optinguru_site_id', get_option( 'optinguru_website_id' ) ), FALSE );
		update_option( 'conv_token', get_option( 'optinguru_token' ) );
		delete_option( 'optinguru_owner_id' );
	} elseif ( get_option( 'convertful_owner_id' ) ) {
		update_option( 'conv_owner_id', get_option( 'convertful_owner_id' ), TRUE );
		update_option( 'conv_site_id', get_option( 'convertful_site_id' ), FALSE );
		update_option( 'conv_token', get_option( 'convertful_token' ) );
		delete_option( 'convertful_owner_id' );
	}

	$owner_id = get_option( 'conv_owner_id' );
	if ( ! is_admin() and $owner_id !== FALSE ) {
		add_action( 'wp_enqueue_scripts', 'conv_enqueue_scripts' );
		add_filter( 'script_loader_tag', 'conv_script_loader_tag', 10, 2 );
		add_filter( 'the_content', 'conv_after_post_content' );
	}

	if ( class_exists( 'woocommerce' ) or class_exists( 'WooCommerce' ) ) {
		require $conv_dir . 'functions/woocommerce.php';
	}
}

/**
 * Get script id
 * @return string
 */
function conv_get_script_id() {
	global $conv_config;
	$url = wp_parse_url( $conv_config['host'] );
	if ( ! preg_match( '/^(.*?)(convertful|devcf)\.[a-z]{3,5}$/', $url['host'] ) ) {
		return 'optin-api';
	}

	return 'convertful-api';
}

/**
 * Get script file name
 * @return string
 */
function conv_get_script_filename() {
	return conv_get_script_id() === 'convertful-api'
		? 'Convertful.js'
		: 'optin.js';
}

function conv_enqueue_scripts() {
	global $conv_config, $conv_version;
	$script_id = conv_get_script_id();
	wp_enqueue_script( $script_id, $conv_config['host'] . '/' . conv_get_script_filename(), array(), $conv_version, TRUE );

	$tags     = array();
	$the_tags = get_the_tags();
	if ( is_array( $the_tags ) && is_singular()) {
		foreach ( $the_tags as $tag ) {
			$tags[] = $tag->slug;
		}
	}

	$categories     = array();
	$the_categories = get_the_category();
	if ( is_array( $the_categories ) && is_singular()) {
		foreach ( $the_categories as $category ) {
			$categories[] = $category->slug;
		}
	}

	$user_meta = wp_get_current_user();

	wp_localize_script( $script_id, 'convPlatformVars', array(
		'postType'   => get_post_type(),
		'categories' => $categories,
		'tags'       => $tags,
		'ajax_url'   => get_home_url() . '/index.php?rest_route=/convertful/v2/',
		'userRoles'  => ( $user_meta instanceof WP_User and ! empty( $user_meta->roles ) ) ? $user_meta->roles : array( 'guest' ),
	) );
}

function conv_script_loader_tag( $tag, $handle ) {
	global $conv_config;
	$script_id = conv_get_script_id();
	if ( $handle !== $script_id ) {
		return $tag;
	}
	$script = sprintf( '%s/%s?owner=%s', $conv_config['host'], conv_get_script_filename(), get_option( 'conv_owner_id' ) );

	return sprintf(
		'<script type="text/javascript" id="%s" src="%s" async="async"></script>',
		$script_id,
		$script
	);
}

function conv_uninstall() {
	// Options cleanup
	foreach ( array( 'owner_id', 'site_id', 'website_id', 'token', 'ref' ) as $option_name ) {
		delete_option( 'optinguru_' . $option_name );
		delete_option( 'convertful_' . $option_name );
		delete_option( 'conv_' . $option_name );
	}
}

function conv_complete_authorization( $request ) {
	$owner_id = (int) $request->get_param( 'owner_id' );
	$site_id  = (int) $request->get_param( 'site_id' );

	conv_check_access();
	if ( empty( $owner_id ) ) {
		wp_send_json_error( [ 'owner_id' => 'Wrong parameters for authorization (owner_id is missing)' ] );
	}

	if ( empty( $site_id ) ) {
		wp_send_json_error( [ 'site_id' => 'Wrong parameters for authorization (site_id is missing)' ] );
	}

	update_option( 'conv_owner_id', (int) $owner_id, TRUE );
	update_option( 'conv_site_id', (int) $site_id, FALSE );

	wp_send_json_success();
}

function conv_get_info( /*WP_REST_Request $request*/ ) {
	conv_check_access();
	$tags = array();
	foreach ( get_tags() as $tag ) {
		$tags[ $tag->slug ] = $tag->name;
	}

	$categories = array();
	foreach ( get_categories() as $category ) {
		$categories[ $category->slug ] = $category->name;
	}

	$post_types = array();
	foreach ( get_post_types( array( 'public' => TRUE ), 'objects' ) as $post_type ) {
		$post_type_name  = isset( $post_type->name ) ? $post_type->name : NULL;
		$post_type_title = ( isset( $post_type->labels ) and isset( $post_type->labels->singular_name ) )
			? $post_type->labels->singular_name
			: $post_type['name'];
		if ( $post_type_name and $post_type_title ) {
			$post_types[ $post_type_name ] = $post_type_title;
		}
	}

	global $wp_roles;
	$user_roles = array();
	foreach ( apply_filters( 'editable_roles', $wp_roles->roles ) as $user_role_name => $user_role ) {
		$user_roles[ $user_role_name ] = isset( $user_role['name'] ) ? $user_role['name'] : $user_role_name;
	}
	$user_roles['guest'] = 'Guest (Unauthenticated)';

	$result = array(
		'tags'       => $tags,
		'categories' => $categories,
		'post_types' => $post_types,
		'user_roles' => $user_roles,
	);

	if ( class_exists( 'woocommerce' ) or class_exists( 'WooCommerce' ) ) {
		// Add WooCommerce coupons and products
		//$result['woo_coupons'] = get_woo_coupons();
		$result['woo_products'] = get_woo_products();
		$result['woo_enabled']  = TRUE;
	}

	wp_send_json_success( $result );
}


function conv_check_access() {
	if ( ! get_option( 'conv_token' ) ) {
		wp_send_json_error( array( 'access_token' => 'Empty WP access token' ) );
	}

	if ( empty( $_POST['access_token'] ) ) {
		wp_send_json_error( array( 'access_token' => 'Empty POST access token' ) );
	}

	if ( $_POST['access_token'] !== get_option( 'conv_token' ) ) {
		wp_send_json_error( array( 'access_token' => 'Wrong access token' ) );
	}
}

function conv_after_post_content( $content ) {
	if ( is_single() ) {
		$content .= '<div class="conv-place conv-place_after_post"></div>';
	}

	return $content;
}

function conv_plugin_action_links( $links ) {
	return array_merge( array( '<a href="' . admin_url( 'tools.php?page=conv-settings' ) . '">' . __( 'Settings' ) . '</a>' ), $links );
}
