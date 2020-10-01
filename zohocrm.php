<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
Plugin Name: Gravity Forms Zoho CRM Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with Zoho CRM, allowing form submissions to be automatically sent to your Zoho CRM account.
Version: 1.12
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformszohocrm
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2020 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 **/

define( 'GF_ZOHOCRM_VERSION', '1.12' );

// If Gravity Forms is loaded, bootstrap the Zoho CRM Add-On.
add_action( 'gform_loaded', array( 'GF_ZohoCRM_Bootstrap', 'load' ), 5 );

/**
 * Class GF_ZohoCRM_Bootstrap
 *
 * Handles the loading of the Zoho CRM Add-On and registers with the Add-On framework.
 */
class GF_ZohoCRM_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Zoho CRM Add-On is loaded.
	 *
	 * @since  1.0
	 * @access public
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		// Get Add-On settings.
		$settings  = get_option( 'gravityformsaddon_gravityformszohocrm_settings', array() );
		$auth_mode = rgar( $settings, 'authMode' );

		// Load legacy Add-On.
		if ( $auth_mode === 'oauth' || ( rgget( 'subview' ) === 'gravityformszohocrm' && rgget( 'page' ) === 'gf_settings' ) ) {
			require_once 'class-gf-zohocrm.php';
		} else {
			require_once 'includes/legacy/class-gf-zohocrm.php';
		}

		GFAddOn::register( 'GFZohoCRM' );

	}

}

/**
 * Returns an instance of the GFZohoCRM class
 *
 * @see    GFZohoCRM::get_instance()
 *
 * @return GFZohoCRM
 */
function gf_zohocrm() {
	return GFZohoCRM::get_instance();
}