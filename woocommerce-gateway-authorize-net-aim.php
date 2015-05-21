<?php
/**
 * Plugin Name: WooCommerce Authorize.net AIM Gateway
 * Plugin URI: http://www.woothemes.com/products/authorize-net-aim/
 * Description: Accept Credit Cards and eChecks via Authorize.net AIM in your WooCommerce store
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 3.3.3
 * Text Domain: woocommerce-gateway-authorize-net-aim
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2011-2014 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-Authorize-Net-AIM
 * @author    SkyVerge
 * @category  Gateway
 * @copyright Copyright (c) 2011-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '1a345d194a0d01e903f7a1363b6c86d2', '18598' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '3.1.0', __( 'WooCommerce Authorize.net AIM Gateway', 'woocommerce-gateway-authorize-net-aim' ), __FILE__, 'init_woocommerce_gateway_authorize_net_aim', array( 'is_payment_gateway' => true, 'minimum_wc_version' => '2.1', 'backwards_compatible' => '3.1.0' ) );

function init_woocommerce_gateway_authorize_net_aim() {

/**
 * # WooCommerce Authorize.net AIM Gateway Main Plugin Class
 *
 * ## Plugin Overview
 *
 * This plugin adds Authorize.net AIM as a payment gateway.  This class handles all the
 * non-gateway tasks such as verifying dependencies are met, loading the text
 * domain, etc.
 *
 * ## Features
 *
 * + Credit Card Authorization
 * + Credit Card Charge
 * + Credit Card Auth Capture
 *
 * ## Admin Considerations
 *
 * + An additional plugin action link is added that allows the admin to activate the legacy SIM gateway for use
 * with non-Authorize.net processors (emulation)
 *
 * + A 'Capture Charge' order action link is added that allows the admin to capture a previously authorized charge for
 * an order
 *
 * ## Frontend Considerations
 *
 * Both the payment fields on checkout (and checkout->pay) and the My cards section on the My Account page are template
 * files for easy customization.
 *
 * ## Database
 *
 * ### Global Settings
 *
 * + `woocommerce_authorize_net_aim_settings` - the serialized gateway settings array
 * + `woocommerce_authorize_net_aim_echeck_settings` - the serialized eCheck gateway settings array
 *
 * ### Options table
 *
 * + `wc_authorize_net_aim_version` - the current plugin version, set on install/upgrade
 *
 * ### Credit Card Order Meta
 *
 * + `_wc_authorize_net_aim_environment` - the environment the transaction was created in, one of 'test' or 'production'
 * + `_wc_authorize_net_aim_trans_id` - the credit card transaction ID returned by Authorize.net
 * + `_wc_authorize_net_aim_trans_date` - the credit card transaction date
 * + `_wc_authorize_net_aim_account_four` - the last four digits of the card used for the order
 * + `_wc_authorize_net_aim_card_type` - the card type used for the transaction, if known
 * + `_wc_authorize_net_aim_card_expiry_date` - the expiration date for the card used for the order
 * + `_wc_authorize_net_aim_authorization_code` - the authorization code returned by Authorize.net
 * + `_wc_authorize_net_aim_charge_captured` - indicates if the transaction was captured, either `yes` or `no`
 *
 * ### eCheck Order Meta
 * + `_wc_authorize_net_aim_echeck_environment` - the environment the transaction was created in, one of 'test' or 'production'
 * + `_wc_authorize_net_aim_echeck_trans_id` - the credit card transaction ID returned by Authorize.net
 * + `_wc_authorize_net_aim_echeck_trans_date` - the credit card transaction date
 * + `_wc_authorize_net_aim_echeck_account_four` - the last four digits of the card used for the order
 * + `_wc_authorize_net_aim_echeck_account_type` - the bank account type used for the transaction, if known, either `checking` or `savings`
 *
 * @since 3.0
 */
class WC_Authorize_Net_AIM extends SV_WC_Payment_Gateway_Plugin {


	/** string version number */
	const VERSION = '3.3.3';

	/** @var WC_Authorize_Net_AIM single instance of this plugin */
	protected static $instance;

	/** string the plugin id */
	const PLUGIN_ID = 'authorize_net_aim';

	/** string plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-gateway-authorize-net-aim';

	/** string the gateway class name */
	const CREDIT_CARD_GATEWAY_CLASS_NAME = 'WC_Gateway_Authorize_Net_AIM_Credit_Card';

