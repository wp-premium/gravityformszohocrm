<?php

/**
 * Gravity Forms Zoho CRM API Library.
 *
 * @since     1.6 Upgraded for Zoho CRM API 2.0.
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_ZohoCRM_API {

	/**
	 * Zoho CRM API URL.
	 *
	 * @since  1.6 Switched v2 API URL.
	 * @since  1.0
	 * @access protected
	 * @var    string $api_url Zoho CRM API URL.
	 */
	protected $api_url = 'https://www.zohoapis.com/crm/v2/';

	/**
	 * Zoho CRM authentication data.
	 *
	 * @since  1.6
	 * @access protected
	 * @var    array $auth_data Zoho CRM authentication data.
	 */
	protected $auth_data = null;

	/**
	 * Initialize API library.
	 *
	 * @since  1.7 Set $api_url by location.
	 * @since  1.6 Replaced $auth_token with $auth_data.
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $auth_data Zoho CRM authentication data.
	 */
	public function __construct( $auth_data = null ) {

		$this->auth_data = $auth_data;

		if ( rgar( $auth_data, 'location' ) ) {
			$api_domains = array(
				'us' => 'https://www.zohoapis.com/',
				'eu' => 'https://www.zohoapis.eu/',
				'in' => 'https://www.zohoapis.in/',
				'au' => 'https://www.zohoapis.com.au/',
			);

			$api_domain    = ( ! rgar( $api_domains, $auth_data['location'] ) ) ? $api_domains['us'] : $api_domains[ $auth_data['location'] ];
			$this->api_url = $api_domain . 'crm/v2/';
		} else {
			/**
			 * Allows Zoho API URL to be changed.
			 * In addition to crm.zoho.com, Zoho CRM has an European solution that points to crm.zoho.eu.
			 *
			 * @since 1.2.5
			 *
			 * @param string $api_url Zoho CRM accounts API URL.
			 */
			$this->api_url = apply_filters( 'gform_zoho_api_url', $this->api_url );
		}

	}

	/**
	 * Get auth token.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $email_address Account email address. Defaults to null.
	 * @param string $password      Account password. Defaults to null.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_auth_token( $email_address = null, $password = null ) {

		// If email address or password are not provided, return null.
		if ( empty( $email_address ) || empty( $password ) ) {
			return null;
		}

		// Get request URL.
		$request_url = gf_zohocrm()->get_accounts_api_url();

		// Prepare request parameters.
		$request_params = array(
			'SCOPE'    => 'ZohoCRM/crmapi',
			'EMAIL_ID' => $email_address,
			'PASSWORD' => $password,
		);

		// Process request.
		$response = wp_remote_request( $request_url . '/apiauthtoken/nb/create',
			array(
				'body'   => $request_params,
				'method' => 'POST',
			)
		);

		// If response is a WordPress error, throw exception.
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		// Split response out based on line breaks.
		$auth_data = explode( "\n", $response['body'] );

		// Remove unneeded authentication data.
		unset( $auth_data[0], $auth_data[1], $auth_data[4] );

		// Parse authentication data.
		foreach ( $auth_data as $key => $line ) {

			// Split key and value apart.
			$line = explode( '=', $line );

			// Add key value pair to authentication data array.
			$auth_data[ $line[0] ] = $line[1];

			// Remove original item.
			unset( $auth_data[ $key ] );

		}

		// If authentication request failed, throw exception.
		if ( 'FALSE' == $auth_data['RESULT'] ) {
			throw new Exception( $auth_data['CAUSE'] );
		}

		return $auth_data['AUTHTOKEN'];

	}





	// # AUTHENTICATION ------------------------------------------------------------------------------------------------

	/**
	 * Refresh access tokens.
	 *
	 * @since 1.6
	 *
	 * @return array|WP_Error
	 */
	public function refresh_token() {
		// Get authentication data.
		$auth_data = $this->auth_data;

		// Add the location params which added after 1.6.
		if ( ! rgar( $auth_data, 'location' ) ) {
			$auth_data['location'] = 'us';
		}

		// If refresh token is not provided, throw exception.
		if ( ! rgar( $auth_data, 'refresh_token' ) ) {
			return new WP_Error( 'zohocrm_refresh_token_error', esc_html__( 'Refresh token must be provided.', 'gravityformszohocrm' ) );
		}

		$response = wp_remote_get(
			add_query_arg( array(
				'refresh_token' => $auth_data['refresh_token'],
				'location'      => $auth_data['location'],
				'state'         => wp_create_nonce( gf_zohocrm()->get_authentication_state_action() ),
 			), gf_zohocrm()->get_gravity_api_url( '/auth/zoho-crm/refresh' ) )
		);

		$response_code = wp_remote_retrieve_response_code( $response );
		$message       = wp_remote_retrieve_response_message( $response );

		if ( $response_code === 200 ) {
			$auth_response = json_decode( wp_remote_retrieve_body( $response ), true );
			$auth_payload  = json_decode( $auth_response['auth_payload'], true );

			if ( isset( $auth_payload['access_token'] ) && wp_verify_nonce( $auth_payload['state'], gf_zohocrm()->get_authentication_state_action() ) ) {
				$auth_data['access_token'] = $auth_payload['access_token'];

				$this->auth_data = $auth_data;

				return $auth_data;
			}

			if ( isset( $auth_payload['error'] ) ) {
				$message = $auth_payload['error'];
			}
		}

		return new WP_Error( 'zohocrm_refresh_token_error', $message, array( 'status' => $response_code ) );
	}

	/**
	 * Revoke authentication token.
	 *
	 * @since  1.6
	 * @access public
	 *
	 * @return array|WP_Error
	 */
	public function revoke_token() {

		// Get authentication data.
		$auth_data = $this->auth_data;

		// If refresh token is not provided, throw exception.
		if ( ! rgar( $auth_data, 'refresh_token' ) ) {
			return new WP_Error( 'zohocrm_revoke_token_error', esc_html__( 'Refresh token must be provided.', 'gravityformszohocrm' ) );
		}

		return $this->make_request( 'token/revoke', array( 'token' => $auth_data['refresh_token'] ), 'POST' );

	}





	// # FIELDS --------------------------------------------------------------------------------------------------------

	/**
	 * Get fields for module.
	 *
	 * @since  1.6 Updated API endpoint.
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $module Module name. Defaults to Leads.
	 *
	 * @return array|WP_Error
	 */
	public function get_fields( $module = 'Leads' ) {

		// Use the "layouts" endpoint so we can have the "requried" key in fields data.
		return $this->make_request( 'settings/layouts', array( 'module' => $module ), 'GET', 'layouts' );

	}





	// # FILES ---------------------------------------------------------------------------------------------------------

	/**
	 * Upload a file.
	 *
	 * @since  1.6 Changed request format.
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module    Module record belongs to. Defaults to Leads.
	 * @param string $record_id Record ID to attach file to.
	 * @param string $file_path File to upload.
	 *
	 * @return array|WP_Error
	 */
	public function upload_file( $module = 'Leads', $record_id = '', $file_path = '' ) {

		// Build request URL.
		$request_url = $this->api_url . $module . '/' . $record_id . '/Attachments';

		// Generate boundary.
		$boundary = wp_generate_password( 24 );

		// Prepare request body.
		$body  = '--' . $boundary . "\r\n";
		$body .= 'Content-Disposition: form-data; name="file"; filename="' . basename( $file_path ) . '"' . "\r\n\r\n";
		$body .= file_get_contents( $file_path ) . "\r\n";
		$body .= '--' . $boundary . '--';

		// Build request arguments.
		$args = array(
			'body'    => $body,
			'method'  => 'POST',
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Zoho-oauthtoken ' . $this->auth_data['access_token'],
				'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
			),
		);

		// Execute API request.
		$response = wp_remote_request( $request_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If an incorrect response code was returned, throw an exception.
		$retrieved_response_code = wp_remote_retrieve_response_code( $response );
		if ( $retrieved_response_code !== 200 ) {
			return new WP_Error( 'zohocrm_refresh_token_error', esc_html__( "Expected response code: 200. Returned response code: {$retrieved_response_code}.", 'gravityformszohocrm' ) );
		}

		// Convert JSON response to array.
		$response = gf_zohocrm()->maybe_decode_json( $response['body'] );

		return rgar( $response, 'data' ) ? $response['data'] : $response;

	}





	// # RECORDS -------------------------------------------------------------------------------------------------------

	/**
	 * Get a specific record.
	 *
	 * @since  1.6
	 * @access public
	 *
	 * @param string $module    Module to insert record into. Defaults to Leads.
	 * @param string $record_id Record ID to request.
	 *
	 * @return array|WP_Error
	 */
	public function get_record( $module = 'Leads', $record_id = '' ) {

		return $this->make_request( $module . '/' . $record_id, null, 'GET', 'data' );

	}

	/**
	 * Insert new record.
	 *
	 * @since  1.6 Added new parameter $upsert.
	 * @since  1.0
	 * @access public
	 *
	 * @param string  $module Module to insert record into. Defaults to Leads.
	 * @param array   $record Record to insert.
	 * @param boolean $upsert If use the /upsert endpoint.
	 *
	 * @return array|WP_Error
	 */
	public function insert_record( $module = 'Leads', $record = array(), $upsert = false ) {

		$response_code = 200;
		// Insert record returns 201 as success.
		if ( ! $upsert ) {
			$response_code = 201;
		} else {
			// Add extra path.
			$upsert = '/upsert';
		}

		/**
		 * Modify the record arguments before they are sent to Zoho CRM.
		 *
		 * @since 1.8.1
		 *
		 * @param array  $record  The record argument.
		 * @param string $module The module.
		 * @param array  $feed  Feed object.
		 * @param array  $entry Entry object.
		 * @param array  $form  Form object.
		 */
		$filtered_record = gf_apply_filters( array( 'gform_zohocrm_record', $record['form']['id'] ), $record, $module, $record['feed'], $record['entry'], $record['form'] );

		if ( $filtered_record !== $record ) {
			gf_zohocrm()->log_debug( __METHOD__ . '(): record sent to Zoho CRM: ' . print_r( $filtered_record, true ) );

			$record = $filtered_record;
		}

		// unset extra fields.
		$check_fields = array( 'feed', 'entry', 'form' );
		foreach ( $check_fields as $field ) {
			${$field} = rgar( $record, $field );
			if ( ! empty( ${$field} ) ) {
				unset( $record[ $field ] );
			}
		}

		$result = $this->make_request( $module . $upsert, $record, 'POST', 'data', $response_code );

		// handle the 202 error by excluding fields with invalid data type and resubmit the data to API again.
		if ( is_wp_error( $result ) && in_array( $module, array( 'Contacts', 'Leads' ), true ) ) {
			$error_data = $result->get_error_data();
			$status     = rgar( $error_data, 'status' );
			$data       = rgar( $error_data, 'data' );

			if ( $status === 202 && is_array( $data ) ) {
				$code = rgar( $data[0], 'code' );
				if ( $code === 'INVALID_DATA' ) {
					$details = rgar( $data[0], 'details' );

					if ( empty( $details['api_name'] ) ) {
						// Aborting here to prevent an infinite loop that has occurred in a few cases.
						gf_zohocrm()->log_error( __METHOD__ . '(): ' . print_r( $error_data, true ) );

						return $result;
					}

					gf_zohocrm()->add_feed_error( sprintf( esc_html__( 'The value of %s cannot be sent to Zoho CRM. Reason: The data format is invalid.', 'gravityformszohocrm' ), rgar( $details, 'api_name', 'api_name missing' ) ), $feed, $entry, $form );

					unset( $record['data'][0][ $details['api_name'] ] );

					foreach ( $check_fields as $field ) {
						$record[ $field ] = ${$field};
					}

					return $this->insert_record( $module, $record, $upsert );
				}
			}
		}

		return $result;

	}

	/**
	 * Search for record.
	 *
	 * @since  1.6
	 *
	 * @param string $module   Module to insert record into. Defaults to Leads.
	 * @param array  $options  Search options.
	 *
	 * @return array
	 */
	public function search_record( $module = 'Leads', $options = array() ) {

		return $this->make_request( $module . '/search', $options, 'GET', 'data', array( 200, 204 ) );

	}





	// # USERS ---------------------------------------------------------------------------------------------------------

	/**
	 * Get available users.
	 *
	 * @since  1.6 Made $users static to reduce API calls.
	 * @since  1.0
	 * @access public
	 *
	 * @param string $type Type of users to get. Defaults to ActiveUsers.
	 *
	 * @return array|WP_Error
	 */
	public function get_users( $type = 'ActiveUsers' ) {
		static $users;

		if ( ! isset( $users ) ) {
			$users = $this->make_request( 'users', array( 'type' => $type ), 'GET', 'users' );
		}

		return $users;
	}





	// # REQUEST METHODS -----------------------------------------------------------------------------------------------

	/**
	 * Make API request.
	 *
	 * @since  1.6 Made $response_code to accept an array of codes.
	 * @since  1.0
	 * @access public
	 *
	 * @param string    $path          Request path.
	 * @param array     $options       Request options.
	 * @param string    $method        Request method. Defaults to GET.
	 * @param string    $return_key    Array key from response to return. Defaults to null (return full response).
	 * @param int|array $response_code Expected HTTP response code.
	 *
	 * @return array|WP_Error
	 */
	public function make_request( $path = '', $options = array(), $method = 'GET', $return_key = null, $response_code = 200 ) {

		// Get authentication data.
		$auth_data = $this->auth_data;

		// Build request URL.
		if ( $path === 'token/revoke' ) {
			$request_url = $this->get_accounts_server( $auth_data['location'] ) . "oauth/v2/{$path}";

			// Add parameters to URL.
			$auth_url = add_query_arg( $options, $request_url );

			// Execute request.
			$response = wp_remote_post( $auth_url );
		} else {
			$request_url = $this->api_url . $path;

			// Add options if this is a GET request.
			if ( 'GET' === $method ) {
				$request_url = add_query_arg( $options, $request_url );
			}

			// Prepare request arguments.
			$args = array(
				'method'  => $method,
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Zoho-oauthtoken ' . $this->auth_data['access_token'],
					'Content-Type'  => 'application/json',
				),
			);

			// Add request arguments to body.
			if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
				$args['body'] = json_encode( $options );
			}

			// Execute API request.
			$response = wp_remote_request( $request_url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If an incorrect response code was returned, return WP_Error.
		$retrieved_response_code = wp_remote_retrieve_response_code( $response );
		if ( is_int( $response_code ) ) {
			$response_code = array( $response_code );
		}
		if ( ! in_array( $retrieved_response_code, $response_code, true ) ) {
			$response_code = implode( ', ', $response_code );
			$error_message = "Expected response code: {$response_code}. Returned response code: {$retrieved_response_code}.";

			$json_body  = gf_zohocrm()->maybe_decode_json( $response['body'] );
			$error_data = array( 'status' => $retrieved_response_code );
			if ( rgar( $json_body, 'data' ) ) {
				$error_data['data'] = $json_body['data'];
			} elseif ( rgar( $json_body, 'message' ) ) {
				$error_data['data'] = $json_body['message'];
			}

			return new WP_Error( 'zohocrm_api_error', $error_message, $error_data );
		}

		// Convert JSON response to array.
		$response = gf_zohocrm()->maybe_decode_json( $response['body'] );

		// If a return key is defined and array item exists, return it.
		if ( ! empty( $return_key ) && rgar( $response, $return_key ) ) {
			return rgar( $response, $return_key );
		}

		return $response;

	}

	/**
	 * Whitelist Zoho accounts server list.
	 *
	 * @sinc3 1.7
	 *
	 * @param string $location Location.
	 *
	 * @return string
	 */
	public function get_accounts_server( $location = 'us' ) {
		$servers = array(
			'us' => 'https://accounts.zoho.com/',
			'eu' => 'https://accounts.zoho.eu/',
			'in' => 'https://accounts.zoho.in/',
			'au' => 'https://accounts.zoho.com.au/',
		);

		return ( ! rgar( $servers, $location ) ) ? 'https://accounts.zoho.com/' : $servers[ $location ];
	}

}
