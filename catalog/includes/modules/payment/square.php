<?php
	/*
		$Id$
		Square Payments Plugin for osCommerce
		http://hichemkhayati.com
		Copyright (c) 2019 Hichem Khayati
		Released under the GNU General Public License
	*/

	function getStateCode ( $input ) {

		$states = array(
					'Alabama'=>'AL',
					'Alaska'=>'AK',
					'Arizona'=>'AZ',
					'Arkansas'=>'AR',
					'California'=>'CA',
					'Colorado'=>'CO',
					'Connecticut'=>'CT',
					'Delaware'=>'DE',
					'Florida'=>'FL',
					'Georgia'=>'GA',
					'Hawaii'=>'HI',
					'Idaho'=>'ID',
					'Illinois'=>'IL',
					'Indiana'=>'IN',
					'Iowa'=>'IA',
					'Kansas'=>'KS',
					'Kentucky'=>'KY',
					'Louisiana'=>'LA',
					'Maine'=>'ME',
					'Maryland'=>'MD',
					'Massachusetts'=>'MA',
					'Michigan'=>'MI',
					'Minnesota'=>'MN',
					'Mississippi'=>'MS',
					'Missouri'=>'MO',
					'Montana'=>'MT',
					'Nebraska'=>'NE',
					'Nevada'=>'NV',
					'New Hampshire'=>'NH',
					'New Jersey'=>'NJ',
					'New Mexico'=>'NM',
					'New York'=>'NY',
					'North Carolina'=>'NC',
					'North Dakota'=>'ND',
					'Ohio'=>'OH',
					'Oklahoma'=>'OK',
					'Oregon'=>'OR',
					'Pennsylvania'=>'PA',
					'Rhode Island'=>'RI',
					'South Carolina'=>'SC',
					'South Dakota'=>'SD',
					'Tennessee'=>'TN',
					'Texas'=>'TX',
					'Utah'=>'UT',
					'Vermont'=>'VT',
					'Virginia'=>'VA',
					'Washington'=>'WA',
					'West Virginia'=>'WV',
					'Wisconsin'=>'WI',
					'Wyoming'=>'WY'
				);

			foreach ($states as $state => $abbr) {
				if ($input == $state)
					return $abbr;
			}

			return $input;

		}


	class square {
		var $code, $title, $description, $enabled;
		function square() {
			global $HTTP_GET_VARS, $PHP_SELF, $order;
			$this->signature = 'square|square|1.0|1.0';
			$this->code = MODULE_PAYMENT_SQUARE_TEXT_CODE;
			$this->title = MODULE_PAYMENT_SQUARE_TEXT_TITLE;
			$this->public_title = MODULE_PAYMENT_SQUARE_TEXT_PUBLIC_TITLE;
			$this->display_title = MODULE_PAYMENT_SQUARE_TEXT_DISPLAY_TITLE;
			
			$this->description = MODULE_PAYMENT_SQUARE_TEXT_DESCRIPTION;
			$this->sort_order = defined('MODULE_PAYMENT_SQUARE_SORT_ORDER') ? MODULE_PAYMENT_SQUARE_SORT_ORDER : 0;
			$this->enabled = defined('MODULE_PAYMENT_SQUARE_STATUS') && (MODULE_PAYMENT_SQUARE_STATUS == 'True') ? true : false;
			$this->order_status = defined('MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID : 0;
			
			
			if ( !function_exists('curl_init') ) {
				$this->description = '<div class="secWarning">' . MODULE_PAYMENT_SQUARE_ERROR_ADMIN_CURL . '</div>' . $this->description;
				$this->enabled = false;
			}
			if ( $this->enabled === true ) {
				if ( !tep_not_null(MODULE_PAYMENT_SQUARE_LOCATION_ID) ) {
					$this->description = '<div class="secWarning">' . MODULE_PAYMENT_SQUARE_ERROR_ADMIN_CONFIGURATION . '</div>' . $this->description;
					$this->enabled = false;
				}
			}
			if ( $this->enabled === true ) {
				if ( isset($order) && is_object($order) ) {
					$this->update_status();
				}
			}
			
		}
		
		function getParams() {
			if (!defined('MODULE_PAYMENT_SQUARE_TRANSACTION_ORDER_STATUS_ID')) {
				$check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Square [Transactions]' limit 1");
				
				if (tep_db_num_rows($check_query) < 1) {
					$status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
					$status = tep_db_fetch_array($status_query);
					
					$status_id = $status['status_id']+1;
					
					$languages = tep_get_languages();
					
					foreach ($languages as $lang) {
						tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'Square [Transactions]')");
					}
					
					$flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
					if (tep_db_num_rows($flags_query) == 1) {
						tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
					}
					} else {
					$check = tep_db_fetch_array($check_query);
					
					$status_id = $check['orders_status_id'];
				}
				} else {
				$status_id = MODULE_PAYMENT_SQUARE_TRANSACTION_ORDER_STATUS_ID;
			}
			
			
			$params = array('MODULE_PAYMENT_SQUARE_STATUS' => array('title' => 'Enable Square Module',
			'desc' => 'Do you want to accept Square payments?',
			'value' => 'True',
			'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '),
			
			'MODULE_PAYMENT_SQUARE_APPLICATION_ID' => array('title' => 'Application ID',
			'desc' => 'Application ID of the account you want to use'),
			'MODULE_PAYMENT_SQUARE_LOCATION_ID' => array('title' => 'Location ID',
			'desc' => 'Location ID of the business you want to use.'),
			'MODULE_PAYMENT_SQUARE_ACCESS_TOKEN' => array('title' => 'Access Token',
			'desc' => 'Access token of the account you want to use'),
			
			
			
			'MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID' => array('title' => 'Set Order Status',
			'desc' => 'Set the status of orders made with this payment module to this value',
			'value' => '0',
			'use_func' => 'tep_get_order_status_name',
			'set_func' => 'tep_cfg_pull_down_order_statuses('),
			'MODULE_PAYMENT_SQUARE_TRANSACTION_ORDER_STATUS_ID' => array('title' => 'Transaction Order Status',
			'desc' => 'Include transaction information in this order status level',
			'value' => $status_id,
			'set_func' => 'tep_cfg_pull_down_order_statuses(',
			'use_func' => 'tep_get_order_status_name'),
			'MODULE_PAYMENT_SQUARE_ZONE' => array('title' => 'Payment Zone',
			'desc' => 'If a zone is selected, only enable this payment method for that zone.',
			'value' => '0',
			'use_func' => 'tep_get_zone_class_title',
			'set_func' => 'tep_cfg_pull_down_zone_classes('),
			'MODULE_PAYMENT_SQUARE_SORT_ORDER' => array('title' => 'Sort order of display.',
			'desc' => 'Sort order of display. Lowest is displayed first.',
			'value' => '0'));
			
			return $params;
		}
		
		function update_status() {
			global $order;
			
			
			if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_SQUARE_ZONE > 0) ) {
				$check_flag = false;
				$check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SQUARE_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
				while ($check = tep_db_fetch_array($check_query)) {
					if ($check['zone_id'] < 1) {
						$check_flag = true;
						break;
						} elseif ($check['zone_id'] == $order->delivery['zone_id']) {
						$check_flag = true;
						break;
					}
				}
				
				if ($check_flag == false) {
					$this->enabled = false;
				}
			}
		}
		
		function check() {
			if (!isset($this->_check)) {
				$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SQUARE_STATUS'");
				$this->_check = tep_db_num_rows($check_query);
			}
			return $this->_check;
		}
		
		function install($parameter = null) {
			$params = $this->getParams();
			if (isset($parameter)) {
				if (isset($params[$parameter])) {
					$params = array($parameter => $params[$parameter]);
					} else {
					$params = array();
				}
			}
			foreach ($params as $key => $data) {
				$sql_data_array = array('configuration_title' => $data['title'],
				'configuration_key' => $key,
				'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
				'configuration_description' => $data['desc'],
				'configuration_group_id' => '6',
				'sort_order' => '0',
				'date_added' => 'now()');
				if (isset($data['set_func'])) {
					$sql_data_array['set_function'] = $data['set_func'];
				}
				if (isset($data['use_func'])) {
					$sql_data_array['use_function'] = $data['use_func'];
				}
				tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
			}
		}
		
		function remove() {
			tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		}
		
		function keys() {
			$keys = array_keys($this->getParams());
			
			if ($this->check()) {
				foreach ($keys as $key) {
					if (!defined($key)) {
						$this->install($key);
					}
				}
			}
			
			return $keys;
		}
		
		function selection() {
			return array('id' => $this->code,
			'module' => $this->display_title);
			
		}
		
		function javascript_validation() {
			return false;
		}
		
		function pre_confirmation_check() {
			global $cartID, $cart, $order;
			if (empty($cart->cartID)) {
				$cartID = $cart->cartID = $cart->generate_cart_id();
			}
			if (!tep_session_is_registered('cartID')) {
				tep_session_register('cartID');
			}
			$order->info['payment_method_raw'] = $order->info['payment_method'];
			$order->info['payment_method'] = '<img src="images/card_acceptance/square_cards.png" class="paymentMethods" border="0" alt="Square Logo" style="padding: 3px;" />';
		}
			
		function confirmation() {
			
			$confirmation = array('fields' => array(
			array('title' => MODULE_PAYMENT_SQUARE_CREDIT_CARD_NUMBER,
			'field' => tep_draw_input_field('', '', 'id="sq-card-number"')),
			
			array('title' => MODULE_PAYMENT_SQUARE_CREDIT_CARD_EXPIRES,
			'field' => tep_draw_input_field('', '', 'id="sq-expiration-date"')),
			
			array('title' => MODULE_PAYMENT_SQUARE_CREDIT_CARD_CVV,
			'field' => tep_draw_input_field('', '', 'id="sq-cvv"')),
			
			array('title' => MODULE_PAYMENT_SQUARE_POSTAL_CODE,
			'field' => tep_draw_input_field('', '', 'id="sq-postal-code"')),
			
			array('title' => '',
			'field' => tep_draw_input_field('nonce', '', 'id="card-nonce"', "hidden")),
			
			array('title' => '',
			'field' => '<div id="errors"></div>')
			
			
			));
			
			return $confirmation;
		}
		
		function process_button() {

				  $process_button_string = tep_draw_button(IMAGE_BUTTON_CONFIRM_ORDER, 'check', null, 'primary', array("type" => "submit", "params"=> 'onclick="submitButtonClick(event)"') , 'class="largeButton blueButton"');

				  return $process_button_string;
		}

		
		function before_process() {
			global $order, $HTTP_POST_VARS, $customer_id;

			require(DIR_WS_CLASSES . "square" . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . 'autoload.php');
			
			$requirement_query = tep_db_query("select f.configuration_value as access_token, s.configuration_value AS locationId from configuration f, configuration s where f.configuration_key = 'MODULE_PAYMENT_SQUARE_ACCESS_TOKEN' AND s.configuration_key = 'MODULE_PAYMENT_SQUARE_LOCATION_ID';");
			$requirement_array = tep_db_fetch_array($requirement_query);
			
			$access_token = $requirement_array['access_token'];
			$locationID = $requirement_array['locationId'];
				
				
			$transaction_api = new \SquareConnect\Api\TransactionApi();
			$request_body = array (
			  "card_nonce" => $HTTP_POST_VARS['nonce'],
			  # Monetary amounts are specified in the smallest unit of the applicable currency.
			  # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
			  "amount_money" => array (
				"amount" => (round($order->info['total'], 2) * 100 ), 
				
			# Amount explanation:
			# 100 is 1.00 USD
			# 10000 is 100.00 USD
				
				"currency" => "USD"
			  ),


			  'billing_address' => array(
			  				'first_name' => $order->billing["firstname"],
			  				'last_name' => $order->billing["lastname"],
			  				'organization' => $order->billing["company"],
						    'address_line_1' => $order->billing['street_address'],
						    'address_line_2' => $order->billing['suburb'],
						    'locality' => $order->billing['city'],
						    'administrative_district_level_1' => getStateCode($order->billing['state']),
						    'postal_code' => $order->billing['postcode'],
						    'country' => $order->billing['country']['iso_code_2'],
				),


			  'buyer_email_address' => $order->customer['email_address'],
			  
			  "note" => "Customer ID: ". $customer_id . " - ".$order->billing["firstname"]." ".$order->billing["lastname"],
			  
			  # Every payment you process with the SDK must have a unique idempotency key.
			  # If you're unsure whether a particular payment succeeded, you can reattempt
			  # it with the same idempotency key without worrying about double charging
			  # the buyer.
			  "idempotency_key" => uniqid()
			);
			

			$location_api = new \SquareConnect\Api\LocationApi();

			try {
				  $result = $transaction_api->charge($access_token, $locationID, $request_body);
				  $error = $result->getErrors();
				} catch (\SquareConnect\ApiException $e) {
				
				 $error = $e->getResponseBody()->errors[0]->category;
				 
				 tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, "Square Debug Error", "Square has encountered some errors:\n\n".var_export($e, true)."n\n\n". "Customer ID: ".$customer_id . "r\n\r\n\r\n".var_export($order, true));
				
				
				}

			
			if  ($error == null)  {
				return true;
			} 
			
			
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code . '&error='.$error, 'SSL'));
		}
		
		
		    function get_error() {
				  global $HTTP_GET_VARS;
				  $error_message = MODULE_SQUARE_ERROR_GENERAL;
			switch ($HTTP_GET_VARS['error']) {
				case "INVALID_REQUEST_ERROR":
					$errorMsg = "Your request was invalid and cannot be processed right now. Please try again later.";
				break;
				
				case "API_ERROR":
					$errorMsg = "There was an error with the installation of Square Payments. Please contact the webmaster about the issue.";
				break;
				
				case "AUTHENTICATION_ERROR":
					$errorMsg = "This website is not authorized to process this transaction.";
				break;
				
				case "RATE_LIMIT_ERROR":
					$errorMsg = "We have reached our transactions' limit per minute. Please try again later.";
				break;
				
				case "PAYMENT_METHOD_ERROR":
					$errorMsg = "An error occurred while processing your payment. Please verify your credit card information.";
				break;
				
				default:
					$errorMsg = "An unexpected error occured. Webmaster was notified about the issue.";
				break;
				
				}
				  $error = array('title' => MODULE_SQUARE_ERROR_TITLE,
								 'error' => $errorMsg);
				  return $error;
			}
		
		function after_process() {
				global $response_array, $insert_id;
				$pp_result = "";

     			  $sql_data_array = array('orders_id' => $insert_id,
                              'orders_status_id' => MODULE_PAYMENT_SQUARE_TRANSACTION_ORDER_STATUS_ID,
                              'date_added' => 'now()',
                              'customer_notified' => '0',

                              'comments' => $pp_result);
     			  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

		}
		
		
		
	}
?>