	/** string the gateway id */
	const CREDIT_CARD_GATEWAY_ID = 'authorize_net_aim';

	/** string the gateway class name */
	const ECHECK_GATEWAY_CLASS_NAME = 'WC_Gateway_Authorize_Net_AIM_eCheck';

	/** string the gateway id */
	const ECHECK_GATEWAY_ID = 'authorize_net_aim_echeck';


	/**
	 * Setup main plugin class
	 *
	 * @since 3.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			self::TEXT_DOMAIN,
			array(
				'gateways' => array(
					self::CREDIT_CARD_GATEWAY_ID => self::CREDIT_CARD_GATEWAY_CLASS_NAME,
					self::ECHECK_GATEWAY_ID      => self::ECHECK_GATEWAY_CLASS_NAME,
				),
				'dependencies'       => array( 'SimpleXML', 'xmlwriter', 'dom' ),
				'require_ssl'        => true,
				'supports'           => array(
					self::FEATURE_CAPTURE_CHARGE,
				),
			)
		);

		// Load gateway files after woocommerce is loaded
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ), 11 );

		// load templates
		add_action( 'init', array( $this, 'include_template_functions' ), 25 );

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

			// handle activating/deactivating legacy SIM gateway
			add_action( 'admin_action_wc_authorize_net_toggle_sim', array( $this, 'maybe_toggle_sim_gateway' ) );
		}
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway_Plugin::load_translation()
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-gateway-authorize-net-aim', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/**
	 * Loads any required files
	 *
	 * @since 3.0
	 */
	public function includes() {

		// gateway classes
		require_once( 'includes/class-wc-gateway-authorize-net-aim.php' );
		require_once( 'includes/class-wc-gateway-authorize-net-aim-credit-card.php' );
		require_once( 'includes/class-wc-gateway-authorize-net-aim-echeck.php' );

		// require checkout billing fields for non-US stores, as all European card processors require the billing fields
		// in order to successfully process transactions
		if ( ! is_admin() && ! strncmp( get_option( 'woocommerce_default_country' ), 'US:', 3 ) ) {

			// remove blank arrays from the state fields, otherwise it's hidden
			add_action( 'woocommerce_states', array( $this, 'tweak_states' ), 1 );

			//  require the billing fields
			add_filter( 'woocommerce_get_country_locale', array( $this, 'require_billing_fields' ), 100 );
		}

		// load the legacy SIM gateway if active
		if ( $this->is_legacy_sim_gateway_active() ) {

			require_once( 'includes/class-wc-gateway-authorize-net-sim.php' );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'load_legacy_sim_gateway' ) );
		}
	}


	/**
	 * Loads the legacy SIM gateway
	 *
	 * @since 3.0
	 * @param array $gateways
	 * @return array
	 */
	public function load_legacy_sim_gateway( $gateways ) {

		$gateways[] = 'WC_Gateway_Authorize_Net_SIM';

		return $gateways;
	}


	/** Frontend methods ******************************************************/


	/**
	 * Function used to init any gateway template functions,
	 * making them pluggable by plugins and themes.
	 *
	 * @since 3.0
	 */
	public function include_template_functions() {

		require_once( 'includes/wc-gateway-authorize-net-aim-template-functions.php' );
	}


	/**
	 * Before requiring all billing fields, the state array has to be removed of blank arrays, otherwise
	 * the field is hidden
	 *
	 * @see WC_Countries::__construct()
	 *
	 * @since 3.0
	 * @param array $countries the available countries
	 * @return array the available countries
	 */
	public function tweak_states( $countries ) {

		foreach ( $countries as $country_code => $states ) {

			if ( is_array( $countries[ $country_code ] ) && empty( $countries[ $country_code ] ) ) {
				$countries[ $country_code ] = null;
			}
		}

		return $countries;
	}


	/**
	 * Require all billing fields to be entered when the merchant is using a European payment processor
	 *
	 * @since 3.0
	 * @param array $locales array of countries and locale-specific address field info
	 * @return array the locales array with billing info required
	 */
	public function require_billing_fields( $locales ) {

		foreach ( $locales as $country_code => $fields ) {

			if ( isset( $locales[ $country_code ]['state']['required'] ) ) {
				$locales[ $country_code ]['state']['required'] = true;
			}
		}

		return $locales;
	}


	/** Admin methods ******************************************************/


	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @since 3.0
	 * @param array $actions associative array of action names to anchor tags
	 * @return array associative array of plugin action links
	 */
	public function plugin_action_links( $actions ) {
		global $status, $page, $s;

		// get the standard action links
		$actions = parent::plugin_action_links( $actions );

		// add an action to enabled the legacy SIM gateway
		if ( $this->is_legacy_sim_gateway_active() ) {
			$actions['deactivate_sim'] = sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( wp_nonce_url(
					add_query_arg(
						array(
							'action'        => 'wc_authorize_net_toggle_sim',
							'gateway'       => 'deactivate',
							'plugin_status' => $status,
							'paged'         => $page,
							's'             => $s ),
						'admin.php' ),
					$this->get_file() ) ),
				esc_attr__( 'Deactivate SIM gateway', self::TEXT_DOMAIN ),
				__( 'Deactivate SIM gateway', self::TEXT_DOMAIN )
			);
		} else {
			$actions['activate_sim'] = sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( wp_nonce_url(
					add_query_arg(
						array(
							'action'        => 'wc_authorize_net_toggle_sim',
							'gateway'       => 'activate',
							'plugin_status' => $status,
							'paged'         => $page,
							's'             => $s ),
						'admin.php' ),
					$this->get_file() ) ),
				esc_attr__( 'Activate SIM gateway', self::TEXT_DOMAIN ),
				__( 'Activate SIM gateway', self::TEXT_DOMAIN )
			);
		}

		return $actions;
	}


	/**
	 * Handles activating/deactivating the legacy SIM gateway
	 *
	 * @since 3.0
	 */
	public function maybe_toggle_sim_gateway() {

		// Plugins page arguments
		$plugin_status = isset( $_GET['plugin_status'] ) ? $_GET['plugin_status'] : '';
		$page          = isset( $_GET['paged'] ) ? $_GET['paged'] : '';
		$s             = isset( $_GET['s'] ) ? $_GET['s'] : '';

		// the gateway action
		$gateway = isset( $_GET['gateway'] ) ? $_GET['gateway'] : '';

		// get the base return url
		$return_url = admin_url( 'plugins.php?plugin_status=' . $plugin_status . '&paged=' . $page . '&s=' . $s );

		// security check
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], $this->get_file() ) ) {
			wp_redirect( $return_url );
			exit;
		}

		// either activate/deactivate
		if ( 'activate' == $gateway || 'deactivate' == $gateway ) {
			update_option( 'wc_authorize_net_aim_sim_active', 'activate' == $gateway ? true : false );
			$return_url = add_query_arg( array( 'wc_authorize_net_aim_sim_active' => $gateway ), $return_url );
		}

		// back to whence we came
		wp_redirect( esc_url_raw( $return_url ) );
		exit;
	}


	/**
	 * Renders an admin notices, along with displaying a message on the plugins list table
	 * when activating/deactivating legacy SIM gateway
	 *
	 * @since 3.2.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		parent::add_admin_notices();

		// legacy gateway notice
		if ( ! empty( $_GET['wc_authorize_net_aim_sim_active'] ) ) {
			if ( 'activate' == $_GET['wc_authorize_net_aim_sim_active'] ) {
				$message = __( "Legacy Authorize.net SIM gateway is now active.", self::TEXT_DOMAIN );
			} else {
				$message = __( "Legacy Authorize.net SIM gateway is now inactive.", self::TEXT_DOMAIN );
			}
			$this->get_admin_notice_handler()->add_admin_notice( $message, 'authorize-net-sim-status', array( 'dismissible' => false ) );
		}
	}


	/**
	 * Returns true if the legacy SIM gateway is active
	 *
	 * @since 3.0
	 * @return bool
	 */
	private function is_legacy_sim_gateway_active() {

		return get_option( 'wc_authorize_net_aim_sim_active' );
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Authorize.net AIM Instance, ensures only one instance is/can be loaded
	 *
	 * @since 3.3.0
	 * @see wc_authorize_net_aim()
	 * @return WC_Authorize_Net_AIM
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Gets the plugin documentation url
	 *
	 * @since 3.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {
		return 'http://docs.woothemes.com/document/authorize-net-aim/';
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 3.0
	 * @see SV_WC_Payment_Gateway::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'WooCommerce Authorize.net AIM Gateway', self::TEXT_DOMAIN );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 3.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Install default settings
	 *
	 * @since 3.0
	 */
	protected function install() {

		// versions prior to 3.0 did not set a version option, so the upgrade method needs to be called manually
		if ( get_option( 'woocommerce_authorize_net_settings' ) ) {

			$this->upgrade( '2.1' );
		}
	}


	/**
	 * Perform any version-related changes.
	 *
	 * @since 3.0
	 * @param int $installed_version the currently installed version of the plugin
	 */
	protected function upgrade( $installed_version ) {

		// upgrade to 3.0
		if ( version_compare( $installed_version, '3.0', '<' ) ) {

			if ( $old_settings = get_option( 'woocommerce_authorize_net_settings' ) ) {

				$new_settings = array();

				// migrate from old settings
				$new_settings['enabled']                  = isset( $old_settings['enabled'] ) ? $old_settings['enabled'] : 'no';
				$new_settings['title']                    = isset( $old_settings['title'] ) ? $old_settings['title'] : '';
				$new_settings['description']              = isset( $old_settings['description'] ) ? $old_settings['description'] : '';
				$new_settings['enable_csc']               = isset( $old_settings['cvv'] ) ? $old_settings['cvv'] : 'yes';
				$new_settings['transaction_type']         = isset( $old_settings['salemethod'] ) && 'AUTH_ONLY' == $old_settings['salemethod'] ? 'authorization' : 'charge';
				$new_settings['environment']              = isset( $old_settings['gatewayurl'] ) && 'https://test.authorize.net/gateway/transact.dll' == $old_settings['gatewayurl'] ? 'test' : 'production';
				$new_settings['api_login_id']             = isset( $old_settings['apilogin'] ) ? $old_settings['apilogin'] : '';
				$new_settings['debug_mode']               = isset( $old_settings['debugon'] ) && 'yes' == $old_settings['debugon'] ? 'log' : 'off';
				$new_settings['api_transaction_key']      = isset( $old_settings['transkey'] ) ? $old_settings['transkey'] : '';
				$new_settings['test_api_login_id']        = isset( $old_settings['gatewayurl'] ) && 'https://test.authorize.net/gateway/transact.dll' == $old_settings['gatewayurl'] ? $new_settings['api_login_id'] : '';
				$new_settings['test_api_transaction_key'] = isset( $old_settings['gatewayurl'] ) && 'https://test.authorize.net/gateway/transact.dll' == $old_settings['gatewayurl'] ? $new_settings['api_transaction_key'] : '';

				// automatically activate legacy SIM gateway if the gateway URL is non-standard
				if ( isset( $old_settings['gatewayurl'] ) &&
					'https://test.authorize.net/gateway/transact.dll' != $old_settings['gatewayurl'] &&
					'https://secure.authorize.net/gateway/transact.dll' != $old_settings['gatewayurl'] ) {

					update_option( 'wc_authorize_net_aim_sim_active', true );
				}

				if ( isset( $old_settings['cardtypes'] ) && is_array( $old_settings['cardtypes'] ) ) {

					$new_settings['card_types'] = array();

					// map old to new
					foreach ( $old_settings['cardtypes'] as $card_type ) {

						switch ( $card_type ) {

							case 'MasterCard':
								$new_settings['card_types'][] = 'MC';
								break;

							case 'Visa':
								$new_settings['card_types'][] = 'VISA';
								break;

							case 'Discover':
								$new_settings['card_types'][] = 'DISC';
								break;

							case 'American Express':
								$new_settings['card_types'][] = 'AMEX';
								break;
						}
					}
				}

				// update to new settings
				update_option( 'woocommerce_authorize_net_aim_settings', $new_settings );

				// change option name for old settings
				update_option( 'woocommerce_authorize_net_sim_settings', $old_settings );
			}
		}
	}


} // end \WC_Authorize_Net_AIM


/**
 * Returns the One True Instance of Authorize.net AIM
 *
 * @since 3.3.0
 * @return WC_Authorize_Net_AIM
 */
function wc_authorize_net_aim() {
	return WC_Authorize_Net_AIM::instance();
}


/**
 * The WC_Authorize_Net_AIM global object, exists only for backwards compat
 *
 * @deprecated 3.3.0
 * @name $wc_authorize_net_aim
 * @global WC_Authorize_Net_AIM $GLOBALS['wc_authorize_net_aim']
 */
$GLOBALS['wc_authorize_net_aim'] = wc_authorize_net_aim();


} // init_woocommerce_gateway_authorize_net_aim
