<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Zoho CRM Add-On.
 *
 * @since     1.6 Updated to use Zoho CRM v2 API.
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
	 * Add AJAX callbacks.
	 *
	 * @since  1.6
	 */
	public function init_ajax() {
		parent::init_ajax();

		// Add AJAX callback for de-authorizing with Zoho CRM.
		add_action( 'wp_ajax_gfzohocrm_deauthorize', array( $this, 'ajax_deauthorize' ) );
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function scripts() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$scripts = array(
			array(
				'handle'  => 'gform_zohocrm_pluginsettings',
				'deps'    => array( 'jquery' ),
				'src'     => $this->get_base_url() . "/js/plugin_settings{$min}.js",
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
						'tab'        => $this->_slug,
					),
				),
				'strings' => array(
					'disconnect'   => wp_strip_all_tags( __( 'Are you sure you want to disconnect from Zoho CRM?', 'gravityformszohocrm' ) ),
					'settings_url' => admin_url( 'admin.php?page=gf_settings&subview=' . $this->get_slug() ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );

	}

	/**
	 * Register needed styles.
	 *
	 * @since  1.6 Added plugin settings CSS.
	 * @since  1.0
	 * @access public
	 *
	 * @return array $styles
	 */
	public function styles() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$styles = array(
			array(
				'handle'  => 'gform_zohocrm_form_settings_css',
				'src'     => $this->get_base_url() . "/css/form_settings{$min}.css",
				'version' => $this->_version,
				'enqueue' => array( array( 'admin_page' => array( 'form_settings' ) ) ),
			),
			array(
				'handle'  => 'gform_zohocrm_pluginsettings',
				'src'     => $this->get_base_url() . "/css/plugin_settings{$min}.css",
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
						'tab'        => $this->_slug,
					),
				),
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
		$html .= '<p>' . esc_html__( 'Due to Zoho CRM\'s daily API usage limits, Gravity Forms stores Zoho CRM custom fields data for twelve hours. If you make a change to your custom fields, you might not see it reflected immediately due to this data caching. To manually clear the custom fields cache, click the button below.', 'gravityformszohocrm' ) . '</p>';
		$html .= '<p><a href="' . add_query_arg( 'clear_field_cache', 'true' ) . '" class="button button-primary">' . esc_html__( 'Clear Custom Fields Cache', 'gravityformszohocrm' ) . '</a></p>';

		echo $html;

		echo parent::render_uninstall();

	}

	/**
	 * Add clear custom fields cache check.
	 *
	 * @since  1.6 Added maybe_update_auth_tokens().
	 * @since  1.1
	 * @access public
	 *
	 * @uses GFZohoCRM::maybe_clear_fields_cache()
	 */
	public function plugin_settings_page() {

		$this->maybe_update_auth_tokens();
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
		if ( 'true' !== rgget( 'clear_field_cache' ) ) {
			return;
		}

		// Clear the cache.
		delete_transient( $this->fields_transient_name );

		// Add success message.
		GFCommon::add_message( esc_html__( 'Custom fields cache has been cleared.', 'gravityformszohocrm' ) );

	}

	/**
	 * Store auth tokens when we get auth payload from Zoho CRM.
	 *
	 * @since 1.6
	 */
	public function maybe_update_auth_tokens() {
		// If access token is provided, save it.
		if ( rgget( 'auth_payload' ) && ! $this->is_save_postback() ) {
			$old_authMode = $this->get_setting( 'authMode' );

			$settings     = array();
			$auth_payload = unserialize( base64_decode( rgget( 'auth_payload' ) ) );

			// Add API info to plugin settings.
			$settings['authMode']   = 'oauth';
			$settings['auth_token'] = array(
				'access_token'  => $auth_payload['access_token'],
				'refresh_token' => $auth_payload['refresh_token'],
				'location'      => $auth_payload['location'],
				'date_created'  => time(),
			);

			// Save plugin settings.
			$this->update_plugin_settings( $settings );

			if ( $old_authMode !== 'oauth' ) {
				// Update cached fields.
				delete_transient( $this->fields_transient_name );
				// Migrate feed settings.
				$this->run_api_name_fix();
			}

			GFCommon::add_message( esc_html__( 'Zoho CRM settings have been updated.', 'gravityformszohocrm' ) );
		}

		// If error is provided, display message.
		if ( rgget( 'auth_error' ) ) {
			// Add error message.
			GFCommon::add_error_message( esc_html__( 'Unable to authenticate with Zoho CRM.', 'gravityformszohocrm' ) );
		}
	}

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.7.4 Remove old authentication methods.
	 * @since  1.6   Added the OAuth authentication.
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		$auth_mode = $this->get_plugin_setting( 'authMode' );

		// Prepare plugin description.
		$description = '<p>';
		$description .= sprintf(
			esc_html__( 'Zoho CRM is a contact management tool that gives you a 360-degree view of your complete sales cycle and pipeline. Use Gravity Forms to collect customer information and automatically add it to your Zoho CRM account. If you don\'t have a Zoho CRM account, you can %1$ssign up for one here.%2$s', 'gravityformszohocrm' ),
			'<a href="http://www.zoho.com/crm/" target="_blank">', '</a>'
		);
		$description .= '</p>';

		if ( empty( $auth_mode ) || $auth_mode === 'oauth' ) {
			$fields = array(
				array(
					'name'              => 'auth_token',
					'type'              => 'auth_token_button',
					'feedback_callback' => array( $this, 'initialize_api' ),
				),
			);
		} else {
			$fields = array(
				array(
					'name'          => 'authMode',
					'label'         => esc_html__( 'Authenticate With', 'gravityformszohocrm' ),
					'type'          => 'radio',
					'default_value' => is_ssl() ? 'oauth' : 'email',
					'onclick'       => "jQuery(this).not(':disabled').parents('form').submit();if(jQuery(this).is(':disabled')){return false;}",
					'choices'       => array(
						array(
							'label'    => ! is_ssl() ? esc_html__( 'OAuth Authentication (recommended, you must have an SSL certificate installed and enabled)', 'gravityformszohocrm' ) : esc_html__( 'OAuth Authentication (recommended)', 'gravityformszohocrm' ),
							'value'    => 'oauth',
							'disabled' => ! is_ssl() ? 'disabled' : array(),
							'tooltip'  => '<h6>' . esc_html__( 'OAuth Authentication (recommended)', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'Communicate with Zoho CRM with their version 2.0 API.', 'gravityformszohocrm' ),
						),
						array(
							'label'   => esc_html__( 'Email Address and Password', 'gravityformszohocrm' ),
							'value'   => 'email',
							'tooltip' => '<h6>' . esc_html__( 'Email Address and Password', 'gravityformszohocrm' ) . '</h6>' . sprintf( esc_html__( 'Communicate with Zoho CRM with their version 1.0 API. Version 1.0 API will be sunsetting on Dec 31, 2019, that means you can no longer submit data to Zoho CRM if you still use this method. %sWe strongly recommend you to switch to "REST API" before then%s.', 'gravityformszohocrm' ), '<strong>', '</strong>' ),
						),
						array(
							'label'   => esc_html__( 'Third Party Service (Google Apps, Facebook, Yahoo)', 'gravityformszohocrm' ),
							'value'   => 'third_party',
							'tooltip' => '<h6>' . esc_html__( 'Third Party Service', 'gravityformszohocrm' ) . '</h6>' . sprintf( esc_html__( 'Communicate with Zoho CRM with their version 1.0 API. Version 1.0 API will be sunsetting on Dec 31, 2019, that means you can no longer submit data to Zoho CRM if you still use this method. %sWe strongly recommend you to switch to "REST API" before then%s.', 'gravityformszohocrm' ), '<strong>', '</strong>' ),
						),
					),
				),
				array(
					'name'              => 'emailAddress',
					'label'             => esc_html__( 'Email Address', 'gravityformszohocrm' ),
					'type'              => 'text',
					'class'             => 'medium',
					'dependency'        => array( 'field' => 'authMode', 'values' => array( 'email' ) ),
					'feedback_callback' => array( $this, 'initialize_api' ),
				),
				array(
					'name'              => 'password',
					'label'             => esc_html__( 'Password', 'gravityformszohocrm' ),
					'type'              => 'text',
					'input_type'        => 'password',
					'class'             => 'medium',
					'dependency'        => array( 'field' => 'authMode', 'values' => array( 'email' ) ),
					'feedback_callback' => array( $this, 'initialize_api' ),
				),
				array(
					'name'       => '',
					'label'      => '',
					'type'       => 'auth_token_button',
					'dependency' => array( 'field' => 'authMode', 'values' => array( 'third_party', 'oauth', '' ) ),
				),
				array(
					'name'          => 'authToken',
					'type'          => 'hidden',
					'dependency'    => array( 'field' => 'authMode', 'values' => array( 'email' ) ),
					'save_callback' => array( $this, 'update_auth_token' ),
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
					'name'              => 'auth_token',
					'type'              => 'hidden',
					'dependency'        => array( 'field' => 'authMode', 'values' => array( 'oauth' ) ),
					'feedback_callback' => array( $this, 'initialize_api' ),
				),
				array(
					'type'       => 'save',
					'dependency' => array( 'field' => 'authMode', 'values' => array( 'third_party', 'email' ) ),
					'messages'   => array(
						'success' => esc_html__( 'Zoho CRM settings have been updated.', 'gravityformszohocrm' ),
					),
				),
			);
		}

		return array(
			array(
				'title'       => '',
				'description' => $description,
				'fields'      => $fields,
			),
		);

	}

	/**
	 * Create Generate Auth Token settings field.
	 *
	 * @since  1.7.4 Display SSL Certificate Required message.
	 * @since  1.6   Added a new button for OAuth mode.
	 * @since  1.1
	 * @access public
	 *
	 * @param  array $field Field properties.
	 * @param  bool  $echo  Display field contents. Defaults to true.
	 *
	 * @return string
	 */
	public function settings_auth_token_button( $field, $echo = true ) {

		if ( $this->get_setting( 'authMode', 'oauth' ) === 'oauth' ) {
			if ( $this->initialize_api() ) {
				$html = '<p>' . esc_html__( 'Signed into Zoho CRM.', 'gravityformszohocrm' );
				$html .= '</p>';
				$html .= sprintf(
					' <a href="#" class="button" id="gform_zohocrm_deauth_button">%1$s</a>',
					esc_html__( 'De-Authorize Zoho CRM', 'gravityformszohocrm' )
				);
			} else {
				// If SSL is available, display custom app settings.
				if ( is_ssl() ) {
					$settings_url = urlencode( admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug ) );
					$auth_url     = add_query_arg( array( 'redirect_to' => $settings_url ), $this->get_gravity_api_url( '/auth/zoho-crm' ) );

					$html = sprintf(
						'<a href="%2$s" class="button" id="gform_zohocrm_auth_button">%s</a>',
						esc_html__( 'Click here to authenticate with Zoho CRM', 'gravityformszohocrm' ),
						$auth_url
					);
				} else {
					$html = '<div class="alert_red" style="padding:20px; padding-top:5px;">';
					$html .= '<h4>' . esc_html__( 'SSL Certificate Required', 'gravityformszohocrm' ) . '</h4>';
					$html .= sprintf( esc_html__( 'Make sure you have an SSL certificate installed and enabled, then %1$sclick here to continue%2$s.', 'gravityformszohocrm' ), '<a href="' . admin_url( 'admin.php?page=gf_settings&subview=gravityformszohocrm', 'https' ) . '">', '</a>' );
					$html .= '</div>';
				}
			}
		} else {
			// Get accounts API URL.
			$accounts_api_url = $this->get_accounts_api_url();

			$html = sprintf(
				'<a href="%1$s" class="button" onclick="%2$s">%3$s</a>',
				"{$accounts_api_url}/apiauthtoken/create?SCOPE=ZohoCRM/crmapi",
				"window.open( '" . $accounts_api_url . "/apiauthtoken/create?SCOPE=ZohoCRM/crmapi', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=590,height=700' );return false;",
				esc_html__( 'Click here to generate an authentication token.', 'gravityformszohocrm' )
			);
		}

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

		if ( 'oauth' === rgar( $old_settings, 'authMode' ) ) {
			// Delete cached fields.
			delete_transient( $this->fields_transient_name );
		}

		// If authentication is through a third party service, return false.
		if ( 'third_party' === rgar( $new_settings, 'authMode' ) ) {
			return false;
		}

		// If authToken returns empty string, we need to get a new one.
		if ( '' === rgar( $new_settings, 'authToken' ) ) {
			return true;
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
	 * @since  1.6 Loaded legacy API.
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

		if ( ! class_exists( 'GF_ZohoCRM_API' ) ) {
			require_once 'includes/legacy/class-gf-zohocrm-api.php';
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

				case 'EXCEEDED_MAXIMUM_ALLOWED_AUTHTOKENS':

					// Log authentication error.
					$this->log_error( __METHOD__ . '(): Maximum number of allowed auth tokens exceeded.' );

					// Set field error.
					$this->set_field_error( $sections[0]['fields'][1], esc_html__( 'Maximum number of allowed auth tokens exceeded. You can remove old tokens via the Active Authtokens area of your Zoho account.', 'gravityformszohocrm' ) );

					break;

			}

			return null;

		}

	}



	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.7.1 Display settings based on available module.
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

		$modules         = $this->get_module_fields();
		$settings_fields = array();

		$actions = array(
			array(
				'label' => esc_html__( 'Select an Action', 'gravityformszohocrm' ),
				'value' => null,
			),
		);

		if ( rgar( $modules, 'Contacts' ) && ! empty( $modules['Contacts'] ) ) {
			$actions[] = array(
				'label' => esc_html__( 'Create a New Contact', 'gravityformszohocrm' ),
				'value' => 'contact',
			);
		}
		if ( rgar( $modules, 'Leads' ) && ! empty( $modules['Leads'] ) ) {
			$actions[] = array(
				'label' => esc_html__( 'Create a New Lead', 'gravityformszohocrm' ),
				'value' => 'lead',
			);
		}

		// Prepare base feed settings section.
		$settings_fields[] = array(
			'fields' => array(
				array(
					'name'          => 'feedName',
					'label'         => esc_html__( 'Feed Name', 'gravityformszohocrm' ),
					'type'          => 'text',
					'required'      => true,
					'default_value' => $this->get_default_feed_name(),
					'tooltip'       => '<h6>' . esc_html__( 'Name', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformszohocrm' ),
				),
				array(
					'name'     => 'action',
					'label'    => esc_html__( 'Action', 'gravityformszohocrm' ),
					'required' => true,
					'type'     => 'select',
					'onchange' => "jQuery(this).parents('form').submit();",
					'tooltip'  => '<h6>' . esc_html__( 'Action', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'Choose what will happen when this feed is processed.', 'gravityformszohocrm' ),
					'choices'  => $actions,
				),
			),
		);

		// Get module feed settings sections.
		if ( rgar( $modules, 'Contacts' ) && ! empty( $modules['Contacts'] ) ) {
			$settings_fields[] = $this->contact_feed_settings_fields();
		}
		if ( rgar( $modules, 'Leads' ) && ! empty( $modules['Leads'] ) ) {
			$settings_fields[] = $this->lead_feed_settings_fields();
		}
		if ( rgar( $modules, 'Tasks' ) && ! empty( $modules['Tasks'] ) ) {
			$settings_fields[] = $this->task_feed_settings_fields();
		}

		// Prepare conditional logic settings section.
		$settings_fields[] = array(
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

		return $settings_fields;
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
					'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' ),
				),
				array(
					'name'      => 'contactCustomFields',
					'label'     => null,
					'type'      => 'dynamic_field_map',
					'field_map' => $this->get_field_map_for_module( 'Contacts', 'dynamic' ),
				),
				array(
					'name'    => 'contactOwner',
					'label'   => esc_html__( 'Contact Owner', 'gravityformszohocrm' ),
					'type'    => 'select',
					'choices' => $this->get_users_for_feed_setting(),
				),
				array(
					'name'  => 'contactDescription',
					'type'  => 'textarea',
					'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label' => esc_html__( 'Contact Description', 'gravityformszohocrm' ),
				),
				array(
					'name'    => 'options',
					'label'   => esc_html__( 'Options', 'gravityformszohocrm' ),
					'type'    => 'checkbox',
					'choices' => array(
						array(
							'name'  => 'contactApprovalMode',
							'label' => esc_html__( 'Approval Mode', 'gravityformszohocrm' ),
						),
						array(
							'name'  => 'contactWorkflowMode',
							'label' => esc_html__( 'Workflow Mode', 'gravityformszohocrm' ),
						),
						array(
							'name'  => 'contactEmailOptOut',
							'label' => esc_html__( 'Email Opt Out', 'gravityformszohocrm' ),
						),
						array(
							'name'    => 'contactDuplicateAllowed',
							'label'   => esc_html__( 'Allow duplicate contacts', 'gravityformszohocrm' ),
							'tooltip' => esc_html__( 'If duplicate contacts are allowed, you will not be able to update contacts if they already exist.', 'gravityformszohocrm' )
						),
						array(
							'name'  => 'contactUpdate',
							'label' => esc_html__( 'Update Contact if contact already exists for email address', 'gravityformszohocrm' ),
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
					'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'gravityformszohocrm' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Zoho CRM fields.', 'gravityformszohocrm' ),
				),
				array(
					'name'      => 'leadCustomFields',
					'label'     => null,
					'type'      => 'dynamic_field_map',
					'field_map' => $this->get_field_map_for_module( 'Leads', 'dynamic' ),
				),
				array(
					'name'    => 'leadOwner',
					'label'   => esc_html__( 'Lead Owner', 'gravityformszohocrm' ),
					'type'    => 'select',
					'choices' => $this->get_users_for_feed_setting(),
				),
				array(
					'name'  => 'leadDescription',
					'type'  => 'textarea',
					'class' => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label' => esc_html__( 'Lead Description', 'gravityformszohocrm' ),
				),
				array(
					'name'    => 'options',
					'label'   => esc_html__( 'Options', 'gravityformszohocrm' ),
					'type'    => 'checkbox',
					'choices' => array(
						array(
							'name'  => 'leadApprovalMode',
							'label' => esc_html__( 'Approval Mode', 'gravityformszohocrm' ),
						),
						array(
							'name'  => 'leadWorkflowMode',
							'label' => esc_html__( 'Workflow Mode', 'gravityformszohocrm' ),
						),
						array(
							'name'  => 'leadEmailOptOut',
							'label' => esc_html__( 'Email Opt Out', 'gravityformszohocrm' ),
						),
						array(
							'name'    => 'leadDuplicateAllowed',
							'label'   => esc_html__( 'Allow duplicate leads', 'gravityformszohocrm' ),
							'tooltip' => esc_html__( 'If duplicate leads are allowed, you will not be able to update leads if they already exist.', 'gravityformszohocrm' ),
						),
						array(
							'name'  => 'leadUpdate',
							'label' => esc_html__( 'Update Lead if lead already exists for email address', 'gravityformszohocrm' ),
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
					'name'       => 'taskSubject',
					'type'       => 'text',
					'class'      => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'      => esc_html__( 'Task Subject', 'gravityformszohocrm' ),
					'required'   => true,
					'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) ),
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
					'name'       => 'taskOwner',
					'label'      => esc_html__( 'Task Owner', 'gravityformszohocrm' ),
					'type'       => 'select',
					'choices'    => $this->get_users_for_feed_setting(),
					'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) ),
				),
				array(
					'name'       => 'taskDescription',
					'type'       => 'textarea',
					'class'      => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'      => esc_html__( 'Task Description', 'gravityformszohocrm' ),
					'dependency' => array( 'field' => 'createTask', 'values' => array( '1' ) ),
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

		$is_single_section = isset( $settings['fields'] );

		if ( $is_single_section ) {
			// Move settings into another array.
			$settings = array( $settings );
		}

		// Add field.
		$settings = parent::add_field_after( $name, $fields, $settings );

		if ( $is_single_section ) {
			// Return the first settings section.
			$settings = $settings[0];
		}

		return $settings;

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

		$is_single_section = isset( $settings['fields'] );

		if ( $is_single_section ) {
			// Move settings into another array.
			$settings = array( $settings );
		}

		// Add field.
		$settings = parent::add_field_before( $name, $fields, $settings );

		if ( $is_single_section ) {
			// Return the first settings section.
			$settings = $settings[0];
		}

		return $settings;

	}

	/**
	 * Set feed creation control.
	 *
	 * @since  1.7.1 Check if Contacts or Leads module available.
	 * @since  1.0
	 * @access public
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		if ( $this->initialize_api() ) {
			$contact_module = $this->get_module_fields( 'Contacts' );
			$lead_module    = $this->get_module_fields( 'Leads' );

			if ( empty( $contact_module ) && empty( $lead_module ) ) {
				return false;
			}

			return true;
		}

		return false;

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
	 * Get the require modules message.
	 *
	 * @since 1.7.1
	 *
	 * @return false|string
	 */
	public function feed_list_message() {
		if ( $this->initialize_api() ) {
			$contact_module = $this->get_module_fields( 'Contacts' );
			$lead_module    = $this->get_module_fields( 'Leads' );

			if ( empty( $contact_module ) && empty( $lead_module ) ) {
				return esc_html__( 'Please show the Contacts or Leads module in your Zoho CRM account to create feed.', 'gravityformszohocrm' );
			}
		}

		return GFFeedAddOn::feed_list_message();
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
	 * @since  1.6 Updated per the format change to $choices.
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module
	 * @param string $field_name
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
			// If choice is an array, get display_value.
			// It looks display_value is always the same as actual_value,
			// since Zoho CRM picklist options don't differentiate label and value.
			if ( is_array( $choice ) && rgar( $choice, 'display_value' ) ) {
				$choice = $choice['display_value'];
			}

			// Add field choice as choice.
			$choices[] = array(
				'label' => esc_html( $choice ),
				'value' => $choice,
			);

		}

		return $choices;

	}

	/**
	 * Get field map fields for a Zoho CRM module.
	 *
	 * @since  1.7.4 Use api_name as field keys.
	 * @since  1.6 Updated per v2 API changes.
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
		$standard_fields = array( 'Company', 'Email', 'First_Name', 'Last_Name' );

		// Get fields for module.
		$fields = $this->get_module_fields( $module );

		// Sort module fields in alphabetical order.
		usort( $fields, array( $this, 'sort_module_fields_by_label' ) );

		// Loop through module fields.
		foreach ( $fields as $field ) {

			// If this is a non-supported field type, skip it.
			if ( in_array( $field['type'], array( 'lookup', 'picklist', 'ownerlookup', 'currency' ) ) ) {
				continue;
			}

			// If this is a standard field map and the field is not a standard field or is not required, skip it.
			if ( 'standard' === $field_map_type && ! $field['required'] && ! in_array( $field['name'], $standard_fields ) ) {
				continue;
			}

			// If this is a dynamic field map and the field matches a standard field or is required, skip it.
			if ( 'dynamic' === $field_map_type && ( $field['required'] || in_array( $field['name'], $standard_fields ) ) ) {
				continue;
			}

			// Get Gravity Forms field type.
			switch ( $field['type'] ) {

				case 'date':
				case 'datetime':
					$field_type = 'date';
					break;

				case 'email':
					$field_type = array( 'email', 'hidden' );
					break;

				case 'phone':
					$field_type = 'phone';
					break;

				default:
					$field_type = null;
					break;

			}

			// Add field to field map.
			$field_map[] = array(
				'name'       => $field['name'],
				'label'      => $field['label'],
				'value'      => $field['name'],
				'required'   => $field['required'],
				'field_type' => $field_type,
			);

		}

		return $field_map;

	}

	/**
	 * Get Zoho CRM users for feed field settings.
	 *
	 * @sicne  1.6 Updated to return WP_Error when errors occurred.
	 * @since  1.0
	 * @access public
	 *
	 * @return array $choices
	 */
	public function get_users_for_feed_setting() {

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( '-None-', 'gravityformszohocrm' ),
				'value' => '',
			),
		);

		// If API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get users because API is not initialized.' );

			return $choices;
		}

		// Get Zoho CRM users.
		$users = $this->api->get_users();
		if ( is_wp_error( $users ) ) {
			// Log that users could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to get users; ' . print_r( $users->get_error_messages(), true ) );

			return $choices;
		}

		// If Zoho CRM users exist, add them as choices.
		if ( ! empty( $users ) ) {

			// Loop through Zoho CRM users.
			foreach ( $users as $user ) {

				// Add user as choice.
				$choices[] = array(
					'label' => esc_html( $user['full_name'] ),
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
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {

			// Log that we cannot process the feed.
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformszohocrm' ), $feed, $entry, $form );

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
	 * @since  1.7.1 Add feed error when Contacts module is hidden.
	 * @since  1.6 Updated data format.
	 * @since  1.0
	 * @access public

	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @return int|null $contact_id
	 */
	public function create_contact( $feed, $entry, $form ) {
		// Get cached fields.
		$cached_fields = $this->get_module_fields( 'Contacts' );
		if ( empty( $cached_fields ) ) {
			// Log that lead could not be created.
			$this->add_feed_error( esc_html__( 'Could not create contact; Contacts module is hidden, please check your Zoho CRM account.', 'gravityformszohocrm' ), $feed, $entry, $form );

			return null;
		}

		// Initialize lead object.
		$contact = array(
			'Email_Opt_Out' => rgars( $feed, 'meta/contactEmailOptOut' ) == '1' ? true : false,
			'Description'   => GFCommon::replace_variables( $feed['meta']['contactDescription'], $form, $entry, false, false, false, 'text' ),
			'Lead_Source'   => rgars( $feed, 'meta/contactLeadSource' ),
			'options'       => array(
				'duplicateCheck' => rgars( $feed, 'meta/contactUpdate' ) == '1' ? 2 : 1,
				'isApproval'     => rgars( $feed, 'meta/contactApprovalMode' ) == '1' ? true : false,
				'wfTrigger'      => rgars( $feed, 'meta/contactWorkflowMode' ) == '1' ? true : false,
			),
		);

		// If duplicate contacts are allowed, remove the duplicate check.
		if ( rgars( $feed, 'meta/contactDuplicateAllowed' ) ) {
			unset( $contact['options']['duplicateCheck'] );
		}

		// Add owner ID.
		if ( rgars( $feed, 'meta/contactOwner' ) ) {
			$contact['Owner'] = array(
				'id' => $feed['meta']['contactOwner'],
			);
		}

		// Get standard and custom fields.
		$standard_fields = $this->get_field_map_fields( $feed, 'contactStandardFields' );
		$custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'contactCustomFields' );

		// Merge standard and custom fields arrays.
		$mapped_fields = array_merge( $standard_fields, $custom_fields );

		// Loop through mapped fields.
		foreach ( $mapped_fields as $field_name => $field_id ) {
			// Get cached module field.
			$module_field = $this->get_module_field( 'Contacts', $field_name );
			if ( ! empty( $module_field ) ) {
				$field_type   = $module_field['type'];
				$field_length = $module_field['length'];
				$field_name   = $module_field['name'];
			} else {
				// Users might set field name with custom key, and the field couldn't be found in Zoho CRM.
				$this->add_feed_error( sprintf( esc_html__( "The field %s cannot be found at Zoho CRM.", 'gravityformszohocrm' ), $field_name ), $feed, $entry, $form );
				$this->log_debug( __METHOD__ . '(): Cached fields: ' . print_r( $cached_fields, true ) );

				continue;
			}

			// Get field value.
			$field_value = $this->get_prepared_field_value( $field_id, $field_type, $form, $entry );

			// validate field value length.
			$_field_value = ( ! is_array( $field_value ) ) ? strval( $field_value ) : json_encode( $field_value );
			if ( mb_strlen( $_field_value, 'utf8' ) > $field_length ) {
				$this->add_feed_error( sprintf( esc_html__( 'The value of %s cannot be sent to Zoho CRM. Reason: The characters of the field value exceed %d.', 'gravityformszohocrm' ), $field_name, $field_length ), $feed, $entry, $form );

				continue;
			}

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

		$contact_data = array( 'data' => array(), 'feed' => $feed, 'entry' => $entry, 'form' => $form );
		foreach ( $contact as $field_key => $field_value ) {
			$contact_data['data'][0][ $field_key ] = $field_value;
		}

		// Setup triggers
		if ( $contact['options']['isApproval'] ) {
			$contact_data['trigger'][]            = 'approval';
			$contact_data['data'][0]['$approved'] = false;
		}
		if ( $contact['options']['wfTrigger'] ) {
			$contact_data['trigger'][] = 'workflow';
			$contact_data['trigger'][] = 'blueprint';
		}

		// Log contact arguments and XML object.
		$this->log_debug( __METHOD__ . '(): Creating contact - arguments: ' . print_r( $contact, true ) );
		$this->log_debug( __METHOD__ . '(): Creating contact - JSON object: ' . print_r( $contact_data, true ) );

		$action = 'create';

		// Create contact.
		if ( ! isset( $contact['options']['duplicateCheck'] ) ) {
			$contact_record = $this->api->insert_record( 'Contacts', $contact_data );
		} else {
			if ( $contact['options']['duplicateCheck'] === 2 ) {
				$contact_record = $this->api->insert_record( 'Contacts', $contact_data, true );
			} else {
				$contacts = $this->api->search_record( 'Contacts', array( 'email' => urlencode( $contact_data['data'][0]['Email'] ), 'approved' => 'both' ) );
				if ( empty( $contacts ) ) {
					$contact_record = $this->api->insert_record( 'Contacts', $contact_data, true );
				} else {
					if ( is_wp_error( $contacts ) ) {
						// Log that contact could not be created.
						$this->add_feed_error( esc_html__( 'Could not validate if the contact already exists; ', 'gravityformszohocrm' ) . $contacts->get_error_message(), $feed, $entry, $form );

						return null;
					}

					$this->add_feed_error( sprintf( esc_html__( 'Contact #%d already exists.', 'gravityformszohocrm' ), $contacts[0]['id'] ), $feed, $entry, $form );

					return null;
				}
			}
		}

		if ( is_wp_error( $contact_record ) ) {
			// Log that contact could not be created.
			$this->add_feed_error( sprintf( esc_html__( "Could not %s contact; %s", 'gravityformszohocrm' ), $action, $contact_record->get_error_message() ), $feed, $entry, $form );

			return null;
		}

		// Get new contact ID.
		$contact_id = $contact_record[0]['details']['id'];
		$action = ( ! isset( $contact_record[0]['action'] ) || $contact_record[0]['action'] === 'insert' ) ? 'created' : 'updated';

		// Save contact ID to entry meta.
		gform_update_meta( $entry['id'], 'zohocrm_contact_id', $contact_id );

		// Log that contact was created.
		$this->log_debug( __METHOD__ . '(): Contact #' . $contact_id . " {$action}." );

		/**
		 * Allow custom actions to be performed after creating contact.
		 *
		 * @since 1.8
		 *
		 * @param array $contact_record  The contact record.
		 * @param array $contact         The contact arguments.
		 * @param array $feed            Feed object.
		 * @param array $entry           Entry object.
		 * @param array $form            Form object.
		 */
		do_action( 'gform_zohocrm_post_create_contact', $contact_record[0], $contact, $feed, $entry, $form );

		return $contact_id;

	}

	/**
	 * Create a new lead from a feed.
	 *
	 * @since  1.7.1 Add feed error when Leads module is hidden.
	 * @since  1.6 Updated data format.
	 * @since  1.0
	 * @access public

	 * @param array $feed  Feed object.
	 * @param array $entry Entry object.
	 * @param array $form  Form object.
	 *
	 * @return int|null $lead_id
	 */
	public function create_lead( $feed, $entry, $form ) {
		// Get cached fields.
		$cached_fields = $this->get_module_fields( 'Leads' );
		if ( empty( $cached_fields ) ) {
			// Log that lead could not be created.
			$this->add_feed_error( esc_html__( 'Could not create lead; Leads module is hidden, please check your Zoho CRM account.', 'gravityformszohocrm' ), $feed, $entry, $form );

			return null;
		}

		// Initialize lead object.
		$lead = array(
			'Email_Opt_Out' => rgars( $feed, 'meta/leadEmailOptOut' ) == '1' ? true : false,
			'Description'   => GFCommon::replace_variables( $feed['meta']['leadDescription'], $form, $entry, false, false, false, 'text' ),
			'Lead_Source'   => rgars( $feed, 'meta/leadSource' ),
			'Lead_Status'   => rgars( $feed, 'meta/leadStatus' ),
			'Rating'        => rgars( $feed, 'meta/leadRating' ),
			'options'       => array(
				'duplicateCheck' => rgars( $feed, 'meta/leadUpdate' ) == '1' ? 2 : 1,
				'isApproval'     => rgars( $feed, 'meta/leadApprovalMode' ) == '1' ? true : false,
				'wfTrigger'      => rgars( $feed, 'meta/leadWorkflowMode' ) == '1' ? true : false,
			),
		);

		// If duplicate leads are allowed, remove the duplicate check.
		if ( rgars( $feed, 'meta/leadDuplicateAllowed' ) ) {
			unset( $lead['options']['duplicateCheck'] );
		}

		// Add owner ID.
		if ( rgars( $feed, 'meta/leadOwner' ) ) {
			$lead['Owner'] = array(
				'id' => $feed['meta']['leadOwner'],
			);
		}

		// Get standard and custom fields.
		$standard_fields = $this->get_field_map_fields( $feed, 'leadStandardFields' );
		$custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'leadCustomFields' );

		// Merge standard and custom fields arrays.
		$mapped_fields = array_merge( $standard_fields, $custom_fields );

		// Loop through mapped fields.
		foreach ( $mapped_fields as $field_name => $field_id ) {
			// Get cached module field.
			$module_field = $this->get_module_field( 'Leads', $field_name );

			if ( ! empty( $module_field ) ) {
				$field_type   = $module_field['type'];
				$field_length = $module_field['length'];
				$field_name   = $module_field['name'];
			} else {
				// Users might set field name with custom key, and the field couldn't be found in Zoho CRM.
				$this->add_feed_error( sprintf( esc_html__( "The field %s cannot be found at Zoho CRM.", 'gravityformszohocrm' ), $field_name ), $feed, $entry, $form );
				$this->log_debug( __METHOD__ . '(): Cached fields: ' . print_r( $cached_fields, true ) );

				continue;
			}

			// Get field value.
			$field_value = $this->get_prepared_field_value( $field_id, $field_type, $form, $entry );

			// validate field value length.
			$_field_value = ( ! is_array( $field_value ) ) ? strval( $field_value ) : json_encode( $field_value );
			if ( mb_strlen( $_field_value, 'utf8' ) > $field_length ) {
				$this->add_feed_error( sprintf( esc_html__( 'The value of %s cannot be sent to Zoho CRM. Reason: The characters of the field value exceed %d.', 'gravityformszohocrm' ), $field_name, $field_length ), $feed, $entry, $form );

				continue;
			}

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

		$lead_data = array( 'data' => array(), 'feed' => $feed, 'entry' => $entry, 'form' => $form );
		foreach ( $lead as $field_key => $field_value ) {
			$lead_data['data'][0][ $field_key ] = $field_value;
		}

		// Setup triggers
		if ( $lead['options']['isApproval'] ) {
			$lead_data['trigger'][]            = 'approval';
			$lead_data['data'][0]['$approved'] = false;
		}
		if ( $lead['options']['wfTrigger'] ) {
			$lead_data['trigger'][] = 'workflow';
			$lead_data['trigger'][] = 'blueprint';
		}

		// Log lead arguments and XML object.
		$this->log_debug( __METHOD__ . '(): Creating lead - arguments: ' . print_r( $lead, true ) );
		$this->log_debug( __METHOD__ . '(): Creating lead - JSON object: ' . print_r( $lead_data, true ) );

		// Create lead.
		if ( ! isset( $lead['options']['duplicateCheck'] ) ) {
			$lead_record = $this->api->insert_record( 'Leads', $lead_data );
		} else {
			if ( $lead['options']['duplicateCheck'] === 2 ) {
				$lead_record = $this->api->insert_record( 'Leads', $lead_data, true );
			} else {
				$leads = $this->api->search_record( 'Leads', array( 'email' => urlencode( $lead_data['data'][0]['Email'] ), 'approved' => 'both' ) );
				if ( empty( $leads ) ) {
					$lead_record = $this->api->insert_record( 'Leads', $lead_data, true );
				} else {
					if ( is_wp_error( $leads ) ) {
						// Log that contact could not be created.
						$this->add_feed_error( esc_html__( 'Could not validate if the lead already exists; ', 'gravityformszohocrm' ) . $leads->get_error_message(), $feed, $entry, $form );

						return null;
					}

					$this->add_feed_error( sprintf( esc_html__( 'Lead #%d already exists.', 'gravityformszohocrm' ), $leads[0]['id'] ), $feed, $entry, $form );

					return null;
				}
			}
		}

		if ( is_wp_error( $lead_record ) ) {
			// Log that lead could not be created.
			$this->add_feed_error( esc_html__( 'Could not create lead; ', 'gravityformszohocrm' ) . $lead_record->get_error_message(), $feed, $entry, $form );

			return null;
		}

		// Get new contact ID.
		$lead_id = $lead_record[0]['details']['id'];
		$action = ( ! isset( $lead_record[0]['action'] ) || $lead_record[0]['action'] === 'insert' ) ? 'created' : 'updated';

		// Save lead ID to entry meta.
		gform_update_meta( $entry['id'], 'zohocrm_lead_id', $lead_id );

		// Log that lead was created.
		$this->log_debug( __METHOD__ . '(): Lead #' . $lead_id . " {$action}." );

		/**
		 * Allow custom actions to be performed after creating lead.
		 *
		 * @since 1.8
		 *
		 * @param array $lead_record  The lead record.
		 * @param array $lead         The lead arguments.
		 * @param array $feed         Feed object.
		 * @param array $entry        Entry object.
		 * @param array $form         Form object.
		 */
		do_action( 'gform_zohocrm_post_create_lead', $lead_record[0], $lead, $feed, $entry, $form );

		return $lead_id;

	}

	/**
	 * Create a new task from a feed.
	 *
	 * @since  1.6 Updated data format.
	 * @since  1.0
	 * @access public
	 *
	 * @param int    $record_id Record ID to add the task to.
	 * @param string $module    Module for record.
	 * @param array  $feed      Feed object.
	 * @param array  $entry     Entry object.
	 * @param array  $form      Form object.
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
			$task['Due_Date'] = is_numeric( $due_date ) ? date( 'Y-m-d', strtotime( '+' . $due_date . ' days' ) ) : $due_date;

		}

		// Add contact ID.
		if ( 'Contacts' === $module ) {
			$task['Who_Id'] = $record_id;
		}

		// Add lead ID.
		if ( 'Leads' === $module ) {
			$task['What_Id']    = $record_id;
			$task['$se_module'] = $module;
		}

		// Add owner ID.
		if ( rgars( $feed, 'meta/taskOwner' ) ) {
			$task['Owner'] = array(
				'id' => $feed['meta']['taskOwner'],
			);
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

		$task_data = array( 'data' => array() );
		foreach ( $task as $field_key => $field_value ) {
			$task_data['data'][0][ $field_key ] = $field_value;
		}

		// Log task arguments and XML object.
		$this->log_debug( __METHOD__ . '(): Creating task - arguments: ' . print_r( $task, true ) );
		$this->log_debug( __METHOD__ . '(): Creating task - JSON object: ' . print_r( $task_data, true ) );

		// Create task.
		$task_record = $this->api->insert_record( 'Tasks', $task_data );

		if ( is_wp_error( $task_record ) ) {
			// Log that task could not be created.
			$this->add_feed_error( esc_html__( 'Could not create task; ', 'gravityformszohocrm' ) . $task_record->get_error_message(), $feed, $entry, $form );

			return null;
		}

		// Get new task ID.
		$task_id = $task_record[0]['details']['id'];

		// Save task ID to entry meta.
		gform_update_meta( $entry['id'], 'zohocrm_task_id', $task_id );

		// Log that task was created.
		$this->log_debug( __METHOD__ . '(): Task #' . $task_id . ' created and assigned to ' . $module . ' #' . $record_id . '.' );

		/**
		 * Allow custom actions to be performed after creating task.
		 *
		 * @since 1.8
		 *
		 * @param array $task_record  The task record.
		 * @param array $task         The task arguments.
		 * @param array $feed         Feed object.
		 * @param array $entry        Entry object.
		 * @param array $form         Form object.
		 */
		do_action( 'gform_zohocrm_post_create_task', $task_record[0], $task, $feed, $entry, $form );

		return $task_id;

	}

	/**
	 * Upload attachments from a feed.
	 *
	 * @since  1.6 Updated data format.
	 * @since  1.0
	 * @access public
	 *
	 * @param int    $record_id Record ID to add attachment to.
	 * @param string $module    Module for record.
	 * @param array  $feed      Feed object.
	 * @param array  $entry     Entry object.
	 * @param array  $form      Form object.
	 */
	public function upload_attachments( $record_id, $module, $feed, $entry, $form ) {

		$this->log_debug( __METHOD__ . "(): Running for {$module} #{$record_id}." );

		// If no file upload fields are selected as attachments, exit.
		if ( ! rgars( $feed, 'meta/' . $module . 'Attachments' ) ) {
			$this->log_debug( __METHOD__ . '(): aborting; Attachments not enabled.' );
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
			$this->log_debug( __METHOD__ . '(): aborting; No fields selected.' );
			return;
		}

		// Loop through file upload fields.
		foreach ( $file_fields as $file_field ) {

			// Get files for field.
			$files = $this->get_field_value( $form, $entry, $file_field );

			// If no files were uploaded for this field, skip it.
			if ( empty( $files ) ) {
				$this->log_debug( __METHOD__ . "(): aborting; No files uploaded for field #{$file_field}." );
				continue;
			}

			$this->log_debug( __METHOD__ . "(): Processing files for field #{$file_field}." );

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

				// Upload file.
				$uploaded_file = $this->api->upload_file( $module_type, $record_id, $file_path );

				if ( is_wp_error( $uploaded_file ) ) {
					// Log that file could not be uploaded.
					$this->log_error( __METHOD__ . '(): File "' . basename( $file_path ) . '" could not be uploaded; ' . $uploaded_file->get_error_message() );
				} else {
					// Log that file was uploaded.
					$this->log_debug( __METHOD__ . '(): File "' . basename( $file_path ) . '" has been uploaded to ' . $module . ' #' . $record_id . '.' );
				}

			}

		}

	}





	// # HELPER FUNCTIONS ----------------------------------------------------------------------------------------------

	/**
	 * Initializes the Zoho CRM API if credentials are valid.
	 *
	 * @since  1.6 Updated per v2 API changes.
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

		$auth_mode = $this->get_setting( 'authMode', $this->get_plugin_setting( 'authMode' ) );
		// Initialize Zoho CRM API library.
		if ( ! class_exists( 'GF_ZohoCRM_API' ) ) {
			if ( $auth_mode === 'oauth' ) {
				require_once 'includes/class-gf-zohocrm-api.php';
			} else {
				require_once 'includes/legacy/class-gf-zohocrm-api.php';
			}
		}

		// Get the authentication token.
		$setting_name = ( $auth_mode === 'oauth' ) ? 'auth_token' : 'authToken';
		$auth_token = $this->get_plugin_setting( $setting_name );

		// If the authentication token is not set, return null.
		if ( rgblank( $auth_token ) ) {
			return null;
		}

		// Log that were testing the API credentials.
		$this->log_debug( __METHOD__ . "(): Validating API credentials." );

		// Initialize a new Zoho CRM API instance.
		$zoho_crm = new GF_ZohoCRM_API( $auth_token );

		if ( $auth_mode === 'oauth' && time() > $auth_token['date_created'] + 3600 ) {
			// Log that authentication test failed.
			$this->log_debug( __METHOD__ . '(): API tokens expired, start refreshing.' );

			// refresh token.
			$auth_token = $zoho_crm->refresh_token();
			if ( ! is_wp_error( $auth_token ) ) {
				$settings['authMode']   = 'oauth';
				$settings['auth_token'] = array(
					'access_token'    => $auth_token['access_token'],
					'refresh_token'   => $auth_token['refresh_token'],
					'location'        => ( ! rgar( $auth_token, 'location' ) ) ? 'us' : $auth_token['location'],
					'date_created'    => time(),
				);

				// Save plugin settings.
				$this->update_plugin_settings( $settings );
				$this->log_debug( __METHOD__ . '(): API access token has been refreshed.' );

			} else {
				$this->log_debug( __METHOD__ . '(): API access token failed to be refreshed; ' . $auth_token->get_error_message() );

				return false;
			}
		}

		// Attempt to retrieve Zoho CRM account users.
		$users = $zoho_crm->get_users();

		if ( is_wp_error( $users ) ) {
			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $users->get_error_message() );

			return false;
		}

		// Log that test passed.
		$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

		// Assign Zoho CRM API instance to the Add-On instance.
		$this->api = $zoho_crm;

		return true;

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
	 * @since  1.6 Updated per v2 API changes.
	 * @since  1.0
	 * @access public
	 *
	 * @return string $fields JSON encoded string of all module fields.
	 */
	public function update_cached_fields() {

		// If API instance is not initialized, exit.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to update fields because API is not initialized.' );

			return '';
		}

		// Get module fields.
		$modules = array( 'Contacts', 'Leads', 'Tasks' );
		$modules_fields = array();

		foreach ( $modules as $module ) {
			${$module} = $this->api->get_fields( $module );
			if ( is_wp_error( ${$module} ) ) {
				$error_data = ${$module}->get_error_data();
				$this->log_error( __METHOD__ . "(): Unable to update $module fields; error data: " . print_r( $error_data, true ) );
			} else {
				$modules_fields[ $module ] = ${$module};
			}
		}

		// Initialize fields array.
		$fields = array();

		// Loop through modules.
		foreach ( $modules_fields as $module_name => $layouts ) {

			// Loop through layouts.
			foreach ( $layouts as $layout ) {
				// Loop through the module's sections.
				foreach ( $layout['sections'] as $section ) {

					// Get section fields array.
					if ( rgar( $section, 'fields' ) ) {
						$section_fields = $section['fields'];
					} else if ( ! rgar( $section, 'fields' ) && is_array( $section ) && isset( $section['label'] ) ) {
						$section_fields = $section;
					}

					// If section fields array could not be found, skip module.
					if ( ! isset( $section_fields ) ) {
						continue;
					}

					// Skip default single field section, will add support to them (image fields etc.).
					if ( count( $section_fields ) === 1 && false === $section_fields[0]['custom_field'] ) {
						continue;
					}

					// Loop through the section's fields.
					foreach ( $section_fields as $section_field ) {
						// Prepare field details.
						$field = array(
							'custom_field' => filter_var( $section_field['custom_field'], FILTER_VALIDATE_BOOLEAN ),
							'label'        => $section_field['field_label'],
							'name'         => $section_field['api_name'],
							'required'     => filter_var( $section_field['required'], FILTER_VALIDATE_BOOLEAN ),
							'type'         => $section_field['data_type'],
							'length'       => $section_field['length'], // v2 API checks field length for validation.
						);

						// Store field choices, if set.
						if ( rgar( $section_field, 'pick_list_values' ) ) {
							$field['choices'] = $section_field['pick_list_values'];
						}

						// Add field to array.
						$fields[ $module_name ][ $section_field['api_name'] ] = $field;

					}

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
	 * @since 1.6   Updated value format.
	 * @since 1.1.9
	 * @access public
	 *
	 * @param string $field_value The field value.
	 * @param array  $form        The form object currently being processed.
	 * @param array  $entry       The entry object currently being processed.
	 * @param string $field_id    The ID of the field being processed.
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

			// If this is a multiselect or checkbox field, convert the comma separated list to an array.
			if ( $input_type == 'multiselect' || ( $is_integer && $input_type == 'checkbox' ) ) {
				$field_value = explode( ', ', $field_value );
			}

		}

		return parent::maybe_override_field_value( $field_value, $form, $entry, $field_id );

	}

	/**
	 * Gets the mapped field value in the format required for the specified Zoho CRM field type.
	 *
	 * @since 1.8
	 *
	 * @param string $field_id   The ID of the form/entry field being processed.
	 * @param string $field_type The Zoho CRM field type.
	 * @param array  $form       The form object currently being processed.
	 * @param array  $entry      The entry object currently being processed.
	 *
	 * @return mixed
	 */
	public function get_prepared_field_value( $field_id, $field_type, $form, $entry ) {

		// Get field value.
		$field_value = $this->get_field_value( $form, $entry, $field_id );

		// Update field value based on the Zoho CRM field type.
		switch ( $field_type ) {
			case 'boolean':
				$field_value = ! ( empty( $field_value ) || ( is_string( $field_value ) && strtolower( $field_value ) === 'false' ) );
				break;

			case 'datetime':
				$field_value = date( 'c', strtotime( $field_value ) );
				break;
		}

		return $field_value;
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

		return ( null === $module ) ? $fields : ( rgar( $fields, $module ) ? rgar( $fields, $module ) : array() );

	}

	/**
	 * Get field from a Zoho CRM module.
	 *
	 * @since  1.7.2  Look up api_name in cached fields too.
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
		// Get api_name for field.
		$api_name = str_replace( ' ', '_', $field_name );

		// Loop through module fields.
		foreach ( $module_fields as $module_field ) {

			// If label or name (api_name) matches, return field.
			if ( rgar( $module_field, 'label' ) === $field_name || rgar( $module_field, 'name' ) === $api_name ) {
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

	/**
	 * Get Gravity API URL.
	 *
	 * @since 1.7
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public function get_gravity_api_url( $path = '' ) {
		return ( defined( 'GRAVITY_API_URL' ) ? GRAVITY_API_URL : 'https://www.gravityhelp.com/wp-json/gravityapi/v1' ) . $path;
	}





	// # UPGRADE ROUTINES ----------------------------------------------------------------------------------------------

	/**
	 * Checks if a previous version was installed and runs any needed migration processes.
	 *
	 * @since  1.7.4  Upgrades for v2 API.
	 * @since  1.1.5
	 * @access public
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 *
	 */
	public function upgrade( $previous_version ) {

		$previous_is_pre_api_name_fix = ! empty( $previous_version ) && version_compare( $previous_version, '1.7.4', '<' );

		if ( $previous_is_pre_api_name_fix && $this->get_plugin_setting( 'authMode' ) === 'oauth' ) {
			$this->run_api_name_fix();
		}

	}

	/**
	 * Upgrade feeds to use api_name as field keys.
	 *
	 * @since 1.7.4
	 */
	public function run_api_name_fix() {
		// Get the Zoho CRM feeds.
		$feeds = $this->get_feeds();

		foreach ( $feeds as &$feed ) {

			if ( rgars( $feed, 'meta/action' ) === 'contact' ) {

				$contact_fields = $this->get_module_fields( 'Contacts' );

				if ( rgars( $feed, 'meta/contactCustomFields' ) ) {

					foreach ( $contact_fields as $contact_field ) {

						foreach ( $feed['meta']['contactCustomFields'] as &$feed_custom_field ) {

							if ( $feed_custom_field['key'] === $contact_field['label'] || $feed_custom_field['key'] === str_replace( '_', ' ', $contact_field['name'] ) ) {
								$feed_custom_field['key'] = $contact_field['name'];
							}

						}

					}

				}

			}

			if ( rgars( $feed, 'meta/action' ) === 'lead' ) {

				$lead_fields = $this->get_module_fields( 'Leads' );

				if ( rgars( $feed, 'meta/leadCustomFields' ) ) {

					foreach ( $lead_fields as $lead_field ) {

						foreach ( $feed['meta']['leadCustomFields'] as &$feed_custom_field ) {

							if ( $feed_custom_field['key'] === $lead_field['label'] || $feed_custom_field['key'] === str_replace( '_', ' ', $lead_field['name'] ) ) {
								$feed_custom_field['key'] = $lead_field['name'];
							}

						}

					}

				}

			}

			$this->update_feed_meta( $feed['id'], $feed['meta'] );

		}
	}

	/**
	 * Revoke token and remove them from Settings.
	 *
	 * @since  1.6
	 */
	public function ajax_deauthorize() {
		// If API instance is not initialized, return choices.
		if ( ! $this->initialize_api() ) {
			$this->log_error( __METHOD__ . '(): Unable to get users because API is not initialized.' );

			wp_send_json_error();
		}

		$result = $this->api->revoke_token();

		if ( is_wp_error( $result ) ) {
			// Log that users could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to revoke token; ' . $result->get_error_message() );

			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} else {
			// Log that we revoked the access token.
			$this->log_debug( __METHOD__ . '(): Refresh token revoked.' );

			// Remove access token from settings.
			delete_option( 'gravityformsaddon_' . $this->_slug . '_settings' );

			// Return success response.
			wp_send_json_success();
		}
	}
}
