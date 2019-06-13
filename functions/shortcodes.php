<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'init', 'conv_register_shortcodes', 20 );
function conv_register_shortcodes() {
	global $conv_config;
	add_shortcode( $conv_config['script_id'], 'conv_handle_shortcode' );
}

function conv_handle_shortcode( $atts, $content, $shortcode ) {
	global $conv_config;
	return isset( $atts['id'] ) ? '<div class="'. esc_attr($conv_config['script_id']) .'-' . intval( $atts['id'] ) . '"></div>' : '';
}
