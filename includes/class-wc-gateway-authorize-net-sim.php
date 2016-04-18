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
 * @package   WC-Gateway-Authorize-Net-AIM/Gateway/SIM
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Gateway_Authorize_Net_SIM extends WC_Payment_Gateway {


	/**
	 * Setup main class
	 *
	 * @since 2.0
	 */
	public function __construct() {

		$this->id           = 'authorize_net_sim';
		$this->method_title = __( 'Authorize.net SIM', 'woocommerce-gateway-authorize-net-aim' );

		$this->supports   = array( 'products' );

		$this->has_fields = true;

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		foreach ( $this->settings as $setting_key => $setting ) {
			$this->$setting_key = $setting;
		}

		// pay page fallback
		add_action( 'woocommerce_receipt_' . $this->id, create_function( '$order', 'echo "<p>" . __( "Thank you for your order.", "woocommerce-gateway-authorize-net-aim" ) . "</p>";' ) );

		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); // WC >= 2.0
		}
	}


	/**
	 * Init form fields
	 *
	 * @since 2.0
	 */
	public function init_form_fields() {

		$this->form_fields = array(

			'enabled' => array(
				'title'   => __( 'Enable / Disable', 'woocommerce-gateway-authorize-net-aim' ),
				'label'   => __( 'Enable this gateway', 'woocommerce-gateway-authorize-net-aim' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),

			'title' => array(
				'title'    => __( 'Title', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'text',
				'desc_tip' => __( 'Payment method title that the customer will see during checkout.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'  => __( 'Credit card', 'woocommerce-gateway-authorize-net-aim' ),
			),

			'description' => array(
				'title'    => __( 'Description', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'textarea',
				'desc_tip' => __( 'Payment method description that the customer will see during checkout.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'  => __( 'Pay securely using your credit card.', 'woocommerce-gateway-authorize-net-aim' ),
			),

			'apilogin' => array(
				'title'    => __( 'API Login', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'password',
				'desc_tip' => __( 'This is the API Login supplied by Authorize.net.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'  => '',
			),

			'transkey' => array(
				'title'    => __( 'Transaction Key', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'password',
				'desc_tip' => __( 'This is the Transaction Key supplied by Authorize.net.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'  => '',
			),

			'salemethod' => array(
				'title'    => __( 'Sale Method', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'select',
				'desc_tip' => __( 'Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'woocommerce-gateway-authorize-net-aim' ),
				'options'  => array(
					'AUTH_ONLY'    => 'Authorize Only',
					'AUTH_CAPTURE' => 'Authorize &amp; Capture',
				),
				'default'  => 'AUTH_CAPTURE',
			),

			'gatewayurl' => array(
				'title'    => __( 'Gateway URL', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'text',
				'desc_tip' => __( 'URL for Authorize.net gateway processor.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'  => 'https://secure.authorize.net/gateway/transact.dll',
			),

			'cardtypes' => array(
				'title'    => __( 'Accepted Cards', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'multiselect',
				'class'    => 'wc-enhanced-select',
				'css'      => 'width: 350px;',
				'desc_tip' => __( 'Select which card types to accept.', 'woocommerce-gateway-authorize-net-aim' ),
				'options' => array(
					'MasterCard'       => 'MasterCard',
					'Visa'             => 'Visa',
					'Discover'         => 'Discover',
					'American Express' => 'American Express',
				),
				'default' => array( 'MasterCard', 'Visa', 'Discover', 'American Express' ),
			),

			'cvv' => array(
				'title'   => __( 'CVV', 'woocommerce-gateway-authorize-net-aim' ),
				'label'   => __( 'Require customer to enter credit card CVV code', 'woocommerce-gateway-authorize-net-aim' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),

			'testmode' => array(
				'title'       => __( 'Authorize.net Test Mode', 'woocommerce-gateway-authorize-net-aim' ),
				'label'       => __( 'Enable Test Mode', 'woocommerce-gateway-authorize-net-aim' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'     => 'no',
			),

			'require_billing_fields' => array(
				'title'    => __( 'Require Billing Fields?', 'woocommerce-gateway-authorize-net-aim' ),
				'desc_tip' => __( 'Enable this to require all billing fields at checkout. Certain payment processors require this to process transactions. Read the documentation to learn more.', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'checkbox',
				'default'  => 'no',
			),

			'debugon' => array(
				'title'       => __( 'Debugging', 'woocommerce-gateway-authorize-net-aim' ),
				'label'       => __( 'Enable debug emails', 'woocommerce-gateway-authorize-net-aim' ),
				'type'        => 'checkbox',
				'description' => __( 'Receive emails containing the data sent to and from Authorize.net. Only works in <strong>Test Mode</strong>.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'     => 'no',
			),

			'debugrecipient' => array(
				'title'    => __( 'Debugging Email', 'woocommerce-gateway-authorize-net-aim' ),
				'type'     => 'text',
				'desc_tip' => __( 'Who should receive the debugging emails.', 'woocommerce-gateway-authorize-net-aim' ),
				'default'  =>  get_option( 'admin_email' ),
			),

		);
	}


	/**
	 * Set payment fields
	 *
	 * @since 2.0
	 */
	public function payment_fields() {

		if ( $this->description ) {

			echo '<p>' . wp_kses_post( $this->description ) . '</p>';
		}
		?>
		<fieldset>

			<p class="form-row form-row-first">
				<label for="ccnum"><?php _e( 'Credit Card number', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="ccnum" name="ccnum" />
			</p>

			<p class="form-row form-row-last">
				<label for="cardtype"><?php _e( 'Card type', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
				<select name="cardtype" id="cardtype" class="woocommerce-select">
					<?php foreach ( $this->cardtypes as $type ) : ?>
						<option value="<?php echo $type ?>"><?php _e($type, 'woocommerce-gateway-authorize-net-aim'); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<div class="clear"></div>

			<p class="form-row form-row-first">
				<label for="cc-expire-month"><?php _e( 'Expiration date', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
				<select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
					<option value=""><?php _e( 'Month', 'woocommerce-gateway-authorize-net-aim' ); ?></option>
					<?php foreach ( range( 1, 12 ) as $month ) : ?>
						<option value="<?php echo $month; ?>"><?php printf( '%02d', $month ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
					<option value=""><?php _e( 'Year', 'woocommerce-gateway-authorize-net-aim' ) ?></option>
					<?php
						$years = array();
						for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i++ ) {
							printf( '<option value="20%u">20%u</option>', $i, $i );
						}
					?>
				</select>
			</p>
			<?php if ( 'yes' == $this->cvv ) : ?>

			<p class="form-row form-row-last">
				<label for="cvv"><?php _e( 'Card security code', 'woocommerce-gateway-authorize-net-aim' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="cvv" name="cvv" maxlength="4" style="width:45px" />
			</p>
			<?php endif; ?>

			<div class="clear"></div>
		</fieldset>
		<?php
	}


	/**
	 * Process payment & return result
	 *
	 * @since 2.0
	 */
	public function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		$testmode = ( 'yes' == $this->testmode ) ? 'TRUE' : 'FALSE';

		try {

			$authnet_request = array(
				"x_tran_key"           => $this->transkey,
				"x_login"              => $this->apilogin,
				"x_amount"             => SV_WC_Helper::number_format( $order->get_total() ),
				"x_card_num"           => SV_WC_Helper::get_post( 'ccnum' ),
				"x_card_code"          => SV_WC_Helper::get_post( 'cvv' ),
				"x_exp_date"           => SV_WC_Helper::get_post( 'expmonth' ) . '-' . SV_WC_Helper::get_post( 'expyear' ),
				"x_type"               => $this->salemethod,
				"x_version"            => "3.1",
				"x_delim_data"         => "TRUE",
				"x_relay_response"     => "FALSE",
				"x_method"             => "CC",
				"x_first_name"         => $order->billing_first_name,
				"x_last_name"          => $order->billing_last_name,
				"x_address"            => $order->billing_address_1,
				"x_city"               => $order->billing_city,
				"x_state"              => $order->billing_state,
				"x_zip"                => $order->billing_postcode,
				"x_country"            => $order->billing_country,
				"x_phone"              => $order->billing_phone,
				"x_email"              => $order->billing_email,
				"x_ship_to_first_name" => $order->shipping_first_name,
				"x_ship_to_last_name"  => $order->shipping_last_name,
				"x_ship_to_company"    => $order->shipping_company,
				"x_ship_to_address"    => $order->shipping_address_1,
				"x_ship_to_city"       => $order->shipping_city,
				"x_ship_to_country"    => $order->shipping_country,
				"x_ship_to_state"      => $order->shipping_state,
				"x_ship_to_zip"        => $order->shipping_postcode,
				"x_cust_id"            => $order->user_id,
				"x_customer_ip"        => $_SERVER['REMOTE_ADDR'],
				"x_tax"                => "Order Tax<|>Order Tax<|>" . SV_WC_Helper::number_format( $order->get_total_tax() ),
				"x_invoice_num"        => ltrim( $order->get_order_number(), '#' ),
				"x_test_request"       => $testmode,
				"x_delim_char"         => '|',
				"x_encap_char"         => '',
			);

			// Don't send card details in the debug email
			$authnet_debug_request = $authnet_request;
			$authnet_debug_request['x_card_num']  = "XXXX";
			$authnet_debug_request['x_card_code'] = "XXXX";
			$authnet_debug_request['x_exp_date']  = "XXXX";

			$this->send_debugging_email( "URL: " . $this->gatewayurl . "\n\nSENDING REQUEST:" . print_r( $authnet_debug_request, true ) );

			// Send request
			$post = '';
			foreach ( $authnet_request AS $key => $val ) {
				$post .= urlencode( $key ) . "=" . urlencode( $val ) . "&";
			}
			$post = substr( $post, 0, -1 );

			$response = wp_safe_remote_post( $this->gatewayurl, array(
				'method'       => 'POST',
				'body'         => $post,
				'redirection'  => 0,
				'timeout'      => 70,
				'sslverify'    => false,
			) );

			if ( is_wp_error( $response ) ) throw new Exception( __( 'There was a problem connecting to the payment gateway.', 'woocommerce-gateway-authorize-net-aim' ) );

			if ( empty( $response['body'] ) ) throw new Exception( __( 'Empty Authorize.net response.', 'woocommerce-gateway-authorize-net-aim' ) );

			$content = $response['body'];

			// prep response
			foreach ( preg_split("/\r?\n/", $content) as $line ) {
				if ( preg_match("/^1|2|3\|/", $line ) ) {
					$data = explode( "|", $line );
				}
			}

			// store response
			$response['response_code']             = $data[0];
			$response['response_sub_code']         = $data[1];
			$response['response_reason_code']      = $data[2];
			$response['response_reason_text']      = $data[3];
			$response['approval_code']             = $data[4];
			$response['avs_code']                  = $data[5];
			$response['transaction_id']            = $data[6];
			$response['invoice_number_echo']       = $data[7];
			$response['description_echo']          = $data[8];
			$response['amount_echo']               = $data[9];
			$response['method_echo']               = $data[10];
			$response['transaction_type_echo']     = $data[11];
			$response['customer_id_echo']          = $data[12];
			$response['first_name_echo']           = $data[13];
			$response['last_name_echo']            = $data[14];
			$response['company_echo']              = $data[15];
			$response['billing_address_echo']      = $data[16];
			$response['city_echo']                 = $data[17];
			$response['state_echo']                = $data[18];
			$response['zip_echo']                  = $data[19];
			$response['country_echo']              = $data[20];
			$response['phone_echo']                = $data[21];
			$response['fax_echo']                  = $data[22];
			$response['email_echo']                = $data[23];
			$response['ship_first_name_echo']      = $data[24];
			$response['ship_last_name_echo']       = $data[25];
			$response['ship_company_echo']         = $data[26];
			$response['ship_billing_address_echo'] = $data[27];
			$response['ship_city_echo']            = $data[28];
			$response['ship_state_echo']           = $data[29];
			$response['ship_zip_echo']             = $data[30];
			$response['ship_country_echo']         = $data[31];
			$response['tax_echo']                  = $data[32];
			$response['duty_echo']                 = $data[33];
			$response['freight_echo']              = $data[34];
			$response['tax_exempt_echo']           = $data[35];
			$response['po_number_echo']            = $data[36];

			$response['md5_hash']           = $data[37];
			$response['cvv_response_code']  = $data[38];
			$response['cavv_response_code'] = $data[39];

			$this->send_debugging_email( "RESPONSE RAW: " . $content . "\n\nRESPONSE:" . print_r( $response,true ) );

			// Retreive response

			if ( ( 1 == $response['response_code'] ) || ( 4 == $response['response_code'] ) ) {
				// Successful payment

				$order->add_order_note( __( 'Authorize.net payment completed', 'woocommerce-gateway-authorize-net-aim' ) . ' (Response Code: ' . $response['response_code'] . ')' );
				$order->payment_complete();

				WC()->cart->empty_cart();

				// Return thank you redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			} else {

				$this->send_debugging_email( "AUTHORIZE.NET ERROR:\nresponse_code:" . $response['response_code'] . "\nresponse_reasib_text:" .$response['response_reason_text'] );

				$cancelNote = __( 'Authorize.net payment failed', 'woocommerce-gateway-authorize-net-aim' ) . ' (Response Code: ' . $response['response_code'] . '). ' . __( 'Payment was rejected due to an error', 'woocommerce-gateway-authorize-net-aim' ) . ': "' . $response['response_reason_text'] . '". ';

				$this->mark_order_as_failed( $order, $cancelNote, $response );

			}

		} catch( Exception $e ) {
			$this->mark_order_as_failed( $order, sprintf( __( 'Connection error: %s', 'woocommerce-gateway-authorize-net-aim' ), $e->getMessage() ) );
		}

	}


	/**
	 * Validate fields
	 *
	 * @since 2.0
	 */
	public function validate_fields() {

		$cardType            = SV_WC_Helper::get_post( 'card_type' );
		$cardNumber          = SV_WC_Helper::get_post( 'ccnum' );
		$cardCSC             = SV_WC_Helper::get_post( 'cvv' );
		$cardExpirationMonth = SV_WC_Helper::get_post( 'expmonth' );
		$cardExpirationYear  = SV_WC_Helper::get_post( 'expyear' );

		if ( 'yes' == $this->cvv ){
			//check security code
			if ( ! ctype_digit( $cardCSC ) ) {
				wc_add_notice( __( 'Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-authorize-net-aim' ), 'error' );
				return false;
			}

			if ( ( strlen( $cardCSC ) != 3 && in_array( $cardType, array('Visa', 'MasterCard', 'Discover' ) ) ) || ( strlen( $cardCSC ) != 4 && $cardType == 'American Express' ) ) {
				wc_add_notice( __( 'Card security code is invalid (wrong length)', 'woocommerce-gateway-authorize-net-aim' ), 'error' );
				return false;
			}
		}

		//check expiration data
		$currentYear = date( 'Y' );

		if ( ! ctype_digit( $cardExpirationMonth ) || ! ctype_digit( $cardExpirationYear ) ||
			$cardExpirationMonth > 12 ||
			$cardExpirationMonth < 1 ||
			$cardExpirationYear < $currentYear ||
			$cardExpirationYear > $currentYear + 20 ) {

			wc_add_notice( __( 'Card expiration date is invalid', 'woocommerce-gateway-authorize-net-aim' ), 'error' );
			return false;

		}

		//check card number
		$cardNumber = str_replace( array( ' ', '-' ), '', $cardNumber );

		if ( empty( $cardNumber ) || ! ctype_digit( $cardNumber ) ) {
			wc_add_notice( __( 'Card number is invalid', 'woocommerce-gateway-authorize-net-aim' ), 'error' );
			return false;
		}

		return true;
	}


	/**
	 * Mark the given order as failed and set the order note
	 *
	 * @since 2.1
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Response optional $response the transaction response object
	 */
	protected function mark_order_as_failed( $order, $error_message, $response = null ) {

		$order_note = sprintf( __( 'Authorize.net AIM Payment Failed (%s)', 'woocommerce-gateway-authorize-net-aim' ), $error_message );

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( ! $order->has_status( 'failed' ) ) {
			$order->update_status( 'failed', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-authorize-net-aim' ), 'error' );

	}


	/**
	 * Send debugging email
	 *
	 * @since 2.0
	 */
	private function send_debugging_email( $debug ) {

		if ( 'yes' != $this->debugon )  return; // Debug must be enabled
		if ( 'yes' != $this->testmode ) return; // Test mode required
		if ( ! $this->debugrecipient )  return; // Recipient needed

		// Send the email
		wp_mail( $this->debugrecipient, __( 'Authorize.net Debug', 'woocommerce-gateway-authorize-net-aim' ), $debug );
	}


} // end \WC_Authorize_Net_SIM class
