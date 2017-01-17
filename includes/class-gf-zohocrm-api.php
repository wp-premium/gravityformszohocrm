<?php

/**
 * Gravity Forms Zoho CRM API Library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_ZohoCRM_API {

	/**
	 * Zoho CRM API URL.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_url Zoho CRM API URL.
	 */
	protected $api_url = 'https://crm.zoho.com/crm/private/';

	/**
	 * Initialize API library.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $auth_token Zoho CRM authentication token. Defaults to empty string.
	 */
	public function __construct( $auth_token = null ) {

		/**
		 * Allows Zoho API URL to be changed.
		 * In addition to crm.zoho.com, Zoho CRM has an European solution that points to crm.zoho.eu.
		 *
		 * @since 1.2.5
		 *
		 * @param string $api_url Zoho CRM accounts API URL.
		 */
		$this->api_url = apply_filters( 'gform_zoho_api_url', $this->api_url );

		$this->auth_token = $auth_token;

	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module     Request module. Defaults to Leads.
	 * @param string $action     Request action.
	 * @param array  $options    Request options.
	 * @param string $method     HTTP method. Defaults to GET.
	 * @param string $return_key Array key from response to return. Defaults to null (return full response).
	 * @param bool   $is_xml     If request is an XML. Defaults to false.
	 *
	 * @return array|string
	 */
	public function make_request( $module = 'Leads', $action = null, $options = array(), $method = 'GET', $return_key = null, $is_xml = false ) {

		// Preapre request options string.
		$request_options  = 'authtoken=' . $this->auth_token . '&scope=crmapi';
		$request_options .= ( ( 'GET' === $method || ( 'POST' === $method && $is_xml ) ) && ! empty( $options ) ) ? '&' . http_build_query( $options ) : '';

		// Prepare request URL.
		$request_url  = $this->api_url;
		$request_url .= ( $is_xml ) ? 'xml/' : 'json/';
		$request_url .= $module;
		$request_url .= ! empty( $action ) ? '/' . $action : null;
		$request_url .= '?' . $request_options;

		// Prepare request arguments.
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Accept'       => $is_xml ? 'application/xml' : 'application/json',
				'Content-Type' => $is_xml ? 'application/xml' : 'application/json',
			),
		);

		// Add request arguments to body.
		if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
			$args['body'] = $is_xml ? $options : json_encode( $options );
		}

		// Execute API request.
		$response = wp_remote_request( $request_url, $args );

		// If API request returns a WordPress error, throw an exception.
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		// If this is an XML request, convert response to an XML object.
		if ( $is_xml ) {

			// Convert response to XML object.
			$response = simplexml_load_string( $response['body'] );

			// If an error is returned, throw an exception.
			if ( isset( $response->error ) ) {
				throw new Exception( $response->error->message );
			}
			
			// If an insert record error is returned, throw an exception.
			if ( isset( $response->result->row->error ) ) {
				throw new Exception( $response->result->row->error->details );
			}

			return $response;

		}

		// Convert JSON response to array.
		$response = json_decode( $response['body'], true );

		// If an error is returned, throw an exception.
		if ( isset( $response['response']['error'] ) ) {
			throw new Exception( $response['response']['error']['message'] );
		}

		// If a return key is defined and array item exists, return it.
		if ( ! empty( $return_key ) && isset( $response[ $return_key ] ) ) {
			return $response[ $return_key ];
		}

		return $response;

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
	 * @return array
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

	/**
	 * Get fields for module.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $module Module name. Defaults to Leads.
	 *
	 * @uses GF_ZohoCRM_API::make_request()
	 *
	 * @return array
	 */
	public function get_fields( $module = 'Leads' ) {

		return $this->make_request( $module, 'getFields', array(), 'GET', $module );

	}

	/**
	 * Get the list of users.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $type Type of users to get. Defaults to ActiveUsers.
	 *
	 * @uses GF_ZohoCRM_API::make_request()
	 *
	 * @return array
	 */
	public function get_users( $type = 'ActiveUsers' ) {

		return $this->make_request( 'Users', 'getUsers', array( 'type' => $type ), 'GET', 'users' );

	}

	/**
	 * Insert new record.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module  Module to insert record into. Defaults to Leads.
	 * @param array  $record  Record to insert.
	 * @param array  $options Insert request options. Defaults to empty array.
	 *
	 * @uses GF_ZohoCRM_API::make_request()
	 *
	 * @return object
	 */
	public function insert_record( $module = 'Leads', $record, $options = array() ) {

		$insert = array_merge( array( 'xmlData' => $record ), $options );

		return $this->make_request( $module, 'insertRecords', $insert, 'POST', null, true );

	}

	/**
	 * Upload a file.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module    Module record belongs to. Defaults to Leads.
	 * @param string $record_id Record ID to attach file to.
	 * @param string $file_path File to upload.
	 *
	 * @return null|string
	 */
	public function upload_file( $module = 'Leads', $record_id, $file_path ) {

		// Prepare file details.
		$upload = array(
			'id'      => $record_id,
			'content' => curl_file_create( $file_path ),
		);

		// Initialize new curl instance.
		$curl = curl_init();

		// Set curl request parameters.
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_VERBOSE, false );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $upload );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_URL, $this->api_url . 'json/' . $module . '/uploadFile?authtoken=' . $this->auth_token . '&scope=crmapi' );

		// Upload file.
		$response = curl_exec( $curl );

		// Close curl instance.
		curl_close( $curl );

		// Decode response from Zoho CRM.
		$response = json_decode( $response, true );

		// If Zoho CRM returns an error, throw an exception.
		if ( isset( $response['response']['error'] ) ) {
			throw new Exception( $response['response']['error']['message'] );
		}

		// Get upload file ID.
		if ( ! empty ( $response['response']['result']['recorddetail'] ) ) {
			foreach ( $response['response']['result']['recorddetail']['FL'] as $record ) {
				if ( $record['val'] == 'Id' ) {
					return $record['content'];
				}
			}
		}

		return null;

	}

}


if ( ! function_exists( 'curl_file_create' ) ) {

	function curl_file_create( $filename, $mimetype = '', $postname = '' ) {

		return "@$filename;filename="
            . ( $postname ? $postname : basename( $filename ) )
            . ( $mimetype ? ";type=$mimetype" : '' );

	}

}
