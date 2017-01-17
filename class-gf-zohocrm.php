<?php

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Zoho CRM Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GFZohoCRM extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Zoho CRM Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from zohocrm.php
	 */
	protected $_version = GF_ZOHOCRM_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.14.26';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformszohocrm';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformszohocrm/zohocrm.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Zoho CRM Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Zoho CRM';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_zohocrm';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_zohocrm';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_zohocrm_uninstall';

	/**
	 * Defines the capabilities needed for the Zoho CRM Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_zohocrm', 'gravityforms_zohocrm_uninstall' );

	/**
	 * Contains an instance of the Zoho CRM API libray, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    object $api If available, contains an instance of the Zoho CRM API library.
	 */
	protected $api = null;

	/**
	 * Defines the transient name used to cache Zoho CRM custom fields.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $fields_transient_name Transient name used to cache Zoho CRM custom fields.
	 */
	protected $fields_transient_name = 'gform_zohocrm_fields';

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return GFZohoCRM
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Autoload the required libraries.
	 *
	 * @since  1.3.1
	 * @access public
	 *
	 * @uses GFAddOn::is_gravityforms_supported()
	 */
	public function pre_init() {

		parent::pre_init();

		if ( $this->is_gravityforms_supported() ) {

			// Initialize Zoho CRM API library.
			if ( ! class_exists( 'GF_ZohoCRM_API' ) ) {
				require_once 'includes/class-gf-zohocrm-api.php';
			}

		}

	}

	/**
	 * Plugin starting point. Adds PayPal delayed payment support.
	 *
	 * @since  1.2
	 * @access public
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
	 * @since  1.0
	 * @access public
	 *
	 * @return array $styles
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => 'gform_zohocrm_form_settings_css',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'form_settings' ) ) ),
			),
		);

		return array_merge( parent::styles(), $styles );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Add clear custom fields cache button.
	 *
	 * @since  1.1
	 * @access public
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
	 * @since  1.1
	 * @access public
	 *
	 * @uses GFZohoCRM::maybe_clear_fields_cache()
	 */
	public function plugin_settings_page() {

		$this->maybe_clear_fields_cache();

		parent::plugin_settings_page();

	}

	/**
	 * Clear the Zoho CRM custom fields cache.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @uses GFCommon::add_message()
	 */
	public function maybe_clear_fields_cache() {

		// If the clear_field_cache parameter isn't set, exit.
		if ( 'true' !== rgget( 'clear_field_cache' )) {
			return;
		}

		// Clear the cache.
		delete_transient( $this->fields_transient_name );

		// Add success message.
		GFCommon::add_message( esc_html__( 'Custom fields cache has been cleared.', 'gravityformszohocrm' ) );

	}

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		// Prepare plugin description.
		$description  = '<p>';
		$description .= sprintf(
			esc_html__( 'Zoho CRM is a contact management tool that gives you a 360-degree view of your complete sales cycle and pipeline. Use Gravity Forms to collect customer information and automatically add it to your Zoho CRM account. If you don\'t have a Zoho CRM account, you can %1$s sign up for one here.%2$s', 'gravityformszohocrm' ),
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
							),
						),
					),
					array(
						'name'              => 'emailAddress',
						'label'             => esc_html__( 'Email Address', 'gravityformszohocrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( '', 'email' ) ),
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'name'              => 'password',
						'label'             => esc_html__( 'Password', 'gravityformszohocrm' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( '', 'email' ) ),
						'feedback_callback' => array( $this, 'initialize_api' ),
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
						'save_callback'     => array( $this, 'update_auth_token' ),
					),
					array(
						'name'              => 'authToken',
						'label'             => esc_html__( 'Authentication Token', 'gravityformszohocrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'authMode', 'values' => array( 'third_party' ) ),
						'feedback_callback' => array( $this, 'initialize_api' ),
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
	 * @since  1.1
	 * @access public
	 *
	 * @param  array $field Field properties.
	 * @param  bool  $echo  Display field contents. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_auth_token_button( $field, $echo = true ) {

		// Get accounts API URL.
		$accounts_api_url = $this->get_accounts_api_url();

		$html = sprintf(
			'<a href="%1$s" class="button" onclick="%2$s">%3$s</a>',
			"{$accounts_api_url}/apiauthtoken/create?SCOPE=ZohoCRM/crmapi",
			"window.open( '" . $accounts_api_url . "/apiauthtoken/create?SCOPE=ZohoCRM/crmapi', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=590,height=700' );return false;",
			esc_html__( 'Click here to generate an authentication token.', 'gravityformszohocrm' )
		);

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Check if the plugin settings have changed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::get_previous_settings()
	 *
	 * @return bool
	 */
	public function have_plugin_settings_changed() {

		// Get previous and new settings.
		$old_settings = $this->get_previous_settings();
		$new_settings = $this->get_posted_settings();

		// If authentication is through a third party service, return false.
		if ( 'third_party' === rgar( $new_settings, 'authMode' ) ) {
			return false;
		}

		// If the email address has changed, return true.
		if ( rgar( $old_settings, 'emailAddress' ) !== rgar( $new_settings, 'emailAddress' ) ) {
			return true;
		}

		// If the password has changed, return true.
		if ( rgar( $old_settings, 'password' ) !== rgar( $new_settings, 'password' ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Create a new authentication token when plugin settings are updated.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $field       Field properties.
	 * @param string $field_value Current field value.
	 *
	 * @return string|null
	 */
	public function update_auth_token( $field, $field_value ) {

		// If settings have not changed, do not update authentication token.
		if ( ! $this->have_plugin_settings_changed() ) {
			return $field_value;
		}

		// Get submitted plugin settings.
		$settings = $this->get_current_settings();

		// If the email address or password are empty, set authentication token to null.
		if ( ! rgar( $settings, 'emailAddress' ) || ! rgar( $settings, 'password' ) ) {
			return null;
		}

		try {

			// Log that we are requesting an authentication token.
			$this->log_debug( __METHOD__ . '(): Requesting auth token.' );

			// Get authentication token.
			$auth_token = GF_ZohoCRM_API::get_auth_token( $settings['emailAddress'], $settings['password'] );

			// Log that we received an authentication token.
			$this->log_debug( __METHOD__ . '(): Auth token successfully retrieved.' );

			return $auth_token;

		} catch ( Exception $e ) {

			// Get plugin settings fields.
			$sections = $this->plugin_settings_fields();

			// Set field error based on error message.
			switch ( $e->getMessage() ) {

				case 'INVALID_CREDENTIALS':

					// Log authentication error.
					$this->log_error( __METHOD__ . '(): Invalid credentials' );

					// Set field errors.
					$this->set_field_error( $sections[0]['fields'][1], esc_html__( 'User does not exist.', 'gravityformszohocrm' ) );
					$this->set_field_error( $sections[0]['fields'][2], esc_html__( 'Invalid password.', 'gravityformszohocrm' ) );

					break;

				case 'INVALID_PASSWORD':

					// Log authentication error.
					$this->log_error( __METHOD__ . '(): Invalid password' );

					// Set field error.
					$this->set_field_error( $sections[0]['fields'][2], esc_html__( 'Invalid password.', 'gravityformszohocrm' ) );

					break;

				case 'NO_SUCH_USER':

					// Log authentication error.
					$this->log_error( __METHOD__ . '(): User does not exist.' );

					// Set field error.
					$this->set_field_error( $sections[0]['fields'][1], esc_html__( 'User does not exist.', 'gravityformszohocrm' ) );

					break;

				case 'WEB_LOGIN_REQUIRED':

					// Log authentication error.
					$this->log_error( __METHOD__ . '(): Invalid credentials: WEB_LOGIN_REQUIRED.' );

					// Set field error.
					$this->set_field_error( $sections[0]['fields'][2], esc_html__( "Invalid password. If two factor authentication is enabled for your account you'll need to use an application specific password.", 'gravityformszohocrm' ) );

					break;

			}

			return null;

		}

	}



	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFFeedAddOn::get_default_feed_name()
	 * @uses GFZohoCRM::contact_feed_settings_fields()
	 * @uses GFZohoCRM::lead_feed_settings_fields()
	 * @uses GFZohoCRM::task_feed_settings_fields()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		// Prepare base feed settings section.
		$base_fields = array(
			'fields' => array(
				array(
					'name'           => 'feedName',
					'label'          => esc_html__( 'Feed Name', 'gravityformszohocrm' ),
					'type'           => 'text',
					'required'       => true,
					'default_value'  => $this->get_default_feed_name(),
					'tooltip'        => '<h6>'. esc_html__( 'Name', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformszohocrm' ),
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
							'value' => null,
						),
						array(
							'label' => esc_html__( 'Create a New Contact', 'gravityformszohocrm' ),
							'value' => 'contact',
						),
						array(
							'label' => esc_html__( 'Create a New Lead', 'gravityformszohocrm' ),
							'value' => 'lead',
						),
					),
				),
			),
		);

		// Prepare conditional logic settings section.
		$conditional_fields = array(
			'title'      => esc_html__( 'Feed Conditional Logic', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => array( 'contact', 'lead' ) ),
			'fields'     => array(
				array(
					'name'           => 'feedCondition',
					'type'           => 'feed_condition',
					'label'          => esc_html__( 'Conditional Logic', 'gravityformszohocrm' ),
					'checkbox_label' => esc_html__( 'Enable', 'gravityformszohocrm' ),
					'instructions'   => esc_html__( 'Export to Zoho CRM if', 'gravityformszohocrm' ),
					'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Zoho CRM when the condition is met. When disabled, all form submissions will be posted.', 'gravityformszohocrm' ),
				),
			),
		);

		// Get module feed settings sections.
		$contact_fields = $this->contact_feed_settings_fields();
		$lead_fields    = $this->lead_feed_settings_fields();
		$task_fields    = $this->task_feed_settings_fields();

		return array( $base_fields, $contact_fields, $lead_fields, $task_fields, $conditional_fields );

	}

	/**
	 * Setup contact fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFZohoCRM::get_field_map_for_module()
	 * @uses GFZohoCRM::get_file_fields_for_feed_setting()
	 * @uses GFZohoCRM::get_module_field_choices()
	 * @uses GFZohoCRM::get_users_for_feed_setting()
	 *
	 * @return array
	 */
	public function contact_feed_settings_fields() {

		// Prepare contact settings fields.
		$fields = array(
			'title'      => esc_html__( 'Contact Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => ( 'contact' ) ),
			'fields'     => array(
				array(
					'name'      => 'contactStandardFields',
					'label'     => esc_html__( 'Map Fields', 'gravityformszohocrm' ),
					'type'      => 'field_map',
					'field_map' => $this->get_field_map_for_module( 'Contacts' ),
					'tooltip'   => '<h6>'. esc_html__( 'Map Fields', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' ),
				),
				array(
					'name'      => 'contactCustomFields',
					'label'     => null,
					'type'      => 'dynamic_field_map',
					'field_map' => $this->get_field_map_for_module( 'Contacts', 'dynamic' ),
				),
				array(
					'name'      => 'contactOwner',
					'label'     => esc_html__( 'Contact Owner', 'gravityformszohocrm' ),
					'type'      => 'select',
					'choices'   => $this->get_users_for_feed_setting(),
				),
				array(
					'name'      => 'contactDescription',
					'type'      => 'textarea',
					'class'     => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'     => esc_html__( 'Contact Description', 'gravityformszohocrm' ),
				),
				array(
					'name'    => 'options',
					'label'   => esc_html__( 'Options', 'gravityformszohocrm' ),
					'type'    => 'checkbox',
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
					),
				),
			),
		);

		// Get source choices.
		$source_choices = $this->get_module_field_choices( 'Contacts', 'Lead Source' );

		// Add Lead Source settings field if source choices exist.
		if ( ! empty( $source_choices ) ) {

			// Prepare Lead Source settings field.
			$source_field = array(
				'name'    => 'contactLeadSource',
				'label'   => esc_html__( 'Lead Source', 'gravityformszohocrm' ),
				'type'    => 'select',
				'choices' => $source_choices,
			);

			// Add settings field.
			$fields = $this->add_field_after( 'contactOwner', $source_field, $fields );

		}

		// Get file field choices.
		$file_choices = $this->get_file_fields_for_feed_setting( 'contact' );

		// Add Attachments settings field if file field choices exist.
		if ( ! empty( $file_choices ) ) {

			// Prepare Attachments settings field.
			$file_field = array(
				'name'    => 'contactAttachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attachments', 'gravityformszohocrm' ),
				'choices' => $file_choices,
				'tooltip' => '<h6>'. esc_html__( 'Attachments', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Zoho CRM has a maximum file size of 20MB. Any file larger than this will not be uploaded. Additionally, files will not be uploaded if you have reached the storage allocation for your Zoho CRM account.', 'gravityformszohocrm' )
			);

			// Add settings field.
			$fields = $this->add_field_after( 'contactDescription', $file_field, $fields );

		}

		return $fields;

	}

	/**
	 * Setup lead fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_before()
	 * @uses GFZohoCRM::get_field_map_for_module()
	 * @uses GFZohoCRM::get_file_fields_for_feed_setting()
	 * @uses GFZohoCRM::get_module_field_choices()
	 * @uses GFZohoCRM::get_users_for_feed_setting()
	 *
	 * @return array
	 */
	public function lead_feed_settings_fields() {

		// Prepare lead settings fields.
		$fields = array(
			'title'      => esc_html__( 'Lead Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => ( 'lead' ) ),
			'fields'     => array(
				array(
					'name'      => 'leadStandardFields',
					'label'     => esc_html__( 'Map Fields', 'gravityformszohocrm' ),
					'type'      => 'field_map',
					'field_map' => $this->get_field_map_for_module( 'Leads' ),
					'tooltip'   => '<h6>'. esc_html__( 'Map Fields', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' ),
				),
				array(
					'name'      => 'leadCustomFields',
					'label'     => null,
					'type'      => 'dynamic_field_map',
					'field_map' => $this->get_field_map_for_module( 'Leads', 'dynamic' ),
				),
				array(
					'name'      => 'leadOwner',
					'label'     => esc_html__( 'Lead Owner', 'gravityformszohocrm' ),
					'type'      => 'select',
					'choices'   => $this->get_users_for_feed_setting(),
				),
				array(
					'name'      => 'leadDescription',
					'type'      => 'textarea',
					'class'     => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'     => esc_html__( 'Lead Description', 'gravityformszohocrm' ),
				),
				array(
					'name'      => 'options',
					'label'     => esc_html__( 'Options', 'gravityformszohocrm' ),
					'type'      => 'checkbox',
					'choices'   => array(
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
							'tooltip'    => esc_html__( 'If duplicate leads are allowed, you will not be able to update leads if they already exist.', 'gravityformszohocrm' ),
						),
						array(
							'name'       => 'leadUpdate',
							'label'      => esc_html__( 'Update Lead if lead already exists for email address', 'gravityformszohocrm' ),
						),
					),
				),
			),
		);

		// Get rating choices.
		$rating_choices = $this->get_module_field_choices( 'Leads', 'Rating' );

		// Add Lead Rating settings field if rating choices exist.
		if ( ! empty( $rating_choices ) ) {

			// Prepare Lead Rating settings field.
			$rating_field = array(
				'name'    => 'leadRating',
				'label'   => esc_html__( 'Lead Rating', 'gravityformszohocrm' ),
				'type'    => 'select',
				'choices' => $rating_choices,
			);

			// Add settings field.
			$fields = $this->add_field_before( 'leadDescription', $rating_field, $fields );

		}

		// Get source choices.
		$source_choices = $this->get_module_field_choices( 'Leads', 'Lead Source' );

		// Add Lead Source settings field if source choices exist.
		if ( ! empty( $source_choices ) ) {

			// Prepare Lead Source settings field.
			$source_field = array(
				'name'    => 'leadSource',
				'label'   => esc_html__( 'Lead Source', 'gravityformszohocrm' ),
				'type'    => 'select',
				'choices' => $source_choices,
			);

			// Add settings field.
			$fields = $this->add_field_before( 'leadDescription', $source_field, $fields );

		}

		// Get status choices.
		$status_choices = $this->get_module_field_choices( 'Leads', 'Lead Status' );

		// Add Lead Status settings field if status choices exist.
		if ( ! empty( $status_choices ) ) {

			// Prepare Lead Status settings field.
			$status_field = array(
				'name'    => 'leadStatus',
				'label'   => esc_html__( 'Lead Status', 'gravityformszohocrm' ),
				'type'    => 'select',
				'choices' => $status_choices,
			);

			// Add settings field.
			$fields = $this->add_field_before( 'leadDescription', $status_field, $fields );

		}

		// Get file field choices.
		$file_choices = $this->get_file_fields_for_feed_setting( 'lead' );

		// Add Attachments settings field if file field choices exist.
		if ( ! empty( $file_choices ) ) {

			// Prepare Attachments settings field.
			$file_field = array(
				'name'    => 'leadAttachments',
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Attachments', 'gravityformszohocrm' ),
				'choices' => $file_choices,
				'tooltip' => '<h6>'. esc_html__( 'Attachments', 'gravityformszohocrm' ) .'</h6>' . esc_html__( 'Zoho CRM has a maximum file size of 20MB. Any file larger than this will not be uploaded. Additionally, files will not be uploaded if you have reached the storage allocation for your Zoho CRM account.', 'gravityformszohocrm' ),
			);

			// Add settings field.
			$fields = $this->add_field_after( 'leadDescription', $file_field, $fields );

		}

		return $fields;

	}

	/**
	 * Setup task fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::add_field_after()
	 * @uses GFAddOn::get_current_settings()
	 * @uses GFZohoCRM::get_module_field_choices()
	 *
	 * @return array
	 */
	public function task_feed_settings_fields() {

		// Get current feed.
		$feed = $this->get_current_settings();

		// Prepare task settings fields.
		$fields = array(
			'title'      => esc_html__( 'Task Details', 'gravityformszohocrm' ),
			'dependency' => array( 'field' => 'action', 'values' => array( 'contact', 'lead' ) ),
			'fields'     => array(
				array(
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
					),
				),
				array(
					'name'                => 'taskSubject',
					'type'                => 'text',
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'               => esc_html__( 'Task Subject', 'gravityformszohocrm' ),
					'required'            => true,
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) ),
				),
				array(
					'name'                => 'taskDueDate',
					'type'                => 'text',
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'               => esc_html__( 'Days Until Due', 'gravityformszohocrm' ),
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) ),
					'validation_callback' => array( $this, 'validate_task_due_date' ),
				),
				array(
					'name'                => 'taskOwner',
					'label'               => esc_html__( 'Task Owner', 'gravityformszohocrm' ),
					'type'                => 'select',
					'choices'             => $this->get_users_for_feed_setting(),
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) ),
				),
				array(
					'name'                => 'taskDescription',
					'type'                => 'textarea',
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'               => esc_html__( 'Task Description', 'gravityformszohocrm' ),
					'dependency'          => array( 'field' => 'createTask', 'values' => array( '1' ) ),
				),
			),
		);

		// Get status choices.
		$status_choices = $this->get_module_field_choices( 'Tasks', 'Status' );

		// Add Task Status settings field if status choices exist.
		if ( ! empty( $status_choices ) ) {

			// Prepare Task Status settings field.
			$status_field = array(
				'name'       => 'taskStatus',
				'label'      => esc_html__( 'Task Status', 'gravityformszohocrm' ),
				'type'       => 'select',
				'choices'    => $status_choices,
				'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) )
			);

			// Add settings field.
			$fields = $this->add_field_after( 'taskOwner', $status_field, $fields );

		}

		return $fields;

	}

	/**
	 * Insert settings field after another field.
	 * (Forked to allow for passing a single settings section.)
	 *
	 * @since  1.3.1
	 * @access public
	 *
	 * @param string $name     Field name to insert settings field after.
	 * @param array  $fields   Settings field.
	 * @param array  $settings Settings section to add field to.
	 *
	 * @return array
	 */
	public function add_field_after( $name, $fields, $settings ) {

		// Move settings into another array.
		$settings = array( $settings );

		// Add field.
		$settings = parent::add_field_after( $name, $fields, $settings );

		// Return the first settings section.
		return $settings[0];

	}

	/**
	 * Insert settings field before another field.
	 * (Forked to allow for passing a single settings section.)
	 *
	 * @since  1.3.1
	 * @access public
	 *
	 * @param string $name     Field name to insert settings field after.
	 * @param array  $fields   Settings field.
	 * @param array  $settings Settings section to add field to.
	 *
	 * @return array
	 */
	public function add_field_before( $name, $fields, $settings ) {

		// Move settings into another array.
		$settings = array( $settings );

		// Add field.
		$settings = parent::add_field_before( $name, $fields, $settings );

		// Return the first settings section.
		return $settings[0];

	}

	/**
	 * Set feed creation control.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.1.7
	 * @access public
	 *
	 * @param int $id Feed ID requesting duplication.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}

	/**
	 * Setup columns for feed list table.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName' => esc_html__( 'Name', 'gravityformszohocrm' ),
			'action'   => esc_html__( 'Action', 'gravityformszohocrm' ),
		);

	}

	/**
	 * Get value for action feed list column.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $feed Feed for current table row.
	 *
	 * @return string
	 */
	public function get_column_value_action( $feed ) {

		// Display contact action string.
		if ( rgars( $feed, 'meta/action' ) == 'contact' ) {
			return esc_html__( 'Create a New Contact', 'gravityformszohocrm' );
		}

		// Display lead action string.
		if ( rgars( $feed, 'meta/action' ) == 'lead' ) {
			return esc_html__( 'Create a New Lead', 'gravityformszohocrm' );
		}

		return esc_html__( 'No Action', 'gravityformszohocrm' );

	}

	/**
	 * Validate Task Days Until Due feed settings field.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $field         Field properties.
	 * @param string $field_setting Field value.
	 *
	 * @uses GFAddOn::set_field_error()
	 * @uses GFCommon::has_merge_tag()
	 */
	public function validate_task_due_date( $field, $field_setting ) {

		// If field value is not blank, is not numeric and does not have a merge tag, set field error.
		if ( ! rgblank( $field_setting ) && ! is_numeric( $field_setting ) && ! GFCommon::has_merge_tag( $field_setting ) ) {
			$this->set_field_error( $field, esc_html__( 'This field must be numeric or a merge tag.', 'gravityformszohocrm' ) );
		}

	}

	/**
	 * Get choices for a specifc Zoho CRM module field formatted for field settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module
	 * @param string $field_name
	 *
	 * @uses GFZohoCRM::get_module_field()
	 *
	 * @return array
	 */
	public function get_module_field_choices( $module, $field_name ) {

		// Initialize choices array.
		$choices = array();

		// Get module field for field name.
		$field = $this->get_module_field( $module, $field_name );

		// If no field choices exist, return choices.
		if ( empty( $field['choices'] ) ) {
			return $choices;
		}

		// Loop through field choices.
		foreach ( $field['choices'] as $choice ) {

			// If choice is an array, use content as choice.
			if ( is_array( $choice ) && rgar( $choice, 'content' ) ) {
				$choice = $choice['content'];
			}

			// Add field choice as choice.
			$choices[] = array(
				'label' => esc_html( $choice ),
				'value' => esc_attr( $choice )
			);

		}

		return $choices;

	}

	/**
	 * Get field map fields for a Zoho CRM module.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module         Module name.
	 * @param string $field_map_type Type of field map: standard or dynamic. Defaults to standard.
	 *
	 * @return array $field_map
	 */
	public function get_field_map_for_module( $module, $field_map_type = 'standard' ) {

		// Initialize field map.
		$field_map = array();

		// Define standard field labels.
		$standard_fields = array( 'Company', 'Email', 'First Name', 'Last Name' );

		// Get fields for module.
		$fields = $this->get_module_fields( $module );

		// Sort module fields in alphabetical order.
		usort( $fields, array( $this, 'sort_module_fields_by_label' ) );

		// Loop through module fields.
		foreach ( $fields as $field ) {

			// If this is a custom field, skip it.
			if ( rgar( $field, 'custom_field' ) ) {
				continue;
			}

			// If this is a non-supported field type, skip it.
			if ( in_array( $field['type'], array( 'Lookup', 'Pick List', 'OwnerLookup', 'Boolean', 'Currency' ) ) ) {
				continue;
			}

			// If this is a standard field map and the field is not a standard field or is not required, skip it.
			if ( 'standard' === $field_map_type && ! $field['required'] && ! in_array( $field['label'], $standard_fields ) ) {
				continue;
			}

			// If this is a dynamic field map and the field matches a standard field or is required, skip it.
			if ( 'dynamic' === $field_map_type && ( $field['required'] || in_array( $field['label'], $standard_fields ) ) ) {
				continue;
			}

			// Get Gravity Forms field type.
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

				default:
					$field_type = null;
					break;

			}

			// Add field to field map.
			$field_map[] = array(
				'name'       => str_replace( ' ', '_', $field['label'] ),
				'label'      => $field['label'],
				'required'   => $field['required'],
				'field_type' => $field_type,
			);

		}

		return $field_map;

	}

	/**
	 * Get Zoho CRM users for feed field settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFZohoCRM::initialize_api()
	 * @uses GF_ZohoCRM_API::get_users()
	 *
	 * @return array $users
	 */
	public function get_users_for_feed_setting() {

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( '-None-', 'gravityformszohocrm' ),
				'value' => ''
			)
		);

		// If API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get users because API is not initialized.' );
			return $choices;
		}

		try {

			// Get Zoho CRM users.
			$users = $this->api->get_users();

		} catch ( Exception $e ) {

			// Log that users could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to get users; ' . $e->getMessage() );

			return $choices;

		}

		// If Zoho CRM users exist, add them as choices.
		if ( ! empty( $users ) ) {

			// Get array keys for users array.
			$array_keys = array_keys( $users['user'] );

			// If the array keys are numeric, use user property as users array.
			if ( is_numeric( $array_keys[0] ) ) {
				$users = $users['user'];
			}

			// Loop through Zoho CRM users.
			foreach ( $users as $user ) {

				// Add user as choice.
				$choices[] = array(
					'label' => esc_html( $user['content'] ),
					'value' => esc_attr( $user['id'] ),
				);

			}

		}

		return $choices;

	}

	/**
	 * Get form file fields for feed field settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $module Module to prepare file fields for. Defaults to contact.
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAPI::get_fields_by_type()
	 *
	 * @return array
	 */
	public function get_file_fields_for_feed_setting( $module = 'contact' ) {

		// Initialize choices array.
		$choices = array();

		// Get the form.
		$form = $this->get_current_form();

		// Get file fields.
		$file_fields = GFAPI::get_fields_by_type( $form, array( 'fileupload' ), true );

		// If file fields exist, prepare them as choices.
		if ( ! empty ( $file_fields ) ) {

			// Loop through file fields.
			foreach ( $file_fields as $field ) {

				// Add file field as choice.
				$choices[] = array(
					'name'          => $module . 'Attachments[' . $field->id . ']',
					'label'         => $field->label,
					'default_value' => 0,
				);

			}

		}

		return $choices;

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the Zoho CRM feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $feed  Feed object.
	 * @param  array $entry Entry object.
	 * @param  array $form  Form object.
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFZohoCRM::create_contact()
	 * @uses GFZohoCRM::create_lead()
	 * @uses GFZohoCRM::create_task()
	 * @uses GFZohoCRM::initialize_api()
	 * @uses GFZohoCRM::upload_attachments()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {

			// Log that we cannot process the feed.
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformzohocrm' ), $feed, $entry, $form );

			return;
		}

		// Create contact.
		if ( rgars( $feed, 'meta/action' ) === 'contact' ) {

			// Get contact ID.
			$contact_id = $this->create_contact( $feed, $entry, $form );

			// If contact was created, upload attachments and create task as needed.
			if ( ! rgblank( $contact_id ) ) {
				$this->upload_attachments( $contact_id, 'contact', $feed, $entry, $form );
				$this->create_task( $contact_id, 'Contacts', $feed, $entry, $form );
			}

		}

		// Create lead.
		if ( rgars( $feed, 'meta/action' ) === 'lead' ) {

			// Get lead ID.
			$lead_id = $this->create_lead( $feed, $entry, $form );

			// If lead was created, upload attachments and create task as needed.
			if ( ! rgblank( $lead_id ) ) {
				$this->upload_attachments( $lead_id, 'lead', $feed, $entry, $form );
				$this->create_task( $lead_id, 'Leads', $feed, $entry, $form );
			}

		}

	}

	/**
	 * Create a new contact from a feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @since  1.0
	 * @access public

	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GFZohoCRM::get_field_xml()
	 * @uses GF_ZohoCRM_API::insert_record()
	 *
	 * @return int|null $contact_id
	 */
	public function create_contact( $feed, $entry, $form ) {

		// Initialize lead object.
		$contact = array(
			'Email Opt Out' => rgars( $feed, 'meta/contactEmailOptOut' ) == '1' ? 'true' : 'false',
			'Description'   => GFCommon::replace_variables( $feed['meta']['contactDescription'], $form, $entry, false, false, false, 'text' ),
			'Lead Source'   => rgars( $feed, 'meta/contactLeadSource' ),
			'options'       => array(
				'duplicateCheck' => rgars( $feed, 'meta/contactUpdate' ) == '1' ? 2 : 1,
				'isApproval'     => rgars( $feed, 'meta/contactApprovalMode' ) == '1' ? 'true' : 'false',
				'wfTrigger'      => rgars( $feed, 'meta/contactWorkflowMode' ) == '1' ? 'true' : 'false',
				'version'        => 4,
				'newFormat'      => 2,
			),
		);

		// If duplicate contacts are allowed, remove the duplicate check.
		if ( rgars( $feed, 'meta/contactDuplicateAllowed' ) ) {
			unset( $contact['options']['duplicateCheck'] );
		}

		// Add owner ID.
		if ( rgars( $feed, 'meta/contactOwner' ) ) {
			$task['SMOWNERID'] = $feed['meta']['contactOwner'];
		}

		// Get standard and custom fields.
		$standard_fields = $this->get_field_map_fields( $feed, 'contactStandardFields' );
		$custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'contactCustomFields' );

		// Merge standard and custom fields arrays.
		$mapped_fields = array_merge( $standard_fields, $custom_fields );

		// Loop through mapped fields.
		foreach ( $mapped_fields as $field_name => $field_id ) {

			// Convert underscores to spaces in field name.
			$field_name  = str_replace( '_', ' ', $field_name );

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			// If field value is empty, skip it.
			if ( rgblank( $field_value ) ) {
				continue;
			}

			// Add mapped field to contact object.
			$contact[ $field_name ] = $field_value;

		}

		/**
		 * Modify the contact arguments before they are sent to Zoho CRM.
		 *
		 * @since 1.0
		 *
		 * @param array $contact The contact arguments.
		 * @param array $feed    Feed object.
		 * @param array $entry   Entry object.
		 * @param array $form    Form object.
		 */
		$contact = gf_apply_filters( 'gform_zohocrm_contact', $form['id'], $contact, $feed, $entry, $form );

		// Initialize contact record XML.
		$contact_xml  = '<Contacts>' . "\r\n";
		$contact_xml .= '<row no="1">' . "\r\n";

		// Loop through contact properties.
		foreach ( $contact as $field_key => $field_value ) {

			// If property value is an array, skip it.
			if ( is_array( $field_value ) ) {
				continue;
			}

			// Add property to XML.
			$contact_xml .= $this->get_field_xml( $field_key, $field_value );

		}

		// Close contact record XML.
		$contact_xml .= '</row>' . "\r\n";
		$contact_xml .= '</Contacts>' . "\r\n";

		// Log contact arguments and XML object.
		$this->log_debug( __METHOD__ . '(): Creating contact - arguments: ' . print_r( $contact, true ) );
		$this->log_debug( __METHOD__ . '(): Creating contact - XML object: ' . print_r( $contact_xml, true ) );

		try {

			// Create contact.
			$contact_record = $this->api->insert_record( 'Contacts', $contact_xml, $contact['options'] );

			// Get new contact ID. */
			$contact_id = 0;
			foreach ( $contact_record->result->recorddetail as $detail ) {
				foreach ( $detail->children() as $field ) {
					if ( $field['val'] == 'Id' ) {
						$contact_id = (string) $field;
						break;
					}
				}
			}

			// Save contact ID to entry meta.
			gform_update_meta( $entry['id'], 'zohocrm_contact_id', $contact_id );

			// Log that contact was created.
			$this->log_debug( __METHOD__ . '(): Contact #' . $contact_id . ' created.' );

			return $contact_id;

		} catch ( Exception $e ) {

			// Log that contact could not be created.
			$this->add_feed_error( 'Could not create contact; ' . esc_html( $e->getMessage() ), $feed, $entry, $form );

			return null;

		}

	}

	/**
	 * Create a new lead from a feed.
	 *
	 * @since  1.0
	 * @access public

	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @uses GFAddOn::get_dynamic_field_map_fields()
	 * @uses GFAddOn::get_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GFZohoCRM::get_field_xml()
	 * @uses GF_ZohoCRM_API::insert_record()
	 *
	 * @return int|null $lead_id
	 */
	public function create_lead( $feed, $entry, $form ) {

		// Initialize lead object.
		$lead = array(
			'Email Opt Out' => rgars( $feed, 'meta/leadEmailOptOut' ) == '1' ? 'true' : 'false',
			'Description'   => GFCommon::replace_variables( $feed['meta']['leadDescription'], $form, $entry, false, false, false, 'text' ),
			'Lead Source'   => rgars( $feed, 'meta/leadSource' ),
			'Lead Status'   => rgars( $feed, 'meta/leadStatus' ),
			'Rating'        => rgars( $feed, 'meta/leadRating' ),
			'options'       => array(
				'duplicateCheck' => rgars( $feed, 'meta/leadUpdate' ) == '1' ? 2 : 1,
				'isApproval'     => rgars( $feed, 'meta/leadApprovalMode' ) == '1' ? 'true' : 'false',
				'wfTrigger'      => rgars( $feed, 'meta/leadWorkflowMode' ) == '1' ? 'true' : 'false',
				'version'        => 4,
				'newFormat'      => 2,
			),
		);

		// If duplicate leads are allowed, remove the duplicate check.
		if ( rgars( $feed, 'meta/leadDuplicateAllowed' ) ) {
			unset( $lead['options']['duplicateCheck'] );
		}

		// Add owner ID.
		if ( rgars( $feed, 'meta/leadOwner' ) ) {
			$task['SMOWNERID'] = $feed['meta']['leadOwner'];
		}

		// Get standard and custom fields.
		$standard_fields = $this->get_field_map_fields( $feed, 'leadStandardFields' );
		$custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'leadCustomFields' );

		// Merge standard and custom fields arrays.
		$mapped_fields = array_merge( $standard_fields, $custom_fields );

		// Loop through mapped fields.
		foreach ( $mapped_fields as $field_name => $field_id ) {

			// Convert underscores to spaces in field name.
			$field_name  = str_replace( '_', ' ', $field_name );

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			// If field value is empty, skip it.
			if ( rgblank( $field_value ) ) {
				continue;
			}

			// Add mapped field to lead object.
			$lead[ $field_name ] = $field_value;

		}

		/**
		 * Modify the lead arguments before they are sent to Zoho CRM.
		 *
		 * @since 1.0
		 *
		 * @param array $lead  The lead arguments.
		 * @param array $feed  Feed object.
		 * @param array $entry Entry object.
		 * @param array $form  Form object.
		 */
		$lead = gf_apply_filters( 'gform_zohocrm_lead', $form['id'], $lead, $feed, $entry, $form );

		// Initialize lead record XML.
		$lead_xml  = '<Leads>' . "\r\n";
		$lead_xml .= '<row no="1">' . "\r\n";

		// Loop through lead properties.
		foreach ( $lead as $field_key => $field_value ) {

			// If property value is an array, skip it.
			if ( is_array( $field_value ) ) {
				continue;
			}

			// Add property to XML.
			$lead_xml .= $this->get_field_xml( $field_key, $field_value );

		}

		// Close lead record XML.
		$lead_xml .= '</row>' . "\r\n";
		$lead_xml .= '</Leads>' . "\r\n";

		// Log lead arguments and XML object.
		$this->log_debug( __METHOD__ . '(): Creating lead - arguments: ' . print_r( $lead, true ) );
		$this->log_debug( __METHOD__ . '(): Creating lead - XML object: ' . print_r( $lead_xml, true ) );

		try {

			// Create lead.
			$lead_record = $this->api->insert_record( 'Leads', $lead_xml, $lead['options'] );

			// Get new lead ID.
			$lead_id = 0;
			foreach ( $lead_record->result->row->success->details as $detail ) {
				foreach ( $detail->children() as $field ) {
					if ( $field['val'] == 'Id' ) {
						$lead_id = (string) $field;
						break;
					}
				}
			}

			// Save lead ID to entry meta.
			gform_update_meta( $entry['id'], 'zohocrm_lead_id', $lead_id );

			// Log that lead was created.
			$this->log_debug( __METHOD__ . '(): Lead #' . $lead_id . ' created.' );

			return $lead_id;

		} catch ( Exception $e ) {

			// Log that lead could not be created.
			$this->add_feed_error( 'Could not create lead; ' . esc_html( $e->getMessage() ), $feed, $entry, $form );

			return null;

		}

	}

	/**
	 * Create a new task from a feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int    $record_id Record ID to add attachment to.
	 * @param string $module    Module for record.
	 * @param array  $feed      Feed object.
	 * @param array  $entry     Entry object.
	 * @param array  $form      Form object.
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GFZohoCRM::get_field_xml()
	 * @uses GF_ZohoCRM_API::insert_record()
	 *
	 * @return null|int
	 */
	public function create_task( $record_id, $module, $feed, $entry, $form ) {

		// If task creation is not enabled, exit.
		if ( rgars( $feed, 'meta/createTask' ) != '1' ) {
			return null;
		}

		// Initialize task object.
		$task = array(
			'Subject'     => GFCommon::replace_variables( $feed['meta']['taskSubject'], $form, $entry, false, false, false, 'text' ),
			'Status'      => rgars( $feed, 'meta/taskStatus' ),
			'Description' => GFCommon::replace_variables( $feed['meta']['taskDescription'], $form, $entry, false, false, false, 'text' ),
		);

		// Add due date.
		if ( rgars( $feed, 'meta/taskDueDate' ) ) {

			// Replace due date merge tags.
			$due_date = GFCommon::replace_variables( $feed['meta']['taskDueDate'], $form, $entry, false, false, false, 'text' );

			// If due date is numeric, use string to time to add date.
			$task['Due Date'] = is_numeric( $due_date ) ? date( 'Y-m-d', strtotime( '+' . $due_date . ' days' ) ) : $due_date;

		}

		// Add contact ID.
		if ( 'Contacts' === $module ) {
			$task['CONTACTID'] = $record_id;
		}

		// Add lead ID.
		if ( 'Leads' === $module ) {
			$task['SEID']     = $record_id;
			$task['SEMODULE'] = $module;
		}

		// Add owner ID.
		if ( rgars( $feed, 'meta/taskOwner' ) ) {
			$task['SMOWNERID'] = $feed['meta']['taskOwner'];
		}

		/**
		 * Modify the task arguments before they are sent to Zoho CRM.
		 *
		 * @since 1.0
		 *
		 * @param array $task  The task arguments.
		 * @param array $feed  Feed object.
		 * @param array $entry Entry object.
		 * @param array $form  Form object.
		 */
		$task = gf_apply_filters( 'gform_zohocrm_task', $form['id'], $task, $feed, $entry, $form );

		// Initialize task record XML.
		$task_xml  = '<Tasks>' . "\r\n";
		$task_xml .= '<row no="1">' . "\r\n";

		// Add task arguments to XML.
		foreach ( $task as $field_key => $field_value ) {
			$task_xml .= $this->get_field_xml( $field_key, $field_value );
		}

		// Close task record XML.
		$task_xml .= '</row>' . "\r\n";
		$task_xml .= '</Tasks>' . "\r\n";

		// Log task arguments and XML object.
		$this->log_debug( __METHOD__ . '(): Creating task - arguments: ' . print_r( $task, true ) );
		$this->log_debug( __METHOD__ . '(): Creating task - XML object: ' . print_r( $task_xml, true ) );

		try {

			// Create task.
			$task_record = $this->api->insert_record( 'Tasks', $task_xml );

			// Get new task ID.
			$task_id = 0;
			foreach ( $task_record->result->recorddetail as $detail ) {
				foreach ( $detail->children() as $field ) {
					if ( $field['val'] == 'Id' ) {
						$task_id = (string) $field;
						break;
					}
				}
			}

			// Save task ID to entry meta.
			gform_update_meta( $entry['id'], 'zohocrm_task_id', $task_id );

			// Log that task was created.
			$this->log_debug( __METHOD__ . '(): Task #' . $task_id . ' created and assigned to ' . $module . ' #' . $record_id . '.' );

			return $task_id;

		} catch ( Exception $e ) {

			// Log that task could not be created.
			$this->add_feed_error( 'Could not create task; ' . esc_html( $e->getMessage() ), $feed, $entry, $form );

			return null;

		}

	}

	/**
	 * Upload attachments from a feed.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int    $record_id Record ID to add attachment to.
	 * @param string $module    Module for record.
	 * @param array  $feed      Feed object.
	 * @param array  $entry     Entry object.
	 * @param array  $form      Form object.
	 *
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::is_json()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GF_ZohoCRM_API::upload_file()
	 */
	public function upload_attachments( $record_id, $module, $feed, $entry, $form ) {

		// If no file upload fields are selected as attachments, exit.
		if ( ! rgars( $feed, 'meta/' . $module . 'Attachments' ) ) {
			return;
		}

		// Prepare module type.
		$module_type = ucfirst( $module ) . 's';

		// Initialize array to store file upload fields.
		$file_fields = array();

		// Loop through attachments settings field choices.
		foreach ( $feed['meta'][ $module . 'Attachments'] as $field_id => $value ) {

			// If this field is enabled for attachments, add it to the file upload fields array.
			if ( '1' == $value ) {
				$file_fields[] = $field_id;
			}

		}

		// If no file upload fields are defined, exit.
		if ( empty( $file_fields ) ) {
			return;
		}

		// Loop through file upload fields.
		foreach ( $file_fields as $file_field ) {

			// Get files for field.
			$files = $this->get_field_value( $form, $entry, $field_to_upload );

			// If no files were uploaded for this field, skip it.
			if ( empty( $files ) ) {
				continue;
			}

			// Convert files value to array.
			$files = $this->is_json( $files ) ? json_decode( $files, true ) : explode( ' , ', $files );

			// Loop through the files.
			foreach ( $files as $i => &$file ) {

				// Convert file URL to local path.
				$file_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );

				// If the file is larger than the maximum allowed by Zoho CRM, skip it.
				if ( filesize( $file_path ) > 20000000 ) {
					$this->log_error( __METHOD__ . '(): Unable to upload file "' . basename( $file_path ) . '" because it is larger than 20MB.' );
					continue;
				}

				try {

					// Upload file.
					$uploaded_file = $this->api->upload_file( $module_type, $record_id, $file_path );

					// Log that file was uploaded.
					$this->log_debug( __METHOD__ . '(): File "' . basename( $file_path ) . '" has been uploaded to ' . $module . ' #' . $record_id . '.' );

				} catch ( Exception $e ) {

					// Log that file could not be uploaded.
					$this->log_error( __METHOD__ . '(): File "' . basename( $file_path ) . '" could not be uploaded; ' . $e->getMessage() );

				}

			}

		}

	}





	// # HELPER FUNCTIONS ----------------------------------------------------------------------------------------------

	/**
	 * Initializes the Zoho CRM API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_setting()
	 * @uses GF_ZohoCRM_API::get_users()
	 *
	 * @return bool|null API initialization state. Returns null if no authentication token is provided.
	 */
	public function initialize_api() {

		// If the API is already initializes, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Get the authentication token.
		$auth_token = $this->get_plugin_setting( 'authToken' );

		// If the authentication token is not set, return null.
		if ( rgblank( $auth_token ) ) {
			return null;
		}

		// Log that were testing the API credentials.
		$this->log_debug( __METHOD__ . "(): Validating API credentials." );

		// Initialize a new Zoho CRM API instance.
		$zoho_crm = new GF_ZohoCRM_API( $auth_token );

		try {

			// Attempt to retrieve Zoho CRM account users.
			$zoho_crm->get_users();

			// Log that test passed.
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

			// Assign Zoho CRM API instance to the Add-On instance.
			$this->api = $zoho_crm;

			return true;

		} catch ( Exception $e ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );

			return false;

		}

	}

	/**
	 * Get the Zoho CRM accounts API URL.
	 *
	 * @since  1.3.1
	 * @access public
	 *
	 * @return string
	 */
	public function get_accounts_api_url() {

		/**
		 * Allows Zoho CRM accounts API URL to be changed.
		 * In addition to crm.zoho.com, Zoho CRM has an European solution that points to crm.zoho.eu.
		 *
		 * @since 1.2.5
		 *
		 * @param string $accounts_api_url Zoho CRM accounts API URL.
		 */
		return apply_filters( 'gform_zoho_accounts_api_url', 'https://accounts.zoho.com' );

	}

	/**
	 * Update the cached fields for all the needed modules.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GF_ZohoCRM_API::get_fields()
	 *
	 * @return string $fields JSON encoded string of all module fields.
	 */
	public function update_cached_fields() {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to update fields because API is not initialized.' );
			return;
		}

		// Initialize fields array.
		$fields = array();

		// Get module fields.
		$modules = array(
			'Contacts' => $this->api->get_fields( 'Contacts' ),
			'Leads'    => $this->api->get_fields( 'Leads' ),
			'Tasks'    => $this->api->get_fields( 'Tasks' )
		);

		// Loop through modules.
		foreach ( $modules as $module_name => $module ) {

			// Initialize array to store valid module fields.
			$module_fields = array();

			// Loop through the module's sections.
			foreach ( $module['section'] as $section ) {

				// Get section fields array.
				if ( rgar( $section, 'FL' ) ) {
					$section_fields = $section['FL'];
				} else if ( ! rgar( $section, 'FL' ) && is_array( $section[0] ) && isset( $section[0]['dv'] ) ) {
					$section_fields = $section;
				}

				// If section fields array could not be found, skip module.
				if ( ! isset( $section_fields ) ) {
					continue;
				}

				// Loop through the section's fields.
				foreach ( $section_fields as $section_field ) {

					// If field object is not an array, skip it.
					if ( ! is_array( $section_field ) ) {
						continue;
					}

					// Prepare field details.
					$field = array(
						'custom_field' => filter_var( $section_field['customfield'], FILTER_VALIDATE_BOOLEAN ),
						'label'        => $section_field['label'],
						'name'         => $section_field['dv'],
						'required'     => filter_var( $section_field['req'], FILTER_VALIDATE_BOOLEAN ),
						'type'         => $section_field['type'],
					);

					// Store field choices, if set.
					if ( rgar( $section_field, 'val' ) ) {
						$field['choices'] = $section_field['val'];
					}

					// Add field to array.
					$fields[ $module_name ][ $section_field['dv'] ] = $field;

				}

			}

		}

		// Convert fields array to JSON string.
		$fields = json_encode( $fields );

		// Store fields.
		set_transient( $this->fields_transient_name, $fields, 60*60*12 );

		return $fields;

	}

	/**
	 * Override how multiple choices in multiselect and checkbox type field values are separated and enable use of the gform_zohocrm_field_value hook.
	 *
	 * @since 1.1.9
	 * @access public
	 *
	 * @param string $field_value The field value.
	 * @param array  $form        The form object currently being processed.
	 * @param array  $entry       The entry object currently being processed.
	 * @param string $field_id    The ID of the field being processed.
	 *
	 * @uses GFFormsModel::get_field()
	 * @uses GF_Field::get_input_type()
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {

		// Get the form field.
		$field = GFFormsModel::get_field( $form, $field_id );

		// If the field is an object, attempt to replace the field value.
		if ( is_object( $field ) ) {

			// Check if the field is an integer.
			$is_integer = $field_id == intval( $field_id );

			// Ge the field input type.
			$input_type = $field->get_input_type();

			// If this is a multiselect or checkbox field, convert the comma separated list to a semi-colon separated list.
			if ( $input_type == 'multiselect' || ( $is_integer && $input_type == 'checkbox' ) ) {
				$field_value = str_replace( ', ', ';', $field_value );
			}

		}

		return parent::maybe_override_field_value( $field_value, $form, $entry, $field_id );

	}

	/**
	 * Get the XML string for the current field.
	 *
	 * @since  1.2.2
	 * @access public
	 *
	 * @param string $field_key   The ID of the field being processed.
	 * @param string $field_value The field value.
	 *
	 * @return string
	 */
	public function get_field_xml( $field_key, $field_value ) {

		// Define known field keys requiring a character data wrapper.
		$known_cdata_keys = array( 'Subject', 'Description' );

		// If the field key is in the known list or is not alphanumeric, wrap the field value in a character data wrapper.
		if ( in_array( $field_key, $known_cdata_keys ) || ! ctype_alnum( $field_value ) ) {
			$field_value = '<![CDATA[ ' . $field_value . ' ]]>';
		}

		return '<FL val="' . $field_key . '">' . $field_value . '</FL>' . "\r\n";

	}

	/**
	 * Get fields for a Zoho CRM module.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module Module to get fields for. Defaults to all modules.
	 *
	 * @uses GFZohoCRM::update_cached_fields()
	 *
	 * @return array
	 */
	public function get_module_fields( $module = null ) {

		// If module fields are not cached, retrieve them.
		if ( false === ( $fields = get_transient( $this->fields_transient_name ) ) ) {
			$fields = $this->update_cached_fields();
		}

		// Convert fields JSON string to array.
		$fields = json_decode( $fields, true );

		return rgar( $fields, $module ) ? rgar( $fields, $module ) : $fields;

	}

	/**
	 * Get field from a Zoho CRM module.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module     Module to get field from.
	 * @param string $field_name Field name to retrieve.
	 *
	 * @uses GFZohoCRM::get_module_fields()
	 *
	 * @return array
	 */
	public function get_module_field( $module, $field_name ) {

		// Get fields for module.
		$module_fields = $this->get_module_fields( $module );

		// Loop through module fields.
		foreach ( $module_fields as $module_field ) {

			// If field label matches the field name, return field.
			if ( rgar( $module_field, 'label' ) === $field_name ) {
				return $module_field;
			}

		}

		return array();

	}

	/**
	 * Sort module fields alphabeically by label.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $a First array item.
	 * @param  array $b Second array item.
	 *
	 * @return int
	 */
	public function sort_module_fields_by_label( $a, $b ) {

		return strcmp( $a['label'], $b['label'] );

	}





	// # UPGRADE ROUTINES ----------------------------------------------------------------------------------------------

	/**
	 * Checks if a previous version was installed and runs any needed migration processes.
	 *
	 * @since  1.1.5
	 * @access public
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 *
	 * @uses GFZohoCRM::run_foreign_language_fix()
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
	 * @since  1.1.5
	 * @access public
	 *
	 * @uses GFFeedAddOn::get_feeds()
	 * @uses GFFeedAddOn::update_feed_meta()
	 * @uses GFZohoCRM::get_module_fields()
	 */
	public function run_foreign_language_fix() {

		// Get the Zoho CRM feeds.
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

}
