<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );
/**
 * Plugin Name: Optin.Guru
 * Version: 0.1
 * Plugin URI: https://optin.guru/
 * Description: Optin.Guru Integration
 * Author: Optin.Guru
 * Author URI: https://optin.guru/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: optinguru
 */

add_action( 'init', 'og_init' );
function og_init() {
	if ( is_admin() ) {
		return;
	}
	add_action( 'wp_enqueue_scripts', 'og_enqueue_sciprts' );
	add_filter( 'script_loader_tag', 'og_script_loader_tag', 10, 2 );
}

//add_action( 'wp_enqueue_scripts', 'og_enqueue_sciprts' );
function og_enqueue_sciprts() {
	// TODO Use the proper link
	wp_enqueue_script( 'og-embed', '//app.optin.guru/hometocome/embed.js', array(), '0.1', TRUE );
}

//add_filter( 'script_loader_tag', 'og_script_loader_tag', 10, 2 );
function og_script_loader_tag( $tag, $handle ) {
	if ( $handle !== 'og-embed' ) {
		return $tag;
	}

	return str_replace( ' src', ' async="async" id="optinguru-embed" src', $tag );
}