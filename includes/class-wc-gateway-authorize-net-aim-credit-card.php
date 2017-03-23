<?php
/**
 * WooCommerce Authorize.Net AIM Gateway
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Authorize.Net AIM Gateway to newer
 * versions in the future. If you wish to customize WooCommerce Authorize.Net AIM Gateway for your
 * needs please refer to http://docs.woothemes.com/document/authorize-net-aim/
 *
 * @package   WC-Gateway-Authorize-Net-AIM/Gateway/Credit-Card
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Authorize.Net AIM Payment Gateway
 *
 * Handles all credit card purchases
 *
 * This is a direct credit card gateway that supports card types, charge,
 * and authorization
 *
 * @since 3.0
 */
class WC_Gateway_Authorize_Net_AIM_Credit_Card extends WC_Gateway_Authorize_Net_AIM {


	/** @var string API client key */
	protected $client_key;

	/** @var bool is Accept.js enabled */
	protected $accept_js_enabled;

	/** @var string API test client key */
	protected $test_client_key;

	/** @var bool test is Accept.js enabled */
	protected $test_accept_js_enabled;


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
				'method_title'       => __( 'Authorize.Net AIM', 'woocommerce-gateway-authorize-net-aim' ),
				'method_description' => __( 'Allow customers to securely pay using their credit cards with Authorize.Net AIM.', 'woocommerce-gateway-authorize-net-aim' ),
				'supports'           => array(
					self::FEATURE_PRODUCTS,
					self::FEATURE_CARD_TYPES,
					self::FEATURE_PAYMENT_FORM,
					self::FEATURE_CREDIT_CARD_CHARGE,
					self::FEATURE_CREDIT_CARD_CHARGE_VIRTUAL,
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

		// add scripts & markup when Accept.js is enabled
		if ( $this->is_accept_js_enabled() ) {

			// remove card number/csc input names so they're not POSTed
			add_filter( 'wc_' . $this->get_id() . '_payment_form_default_credit_card_fields', array( $this, 'remove_credit_card_field_input_names' ) );

			// render a hidden input for the payment nonce before the credit card fields
			add_action( 'wc_' . $this->get_id() . '_payment_form', array( $this, 'render_accept_js_fields' ) );
		}
	}


	/**
	 * Get the form fields specific to this method.
	 *
	 * @since 3.9.0
	 * @see WC_Gateway_Authorize_Net_AIM::get_method_form_fields()
	 * @return array
	 */
	protected function get_method_form_fields() {

		$fields = array_merge( parent::get_method_form_fields(), array(

			/** Accept.js settings **/

			// production settings
			'accept_js_enabled' => array(
				'title'       => __( 'Accept.js', 'woocommerce-gateway-authorize-net-aim' ),
				'type'        => 'checkbox',
				'class'       => 'environment-field production-field accept-js-toggle',
				'label'       => __( 'Enable Accept.js to minimize PCI compliance and send credit card details directly to Authorize.Net', 'woocommerce-gateway-authorize-net-aim' ),
				/** translators: Placeholders: %1$s - <a> tag, %2$s = </a> tag **/
				'description' => sprintf( __( 'You must obtain a Client Key to use Accept.js at checkout. %1$sLearn more &raquo;%2$s', 'woocommerce-gateway-authorize-net-aim' ), '<a href="https://docs.woocommerce.com/document/authorize-net-aim/#accept-js-support" target="_blank">', '</a>' ),
				'default'     => 'no',
			),
			'client_key' => array(
				'title' => __( 'Client Key', 'woocommerce-gateway-authorize-net-aim' ),
				'class' => 'environment-field production-field',
			),

			// test settings
			'test_accept_js_enabled' => array(
				'title'       => __( 'Accept.js', 'woocommerce-gateway-authorize-net-aim' ),
				'type'        => 'checkbox',
				'class'       => 'environment-field test-field accept-js-toggle',
				'label'       => __( 'Enable Accept.js to minimize PCI compliance and send credit card details directly to Authorize.Net', 'woocommerce-gateway-authorize-net-aim' ),
				/** translators: Placeholders: %1$s - <a> tag, %2$s = </a> tag **/
				'description' => sprintf( __( 'You must obtain a Client Key to use Accept.js at checkout. %1$sLearn more &raquo;%2$s', 'woocommerce-gateway-authorize-net-aim' ), '<a href="https://docs.woocommerce.com/document/authorize-net-aim/#accept-js-support" target="_blank">', '</a>' ),
				'default'     => 'no',
			),
			'test_client_key' => array(
				'title' => __( 'Client Key', 'woocommerce-gateway-authorize-net-aim' ),
				'class' => 'environment-field test-field',
			),

		) );

		return $fields;
	}

	/**
	 * Display settings page with some additional JS for hiding conditional fields.
	 *
	 * @since 3.9.0
	 * @see SV_WC_Payment_Gateway::admin_options()
	 */
	public function admin_options() {

		parent::admin_options();

		// add inline javascript
		ob_start();
		?>

		$( '.accept-js-toggle' ).change( function() {

			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( 'tr' ).next().show();
			} else {
				$( this ).closest( 'tr' ).next().hide();
			}

		} ).change();

		$( '#woocommerce_<?php echo $this->get_id(); ?>_environment' ).change( function() {

			if ( 'production' === $( this ).val() ) {
				var accept_js_setting = $( '#woocommerce_<?php echo $this->get_id(); ?>_accept_js_enabled' );
			} else {
				var accept_js_setting = $( '#woocommerce_<?php echo $this->get_id(); ?>_test_accept_js_enabled' );
			}

			$( accept_js_setting ).change();

		} ).change();
		<?php

