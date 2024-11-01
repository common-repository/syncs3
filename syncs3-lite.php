<?php
/**
 * Plugin Name: SyncS3 Lite
 * Plugin URI: https://elegantmodules.com/modules/syncs3-gravity-forms/
 * Description: Push and sync Gravity Forms file uploads to your Amazon S3 buckets.
 * Version: 1.1.2
 * Author: Elegant Modules
 * Author URI: https://elegantmodules.com
 * Text Domain: syncs3
 * Domain Path: /languages/
 *
 * License: GPL-2.0+
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */

/*
	Copyright 2020  Elegant Modules (hey@elegantmodules.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	Permission is hereby granted, free of charge, to any person obtaining a copy of this
	software and associated documentation files (the "Software"), to deal in the Software
	without restriction, including without limitation the rights to use, copy, modify, merge,
	publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
	to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SyncS3' ) ) :

class SyncS3 {

	private static $instance;

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof SyncS3 ) ) {
			
			self::$instance = new SyncS3;

			self::$instance->constants();
			self::$instance->includes();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Constants
	 */
	public function constants() {

		// Plugin version
		if ( ! defined( 'SYNCS3_VERSION' ) ) {
			define( 'SYNCS3_VERSION', '1.1.2' );
		}

		// Plugin file
		if ( ! defined( 'SYNCS3_PLUGIN_FILE' ) ) {
			define( 'SYNCS3_PLUGIN_FILE', __FILE__ );
		}

		// Plugin basename
		if ( ! defined( 'SYNCS3_PLUGIN_BASENAME' ) ) {
			define( 'SYNCS3_PLUGIN_BASENAME', plugin_basename( SYNCS3_PLUGIN_FILE ) );
		}

		// Plugin directory path
		if ( ! defined( 'SYNCS3_PLUGIN_DIR_PATH' ) ) {
			define( 'SYNCS3_PLUGIN_DIR_PATH', trailingslashit( plugin_dir_path( SYNCS3_PLUGIN_FILE )  ) );
		}

		// Plugin directory URL
		if ( ! defined( 'SYNCS3_PLUGIN_DIR_URL' ) ) {
			define( 'SYNCS3_PLUGIN_DIR_URL', trailingslashit( plugin_dir_url( SYNCS3_PLUGIN_FILE )  ) );
		}

		// Templates directory
		if ( ! defined( 'SYNCS3_PLUGIN_TEMPLATES_DIR_PATH' ) ) {
			define ( 'SYNCS3_PLUGIN_TEMPLATES_DIR_PATH', SYNCS3_PLUGIN_DIR_PATH . 'templates/' );
		}
	}

	public static function autoload() {
		require 'lib/autoload.php';
	}

	/**
	 * Include files
	 */
	public function includes() {
		include_once 'includes/helpers.php';
	}

	/**
	 * Action/filter hooks
	 */
	public function hooks() {
		add_action( 'plugins_loaded', array( $this, 'loaded' ) );
		add_action( 'gform_loaded', array( $this, 'register_addon' ), 5 );
	}

	/**
	 * Load plugin text domain
	 */
	public function loaded() {

		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'syncs3' );
		
		unload_textdomain( 'syncs3' );
		
		load_textdomain( 'syncs3', WP_LANG_DIR . '/syncs3/syncs3-' . $locale . '.mo' );
		load_plugin_textdomain( 'syncs3', false, dirname( SYNCS3_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Registers the GFAddon
	 *
	 * @return void
	 */
	public function register_addon() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once 'includes/class-syncs3-addon.php';
		GFAddOn::register( 'SyncS3Addon' );
	}
}

endif;

/**
 * Main function
 * 
 * @return object 	SyncS3 instance
 */
function syncs3() {
	return SyncS3::instance();
}

syncs3();
