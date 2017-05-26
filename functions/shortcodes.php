<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'init', 'conv_register_shortcodes', 20 );
function conv_register_shortcodes() {
	add_shortcode( 'convertful', 'conv_handle_shortcode' );
}

function conv_handle_shortcode( $atts, $content, $shortcode ) {
	return isset( $atts['id'] ) ? '<div class="convertful-' . intval( $atts['id'] ) . '"></div>' : '';
}