		wc_enqueue_js( ob_get_clean() );

	}


	/**
	 * Enqueue the gateway-specific assets if present.
	 *
	 * @since 3.9.0
	 */
	protected function enqueue_gateway_assets() {

		parent::enqueue_gateway_assets();

		if ( $this->is_accept_js_enabled() ) {

			$url = $this->is_production_environment() ? 'https://js.authorize.net/v1/Accept.js' : 'https://jstest.authorize.net/v1/Accept.js';

			wp_enqueue_script( $this->get_gateway_js_handle() . '-accept-js', $url, array(), null );
		}
	}


	/**
	 * Get the localized parameters for the gateway JS.
	 *
	 * @since 3.9.0
	 * @return array
	 */
	protected function get_gateway_js_localized_script_params() {

		$params = array(
			'accept_js_enabled' => $this->is_accept_js_enabled(),
			'login_id'          => $this->get_api_login_id(),
			'client_key'        => $this->get_client_key(),
			'general_error'     => __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-authorize-net-aim' ),
		);

		return $params;
	}


	/**
	 * Remove the input names for the card number and CSC fields so they're
	 * not POSTed to the server, for security and compliance with Accept.js
	 *
	 * @since 3.9.0
	 * @param array $fields credit card fields
	 * @return array
	 */
	public function remove_credit_card_field_input_names( $fields ) {

		$fields['card-number']['name'] = '';

		if ( $this->csc_enabled() ) {
			$fields['card-csc']['name'] = '';
		}

		return $fields;
	}


	/**
	 * Render a hidden input for the payment nonce before the credit card fields. This is populated
	 * by the gateway JS when it receives a nonce from Accept.js.
	 *
	 * @since 3.9.0
	 */
	public function render_accept_js_fields() {

		$fields = array(
			'payment-nonce',
			'payment-descriptor',
			'card-type',
			'last-four',
		);

		foreach ( $fields as $field ) {

			$name = 'wc-' . $this->get_id_dasherized() . '-' . $field;

			echo '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" />';
		}
	}


	/**
	 * Bypass credit card validation if Accept.js is enabled.
	 *
	 * @since 3.9.0
	 * @param bool $is_valid whether the credit card fields are valid
	 * @return bool
	 */
	protected function validate_credit_card_fields( $is_valid ) {

		if ( ! $this->is_accept_js_enabled() ) {
			return parent::validate_credit_card_fields( $is_valid );
		}

		if ( ! SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-nonce' ) ) {
			$this->add_debug_message( 'Accept.js Error: payment nonce is missing', 'error' );
			$is_valid = false;
		}

		if ( ! SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-descriptor' ) ) {
			$this->add_debug_message( 'Accept.js Error: payment descriptor is missing', 'error' );
			$is_valid = false;
		}

		if ( ! $is_valid ) {

			$params = $this->get_gateway_js_localized_script_params();

			SV_WC_Helper::wc_add_notice( $params['general_error'], 'error' );
		}

		return $is_valid;
	}


	/**
	 * Add payment data to the order.
	 *
	 * @since 3.9.0
	 * @param int $order_id the order ID
	 * @return \WC_Order
	 */
	public function get_order( $order_id ) {

		$order = parent::get_order( $order_id );

		if ( $this->is_accept_js_enabled() && $nonce = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-nonce' ) ) {

			$order->payment->type = $order->payment->type . '_accept_js';

			// expiry month/year
			list( $order->payment->exp_month, $order->payment->exp_year ) = array_map( 'trim', explode( '/', SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-expiry' ) ) );

			// card data
			$order->payment->card_type      = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-card-type' );
			$order->payment->account_number = $order->payment->last_four = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-last-four' );

			// nonce data
			$order->payment->descriptor = SV_WC_Helper::get_post( 'wc-' . $this->get_id_dasherized() . '-payment-descriptor' );
			$order->payment->nonce      = $nonce;
		}

		return $order;
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
	 * Add Authorize.Net AIM specific data to the order for performing a refund,
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
	 * Authorize.Net allows for an authorized & captured transaction that has not
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



	/**
	 * Return the default values for this payment method, used to pre-fill
	 * an authorize.net valid test account number when in testing mode
	 *
	 * @since 3.8.0
	 * @see SV_WC_Payment_Gateway::get_payment_method_defaults()
	 * @return array
	 */
	public function get_payment_method_defaults() {

		$defaults = parent::get_payment_method_defaults();

		if ( $this->is_test_environment() ) {

			$defaults['account-number'] = '4007000000027';
		}

		return $defaults;
	}


	/**
	 * Get the API client key.
	 *
	 * @since 3.9.0
	 * @param string $environment_id the desired environment
	 * @return string
	 */
	public function get_client_key( $environment_id = '' ) {

		if ( ! $environment_id ) {
			$environment_id = $this->get_environment();
		}

		return 'production' === $environment_id ? $this->client_key : $this->test_client_key;
	}


	/**
	 * Determine if Accept.js is enabled.
	 *
	 * @since 3.9.0
	 * @param string $environment_id the desired environment
	 * @return bool
	 */
	public function is_accept_js_enabled( $environment_id = '' ) {

		if ( ! $environment_id ) {
			$environment_id = $this->get_environment();
		}

		return 'yes' === ( 'production' === $environment_id ? $this->accept_js_enabled : $this->test_accept_js_enabled );
	}


	/**
	 * Determine if Accept.js is properly configured.
	 *
	 * @since 3.9.0
	 * @return bool
	 */
	public function is_accept_js_configured() {

		return $this->is_accept_js_enabled() && $this->get_client_key();
	}


}
