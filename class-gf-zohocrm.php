<?php
	
GFForms::include_feed_addon_framework();

class GFZohoCRM extends GFFeedAddOn {
	
	protected $_version = GF_ZOHOCRM_VERSION;
	protected $_min_gravityforms_version = '1.9.10.16';
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
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new self;

		return self::$_instance;
		
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
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
		
		$description  = '<p>';
		$description .= sprintf(
			__( 'Zoho CRM is a contact management tool that gives you a 360-degree view of your complete sales cycle and pipeline. Use Gravity Forms to collect customer information and automatically add them to your Zoho CRM account. If you don\'t have a Zoho CRM account, you can %1$s sign up for one here.%2$s', 'gravityformszohocrm' ),
			'<a href="http://www.zoho.com/crm/" target="_blank">', '</a>'
		);
		$description .= '</p>';
						
		return array(
			array(
				'title'       => '',
				'description' => $description,
				'fields'      => array(
					array(
						'name'              => 'authToken',
						'type'              => 'hidden',
					),
					array(
						'name'              => 'emailAddress',
						'label'             => __( 'Email Address', 'gravityformszohocrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'name'              => 'password',
						'label'             => __( 'Password', 'gravityformszohocrm' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => __( 'Zoho CRM settings have been updated.', 'gravityformszohocrm' )
						),
					),
				),
			),
		);
		
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
		
		/* If the email address has changed, return true. */
		if ( $previous_settings['emailAddress'] !== $new_settings['emailAddress'] ) {
			
			return true;
			
		}

		/* If the password has changed, return true. */
		if ( $previous_settings['password'] !== $new_settings['password'] ) {
			
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
		$auth_request = Zoho_CRM::get_auth_token( $settings['emailAddress'], $settings['password'] );
		
		/* If auth token request succeeded, set auth token to auth token field. */
		if ( $auth_request['success'] ) {
			
			$settings['authToken'] = $auth_request['auth_token'];
			
		} else {
			
			$sections = $this->plugin_settings_fields();
			$settings['authToken'] = '';
			
			/* Set field error based on error message. */
			if ( $auth_request['error'] == 'NO_SUCH_USER' ) {
				
				$this->set_field_error( $sections[0]['fields'][1], __( 'User does not exist.', 'gravityformszoho' ) );
				
			} else if ( $auth_request['error'] == 'INVALID_PASSWORD' ) {
				
				$this->set_field_error( $sections[0]['fields'][2], __( 'Invalid password.', 'gravityformszoho' ) );
				
			} else if ( $auth_request['error'] == 'INVALID_CREDENTIALS' ) {
				
				$this->set_field_error( $sections[0]['fields'][1], __( 'User does not exist.', 'gravityformszoho' ) );
				$this->set_field_error( $sections[0]['fields'][2], __( 'Invalid password.', 'gravityformszoho' ) );
				
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
		
		$feed = ( $this->get_posted_settings() ) ? $this->get_posted_settings() : $this->get_current_feed();
		
		$contact_file_fields = $this->get_file_fields_for_feed_setting( 'contact' );
		$lead_file_fields    = $this->get_file_fields_for_feed_setting( 'lead' );
		
		/* Build base fields array. */
		$base_fields = array(
			'title'  => '',
			'fields' => array(
				array(
					'name'           => 'feedName',
					'label'          => __( 'Feed Name', 'gravityformszohocrm' ),
					'type'           => 'text',
					'required'       => true,
					'default_value'  => $this->get_default_feed_name(),
					'tooltip'        => '<h6>'. __( 'Name', 'gravityformszohocrm' ) .'</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformszohocrm' )
				),
				array(
					'name'           => 'action',
					'label'          => __( 'Action', 'gravityformszohocrm' ),
					'required'       => true,
					'type'           => 'select',
					'onchange'       => "jQuery(this).parents('form').submit();",
					'tooltip'        => '<h6>'. __( 'Action', 'gravityformszohocrm' ) .'</h6>' . __( 'Choose what will happen when this feed is processed.', 'gravityformszohocrm' ),
					'choices'        => array(
						array(
							'label' => __( 'Create an Action', 'gravityformszohocrm' ),
							'value' => ''
						),
						array(
							'label' => __( 'Create a New Contact', 'gravityformszohocrm' ),
							'value' => 'contact'
						),
						array(
							'label' => __( 'Create a New Lead', 'gravityformszohocrm' ),
							'value' => 'lead'
						),						
					)
				)
			)
		);
		
		/* Build contact fields array. */
		$contact_fields = array(
			'title'      => __( 'Contact Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => ( 'contact' ) ),
			'fields'     => array(
				array(
					'name'       => 'contactStandardFields',
					'label'      => __( 'Map Fields', 'gravityformszohocrm' ),
					'type'       => 'field_map',
					'field_map'  => $this->get_field_map_for_module( 'Contacts' ),
					'tooltip'    => '<h6>'. __( 'Map Fields', 'gravityformszohocrm' ) .'</h6>' . __( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' )
				),
				array(
					'name'       => 'contactCustomFields',
					'label'      => '',
					'type'       => 'dynamic_field_map',
					'field_map'  => $this->get_field_map_for_module( 'Contacts', 'dynamic' ),
				),
				array(
					'name'       => 'contactOwner',
					'label'      => __( 'Contact Owner', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_users_for_feed_setting()
				),
				array(
					'name'       => 'contactLeadSource',
					'label'      => __( 'Lead Source', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_module_field_choices( 'Contacts', 'Lead Source' )
				),
				array(
					'name'       => 'contactDescription',
					'type'       => 'textarea',
					'class'      => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'      => __( 'Contact Description', 'gravityformshelpscout' ),
				),
			)
		);

		if ( ! empty ( $contact_file_fields ) ) {

			$contact_fields['fields'][] = array(
				'name'    => 'contactAttachments',
				'type'    => 'checkbox',
				'label'   => __( 'Attachments', 'gravityformshelpscout' ),
				'choices' => $contact_file_fields,
				'tooltip' => '<h6>'. __( 'Attachments', 'gravityformszohocrm' ) .'</h6>' . __( 'Zoho CRM has a maximum file size of 20MB. Any file larger than this will not be uploaded. Additionally, files will not be uploaded if you have reached the storage allocation for your Zoho CRM account.', 'gravityformszohocrm' )
			);

		}
		
		$contact_fields['fields'][] = array(
			'name'       => 'options',
			'label'      => __( 'Options', 'gravityformszohocrm' ),
			'type'       => 'checkbox',
			'choices'    => array(
				array(
					'name'          => 'contactApprovalMode',
					'label'         => __( 'Approval Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'          => 'contactWorkflowMode',
					'label'         => __( 'Workflow Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'          => 'contactEmailOptOut',
					'label'         => __( 'Email Opt Out', 'gravityformszohocrm' ),
				),
				array(
					'name'          => 'contactUpdate',
					'label'         => __( 'Update Contact if contact already exists for email address', 'gravityformszohocrm' ),
				),
			)
		);


		/* Build lead fields array. */
		$lead_fields = array(
			'title'      => __( 'Lead Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => ( 'lead' ) ),
			'fields'     => array(
				array(
					'name'       => 'leadStandardFields',
					'label'      => __( 'Map Fields', 'gravityformszohocrm' ),
					'type'       => 'field_map',
					'field_map'  => $this->get_field_map_for_module( 'Leads' ),
					'tooltip'    => '<h6>'. __( 'Map Fields', 'gravityformszohocrm' ) .'</h6>' . __( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' )
				),
				array(
					'name'       => 'leadCustomFields',
					'label'      => '',
					'type'       => 'dynamic_field_map',
					'field_map'  => $this->get_field_map_for_module( 'Leads', 'dynamic' ),
				),
				array(
					'name'       => 'leadOwner',
					'label'      => __( 'Lead Owner', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_users_for_feed_setting()
				),
				array(
					'name'       => 'leadRating',
					'label'      => __( 'Lead Rating', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_module_field_choices( 'Leads', 'Rating' )
				),
				array(
					'name'       => 'leadSource',
					'label'      => __( 'Lead Source', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_module_field_choices( 'Leads', 'Lead Source' )
				),
				array(
					'name'       => 'leadStatus',
					'label'      => __( 'Lead Status', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_module_field_choices( 'Leads', 'Lead Status' )
				),
				array(
					'name'       => 'leadDescription',
					'type'       => 'textarea',
					'class'      => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'      => __( 'Lead Description', 'gravityformshelpscout' ),
				),
			)
		);
		
		if ( ! empty ( $lead_file_fields ) ) {

			$lead_fields['fields'][] = array(
				'name'    => 'leadAttachments',
				'type'    => 'checkbox',
				'label'   => __( 'Attachments', 'gravityformshelpscout' ),
				'choices' => $lead_file_fields,
				'tooltip' => '<h6>'. __( 'Attachments', 'gravityformszohocrm' ) .'</h6>' . __( 'Zoho CRM has a maximum file size of 20MB. Any file larger than this will not be uploaded. Additionally, files will not be uploaded if you have reached the storage allocation for your Zoho CRM account.', 'gravityformszohocrm' )
			);

		}
		
		$lead_fields['fields'][] = array(
			'name'       => 'options',
			'label'      => __( 'Options', 'gravityformszohocrm' ),
			'type'       => 'checkbox',
			'choices'    => array(
				array(
					'name'  => 'leadApprovalMode',
					'label' => __( 'Approval Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'  => 'leadWorkflowMode',
					'label' => __( 'Workflow Mode', 'gravityformszohocrm' ),
				),
				array(
					'name'  => 'leadEmailOptOut',
					'label' => __( 'Email Opt Out', 'gravityformszohocrm' ),
				),
				array(
					'name'  => 'leadUpdate',
					'label' => __( 'Update Lead if lead already exists for email address', 'gravityformszohocrm' ),
				),
			)
		);

		/* Build task fields array. */
		$task_fields = array(
			'title'      => __( 'Task Details', 'gravityformszohocrm' ),
			'dependency' => array( $this, 'show_task_conditional_sections' ),
			'fields'     => array(
				array(
					'name'                => 'createTask',
					'label'               => __( 'Create Task', 'gravityformszohocrm' ),
					'type'                => 'checkbox',
					'onclick'             => "jQuery(this).parents('form').submit();",
					'choices'             => array(
						array(
							'name'  => 'createTask',
							'label' => sprintf( 
								__( 'Create Task for %s', 'gravityformszohocrm' ), 
								rgars( $feed, 'action' ) ? ucfirst( rgar( $feed, 'action' ) ) : ucfirst( rgars( $feed, 'meta/action' ) )
							),
						),
					)
				),
				array(
					'name'                => 'taskSubject',
					'type'                => 'text',
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'               => __( 'Task Subject', 'gravityformszohocrm' ),
					'required'            => true,
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) )
				),
				array(
					'name'                => 'taskDueDate',
					'type'                => 'text',
					'class'               => 'small',
					'label'               => __( 'Days Until Due', 'gravityformszohocrm' ),
					'validation_callback' => array( $this, 'validate_task_due_date' ),
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) )
				),
				array(
					'name'                => 'taskOwner',
					'label'               => __( 'Task Owner', 'gravityformszohocrm' ),
					'type'                => 'select',
					'choices'             => $this->get_users_for_feed_setting(),
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) )
				),
				array(
					'name'                => 'taskStatus',
					'label'               => __( 'Task Status', 'gravityformszohocrm' ),
					'type'                => 'select',
					'choices'             => $this->get_module_field_choices( 'Tasks', 'Status' ),
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) )
				),
			)
		);

		/* Build conditional logic field array. */
		$conditional_fields = array(
			'title'      => __( 'Feed Conditional Logic', 'gravityformszohocrm' ),
			'dependency' => array( $this, 'show_task_conditional_sections' ),
			'fields'     => array(
				array(
					'name'           => 'feedCondition',
					'type'           => 'feed_condition',
					'label'          => __( 'Conditional Logic', 'gravityformszohocrm' ),
					'checkbox_label' => __( 'Enable', 'gravityformszohocrm' ),
					'instructions'   => __( 'Export to Zoho CRM if', 'gravityformszohocrm' ),
					'tooltip'        => '<h6>' . __( 'Conditional Logic', 'gravityformszohocrm' ) . '</h6>' . __( 'When conditional logic is enabled, form submissions will only be exported to Zoho CRM when the condition is met. When disabled, all form submissions will be posted.', 'gravityformszohocrm' )
				),
				
			)
		);
		
		return array( $base_fields, $contact_fields, $lead_fields, $task_fields, $conditional_fields );

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
	 * Setup columns for feed list table.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feedName' => __( 'Name', 'gravityformszohocrm' ),
			'action'   => __( 'Action', 'gravityformszohocrm' )
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
		$feed = $this->get_current_feed();
		
		/* Get posted settings. */
		$posted_settings = $this->get_posted_settings();
		
		/* Show if an action is chosen */
		return ( rgar( $posted_settings, 'action' ) !== '' || rgars( $feed, 'meta/action' ) !== '' );
			
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
		
		if ( ! rgblank( $field_setting ) && ! is_numeric( $field_setting ) ) {
			$this->set_field_error( $field, esc_html__( 'This field must be numeric.', 'gravityforms' ) );
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
		
		return rgar( $module_fields, $field_name );
		
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
		
		foreach ( $field['choices'] as $choice ) {
			
			$choices[] = array(
				'label' => $choice,
				'value' => $choice	
			);
			
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
					$field_type = 'email';
					break;

				case 'Phone':
					$field_type = 'phone';
					break;
				
			}
			
			/* Add field to field map. */
			$field_map[] = array(
				'name'       => str_replace( ' ', '_', $field['name'] ),
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
				'label' => __( '-None-', 'gravityformszohocrm' ),
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
		$file_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload' ) );

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
		
		if ( rgars( $feed, 'meta/createTask' ) != '1' )
			return;
		
		/* Create task object. */
		$task = array(
			'Due Date'  => rgars( $feed, 'meta/taskDueDate' ) ? date( 'Y-m-d', strtotime( '+' . $feed['meta']['taskDueDate'] . ' days' ) ) : '',
			'Subject'   => GFCommon::replace_variables( $feed['meta']['taskSubject'], $form, $entry, false, false, false, 'text' ),
			'Status'    => rgars( $feed, 'meta/taskStatus' ),
			'SMOWNERID' => rgars( $feed, 'meta/taskOwner' )
		);
		
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
						
			if ( $field_key === 'Subject' )
				$field_value = '<![CDATA[ ' . $field_value . ' ]]>';

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
			if ( empty( $files ) )
				continue;

			$files = $this->is_json( $files ) ? json_decode( $files, true ) : array( $files );

			/* Loop through the files, change the URL to a path and check the maximum size. */
			foreach ( $files as $index => &$file ) {
				
				/* Change the URL to the local path. */
				$file = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );
				
				/* Check if file is larger than the max size allowed. */
				if ( filesize( $file ) > 20000000 ) {
					
					$this->log_error( __METHOD__ . '(): Unable to upload file "' . basename( $file ) . '" because it is larger than 20MB. ');
					
					unset( $files[$index] );
					
				}
				
			}
						
		}
		
		
		/* If there are still files left to be uploaded, upload 'em. */
		if ( ! empty( $files ) ) {
			
			foreach ( $files as $file ) {
				
				try {
					
					$module_type = ucfirst( $module ) . 's';
					
					/* Upload file. */
					$uploaded_file = $this->api->upload_file( $module_type, $record_id, $file );
										
					/* Log that the file was uploaded. */
					$this->log_debug( __METHOD__ . '(): File "' . basename( $file ) . '" has been uploaded to ' . $module . ' #' . $record_id . '.' );
					
				} catch ( Exception $e ) {
					
					/* Log that the file was not uploaded. */
					$this->log_error( __METHOD__ . '(): File "' . basename( $file ) . '" has not been uploaded; ' . $e->getMessage() );
					
					
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
	
		set_transient( $this->fields_transient_name, $fields, 60*60*24 );

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

}
