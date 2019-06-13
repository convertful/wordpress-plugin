<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Plugin Name: Convertful - Your Ultimate On-Site Conversion Tool
 * Version: 1.7
 * Plugin URI: https://convertful.com/
 * Description: All the modern on-site conversion solutions, natively integrates with all modern Email Marketing Platforms.
 * Author: Convertful
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: convertful
 */

// Global variables for plugin usage (global declaration is needed here for WP CLI compatibility)
global $conv_file, $conv_dir, $conv_uri, $conv_version;
$conv_file = __FILE__;
$conv_dir = plugin_dir_path( __FILE__ );
$conv_uri = plugins_url( '', __FILE__ );
$conv_version = preg_match( '~Version\: ([^\n]+)~', file_get_contents( __FILE__, NULL, NULL, 82, 150 ), $conv_matches ) ? $conv_matches[1] : FALSE;
unset( $conv_matches );

if (file_exists($conv_dir.'config.php'))
{
	$conv_config = require $conv_dir.'config.php';
}

/**
 * Get script id
 * @return string
 */
function conv_get_script_id()
{
	global $conv_config;
	$url = wp_parse_url($conv_config['host']);
	if ( ! preg_match('/^(.*?)(convertful|devcf)\.(com|su|local)$/', $url['host']))
	{
		return 'optin-api';
	}
	return 'convertful-api';
}

/**
 * Get script file name
 * @return string
 */
function conv_get_script_filename()
{
	return conv_get_script_id() === 'convertful-api'
		? 'Convertful.js'
		: 'optin.js';
}

add_action( 'init', 'conv_init' );
function conv_init() {
	if ( get_option( 'optinguru_owner_id' ) ) {
		update_option( 'conv_owner_id', get_option( 'optinguru_owner_id' ), TRUE );
		update_option( 'conv_site_id', get_option( 'optinguru_site_id', get_option( 'optinguru_website_id' ) ), FALSE );
		update_option( 'conv_token', get_option( 'optinguru_token' ), FALSE );
	}
	if ( get_option( 'convertful_owner_id' ) ) {
		update_option( 'conv_owner_id', get_option( 'convertful_owner_id' ), TRUE );
		update_option( 'conv_site_id', get_option( 'convertful_site_id' ), FALSE );
		update_option( 'conv_token', get_option( 'convertful_token' ), FALSE );
	}
	$owner_id = get_option( 'conv_owner_id' );
	if ( ! is_admin() AND $owner_id !== FALSE ) {
		add_action( 'wp_enqueue_scripts', 'conv_enqueue_scripts' );
		add_filter( 'script_loader_tag', 'conv_script_loader_tag', 10, 2 );
		add_filter( 'the_content', 'conv_after_post_content' );
	}
}

if ( is_admin() ) {
	require $conv_dir . 'functions/admin_pages.php';
}

// Shortcodes
require $conv_dir . 'functions/shortcodes.php';

function conv_enqueue_scripts() {
	global $conv_config, $conv_version;
	$script_id = conv_get_script_id();
	wp_enqueue_script( $script_id, $conv_config['host'].'/'.conv_get_script_filename(), array(), $conv_version, TRUE );

	$tags = array();
	$the_tags = get_the_tags();
	if ( is_array( $the_tags ) ) {
		foreach ( $the_tags as $tag ) {
			$tags[] = $tag->slug;
		}
	}

	$categories = array();
	$the_categories = get_the_category();
	if ( is_array($the_categories)) {
		foreach ( $the_categories as $category ) {
			$categories[] = $category->slug;
		}
	}

	$user_meta = wp_get_current_user();

	wp_localize_script( $script_id, 'convPlatformVars', array(
		'postType' => get_post_type(),
		'categories' => $categories,
		'tags' => $tags,
		'userRoles' => ( $user_meta instanceof WP_User AND ! empty($user_meta->roles) ) ? $user_meta->roles : array( 'guest' ),
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
	$ref_data = get_transient( 'conv-ref' );
	if ( $ref_data AND strpos( $ref_data, '|' ) !== FALSE ) {
		$ref_data = explode( '|', $ref_data );
		// Preventing violations with lifetime values
		if ( time() - intval( $ref_data[1] ) < DAY_IN_SECONDS ) {
			update_option( 'conv_ref', $ref_data[0], FALSE );
		}
		delete_transient( 'conv-ref' );
	}
	$owner_id = get_option( 'conv_owner_id' );
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

function conv_after_post_content( $content ) {
	if ( is_single() ) {
		$content .= '<div class="conv-place conv-place_after_post"></div>';
	}

	return $content;
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
		delete_option( 'conv_' . $option_name );
	}
}

if ( wp_doing_ajax() OR defined( 'DOING_AJAX' ) ) {

	add_action( 'wp_ajax_conv_get_info', 'conv_get_info' );
	add_action( 'wp_ajax_nopriv_conv_get_info', 'conv_get_info' );
	function conv_get_info() {

		if ( $_POST['access_token'] !== get_option( 'conv_token' )) {
			wp_send_json_error( array(
				'access_token' => 'Wrong access token',
			) );
		}

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
			$post_type_name = isset($post_type->name) ? $post_type->name : NULL;
			$post_type_title = (isset($post_type->labels) AND isset($post_type->labels->singular_name)) ? $post_type->labels->singular_name : $post_type['name'];
			if ($post_type_name AND $post_type_title)
			{
				$post_types[ $post_type_name ] = $post_type_title;
			}
		}

		global $wp_roles;
		$user_roles = array();
		foreach ( apply_filters('editable_roles', $wp_roles->roles) as $user_role_name => $user_role ) {
			$user_roles[ $user_role_name ] = isset($user_role['name']) ? $user_role['name'] : $user_role_name;
		}
		$user_roles['guest'] = 'Guest (Unauthenticated)';

		$result = array(
			'tags' => $tags,
			'categories' => $categories,
			'post_types' => $post_types,
			'user_roles' => $user_roles,
		);

		wp_send_json_success($result);
	}


	add_action( 'wp_ajax_conv_complete_authorization', 'conv_complete_authorization' );
	add_action( 'wp_ajax_nopriv_conv_complete_authorization', 'conv_complete_authorization' );
	function conv_complete_authorization() {

		if ( $_POST['access_token'] !== get_option( 'conv_token' )) {
			wp_send_json_error( array(
				'access_token' => 'Wrong access token',
			) );
		}

		foreach ( array( 'owner_id', 'site_id' ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) OR empty( $_POST[ $key ] ) ) {
				wp_send_json_error( array(
					$key => 'Wrong parameters for authorization (owner_id or site_id missing)',
				) );
			}
		}

		update_option( 'conv_owner_id', (int) $_POST['owner_id'], TRUE );
		update_option( 'conv_site_id', (int) $_POST['site_id'], FALSE );

		wp_send_json_success();
	}


}


