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
 * @package   WC-Gateway-Authorize-Net-AIM/Gateway/eCheck
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Authorize.net AIM eCheck Payment Gateway
 *
 * Handles all purchases with eChecks
 *
 * This is a direct check gateway
 *
 * @since 3.0
 */
class WC_Gateway_Authorize_Net_AIM_eCheck extends WC_Gateway_Authorize_Net_AIM {


	/**
	 * Initialize the gateway
	 *
	 * @since 3.0
	 */
	public function __construct() {

		parent::__construct(
			WC_Authorize_Net_AIM::ECHECK_GATEWAY_ID,
			wc_authorize_net_aim(),
			array(
				'method_title'       => __( 'Authorize.net AIM eCheck', 'woocommerce-gateway-authorize-net-aim' ),
				'method_description' => __( 'Allow customers to securely pay using their checking accounts with Authorize.net AIM.', 'woocommerce-gateway-authorize-net-aim' ),
				'supports'           => array(
					self::FEATURE_PRODUCTS,
				 ),
				'payment_type'       => 'echeck',
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

		woocommerce_authorize_net_aim_echeck_payment_fields( $this );
	}


}
