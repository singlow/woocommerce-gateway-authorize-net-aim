<?php
/**
 * WooCommerce Authorize.net AIM Gateway
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Authorize.net AIM Gateway to newer
 * versions in the future. If you wish to customize WooCommerce Authorize.net AIM Gateway for your
 * needs please refer to http://docs.woothemes.com/document/authorize-net-aim/
 *
 * @package   WC-Gateway-Authorize-Net-AIM/Template
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * Template Function Overrides
 *
 * @version 3.0
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if ( ! function_exists( 'woocommerce_authorize_net_aim_payment_fields' ) ) {

	/**
	 * Pluggable function to render the checkout page payment fields form
	 *
	 * @since 3.0
	 * @param WC_Gateway_Authorize_Net_AIM_Credit_Card $gateway gateway object
	 */
	function woocommerce_authorize_net_aim_payment_fields( $gateway ) {

		// safely display the description, if there is one
		if ( $gateway->get_description() ) {
			echo '<p>' . wp_kses_post( $gateway->get_description() ) . '</p>';
		}

		$payment_method_defaults = array(
			'account-number' => '',
			'exp-month'      => '',
			'exp-year'       => '',
			'csc'            => '',
		);

		// for the test environment, display a notice and supply a default test payment method
		if ( $gateway->is_test_environment() ) {
			echo '<p>' . __( 'TEST MODE ENABLED', 'woocommerce-gateway-authorize-net-aim' ) . '</p>';

			$payment_method_defaults = array(
				'account-number' => '4007000000027',
				'exp-month'      => '1',
				'exp-year'       => date( 'Y' ) + 1,
				'csc'            => '123',
			);
		}

		// load the payment fields template file
		wc_get_template(
			'checkout/authorize-net-aim-payment-fields.php',
			array(
				'payment_method_defaults' => $payment_method_defaults,
				'enable_csc'              => $gateway->csc_enabled(),
			),
			'',
			$gateway->get_plugin()->get_plugin_path() . '/templates/'
		);
	}
}


if ( ! function_exists( 'woocommerce_authorize_net_aim_echeck_payment_fields' ) ) {

	/**
	 * Pluggable function to render the checkout page payment fields form
	 *
	 * @since 3.0
	 * @param WC_Gateway_Authorize_Net_AIM_Credit_Card $gateway gateway object
	 */
	function woocommerce_authorize_net_aim_echeck_payment_fields( $gateway ) {

		// safely display the description, if there is one
		if ( $gateway->get_description() ) {
			echo '<p>' . wp_kses_post( $gateway->get_description() ) . '</p>';
		}

		$payment_method_defaults = array(
			'account-number' => '',
			'routing-number' => '',
		);

		// for the test environment, display a notice and supply a default test payment method
		if ( $gateway->is_test_environment() ) {
			echo '<p>' . __( 'TEST MODE ENABLED', 'woocommerce-gateway-authorize-net-aim' ) . '</p>';

			$payment_method_defaults = array(
				'account-number' => '8675309',
				'routing-number' => '031202084',
			);
		}

		// load the payment fields template file
		wc_get_template(
			'checkout/authorize-net-aim-echeck-payment-fields.php',
			array(
				'payment_method_defaults' => $payment_method_defaults,
				'sample_check_image_url'  => WC_HTTPS::force_https_url( $gateway->get_plugin()->get_payment_gateway_framework_assets_url() . '/images/sample-check.png' ),
				'states'                  => WC()->countries->get_states( 'US' ),
			),
			'',
			$gateway->get_plugin()->get_plugin_path() . '/templates/'
		);
	}
}
