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
 * @package   WC-Gateway-Authorize-Net-AIM/Templates/Payment-Fields/eCheck
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * The checkout page eCheck form
 *
 * @param array $payment_method_defaults optional card defaults to pre-populate the form fields
 * @param string $sample_check_image_url url to the sample check image
 * @param array $states associative array of state code to name
 *
 * @version 3.0
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<style type="text/css">#payment ul.payment_methods li label[for='payment_method_authorize_net_aim_echeck'] img:nth-child(n+2) { margin-left:1px; } .woocommerce #payment ul.payment_methods li .payment_method_authorize_net_aim_echeck img, .woocommerce-page #payment ul.payment_methods li .payment_method_authorize_net_aim_echeck img { margin-left:0; }</style>
<div class="sample-check" style="display:none;">
	<p><?php _e( 'How to find your ABA routing number and account number', 'woocommerce-gateway-authorize-net-aim' ); ?></p>
	<img width="403" src="<?php echo esc_url( $sample_check_image_url ); ?>" style="box-shadow:none;" />
</div>
<fieldset>
	<div class="wc-authorize-net-aim-echeck-new-payment-method-form js-wc-authorize-net-aim-echeck-new-payment-method-form">

		<p class="form-row form-row-first">
			<label for="wc-authorize-net-aim-echeck-routing-number"><?php _e( 'Routing Number (9 digits)', 'woocommerce-gateway-authorize-net-aim' ); ?> <img title="<?php esc_attr_e( 'Where do I find this?', 'woocommerce-gateway-authorize-net-aim' ); ?>" class="js-wc-authorize-net-aim-echeck-account-help" style="margin-bottom:3px;cursor:pointer;box-shadow:none;" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></label>
			<input id="wc-authorize-net-aim-echeck-routing-number" name="wc-authorize-net-aim-echeck-routing-number" type="text" class="input-text js-wc-payment-gateway-routing-number" autocomplete="off" value="<?php echo esc_attr( $payment_method_defaults['routing-number'] );?>" />
		</p>
		<p class="form-row form-row-last">
			<label for="wc-authorize-net-aim-echeck-account-number"><?php _e( 'Account Number (3-17 digits)', 'woocommerce-gateway-authorize-net-aim' ); ?> <img title="<?php esc_attr_e( 'Where do I find this?', 'woocommerce-gateway-authorize-net-aim' ); ?>" class="js-wc-authorize-net-aim-echeck-account-help" style="margin-bottom:3px;cursor:pointer;box-shadow:none;" class="help_tip" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" width="16" height="16" /></label>
			<input id="wc-authorize-net-aim-echeck-account-number" name="wc-authorize-net-aim-echeck-account-number" type="text" class="input-text js-wc-payment-gateway-account-number" autocomplete="off" value="<?php echo esc_attr( $payment_method_defaults['account-number'] );?>" />
		</p>
		<div class="clear"></div>

		<p class="form-row">
			<label for="wc-authorize-net-aim-echeck-account-type"><?php _e( 'Account Type', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
			<select id="wc-authorize-net-aim-echeck-account-type" name="wc-authorize-net-aim-echeck-account-type" class=" js-wc-payment-gateway-account-type" style="width:auto;">
				<option value="checking"><?php _e( 'Checking', 'woocommerce-gateway-authorize-net-aim' ); ?></option>
				<option value="savings"><?php _e( 'Savings', 'woocommerce-gateway-authorize-net-aim' ); ?></option>
			</select>
		</p>
		<div class="clear"></div>

	</div>
</fieldset>
