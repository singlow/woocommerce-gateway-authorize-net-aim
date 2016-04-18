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
 * @package   WC-Gateway-Authorize-Net-AIM/Templates/Payment-Fields/Credit-Card
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * The checkout page credit card form
 *
 * @param array $payment_method_defaults optional card defaults to pre-populate the form fields
 * @param boolean $enable_csc true if the Card Security Code (CVV) field should be rendered
 *
 * @version 3.0
 * @since 3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<style type="text/css">#payment ul.payment_methods li label[for='payment_method_authorize_net_aim'] img:nth-child(n+2) { margin-left:1px; } .woocommerce #payment ul.payment_methods li .payment_method_authorize_net_aim img, .woocommerce-page #payment ul.payment_methods li .payment_method_authorize_net_aim img { margin-left:0; }</style>
<fieldset>
	<div class="wc-authorize-net-aim-new-payment-method-form js-wc-authorize-net-aim-new-payment-method-form">
		<p class="form-row form-row-first">
			<label for="wc-authorize-net-aim-account-number"><?php esc_html_e( 'Credit Card Number', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
			<input type="text" class="input-text js-wc-payment-gateway-account-number" id="wc-authorize-net-aim-account-number" name="wc-authorize-net-aim-account-number" maxlength="19" autocomplete="off" value="<?php echo esc_attr( $payment_method_defaults['account-number'] ); ?>" />
		</p>

		<p class="form-row form-row-last">
			<label for="wc-authorize-net-aim-exp-month"><?php esc_html_e( 'Expiration Date', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
			<select name="wc-authorize-net-aim-exp-month" id="wc-authorize-net-aim-exp-month" class="js-wc-payment-gateway-card-exp-month" style="width:auto;">
				<option value=""><?php esc_html_e( 'Month', 'woocommerce-gateway-authorize-net-aim' ) ?></option>
				<?php foreach ( range( 1, 12 ) as $month ) : ?>
					<option value="<?php printf( '%02d', $month ) ?>" <?php selected( $payment_method_defaults['exp-month'], $month ); ?>><?php printf( '%02d', $month ) ?></option>
				<?php endforeach; ?>
			</select>
			<select name="wc-authorize-net-aim-exp-year" id="wc-authorize-net-aim-exp-year" class="js-wc-payment-gateway-card-exp-year" style="width:auto;">
				<option value=""><?php esc_html_e( 'Year', 'woocommerce-gateway-authorize-net-aim' ) ?></option>
				<?php foreach ( range( date( 'Y' ), date( 'Y' ) + 10 ) as $year ) : ?>
					<option value="<?php echo $year ?>" <?php selected( $payment_method_defaults['exp-year'], $year ); ?>><?php echo $year ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<div class="clear"></div>

		<?php if ( $enable_csc ) : ?>
			<p class="form-row form-row-wide">
				<label for="wc-authorize-net-aim-csc"><?php esc_html_e( "Card Security Code", 'woocommerce-gateway-authorize-net-aim' ) ?> <span class="required">*</span></label>
				<input type="text" class="input-text js-wc-payment-gateway-csc" id="wc-authorize-net-aim-csc" name="wc-authorize-net-aim-csc" maxlength="4" style="width:60px" autocomplete="off" value="<?php echo esc_attr( $payment_method_defaults['csc'] ); ?>" />
			</p>
			<div class="clear"></div>
		<?php endif; ?>

	</div>
</fieldset>
