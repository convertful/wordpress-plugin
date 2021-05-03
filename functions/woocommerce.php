<?php defined( 'ABSPATH' ) or die( 'This script cannot be accessed directly.' );

//global $woocommerce;

add_action( 'rest_api_init', function () {
	register_rest_route( 'convertful/v2', '/add_to_woo_cart/', array(
		'methods'  => 'POST',
		'callback' => 'add_to_woo_cart',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'convertful/v2', '/add_woo_coupon/', array(
		'methods'  => 'POST',
		'callback' => 'add_woo_coupon',
		'permission_callback' => '__return_true',
	) );
});

/*function get_woo_coupons()
{
	$args = array(
		'posts_per_page'   => -1,
		'orderby'          => 'title',
		'order'            => 'asc',
		'post_type'        => 'shop_coupon',
		'post_status'      => 'publish',
	);

	$coupons = get_posts( $args );
	$coupon_names = array();
	foreach ( $coupons as $coupon ) {
		// Get the name for each coupon post
		array_push( $coupon_names, $coupon->post_title );
	}

	return $coupon_names;
}*/

function get_woo_products()
{
	$args = array(
		'order' => 'DESC',
		'limit' => -1,
	);
	$products = wc_get_products( $args );
	$product_names = array();
	foreach ( $products as $product ) {
		// Get the name for each product
		$product_names[$product->id] = $product->name;
	}

	return $product_names;
}

function add_to_woo_cart()
{
	global $woocommerce;
	$product_id = $_POST['product_id'];
	if (! empty($product_id))
	{
		$woocommerce->cart->add_to_cart($product_id);
	}
}

function add_woo_coupon(){
	$coupon_code = $_POST['code']; // Code
	$amount = $_POST['amount'];
	$discount_type = $_POST['discount_type'];
	$emails = $_POST['emails'];
	$coupon = array(
		'post_title' => $coupon_code,
		'post_excerpt' => 'Convertful auto-generated coupon',
		'post_content' => '',
		'post_status' => 'publish',
		'post_author' => 1,
		'post_type' => 'shop_coupon'
	);
	$new_coupon_id = wp_insert_post( $coupon );
	update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
	update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
	update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
	update_post_meta( $new_coupon_id, 'product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_sale_items', 'yes' );
	update_post_meta( $new_coupon_id, 'usage_limit', 1 );
	update_post_meta( $new_coupon_id, 'expiry_date', '' );
	update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
	if (! empty($emails))
	{
		$emails = (array)$emails;
		update_post_meta( $new_coupon_id, 'customer_email', $emails );
	}
}