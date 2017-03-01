<?php
/**
 * Plugin Name:     Superiocity Customizations
 * Plugin URI:      http://www.superiocity.com
 * Description:     Customization for use on the superiocity.com website
 * Author:          Larry Kagan
 * Author URI:      http://www.superiocity.com
 * Text Domain:     superiocity
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Superiocity
 */


function resolve_dupes_add_to_cart_redirect( $url = false ) {
	if ( ! empty( $url ) ) {
		return $url;
	}

	return remove_query_arg( 'add-to-cart' );
}

add_action( 'woocommerce_add_to_cart_redirect' , 'resolve_dupes_add_to_cart_redirect' );
