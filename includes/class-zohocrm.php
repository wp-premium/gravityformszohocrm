<?php
	
	class Zoho_CRM {
		
		protected $api_url = 'https://crm.zoho.com/crm/private/';
		
		public function __construct( $auth_token = null ) {
			
			$this->auth_token = $auth_token;
			
		}

		/**
		 * Make API request.
		 * 
		 * @access public
		 * @param string $module (default: 'Leads')
		 * @param mixed $action (default: null)
		 * @param array $options (default: array())
		 * @param string $method (default: 'GET')
		 * @param mixed $return_key (default: null)
		 * @return array
		 */
		public function make_request( $module = 'Leads', $action = null, $options = array(), $method = 'GET', $return_key = null, $is_xml = false ) {
			
			/* Build request options string. */
			$request_options  = 'authtoken=' . $this->auth_token . '&scope=crmapi';
			$request_options .= ( ( $method == 'GET' || ( $method == 'POST' && $is_xml ) ) && ! empty( $options ) ) ? '&' . http_build_query( $options ) : '';
			
			/* Build request URL. */
			$request_url  = $this->api_url;
			$request_url .= ( $is_xml ) ? 'xml/' : 'json/';
			$request_url .= $module;
			$request_url .= ! empty( $action ) ? '/' . $action : null;
			$request_url .= '?' . $request_options;
			
			/* Prepare request and execute. */
			$args = array( 
				'method'  => $method,
				'headers' => array(
					'Accept'       => $is_xml ? 'application/xml' : 'application/json',
					'Content-Type' => $is_xml ? 'application/xml' : 'application/json'
				)
			);
			
			if ( $method == 'POST' || $method == 'PUT' ) {
				
				$args['body'] = $is_xml ? $options : json_encode( $options );
				
			}
			
			$response = wp_remote_request( $request_url, $args );
						
			/* If WP_Error, die. Otherwise, return decoded JSON. */
			if ( is_wp_error( $response ) ) {
				
				die( 'Request failed. '. $response->get_error_message() );
				
			} else {
				
				if ( $is_xml ) {
					
					$response_body = simplexml_load_string( $response['body'] );
					
					if ( isset( $response_body->error ) ) {
						
						throw new Exception( $response_body->error->message );
						
					} else {
						
						return $response_body;
						
					}
					
				} else {
				
					$response_body = json_decode( $response['body'], true );

					if ( isset( $response_body['response']['error'] ) ) {
						
						throw new Exception( $response_body['response']['error']['message'] );
						
					} else {
	
						return ( empty( $return_key ) || ( ! empty( $return_key ) && ! isset( $response_body[$return_key] ) ) ) ? $response_body : $response_body[$return_key];
						
					}

				}
				
			}
			
		}

		/**
		 * Get auth token.
		 * 
		 * @access public
		 * @static
		 * @param string $email_address (default: NULL)
		 * @param string $password (default: NULL)
		 * @return array
		 */
		public static function get_auth_token( $email_address = null, $password = null ) {
			
			/* If email address or password are not provided, return null. */
			if ( empty( $email_address ) || empty( $password ) ) {
				
				return null;
				
			}
			
			/* Prepare parameters for request. */
			$parameters = array(
				'SCOPE'    => 'ZohoCRM/crmapi',
				'EMAIL_ID' => $email_address,
				'PASSWORD' => $password
			);
			
			/* Execute request. */
			$response = wp_remote_request( 'https://accounts.zoho.com/apiauthtoken/nb/create', array(
				'body'   => $parameters,
				'method' => 'POST'
			) );
			
			/* If WordPress error, exit. */
			if ( is_wp_error( $response ) ) {
				
				die( 'Request failed. ' . $response->get_error_messages() );
				
			}
			
			/* Split response out based on line breaks. */
			$auth_response = explode( "\n", $response['body'] );
			
			/* Remove the unneeded lines. */
			unset( $auth_response[0] );
			unset( $auth_response[1] );
			unset( $auth_response[4] );
			
			/* Refactor auth response. */
			foreach ( $auth_response as $key => $line ) {
				
				$line = explode( '=', $line );
				$auth_response[ $line[0] ] = $line[1];
				unset( $auth_response[$key] );
				
			}
			
			/* If failed, set success to false and return error message. */
			if ( isset( $auth_response['CAUSE'] ) ) {
				
				return array(
					'success' => false,
					'error'   => $auth_response['CAUSE']
				);
				
			}
			
			/* If succeeded, set success to true and return auth token. */ 
			if ( isset( $auth_response['AUTHTOKEN'] ) ) {
				
				return array(
					'success'    => true,
					'auth_token' => $auth_response['AUTHTOKEN']
				);
				
			}
			
		}
		
		/**
		 * Get fields for module.
		 * 
		 * @access public
		 * @param string $module (default: 'Leads')
		 * @return array
		 */
		public function get_fields( $module = 'Leads' ) {
			
			return $this->make_request( $module, 'getFields', array(), 'GET', $module );
			
		}
		
		/**
		 * Get the list of users.
		 * 
		 * @access public
		 * @param string $type (default: 'ActiveUsers')
		 * @return array $users
		 */
		public function get_users( $type = 'ActiveUsers' ) {
			
			return $this->make_request( 'Users', 'getUsers', array( 'type' => $type ), 'GET', 'users' );
			
		}
		
		/**
		 * Insert new record.
		 * 
		 * @access public
		 * @param string $module (default: 'Leads')
		 * @param array $record
		 * @param array $options (default: array())
		 * @return object
		 */
		public function insert_record( $module = 'Leads', $record, $options = array() ) {
			
			$insert = array_merge( array( 'xmlData' => $record ), $options );

			return $this->make_request( $module, 'insertRecords', $insert, 'POST', null, true );
			
		}
		
		/**
		 * Upload a file.
		 * 
		 * @access public
		 * @param string $module (default: 'Leads')
		 * @param mixed $record_id
		 * @param mixed $file_path
		 * @return void
		 */
		public function upload_file( $module = 'Leads', $record_id, $file_path ) {
			
			$upload = array( 
				'id'      => $record_id, 
				'content' => curl_file_create( $file_path )
			);
			
			$curl = curl_init();
			
			curl_setopt( $curl, CURLOPT_HEADER, false );
			curl_setopt( $curl, CURLOPT_VERBOSE, false );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $upload );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_URL, $this->api_url . 'json/' . $module . '/uploadFile?authtoken=' . $this->auth_token . '&scope=crmapi' );
			
			$response = curl_exec( $curl );
			
			curl_close( $curl );
			
			$response = json_decode( $response, true );

			if ( isset( $response['response']['error'] ) ) {
				
				throw new Exception( $response['response']['error']['message'] );
				
			} else {
				
				if ( ! empty ( $response['response']['result']['recorddetail'] ) ) {
					
					foreach ( $response['response']['result']['recorddetail']['FL'] as $record ) {
						
						if ( $record['val'] == 'Id' )
							return $record['content'];
						
					}
					
				}
				
				return null;
				
			}
			
		}
		
	}
	
	
	if ( ! function_exists( 'curl_file_create' ) ) {
		
		function curl_file_create( $filename, $mimetype = '', $postname = '' ) {
			
			return "@$filename;filename="
	            . ( $postname ? $postname : basename( $filename ) )
	            . ( $mimetype ? ";type=$mimetype" : '' );
			
		}
		
	}
