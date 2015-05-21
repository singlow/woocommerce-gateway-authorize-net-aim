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
 * @package   WC-Gateway-Authorize-Net-AIM/Gateway
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Authorize.net AIM Payment Gateway Parent Class
 *
 * Functionality which is shared between the credit card and echeck gateways
 *
 * @since 3.0
 */
class WC_Gateway_Authorize_Net_AIM extends SV_WC_Payment_Gateway_Direct {


	/** @var string authorize.net API login ID */
	public $api_login_id;

	/** @var string authorize.net API transaction key */
	public $api_transaction_key;

	/** @var string authorize.net test API login ID */
	public $test_api_login_id;

	/** @var string authorize.net test API transaction key */
	public $test_api_transaction_key;

	/** @var string determines how to process transactions, auth & capture or auth only */
	public $transaction_type;

	/** @var string require the card security code during checkout */
	public $require_cvv;

	/** @var string the location of the merchant's payment processor, determines what fields are required at checkout */
	public $payment_processor_location;

	/** @var WC_Authorize_Net_AIM_API instance */
	protected $api;

	/** @var array shared settings names */
	protected $shared_settings_names = array( 'api_login_id', 'api_transaction_key', 'test_api_login_id', 'test_api_transaction_key' );


	/**
	 * Returns an array of form fields specific for this method
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::get_method_form_fields()
	 * @return array of form fields
	 */
	protected function get_method_form_fields() {

		return array(

			'api_login_id' => array(
				'title'    => __( 'API Login ID', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
				'type'     => 'text',
				'class'    => 'environment-field production-field',
				'desc_tip' => __( 'Your Authorize.net API Login ID', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
			),

			'api_transaction_key' => array(
				'title'    => __( 'API Transaction Key', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
				'type'     => 'password',
				'class'    => 'environment-field production-field',
				'desc_tip' => __( 'Your Authorize.net API Transaction Key', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
			),

			'test_api_login_id' => array(
				'title'    => __( 'Test API Login ID', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
				'type'     => 'text',
				'class'    => 'environment-field test-field',
				'desc_tip' => __( 'Your test Authorize.net API Login ID', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
			),

			'test_api_transaction_key' => array(
				'title'    => __( 'Test API Transaction Key', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
				'type'     => 'password',
				'class'    => 'environment-field test-field',
				'desc_tip' => __( 'Your test Authorize.net API Transaction Key', WC_Authorize_Net_AIM::TEXT_DOMAIN ),
			),
		);
	}


	/**
	 * Add any Authorize.net AIM specific transaction information as
	 * class members of WC_Order instance.  Added members can include:
	 *
	 * auth_net_aim_merchant_defined_fields - custom fields added to the transaction in format array( name => value )
	 *
	 * @since 3.0
	 * @see WC_Gateway_Authorize_Net_AIM::get_order()
	 * @param int $order_id order ID being processed
	 * @return WC_Order object with payment and transaction information attached
	 */
	protected function get_order( $order_id ) {

		// add common order members
		$order = parent::get_order( $order_id );

		// set card type
		if ( $this->is_credit_card_gateway() ) {

			$order->payment->card_type = SV_WC_Payment_Gateway_Payment_Token::type_from_account_number( $order->payment->account_number );
		}

		/**
		 * Filter the order description
		 *
		 * @since 3.3.2
		 * @param string $description The order description
		 * @param int $order_id The order ID being processed
		 * @param WC_Gateway_Authorize_Net_AIM $aim The gateway class instance
		 */
		$order->description = apply_filters( 'wc_authorize_net_aim_transaction_description', sprintf( _x( '%s - Order %s', 'Order description', WC_Authorize_Net_AIM::TEXT_DOMAIN ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() ), $order_id, $this );

		return $order;
	}


	/**
	 * Returns true if the gateway is properly configured to perform transactions.
	 * Authorize.net AIM requires: API Login ID & API Transaction Key
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::is_configured()
	 * @return boolean true if the gateway is properly configured
	 */
	protected function is_configured() {

		$is_configured = parent::is_configured();

		// missing configuration
		if ( ! $this->get_api_login_id() || ! $this->get_api_transaction_key() ) {
			$is_configured = false;
		}

		return $is_configured;
	}


	/** Getter methods ******************************************************/


	/**
	 * Get the API object
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::get_api()
	 * @return WC_Authorize_Net_AIM_API API instance
	 */
	public function get_api() {

		if ( is_object( $this->api ) ) {
			return $this->api;
		}

		$plugin_path = $this->get_plugin()->get_plugin_path();

		// main API class responsible for communication with AIM API
		require_once( $plugin_path . '/includes/api/class-wc-authorize-net-aim-api.php' );

		// API request
		require_once( $plugin_path . '/includes/api/class-wc-authorize-net-aim-api-request.php' );

		// API response
		require_once( $plugin_path . '/includes/api/class-wc-authorize-net-aim-api-response.php' );

		return $this->api = new WC_Authorize_Net_AIM_API( $this->get_id(), $this->get_environment(), $this->get_api_login_id(), $this->get_api_transaction_key() );
	}


	/**
	 * Returns the API Login ID based on the current environment
	 *
	 * @since 3.0
	 * @param string $environment_id optional one of 'test' or 'production', defaults to current configured environment
	 * @return string the API login ID to use
	 */
	public function get_api_login_id( $environment_id = null ) {

		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		return 'production' == $environment_id ? $this->api_login_id : $this->test_api_login_id;
	}


	/**
	 * Returns the API Transaction Key based on the current environment
	 *
	 * @since 3.0
	 * @param string $environment_id optional one of 'test' or 'production', defaults to current configured environment
	 * @return string the API transaction key to use
	 */
	public function get_api_transaction_key( $environment_id = null ) {

		if ( is_null( $environment_id ) ) {
			$environment_id = $this->get_environment();
		}

		return 'production' == $environment_id ? $this->api_transaction_key : $this->test_api_transaction_key;
	}


	/**
	 * Authorize.net AIM does not support customer IDs
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::get_customer_id_user_meta_name()
	 * @param $environment_id
	 * @return bool false
	 */
	public function get_customer_id_user_meta_name( $environment_id = null ) {

		return false;
	}


	/**
	 * Authorize.net AIM does not support customer IDs
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::get_guest_customer_id()
	 * @param WC_Order $order
	 * @return bool false
	 */
	public function get_guest_customer_id( WC_Order $order ) {

		return false;
	}


	/**
	 * Authorize.net AIM does not support customer IDs
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::get_customer_id()
	 * @param int $user_id wordpress user identifier
	 * @param array $args optional additional arguments which can include: environment_id, autocreate (true/false), and order
	 * @return false
	 */
	public function get_customer_id( $user_id, $args = array() ) {

		return false;
	}


}
