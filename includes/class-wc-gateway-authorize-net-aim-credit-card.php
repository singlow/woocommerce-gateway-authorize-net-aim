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
 * @package   WC-Gateway-Authorize-Net-AIM/Gateway/Credit-Card
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Authorize.net AIM Payment Gateway
 *
 * Handles all credit card purchases
 *
 * This is a direct credit card gateway that supports card types, charge,
 * and authorization
 *
 * @since 3.0
 */
class WC_Gateway_Authorize_Net_AIM_Credit_Card extends WC_Gateway_Authorize_Net_AIM {


	/**
	 * Initialize the gateway
	 *
	 * @since 3.0
	 */
	public function __construct() {

		parent::__construct(
			WC_Authorize_Net_AIM::CREDIT_CARD_GATEWAY_ID,
			wc_authorize_net_aim(),
			array(
				'method_title'       => __( 'Authorize.net AIM', 'woocommerce-gateway-authorize-net-aim' ),
				'method_description' => __( 'Allow customers to securely pay using their credit cards with Authorize.net AIM.', 'woocommerce-gateway-authorize-net-aim' ),
				'supports'           => array(
					self::FEATURE_PRODUCTS,
					self::FEATURE_CARD_TYPES,
					self::FEATURE_CREDIT_CARD_CHARGE,
					self::FEATURE_CREDIT_CARD_AUTHORIZATION,
					self::FEATURE_CREDIT_CARD_CAPTURE,
					self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES,
					self::FEATURE_REFUNDS,
					self::FEATURE_VOIDS,
				 ),
				'payment_type'       => 'credit-card',
				'environments'       => array( 'production' => __( 'Production', 'woocommerce-gateway-authorize-net-aim' ), 'test' => __( 'Test', 'woocommerce-gateway-authorize-net-aim' ) ),
				'shared_settings'    => $this->shared_settings_names,
			)
		);
	}


	/**
	 * Display the payment fields on the checkout page
	 *
	 * @since 3.0
	 * @see WC_Payment_Gateway::payment_fields()
	 */
	public function payment_fields() {

		woocommerce_authorize_net_aim_payment_fields( $this );
	}


	/**
	 * Add original transaction ID for capturing a prior authorization
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway_Direct::get_order_for_capture()
	 * @param WC_Order $order order object
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order_for_capture( $order ) {

		$order = parent::get_order_for_capture( $order );

		$order->auth_net_aim_ref_trans_id = $order->wc_authorize_net_aim_trans_id;

		return $order;
	}


	/**
	 * Add Authorize.net AIM specific data to the order for performing a refund,
	 * currently this is just the last 4 digits & expiration date of the credit
	 * card on the original transaction
	 *
	 * @since 3.3.0
	 * @see SV_WC_Payment_Gateway::get_order_for_refund()
	 * @param WC_Order $order|int the order
	 * @param float $amount refund amount
	 * @param string $reason refund reason text
	 * @return WC_Order|WP_Error order object on success, or WP_Error if last four are missing
	 */
	protected function get_order_for_refund( $order, $amount, $reason ) {

		$order = parent::get_order_for_refund( $order, $amount, $reason );

		$order->refund->account_four = $this->get_order_meta( $order->id, 'account_four' );
		$order->refund->expiry_date = date( 'm-Y', strtotime( '20' . $this->get_order_meta( $order->id, 'card_expiry_date' ) ) );

		if ( ! $order->refund->account_four ) {
			return new WP_Error( 'wc_' . $this->get_id() . '_refund_error', __( '%s Refund error - order is missing credit card last four.', 'woocommerce-gateway-authorize-net-aim' ), $this->get_method_title() );
		}

		return $order;
	}


	/**
	 * Authorize.net allows for an authorized & captured transaction that has not
	 * yet settled to be voided. This overrides the refund method when a refund
	 * request encounters the "Code 54 - The referenced transaction does not meet
	 * the criteria for issuing a credit." error and attempts a void instead.
	 *
	 * @since 3.4.0
	 * @see SV_WC_Payment_Gateway::maybe_void_instead_of_refund()
	 * @param \WC_Order $order order
	 * @param \WC_Authorize_Net_AIM_API_Response $response refund response
	 * @return boolean true if a void should be performed instead of a refund
	 */
	protected function maybe_void_instead_of_refund( $order, $response ) {

		return ! $response->transaction_approved() && '3' == $response->get_transaction_response_code() && '54' == $response->get_transaction_response_reason_code();
	}


}
