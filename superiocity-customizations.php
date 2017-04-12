<?php
declare( strict_types=1 );
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

namespace Superiocity;

/**
 * Miscellaneous customizations for the superiocity.com website.
 *
 * @package Superiocity
 */
class Customization {

	const HACK_REPAIR_ID = 1758;

	/**
	 * Customization constructor.
	 */
	public function __construct() {
		add_action(
			'woocommerce_add_to_cart_redirect' ,
			array( $this, 'resolve_dupes_add_to_cart_redirect' )
		);

		add_filter(
			'woocommerce_email_footer_text' ,
			array( $this, 'update_woo_email_footer' )
		);

		add_filter(
			'woocommerce_checkout_fields',
			array( $this, 'update_checkout_fields' )
		);

		add_action(
			'woocommerce_checkout_update_order_meta',
			array( $this, 'save_custom_checkout_fields' )
		);

		add_action(
			'woocommerce_admin_order_data_after_billing_address',
			array( $this, 'show_checkout_fields_admin' )
		);

		// Remove the Yoast JSON-LD.
		add_filter( 'wpseo_json_ld_output', '__return_false' );

	}


	/**
	 * Avoid duplicates when adding an item to the cart via custom link.
	 *
	 * @param bool $url Accept a default url.
	 * @return bool|string
	 */
	public function resolve_dupes_add_to_cart_redirect( $url = false ) {
		if ( ! empty( $url ) ) {
			return $url;
		}

		return remove_query_arg( 'add-to-cart' );
	}


	/**
	 * Change the default email footer content.
	 *
	 * @param string $content The original email footer content.
	 * @return string
	 */
	public function update_woo_email_footer( string $content ) : string {
		$footer = '<a href="' . get_site_url() .
				'">Superiocity Web Development & Design</a>';
		return $footer;
	}


	/**
	 * Update the checkout fields.
	 *
	 * @param array $fields The original fields.
	 * @return array
	 */
	public function update_checkout_fields( array $fields ) : array {

		$this->remove_unwanted_fields( $fields );
		$cart_items = WC()->cart->get_cart();

		// Adjust checkout for when hack repair is in the cart.
		if ( $this->hack_repair_in_cart( $cart_items ) ) {
			$this->update_checkout_fields_for_hack( $fields );
		}

		return $fields;
	}


	/**
	 * Does the cart have the hack repair in it?
	 *
	 * @param array $cart_items All items in the cart.
	 * @return bool
	 */
	protected function hack_repair_in_cart( array $cart_items ) : bool {

		$cart_has_hack_repair = false;

		if ( empty( $cart_items ) ) {
			return false;
		}

		foreach ( $cart_items as $item ) {
			if ( self::HACK_REPAIR_ID === $item['product_id'] ) {
				$cart_has_hack_repair = true;
			}
		}

		return $cart_has_hack_repair;
	}


	/**
	 * Remove unwanted fields during checkout.
	 *
	 * @param array $fields Checkout form fields.
	 */
	protected function remove_unwanted_fields( array &$fields ) {

		$billing = $fields['billing'];

		unset( $billing['billing_country'], $billing['billing_address_1'],
			$billing['billing_address_2'], $billing['billing_city'],
			$billing['billing_state'], $billing['billing_postcode']
		);

		$fields['billing'] = $billing;
	}


	/**
	 * Get customized checkout fields.
	 *
	 * @return array
	 */
	protected function get_custom_checkout_fields() : array {

		$add_fields = array(
			'web_address' => array(
				'type'        => 'text',
				'label'       => 'Hacked Website Address',
				'placeholder' => 'www.my-hacked-site.com',
				'required'    => true,
			),
			'host_name' => array(
				'type'        => 'text',
				'label'       => 'Website Host',
				'placeholder' => 'e.g. GoDaddy, HostGator, etc.',
				'required'    => true,
			),
			'host_user' => array(
				'type'        => 'text',
				'label'       => 'Host Login Username',
				'class'       => array( 'form-row-first' ),
				'required'    => true,
			),
			'host_pass' => array(
				'type'        => 'password',
				'label'       => 'Host Login Password',
				'class'       => array( 'form-row-last' ),
				'required'    => true,
			),
		);

		return $add_fields;
	}


	/**
	 * Customize checkout forms for hacked website repair.
	 *
	 * @param array $fields All checkout fields.
	 */
	protected function update_checkout_fields_for_hack( array &$fields ) {

		$fields['order'] = $this->get_custom_checkout_fields();
	}


	/**
	 * Save the data from checkout custom fields.
	 */
	public function save_custom_checkout_fields( int $order_id ) {

		$fields = $this->get_custom_checkout_fields();

		foreach ( $fields as $key => $attributes ) {
			if ( ! empty( $_POST[$key] ) ) {
				update_post_meta(
					$order_id,
					$attributes['label'],
					sanitize_text_field( $_POST[$key] )
				);
			}
		}
	}


	/**
	 * Show the checkout custom fields in the admin.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public function show_checkout_fields_admin( $order ) {

		$output = '';
		$fields = $this->get_custom_checkout_fields();
		$line   = '<strong>%s:</strong> %s<br />';

		foreach ( $fields as $key => $attributes ) {
			$value = get_post_meta( $order->id, $attributes['label'], true );
			$output .= sprintf( $line, $attributes['label'], $value );
		}

		print( '<p>' . $output . '</p>' );
	}
}


new Customization();
