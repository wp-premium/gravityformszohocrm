<?php
	
GFForms::include_feed_addon_framework();

class GFZohoCRM extends GFFeedAddOn {
	
	protected $_version = GF_ZOHOCRM_VERSION;
	protected $_min_gravityforms_version = '1.9.14.26';
	protected $_slug = 'gravityformszohocrm';
	protected $_path = 'gravityformszohocrm/zohocrm.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Zoho CRM Add-On';
	protected $_short_title = 'Zoho CRM';
	protected $_enable_rg_autoupgrade = true;
	protected $api = null;
	protected $fields_transient_name = 'gform_zohocrm_fields';
	private static $_instance = null;

	/* Permissions */
	protected $_capabilities_settings_page = 'gravityforms_zohocrm';
	protected $_capabilities_form_settings = 'gravityforms_zohocrm';
	protected $_capabilities_uninstall = 'gravityforms_zohocrm_uninstall';

	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_zohocrm', 'gravityforms_zohocrm_uninstall' );
	
	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return GFZohoCRM
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
		
	}

	/**
	 * Plugin starting point. Adds PayPal delayed payment support.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		parent::init();
		
		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Create record in Zoho CRM only when payment is received.', 'gravityformszohocrm' )
			)
		);
		
	}

	/**
	 * Register needed styles.
	 * 
	 * @access public
	 * @return array $styles
	 */
	public function styles() {
		
		$styles = array(
			array(
				'handle'  => 'gform_zohocrm_form_settings_css',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				)
			)
		);
		
		return array_merge( parent::styles(), $styles );
		
	}

	/**
	 * Add clear custom fields cache button.
	 * 
	 * @access public
	 * @return void
	 */
	public function render_uninstall() {
		
		$html  = '<div class="hr-divider"></div>';
		$html .= '<h3><span><i class="fa fa-list"></i> ' . esc_html__( 'Clear Custom Fields Cache', 'gravityformszohocrm' ) . '</span></h3>';
		
		$html .= '<p>' . esc_html__( 'Due to Zoho CRM\'s daily API usage limits, Gravity Forms stores Zoho CRM custom fields data for twelve hours. If you make a change to your custom fields, you might not see it reflected immediately due to this data caching. To manually clear the custom fields cache, click the button below.', 'gravityformzohocrm' ) . '</p>';
		
		$html .= '<p><a href="' . add_query_arg( 'clear_field_cache', 'true' ) . '" class="button button-primary">' . esc_html__( 'Clear Custom Fields Cache', 'gravityformszohocrm' ) . '</a></p>'; 
		
		echo $html;
		
		echo parent::render_uninstall();
		
	}

	/**
	 * Add clear custom fields cache check.
	 * 
	 * @access public
	 * @return void
	 */
	public function plugin_settings_page() {
		
		$this->maybe_clear_fields_cache();
		
		parent::plugin_settings_page();
		
	}

	/**
	 * Clear the Zoho CRM custom fields cache.
	 * 
	 * @access public
	 * @return void
	 */
	public function maybe_clear_fields_cache() {
		
		/* If the clear_field_cache parameter isn't set, exit. */
		if ( rgget( 'clear_field_cache' ) !== 'true' ) {
			return;
		}
		
		/* Clear the cache. */
		delete_transient( $this->fields_transient_name );
		
		/* Add success message. */
		GFCommon::add_message( esc_html__( 'Custom fields cache has been cleared.', 'gravityformszohocrm' ) );
		
	}

	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
		
		$description  = '<p>';
		$description .= sprintf(
			esc_html__( 'Zoho CRM is a contact management tool that gives you a 360-degree view of your complete sales cycle and pipeline. Use Gravity Forms to collect customer information and automatically add them to your Zoho CRM account. If you don\'t have a Zoho CRM account, you can %1$s sign up for one here.%2$s', 'gravityformszohocrm' ),
			'<a href="http://www.zoho.com/crm/" target="_blank">', '</a>'
		);
		$description .= '</p>';
						
		return array(
			array(
				'title'       => '',
				'description' => $description,
				'fields'      => array(
					array(
						'name'              => 'authMode',
						'label'             => esc_html__( 'Authenticate With', 'gravityformszohocrm' ),
						'type'              => 'radio',
						'default_value'     => 'email',
						'onclick'           => "jQuery(this).parents('form').submit();",
						'choices'           => array(
							array(
								'label' => esc_html__( 'Email Address and Password', 'gravityformszohocrm' ),
								'value' => 'email'
							),
							array(
								'label' => esc_html__( 'Third Party Service (Google Apps, Facebook, Yahoo)', 'gravityformszohocrm' ),
								'value' => 'third_party'
							)
						)
					),
					array(
						'name'              => 'emailAddress',
						'label'             => esc_html__( 'Email Address', 'gravityformszohocrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( '', 'email' ) ),
						'feedback_callback' => array( $this, 'plugin_settings_email_feedback' )
					),
					array(
						'name'              => 'password',
						'label'             => esc_html__( 'Password', 'gravityformszohocrm' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( '', 'email' ) ),
						'feedback_callback' => array( $this, 'plugin_settings_email_feedback' )
					),
					array(
						'name'              => '',
						'label'             => '',
						'type'              => 'auth_token_button',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( 'third_party' ) ),
					),
					array(
						'name'              => 'authToken',
						'type'              => 'hidden',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( '', 'email' ) ),
					),
					array(
						'name'              => 'authToken',
						'label'             => esc_html__( 'Authentication Token', 'gravityformszohocrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( 'third_party' ) ),
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => esc_html__( 'Zoho CRM settings have been updated.', 'gravityformszohocrm' )
						),
					),
				),
			),
		);
		
	}
	
	/**
	 * Create Generate Auth Token settings field.
	 * 
	 * @access public
	 * @param array $field
	 * @param bool $echo (default: true)
	 * @return string $html
	 */
	public function settings_auth_token_button( $field, $echo = true ) {
		
		$html = sprintf(
			'<a href="%1$s" class="button" onclick="%2$s">%3$s</a>',
			'https://accounts.zoho.com/apiauthtoken/create?SCOPE=ZohoCRM/crmapi',
			"window.open( 'https://accounts.zoho.com/apiauthtoken/create?SCOPE=ZohoCRM/crmapi', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=590,height=700' );return false;",
			esc_html__( 'Click here to generate an authentication token.', 'gravityformszohocrm' )
		);
		
		if ( $echo ) {
			echo $html;
		}

		return $html;
		
	}

	/**
	 * Get feedback for email address and password fields.
	 * 
	 * @access public
	 * @param string $value
	 * @param array $field
	 * @return bool|null
	 */
	public function plugin_settings_email_feedback( $value, $field ) {
		
		if ( rgblank( $value ) ) {
			return null;
		}
		
		return $this->initialize_api();
		
	}

	/**
	 * Fork of maybe_save_plugin_settings to get auth token..
	 * 
	 * @access public
	 * @return void
	 */
	public function maybe_save_plugin_settings() {

		if ( $this->is_save_postback() ) {

			// store a copy of the previous settings for cases where action whould only happen if value has changed
			$this->set_previous_settings( $this->get_plugin_settings() );

			$settings = $this->get_posted_settings();
			
			if ( $this->have_plugin_settings_changed() ) {
				
				$settings = $this->update_auth_token( $settings );
				
			}
			
			$sections = $this->plugin_settings_fields();
			$is_valid = $this->validate_settings( $sections, $settings );

			if ( $is_valid ) {
				
				$settings = $this->filter_settings( $sections, $settings );
				$this->update_plugin_settings( $settings );
				GFCommon::add_message( $this->get_save_success_message( $sections ) );
				
			} else {
				
				GFCommon::add_error_message( $this->get_save_error_message( $sections ) );
			}
			
		}

	}
	
	/**
	 * Check if the plugin settings have changed.
	 * 
	 * @access public
	 * @return bool
	 */
	public function have_plugin_settings_changed() {
		
		/* Get previous and new settings. */
		$previous_settings = $this->get_previous_settings();
		$new_settings      = $this->get_posted_settings();
				
		/* Check auth mode. */
		if ( rgar( $new_settings, 'authMode' ) === 'third_party' ) {
			return false;
		}
				
		/* If the email address has changed, return true. */
		if ( rgar( $previous_settings, 'emailAddress' ) !== rgar( $new_settings, 'emailAddress' ) ) {
			
			return true;
			
		}

		/* If the password has changed, return true. */
		if ( rgar( $previous_settings, 'password' ) !== rgar( $new_settings, 'password' ) ) {
			
			return true;
			
		}
		
		return false;
		
	}

	/**
	 * Update plugin settings with new auth token on save.
	 * 
	 * @access public
	 * @param array $settings
	 * @return array $settings
	 */
	public function update_auth_token( $settings ) {
		
		/* Include the API library. */
		if ( ! class_exists( 'Zoho_CRM' ) ) {
			require_once 'includes/class-zohocrm.php';
		}
		
		/* Run an auth token request. */
		$this->log_debug( __METHOD__ . '(): Requesting auth token.' );
		$auth_request = Zoho_CRM::get_auth_token( $settings['emailAddress'], $settings['password'] );
		
		/* If auth token request succeeded, set auth token to auth token field. */
		if ( $auth_request['success'] ) {
			
			$settings['authToken'] = $auth_request['auth_token'];
			$this->log_debug( __METHOD__ . '(): Auth token successfully retrieved.' );

		} else {
			
			$sections = $this->plugin_settings_fields();
			$settings['authToken'] = '';
			$this->log_error( __METHOD__ . '(): Unable to retrieve auth token.' );
			
			/* Set field error based on error message. */
			if ( $auth_request['error'] == 'NO_SUCH_USER' ) {

				$this->log_error( __METHOD__ . '(): User does not exist.' );
				$this->set_field_error( $sections[0]['fields'][1], esc_html__( 'User does not exist.', 'gravityformszohocrm' ) );
				
			} else if ( $auth_request['error'] == 'INVALID_PASSWORD' ) {

				$this->log_error( __METHOD__ . '(): Invalid password' );
				$this->set_field_error( $sections[0]['fields'][2], esc_html__( 'Invalid password.', 'gravityformszohocrm' ) );
				
			} else if ( $auth_request['error'] == 'INVALID_CREDENTIALS' ) {

				$this->log_error( __METHOD__ . '(): Invalid credentials' );
				$this->set_field_error( $sections[0]['fields'][1], esc_html__( 'User does not exist.', 'gravityformszohocrm' ) );
				$this->set_field_error( $sections[0]['fields'][2], esc_html__( 'Invalid password.', 'gravityformszohocrm' ) );
				
			} else if ( $auth_request['error'] == 'WEB_LOGIN_REQUIRED' ) {

				$this->log_error( __METHOD__ . '(): Invalid credentials: WEB_LOGIN_REQUIRED.' );
				$this->set_field_error( $sections[0]['fields'][2], esc_html__( "Invalid password. If two factor authentication is enabled for your account you'll need to use an application specific password.", 'gravityformszohocrm' ) );

			}
			
		}
		
		return $settings;
		
	}

	/**
	 * Setup fields for feed settings.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_settings_fields() {
		
		/* Build base fields array. */
		$base_fields = array(
			'title'  => '',
			'fields' => array(
				array(
					'name'           => 'feedName',
					'label'          => esc_html__( 'Feed Name', 'gravityformszohocrm' ),
					'type'           => 'text',
					'required'       => true,
					'default_value'  => $this->get_default_feed_name(),
					'tooltip'        => '<h6>'. esc_html__( 'Name', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformszohocrm' )
				),
				array(
					'name'           => 'action',
					'label'          => esc_html__( 'Action', 'gravityformszohocrm' ),
					'required'       => true,
					'type'           => 'select',
					'onchange'       => "jQuery(this).parents('form').submit();",
					'tooltip'        => '<h6>'. esc_html__( 'Action', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Choose what will happen when this feed is processed.', 'gravityformszohocrm' ),
					'choices'        => array(
						array(
							'label' => esc_html__( 'Create an Action', 'gravityformszohocrm' ),
							'value' => ''
						),
						array(
							'label' => esc_html__( 'Create a New Contact', 'gravityformszohocrm' ),
							'value' => 'contact'
						),
						array(
							'label' => esc_html__( 'Create a New Lead', 'gravityformszohocrm' ),
							'value' => 'lead'
						),						
					)
				)
			)
		);
		
		$contact_fields = $this->contact_feed_settings_fields();
		$lead_fields    = $this->lead_feed_settings_fields();
		$task_fields    = $this->task_feed_settings_fields();

		/* Build conditional logic field array. */
		$conditional_fields = array(
			'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformszohocrm' ),
			'dependency' => array( $this, 'show_task_conditional_sections' ),
			'fields'     => array(
				array(
					'name'           => 'feedCondition',
					'type'           => 'feed_condition',
					'label'          => esc_html__( 'Conditional Logic', 'gravityformszohocrm' ),
					'checkbox_label' => esc_html__( 'Enable', 'gravityformszohocrm' ),
					'instructions'   => esc_html__( 'Export to Zoho CRM if', 'gravityformszohocrm' ),
					'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Zoho CRM when the condition is met. When disabled, all form submissions will be posted.', 'gravityformszohocrm' )
				),
				
			)
		);
		
		return array( $base_fields, $contact_fields, $lead_fields, $task_fields, $conditional_fields );

	}

	/**
	 * Setup contact fields for feed settings.
	 * 
	 * @access public
	 * @return array $contact_fields
	 */
	public function contact_feed_settings_fields() {
		
		/* Get needed module field choices. */
		$contact_source_choices = $this->get_module_field_choices( 'Contacts', 'Lead Source' );
		$contact_files_choices  = $this->get_file_fields_for_feed_setting( 'contact' );

		/* Build contact fields array. */
		$contact_fields = array(
			'title'      => esc_html__( 'Contact Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => ( 'contact' ) ),
			'fields'     => array()
		);
		
		$contact_fields['fields'][] = array(
			'name'      => 'contactStandardFields',
			'label'     => esc_html__( 'Map Fields', 'gravityformszohocrm' ),
			'type'      => 'field_map',
			'field_map' => $this->get_field_map_for_module( 'Contacts' ),
			'tooltip'   => '<h6>'. esc_html__( 'Map Fields', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' )
		);
		
		$contact_fields['fields'][] = array(
			'name'       => 'contactCustomFields',
			'label'      => '',
			'type'       => 'dynamic_field_map',
			'field_map'  => $this->get_field_map_for_module( 'Contacts', 'dynamic' ),
		);
		
		$contact_fields['fields'][] = array(
			'name'    => 'contactOwner',
			'label'   => esc_html__( 'Contact Owner', 'gravityformszohocrm' ),
			'type'    => 'select',
			'choices' => $this->get_users_for_feed_setting()
		);
		
		if ( ! empty( $contact_source_choices ) ) {
			$contact_fields['fields'][] = array(
				'name'    => 'contactLeadSource',
				'label'   => esc_html__( 'Lead Source', 'gravityformszohocrm' ),
				'type'    => 'select',
				'choices' => $contact_source_choices
			);
		}

		$contact_fields['fields'][] = array(
			'name'  => 'contactDescription',
			'type'  => 'textarea',
			'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
			'label' => esc_html__( 'Contact Description', 'gravityformszohocrm' ),
		);

		if ( ! empty( $contact_files_choices ) ) {
			$contact_fields['fields'][] = array(
				'name'    => 'contactAttachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attachments', 'gravityformszohocrm' ),
				'choices' => $contact_files_choices,
				'tooltip' => '<h6>'. esc_html__( 'Attachments', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Zoho CRM has a maximum file size of 20MB. Any file larger than this will not be uploaded. Additionally, files will not be uploaded if you have reached the storage allocation for your Zoho CRM account.', 'gravityformszohocrm' )
			);
		}
		
		$contact_fields['fields'][] = array(
			'name'    => 'options',
			'label'   => esc_html__( 'Options', 'gravityformszohocrm' ),
			'type'    => 'checkbox',
			'onclick' => "jQuery(this).parents('form').submit();",
			'choices' => array(
				array(
					'name'    => 'contactApprovalMode',
					'label'   => esc_html__( 'Approval Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'    => 'contactWorkflowMode',
					'label'   => esc_html__( 'Workflow Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'    => 'contactEmailOptOut',
					'label'   => esc_html__( 'Email Opt Out', 'gravityformszohocrm' ),
				),
				array(
					'name'    => 'contactDuplicateAllowed',
					'label'   => esc_html__( 'Allow duplicate contacts', 'gravityformszohocrm' ),
					'tooltip' => esc_html__( 'If duplicate contacts are allowed, you will not be able to update contacts if they already exist.', 'gravityformszohocrm' )
				),
				array(
					'name'    => 'contactUpdate',
					'label'   => esc_html__( 'Update Contact if contact already exists for email address', 'gravityformszohocrm' ),
				),
			)
		);

		return $contact_fields;
		
	}

	/**
	 * Setup lead fields for feed settings.
	 * 
	 * @access public
	 * @return array $lead_fields
	 */
	public function lead_feed_settings_fields() {
		
		/* Get needed module field choices. */
		$lead_rating_choices = $this->get_module_field_choices( 'Leads', 'Rating' );
		$lead_source_choices = $this->get_module_field_choices( 'Leads', 'Lead Source' );
		$lead_status_choices = $this->get_module_field_choices( 'Leads', 'Lead Status' );
		$lead_files_choices  = $this->get_file_fields_for_feed_setting( 'lead' );
		
		/* Build lead fields array. */
		$lead_fields = array(
			'title'      => esc_html__( 'Lead Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => ( 'lead' ) ),
			'fields'     => array()
		);
		
		$lead_fields['fields'][] = array(
			'name'      => 'leadStandardFields',
			'label'     => esc_html__( 'Map Fields', 'gravityformszohocrm' ),
			'type'      => 'field_map',
			'field_map' => $this->get_field_map_for_module( 'Leads' ),
			'tooltip'   => '<h6>'. esc_html__( 'Map Fields', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' )
		);
		
		$lead_fields['fields'][] = array(
			'name'      => 'leadCustomFields',
			'label'     => '',
			'type'      => 'dynamic_field_map',
			'field_map' => $this->get_field_map_for_module( 'Leads', 'dynamic' ),
		);
		
		$lead_fields['fields'][] = array(
			'name'    => 'leadOwner',
			'label'   => esc_html__( 'Lead Owner', 'gravityformszohocrm' ),
			'type'    => 'select',
			'choices' => $this->get_users_for_feed_setting()
		);
		
		if ( ! empty( $lead_rating_choices ) ) {
			$lead_fields['fields'][] = array(
				'name'    => 'leadRating',
				'label'   => esc_html__( 'Lead Rating', 'gravityformszohocrm' ),
				'type'    => 'select',
				'choices' => $lead_rating_choices
			);
		}
		
		if ( ! empty( $lead_source_choices ) ) {
			$lead_fields['fields'][] = array(
				'name'       => 'leadSource',
				'label'      => esc_html__( 'Lead Source', 'gravityformszohocrm' ),
				'type'       => 'select',
				'choices'    => $lead_source_choices
			);
		}
		
		if ( ! empty( $lead_status_choices ) ) {
			$lead_fields['fields'][] = array(
				'name'       => 'leadStatus',
				'label'      => esc_html__( 'Lead Status', 'gravityformszohocrm' ),
				'type'       => 'select',
				'choices'    => $lead_status_choices
			);
		}
		
		$lead_fields['fields'][] = array(
			'name'  => 'leadDescription',
			'type'  => 'textarea',
			'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
			'label' => esc_html__( 'Lead Description', 'gravityformszohocrm' ),
		);
		
		if ( ! empty ( $lead_files_choices ) ) {
			$lead_fields['fields'][] = array(
				'name'    => 'leadAttachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attachments', 'gravityformszohocrm' ),
				'choices' => $lead_files_choices,
				'tooltip' => '<h6>'. esc_html__( 'Attachments', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Zoho CRM has a maximum file size of 20MB. Any file larger than this will not be uploaded. Additionally, files will not be uploaded if you have reached the storage allocation for your Zoho CRM account.', 'gravityformszohocrm' )
			);
		}
		
		$lead_fields['fields'][] = array(
			'name'       => 'options',
			'label'      => esc_html__( 'Options', 'gravityformszohocrm' ),
			'type'       => 'checkbox',
			'onclick'    => "jQuery(this).parents('form').submit();",
			'choices'    => array(
				array(
					'name'       => 'leadApprovalMode',
					'label'      => esc_html__( 'Approval Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'       => 'leadWorkflowMode',
					'label'      => esc_html__( 'Workflow Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'       => 'leadEmailOptOut',
					'label'      => esc_html__( 'Email Opt Out', 'gravityformszohocrm' ),
				),
				array(
					'name'       => 'leadDuplicateAllowed',
					'label'      => esc_html__( 'Allow duplicate leads', 'gravityformszohocrm' ),
					'tooltip'    => esc_html__( 'If duplicate leads are allowed, you will not be able to update leads if they already exist.', 'gravityformszohocrm' )
				),
				array(
					'name'       => 'leadUpdate',
					'label'      => esc_html__( 'Update Lead if lead already exists for email address', 'gravityformszohocrm' ),
				),
			)
		);
		
		return $lead_fields;
		
	}

	/**
	 * Setup task fields for feed settings.
	 * 
	 * @access public
	 * @return array $task_fields
	 */
	public function task_feed_settings_fields() {
		
		$feed = ( $this->get_posted_settings() ) ? $this->get_posted_settings() : $this->get_current_feed();		
		
		/* Get needed module field choices. */
		$task_status_choices = $this->get_module_field_choices( 'Tasks', 'Status' );
		
		/* Build task fields array. */
		$task_fields = array(
			'title'      => esc_html__( 'Task Details', 'gravityformszohocrm' ),
			'dependency' => array( $this, 'show_task_conditional_sections' ),
			'fields'     => array()
		);
		
		$task_fields['fields'][] = array(
			'name'    => 'createTask',
			'label'   => esc_html__( 'Create Task', 'gravityformszohocrm' ),
			'type'    => 'checkbox',
			'onclick' => "jQuery(this).parents('form').submit();",
			'choices' => array(
				array(
					'name'  => 'createTask',
					'label' => sprintf( 
						esc_html__( 'Create Task for %s', 'gravityformszohocrm' ), 
						rgars( $feed, 'action' ) ? ucfirst( rgar( $feed, 'action' ) ) : ucfirst( rgars( $feed, 'meta/action' ) )
					),
				),
			)
		);
		
		$task_fields['fields'][] = array(
			'name'       => 'taskSubject',
			'type'       => 'text',
			'class'      => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
			'label'      => esc_html__( 'Task Subject', 'gravityformszohocrm' ),
			'required'   => true,
			'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) )
		);

		$task_fields['fields'][] = array(
			'name'                => 'taskDueDate',
			'type'                => 'text',
			'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
			'label'               => esc_html__( 'Days Until Due', 'gravityformszohocrm' ),
			'validation_callback' => array( $this, 'validate_task_due_date' ),
			'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) )
		);
		
		$task_fields['fields'][] = array(
			'name'       => 'taskOwner',
			'label'      => esc_html__( 'Task Owner', 'gravityformszohocrm' ),
			'type'       => 'select',
			'choices'    => $this->get_users_for_feed_setting(),
			'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) )
		);
		
		if ( ! empty( $task_status_choices ) ) {
			$task_fields['fields'][] = array(
				'name'       => 'taskStatus',
				'label'      => esc_html__( 'Task Status', 'gravityformszohocrm' ),
				'type'       => 'select',
				'choices'    => $task_status_choices,
				'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) )
			);
		}

		$task_fields['fields'][] = array(
			'name'  => 'taskDescription',
			'type'  => 'textarea',
			'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
			'label' => esc_html__( 'Task Description', 'gravityformszohocrm' ),
		);
		
		return $task_fields;
		
	}

	/**
	 * Set feed creation control.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		
		return $this->initialize_api();
		
	}

	/**
	 * Enable feed duplication.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {
		
		return true;
		
	}

	/**
	 * Setup columns for feed list table.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feedName' => esc_html__( 'Name', 'gravityformszohocrm' ),
			'action'   => esc_html__( 'Action', 'gravityformszohocrm' )
		);
		
	}

	/**
	 * Get value for action feed list column.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string $action
	 */
	public function get_column_value_action( $feed ) {
		
		if ( rgars( $feed, 'meta/action' ) == 'contact' ) {
			
			return esc_html__( 'Create a New Contact', 'gravityformszohocrm' );
			
		} else if ( rgars( $feed, 'meta/action' ) == 'lead' ) {
			
			return esc_html__( 'Create a New Lead', 'gravityformszohocrm' );
			
		}
		
	}

	/**
	 * Custom dependency to show Task and Feed Conditional Logic feed settings sections.
	 * 
	 * @access public
	 * @return void
	 */
	public function show_task_conditional_sections() {
		
		/* Get current feed. */
		$feed = ( $this->get_posted_settings() ) ? $this->get_posted_settings() : $this->get_current_feed();		
		
		/* Show if an action is chosen */
		return ( rgar( $feed, 'action' ) !== '' || rgars( $feed, 'meta/action' ) !== '' );
			
	}

	/**
	 * Validate Task Days Until Due feed settings field.
	 * 
	 * @access public
	 * @param array $field
	 * @param string $field_setting
	 * @return void
	 */
	public function validate_task_due_date( $field, $field_setting ) {

		if ( ! rgblank( $field_setting ) && ! is_numeric( $field_setting ) && ! GFCommon::has_merge_tag( $field_setting ) ) {
			$this->set_field_error( $field, esc_html__( 'This field must be numeric or a merge tag.', 'gravityformszohocrm' ) );
		}
		
	}

	/**
	 * Get fields for a Zoho CRM module.
	 * 
	 * @access public
	 * @param string $module (default: null)
	 * @return array $fields
	 */
	public function get_module_fields( $module = null ) {
		
		if ( false === ( $fields = get_transient( $this->fields_transient_name ) ) ) {
			$fields = $this->update_cached_fields();
		}
		
		/* Decode the JSON string. */
		$fields = json_decode( $fields, true );
		
		return rgar( $fields, $module ) ? rgar( $fields, $module ) : $fields; 
		
	}

	/**
	 * Get field from a Zoho CRM module.
	 * 
	 * @access public
	 * @param string $module
	 * @param string $field_name
	 * @return array $field
	 */
	public function get_module_field( $module, $field_name ) {
		
		$module_fields = $this->get_module_fields( $module );
	
		foreach ( $module_fields as $module_field ) {
			if ( rgar( $module_field, 'label' ) === $field_name ) {
				return $module_field;
			}
		}
	
		return array();
		
	}
	
	/**
	 * Get choices for a specifc Zoho CRM module field formatted for field settings.
	 * 
	 * @access public
	 * @param string $module
	 * @param string $field_name
	 * @return array $choices
	 */
	public function get_module_field_choices( $module, $field_name ) {
		
		$field   = $this->get_module_field( $module, $field_name );
		$choices = array();
		
		if ( ! empty( $field['choices'] ) ) {
		
			foreach ( $field['choices'] as $choice ) {
				
				if ( is_array( $choice ) && rgar( $choice, 'content' ) ) {
					$choice = $choice['content'];
				}
				
				$choices[] = array(
					'label' => $choice,
					'value' => $choice	
				);
				
			}
			
		}
		
		return $choices;
		
	}

	/**
	 * Get field map fields for a Zoho CRM module.
	 * 
	 * @access public
	 * @param string $module
	 * @param string $field_map_type (default: standard)
	 * @return array $field_map
	 */
	public function get_field_map_for_module( $module, $field_map_type = 'standard' ) {
		
		$fields    = $this->get_module_fields( $module );
		$field_map = array();
		
		/* Sort fields in alphabetical order. */
		usort( $fields, array( $this, 'sort_module_fields_by_label' ) );
		
		foreach ( $fields as $field ) {

			/* If this is a custom field or a non-support field type, skip it. */
			if ( rgar( $field, 'custom_field' ) || in_array( $field['type'], array( 'Lookup', 'Pick List', 'OwnerLookup', 'Boolean', 'Currency' ) ) )
				continue;
			
			/* Prepare field type. */
			$field_type = null;
			switch ( $field['type'] ) {
				
				case 'Date':
				case 'DateTime':
					$field_type = 'date';
					break;
				
				case 'Email':
					$field_type = array( 'email', 'hidden' );
					break;

				case 'Phone':
					$field_type = 'phone';
					break;
				
			}
			
			/* Add field to field map. */
			$field_map[] = array(
				'name'       => str_replace( ' ', '_', $field['label'] ),
				'label'      => $field['label'],
				'required'   => $field['required'],
				'field_type' => $field_type,
			);

		}
		
		foreach ( $field_map as $key => &$field ) {
			
			$standard_test = in_array( rgar( $field, 'label' ), array( 'Company', 'Email', 'First Name', 'Last Name' ) );
			$required_test = rgar( $field, 'required' );
			
			if ( $field_map_type === 'standard' ) {
				
				if ( $standard_test ) {
					$field['required'] = true;
				}
				
				if ( ! $standard_test && ! $required_test ) {
					unset( $field_map[$key] );
				}
				
			} else if ( $field_map_type === 'dynamic' ) {
				
				if ( $standard_test || $required_test ) {
					unset( $field_map[$key] );
				}
				
			}
						
		}
		
		return $field_map;
		
	}

	/**
	 * Sort module fields alphabeically by label.
	 * 
	 * @access public
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public function sort_module_fields_by_label( $a, $b ) {
		
		return strcmp( $a['label'], $b['label'] );		
		
	}

	/**
	 * Get Zoho CRM users for feed field settings.
	 * 
	 * @access public
	 * @return array $users
	 */
	public function get_users_for_feed_setting() {
		
		$users = array(
			array(
				'label' => esc_html__( '-None-', 'gravityformszohocrm' ),
				'value' => ''
			)
		);
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			$this->log_error( __METHOD__ . '(): Unable to get users because API is not initialized.' );
			return $users;
			
		}

		$zoho_users = $this->api->get_users();
		
		if ( ! empty( $zoho_users ) ) {

			/* Modify $zoho_users depending on user count. */
			$array_keys = array_keys( $zoho_users['user'] );

			if ( is_numeric( $array_keys[0] ) ) {
				$zoho_users = $zoho_users['user'];
			}

			foreach ( $zoho_users as $user ) {
				
				$users[] = array(
					'label' => $user['content'],
					'value' => $user['id']	
				);
				
			}
			
		}
		
		return $users;
		
	}

	/**
	 * Get form file fields for feed field settings.
	 * 
	 * @access public
	 * @param string $module (default: 'contact')
	 * @return array $fields
	 */
	public function get_file_fields_for_feed_setting( $module = 'contact' ) {

		/* Setup choices array. */
		$choices = array();

		/* Get the form. */
		$form = GFAPI::get_form( rgget( 'id' ) );

		/* Get file fields for the form. */
		$file_fields = GFAPI::get_fields_by_type( $form, array( 'fileupload' ), true );

		if ( ! empty ( $file_fields ) ) {

			foreach ( $file_fields as $field ) {

				$choices[] = array(
					'name'          => $module . 'Attachments[' . $field->id . ']',
					'label'         => $field->label,
					'default_value' => 0,
				);

			}

		}

		return $choices;

	}

	/**
	 * Process the Zoho CRM feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
		
			$this->log_error( __METHOD__ . '(): Failed to set up the API.' );
			return;
			
		}

		/* Create contact or lead */
		if ( rgars( $feed, 'meta/action' ) === 'contact' ) {
			
			$contact_id = $this->create_contact( $feed, $entry, $form );
			
			if ( ! rgblank( $contact_id ) ) {
				
				$this->upload_attachments( $contact_id, 'contact', $feed, $entry, $form );
				
				$this->create_task( $contact_id, 'Contacts', $feed, $entry, $form );
				
			}

		} else if ( rgars( $feed, 'meta/action' ) === 'lead' ) {
			
			$lead_id = $this->create_lead( $feed, $entry, $form );
			
			if ( ! rgblank( $lead_id ) ) {
				
				$this->upload_attachments( $lead_id, 'lead', $feed, $entry, $form );
				
				$this->create_task( $lead_id, 'Leads', $feed, $entry, $form );
			
			}
			
		}

	}
	
	/**
	 * Create a new contact from a feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return int $contact_id
	 */
	public function create_contact( $feed, $entry, $form ) {
		
		/* Create contact object. */
		$contact = array(
			'Email Opt Out' => rgars( $feed, 'meta/contactEmailOptOut' ) == '1' ? 'true' : 'false',
			'Description'   => GFCommon::replace_variables( $feed['meta']['contactDescription'], $form, $entry, false, false, false, 'text' ),
			'Lead Source'   => rgars( $feed, 'meta/contactLeadSource' ),
			'SMOWNERID'     => rgars( $feed, 'meta/contactOwner' ),
			'options'       => array(
				'duplicateCheck' => rgars( $feed, 'meta/contactUpdate' ) == '1' ? '2' : '1',
				'isApproval'     => rgars( $feed, 'meta/contactApprovalMode' ) == '1' ? 'true' : 'false',
				'wfTrigger'      => rgars( $feed, 'meta/contactWorkflowMode' ) == '1' ? 'true' : 'false'
			)
		);
			
		/* If duplicate contacts are allowed, remove the duplicate check. */
		if ( rgars( $feed, 'meta/contactDuplicateAllowed' ) ) {
			unset( $contact['options']['duplicateCheck'] );
		}

		/* Get field map fields. */
		$standard_fields = $this->get_field_map_fields( $feed, 'contactStandardFields' );
		$custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'contactCustomFields' );
		
		$mapped_fields = array_merge( $standard_fields, $custom_fields );
		
		foreach ( $mapped_fields as $field_name => $field_id ) {
			
			$field_name  = str_replace( '_', ' ', $field_name );
			$field_value = $this->get_field_value( $form, $entry, $field_id );
			
			if ( rgblank( $field_value ) )
				continue;
			
			$contact[ $field_name ] = $field_value;
			
		}
		
		/* Filter contact. */
		$contact = gf_apply_filters( 'gform_zohocrm_contact', $form['id'], $contact, $feed, $entry, $form );
		
		/* Remove SMOWNERID if not set. */
		if ( rgblank( $contact['SMOWNERID'] ) ) {
			unset( $contact['SMOWNERID'] );
		}
		
		/* Prepare contact record XML. */
		$contact_xml  = '<Contacts>' . "\r\n";
		$contact_xml .= '<row no="1">' . "\r\n";
		
		foreach ( $contact as $field_key => $field_value ) {
			
			if ( is_array( $field_value ) )
				continue;
			
			if ( $field_key === 'Description' )
				$field_value = '<![CDATA[ ' . $field_value . ' ]]>';

			$contact_xml .= '<FL val="' . $field_key . '">' . $field_value . '</FL>' . "\r\n";
			
		}
		
		$contact_xml .= '</row>' . "\r\n";
		$contact_xml .= '</Contacts>' . "\r\n";
		
		$this->log_debug( __METHOD__ . '(): Creating contact: ' . print_r( $contact, true ) );

		try {
		
			/* Insert contact record. */
			$contact_record = $this->api->insert_record( 'Contacts', $contact_xml, $contact['options'] );
		
			/* Get contact ID of new contact record. */
			$contact_id = 0;
			foreach ( $contact_record->result->recorddetail as $detail ) {
				
				foreach ( $detail->children() as $field ) {
					
					if ( $field['val'] == 'Id' ) {
						
						$contact_id = (string) $field;
						
					}
					
				}
				
			}
		
			/* Save contact ID to entry meta. */
			gform_update_meta( $entry['id'], 'zohocrm_contact_id', $contact_id );
			
			/* Log that contact was created. */
			$this->log_debug( __METHOD__ . '(): Contact #' . $contact_id . ' created.' );
			
			return $contact_id;
		
		} catch ( Exception $e ) {
			
			$this->log_error( __METHOD__ . '(): Could not create contact; ' . $e->getMessage() );
			
			return null;
			
		}
		
	}
	
	/**
	 * Create a new lead from a feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return int $lead_id
	 */
	public function create_lead( $feed, $entry, $form ) {
		
		/* Create lead object. */
		$lead = array(
			'Email Opt Out' => rgars( $feed, 'meta/leadEmailOptOut' ) == '1' ? 'true' : 'false',
			'Description'   => GFCommon::replace_variables( $feed['meta']['leadDescription'], $form, $entry, false, false, false, 'text' ),
			'Lead Source'   => rgars( $feed, 'meta/leadSource' ),
			'Lead Status'   => rgars( $feed, 'meta/leadStatus' ),
			'Rating'        => rgars( $feed, 'meta/leadRating' ),
			'SMOWNERID'     => rgars( $feed, 'meta/leadOwner' ),
			'options'       => array(
				'duplicateCheck' => rgars( $feed, 'meta/leadUpdate' ) == '1' ? '2' : '1',
				'isApproval'     => rgars( $feed, 'meta/leadApprovalMode' ) == '1' ? 'true' : 'false',
				'wfTrigger'      => rgars( $feed, 'meta/leadWorkflowMode' ) == '1' ? 'true' : 'false'
			)
		);
		
		/* If duplicate leads are allowed, remove the duplicate check. */
		if ( rgars( $feed, 'meta/leadDuplicateAllowed' ) ) {
			unset( $lead['options']['duplicateCheck'] );
		}
			
		/* Add standard fields. */
		$standard_fields = $this->get_field_map_fields( $feed, 'leadStandardFields' );
		$custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'leadCustomFields' );
		
		$mapped_fields = array_merge( $standard_fields, $custom_fields );
		
		foreach ( $mapped_fields as $field_name => $field_id ) {
			
			$field_name  = str_replace( '_', ' ', $field_name );
			$field_value = $this->get_field_value( $form, $entry, $field_id );
			
			if ( rgblank( $field_value ) )
				continue;
			
			$lead[ $field_name ] = $field_value;
			
		}
		
		/* Filter lead. */
		$lead = gf_apply_filters( 'gform_zohocrm_lead', $form['id'], $lead, $feed, $entry, $form );
		
		/* Remove SMOWNERID if not set. */
		if ( rgblank( $lead['SMOWNERID'] ) ) {
			unset( $lead['SMOWNERID'] );
		}

		/* Prepare lead record XML. */
		$lead_xml  = '<Leads>' . "\r\n";
		$lead_xml .= '<row no="1">' . "\r\n";
		
		foreach ( $lead as $field_key => $field_value ) {
			
			if ( is_array( $field_value ) )
				continue;
			
			if ( $field_key === 'Description' )
				$field_value = '<![CDATA[ ' . $field_value . ' ]]>';
				
			$lead_xml .= '<FL val="' . $field_key . '">' . $field_value . '</FL>' . "\r\n";
			
		}
		
		$lead_xml .= '</row>' . "\r\n";
		$lead_xml .= '</Leads>' . "\r\n";
		
		$this->log_debug( __METHOD__ . '(): Creating lead: ' . print_r( $lead, true ) );
		$this->log_debug( __METHOD__ . '(): Creating lead XML: ' . print_r( $lead_xml, true ) );

		try {
		
			/* Insert lead record. */
			$lead_record = $this->api->insert_record( 'Leads', $lead_xml, $lead['options'] );
		
			/* Get lead ID of new lead record. */
			$lead_id = 0;
			foreach ( $lead_record->result->recorddetail as $detail ) {
				
				foreach ( $detail->children() as $field ) {
					
					if ( $field['val'] == 'Id' ) {
						
						$lead_id = (string) $field;
						
					}
					
				}
				
			}
		
			/* Save lead ID to entry meta. */
			gform_update_meta( $entry['id'], 'zohocrm_lead_id', $lead_id );
			
			/* Log that lead was created. */
			$this->log_debug( __METHOD__ . '(): Lead #' . $lead_id . ' created.' );
			
			return $lead_id;
		
		} catch ( Exception $e ) {
			
			$this->log_error( __METHOD__ . '(): Could not create Lead; ' . $e->getMessage() );
			
			return null;
			
		}
		
	}
	
	/**
	 * Create a new task from a feed.
	 * 
	 * @access public
	 * @param int $record_id
	 * @param string $module
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function create_task( $record_id, $module, $feed, $entry, $form ) {

		if ( rgars( $feed, 'meta/createTask' ) != '1' ) {
			return;
		}
		
		/* Create task object. */
		$task = array(
			'Subject'     => GFCommon::replace_variables( $feed['meta']['taskSubject'], $form, $entry, false, false, false, 'text' ),
			'Status'      => rgars( $feed, 'meta/taskStatus' ),
			'SMOWNERID'   => rgars( $feed, 'meta/taskOwner' ),
			'Description' => GFCommon::replace_variables( $feed['meta']['taskDescription'], $form, $entry, false, false, false, 'text' ),
		);

		if ( rgars( $feed, 'meta/taskDueDate' ) ) {
			$due_date = GFCommon::replace_variables( $feed['meta']['taskDueDate'], $form, $entry, false, false, false, 'text' );
			if ( is_numeric( $due_date ) ) {
				$task['Due Date'] = date( 'Y-m-d', strtotime( '+' . $due_date . ' days' ) );
			}
		}
		
		/* Add lead or contact ID. */
		if ( $module === 'Contacts' ) {
			$task['CONTACTID'] = $record_id;
		} else if ( $module === 'Leads' ) {
			$task['SEID']     = $record_id;
			$task['SEMODULE'] = $module;
		}
	
		/* Filter task. */
		$task = gf_apply_filters( 'gform_zohocrm_task', $form['id'], $task, $feed, $entry, $form );
		
		/* Remove SMOWNERID if not set. */
		if ( rgblank( $task['SMOWNERID'] ) ) {
			unset( $task['SMOWNERID'] );
		}

		/* Prepare task record XML. */
		$task_xml  = '<Tasks>' . "\r\n";
		$task_xml .= '<row no="1">' . "\r\n";
		
		foreach ( $task as $field_key => $field_value ) {

			if ( $field_key === 'Subject' ) {
				$field_value = '<![CDATA[ ' . $field_value . ' ]]>';
			}

			$task_xml .= '<FL val="' . $field_key . '">' . $field_value . '</FL>' . "\r\n";
			
		}
		
		$task_xml .= '</row>' . "\r\n";
		$task_xml .= '</Tasks>' . "\r\n";
		
		$this->log_debug( __METHOD__ . '(): Creating task: ' . print_r( $task, true ) );
		
		try {
		
			/* Insert task record. */
			$task_record = $this->api->insert_record( 'Tasks', $task_xml );
		
			/* Get ID of new task record. */
			$task_id = 0;
			foreach ( $task_record->result->recorddetail as $detail ) {
				
				foreach ( $detail->children() as $field ) {
					
					if ( $field['val'] == 'Id' ) {
						
						$task_id = (string) $field;
						
					}
					
				}
				
			}
		
			/* Save task ID to entry meta. */
			gform_update_meta( $entry['id'], 'zohocrm_task_id', $task_id );
			
			/* Log that task was created. */
			$this->log_debug( __METHOD__ . '(): Task #' . $task_id . ' created and assigned to ' . $module . ' #' . $record_id . '.' );
			
			return $task_id;
		
		} catch ( Exception $e ) {
			
			$this->log_error( __METHOD__ . '(): Could not create task; ' . $e->getMessage() );
			
			return null;
			
		}
		
	}
	
	/**
	 * Upload attachments from a feed.
	 * 
	 * @access public
	 * @param int $record_id
	 * @param string $module
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function upload_attachments( $record_id, $module, $feed, $entry, $form ) {

		$attachment_files = array();
		$fields_to_upload = array();

		if ( ! rgars( $feed, 'meta/' . $module . 'Attachments' ) ) {
			return;
		}

		/* Get fields flagged for uploading. */
		foreach ( rgars( $feed, 'meta/' . $module . 'Attachments' ) as $field_id => $upload_attachment ) {

			if ( $upload_attachment == '1' ) {
				$fields_to_upload[] = $field_id;
			}

		}

		/* If no fields are flagged to be uploaded, exit. */
		if ( empty( $fields_to_upload ) ) {
			return;
		}

		/* Get the file paths for files to be uploaded. */
		foreach ( $fields_to_upload as $field_to_upload ) {

			$files = $this->get_field_value( $form, $entry, $field_to_upload );

			/* If no files were uploaded for this field, move on. */
			if ( empty( $files ) ) {
				continue;
			}

			$files = $this->is_json( $files ) ? json_decode( $files, true ) : explode( ' , ', $files );

			/* Loop through the files, change the URL to a path and check the maximum size. */
			foreach ( $files as $file ) {

				/* Change the URL to the local path. */
				$file = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );

				/* Check if file is larger than the max size allowed. */
				if ( filesize( $file ) > 20000000 ) {

					$this->log_error( __METHOD__ . '(): Unable to upload file "' . basename( $file ) . '" because it is larger than 20MB.' );

				} else {
					$attachment_files[] = $file;
				}

			}

		}


		/* If there are still files left to be uploaded, upload 'em. */
		if ( ! empty( $attachment_files ) ) {

			foreach ( $attachment_files as $file_to_upload ) {

				try {

					$module_type = ucfirst( $module ) . 's';

					/* Upload file. */
					$uploaded_file = $this->api->upload_file( $module_type, $record_id, $file_to_upload );

					/* Log that the file was uploaded. */
					$this->log_debug( __METHOD__ . '(): File "' . basename( $file_to_upload ) . '" has been uploaded to ' . $module . ' #' . $record_id . '.' );

				} catch ( Exception $e ) {

					/* Log that the file was not uploaded. */
					$this->log_error( __METHOD__ . '(): File "' . basename( $file_to_upload ) . '" has not been uploaded; ' . $e->getMessage() );


				}

			}

		}

	}
	
	/**
	 * Update the cached fields for all the needed modules.
	 * 
	 * @access public
	 * @return string $fields
	 */
	public function update_cached_fields() {
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			$this->log_error( __METHOD__ . '(): Unable to update fields because API is not initialized.' );
			return;
			
		}
			
		/* Get fields for each module. */
		$fields = array(
			'Contacts' => $this->api->get_fields( 'Contacts' ),
			'Leads'    => $this->api->get_fields( 'Leads' ),
			'Tasks'    => $this->api->get_fields( 'Tasks' )		
		);
			
		
		foreach ( $fields as &$module ) {
			
			$module_fields = array();
			
			foreach ( $module['section'] as $section ) {
				
				foreach ( $section['FL'] as $field ) {

					if ( ! is_array( $field ) )
						continue;
						
					$new_module_field = array(
						'custom_field' => filter_var( $field['customfield'], FILTER_VALIDATE_BOOLEAN ),
						'label'        => $field['label'],
						'name'         => $field['dv'],
						'required'     => filter_var( $field['req'], FILTER_VALIDATE_BOOLEAN ),
						'type'         => $field['type']
					);
					
					if ( rgar( $field, 'val' ) ) {
						
						$new_module_field['choices'] = $field['val'];
						
					}
					
					$module_fields[ $field['dv'] ] = $new_module_field;
					
				}
				
			}
			
			$module = $module_fields;
			
		}

		$fields = json_encode( $fields );
	
		set_transient( $this->fields_transient_name, $fields, 60*60*12 );

		return $fields;
		
	}

	/**
	 * Initialized Zoho CRM API if credentials are valid.
	 * 
	 * @access public
	 * @return bool
	 */
	public function initialize_api() {

		if ( ! is_null( $this->api ) ) {
			return true;
		}
		
		/* Include the API library. */
		if ( ! class_exists( 'Zoho_CRM' ) ) {
			require_once 'includes/class-zohocrm.php';
		}

		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If the auth token, return null. */
		if ( rgblank( $settings['authToken'] ) )
			return null;
			
		$this->log_debug( __METHOD__ . "(): Validating API credentials." );
		
		$zohocrm = new Zoho_CRM( $settings['authToken'] );
		
		try {
			
			/* Run API test. */
			$zohocrm->get_users();
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
			
			/* Assign Zoho CRM object to the class. */
			$this->api = $zohocrm;
			
			return true;
			
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );			

			return false;
			
		}
		
	}
	
	/**
	 * Checks if a previous version was installed and runs any needed migration processes.
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 */
	public function upgrade( $previous_version ) {

		$previous_is_pre_foreign_language_fix = ! empty( $previous_version ) && version_compare( $previous_version, '1.1.5', '<' );
		
		if ( $previous_is_pre_foreign_language_fix ) {
			$this->run_foreign_language_fix();
		}

	}
	
	/**
	 * Updates feeds to use Zoho CRM module field label instead of field name.
	 * 
	 * @access public
	 * @return void
	 */
	public function run_foreign_language_fix() {
		
		/* Get the Zoho CRM feeds. */
		$feeds = $this->get_feeds();
		
		foreach ( $feeds as &$feed ) {
			
			if ( rgars( $feed, 'meta/action' ) === 'contact' ) {
				
				$contact_fields = $this->get_module_fields( 'Contacts' );
				
				foreach ( $contact_fields as $contact_field ) {
					
					$search_for   = 'contactStandardFields_' . str_replace( ' ', '_', $contact_field['name'] );
					$replace_with = 'contactStandardFields_' . str_replace( ' ', '_', $contact_field['label'] );
					
					if ( rgars( $feed, 'meta/' . $search_for ) ) {
						$value = rgars( $feed, 'meta/' . $search_for );
						unset( $feed['meta'][ $search_for ] );
						$feed['meta'][ $replace_with ] = $value;
					}
					
				}

				if ( rgars( $feed, 'meta/contactCustomFields' ) ) {
				
					foreach ( $contact_fields as $contact_field ) {
						
						foreach ( $feed['meta']['contactCustomFields'] as &$feed_custom_field ) {
							
							if ( $feed_custom_field['key'] === $contact_field['name'] ) {
								$feed_custom_field['key'] = $contact_field['label'];
							}
							
						}
						
					}
					
				}
				
			}
			
			if ( rgars( $feed, 'meta/action' ) === 'lead' ) {
				
				$lead_fields = $this->get_module_fields( 'Leads' );
				
				foreach ( $lead_fields as $lead_field ) {
					
					$search_for   = 'leadStandardFields_' . str_replace( ' ', '_', $lead_field['name'] );
					$replace_with = 'leadStandardFields_' . str_replace( ' ', '_', $lead_field['label'] );
					
					if ( rgars( $feed, 'meta/' . $search_for ) ) {
						$value = rgars( $feed, 'meta/' . $search_for );
						unset( $feed['meta'][ $search_for ] );
						$feed['meta'][ $replace_with ] = $value;
					}
					
				}

				if ( rgars( $feed, 'meta/leadCustomFields' ) ) {
				
					foreach ( $lead_fields as $lead_field ) {
						
						foreach ( $feed['meta']['leadCustomFields'] as &$feed_custom_field ) {
							
							if ( $feed_custom_field['key'] === $lead_field['name'] ) {
								$feed_custom_field['key'] = $lead_field['label'];
							}
							
						}
						
					}
					
				}
				
			}
			
			$this->update_feed_meta( $feed['id'], $feed['meta'] );
			
		}
		
	}

	/**
	 * Override how multiple choices in multiselect and checkbox type field values are separated and enable use of the gform_zohocrm_field_value hook.
	 *
	 * @param string $field_value The field value.
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param string $field_id The ID of the field being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {
		$field = GFFormsModel::get_field( $form, $field_id );

		if ( is_object( $field ) ) {
			$is_integer = $field_id == intval( $field_id );
			$input_type = $field->get_input_type();

			if ( $input_type == 'multiselect' || ( $is_integer && $input_type == 'checkbox' ) ) {
				$field_value = str_replace( ', ', ';', $field_value );
			}
		}

		return parent::maybe_override_field_value( $field_value, $form, $entry, $field_id );
	}

}
