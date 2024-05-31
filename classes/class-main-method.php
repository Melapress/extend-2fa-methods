<?php
/**
 * Main Method extender class example.
 *
 * @package    extend-2fa-methods
 * @subpackage methods
 *
 * @copyright  2024 Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 * @see       https://wordpress.org/plugins/wp-2fa/
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WP2FA\Methods;

use WP2FA\WP2FA;
use WP2FA\Admin\Controllers\Settings;
use WP2FA\Admin\Helpers\User_Helper;
use WP2FA\Admin\User_Profile;
use WP2FA\Methods\Wizards\Main_Method_Wizard_Steps;

/**
 * Class for handling email codes.
 *
 * @since 1.0.0
 *
 * @package extend-2fa-methods
 */
if ( ! class_exists( '\WP2FA\Methods\Main_Method' ) ) {
	/**
	 * Main method code class.
	 *
	 * @since 1.0.0
	 */
	class Main_Method {

		/**
		 * The name of the method.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		public const METHOD_NAME = 'main_method';

		/**
		 * The name of the method stored in the policy
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		public const POLICY_SETTINGS_NAME = 'enable_main_method';

		/**
		 * Is the mail enabled
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private static $email_enabled = null;

		/**
		 * Inits the class and sets the filters.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function init() {

			\add_filter( WP_2FA_PREFIX . 'providers_translated_names', array( __CLASS__, 'name_translated' ) );

			\add_filter( WP_2FA_PREFIX . 'providers', array( __CLASS__, 'method_provider' ) );

			\add_filter( WP_2FA_PREFIX . 'default_settings', array( __CLASS__, 'add_default_settings' ) );

			\add_filter( WP_2FA_PREFIX . 'loop_settings', array( __CLASS__, 'settings_loop' ), 10, 1 );

			\add_filter( WP_2FA_PREFIX . 'no_method_enabled', array( __CLASS__, 'return_default_selection' ), 10, 1 );

			\add_action( 'wp_ajax_confirm_main_method_via_ajax', array( __CLASS__, 'confirm_main_method_via_ajax' ) );

			// add the TOTP methods to the list of available methods if enabled.
			\add_filter(
				WP_2FA_PREFIX . 'available_2fa_methods',
				function ( $available_methods ) {
					if ( ! empty( Settings::get_role_or_default_setting( self::POLICY_SETTINGS_NAME, 'current' ) ) ) {
						array_push( $available_methods, self::METHOD_NAME );
					}

					return $available_methods;
				}
			);

			Main_Method_Wizard_Steps::init();
		}

		/**
		 * Adds email provider translatable name
		 *
		 * @param array $providers - Array with all currently supported providers and their translated names.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function name_translated( array $providers ) {
			$providers[ self::METHOD_NAME ] = esc_html__( 'Main Method Example', 'extend-2fa-methods' );

			return $providers;
		}

		/**
		 * Adds email as a provider
		 *
		 * @param array $providers - Array with all currently supported providers.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function method_provider( array $providers ) {
			array_push( $providers, self::METHOD_NAME );

			return $providers;
		}

		/**
		 * Adds the extension default settings to the main plugin settings
		 *
		 * @param array $default_settings - array with plugin default settings.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function add_default_settings( array $default_settings ) {
			$default_settings[ self::POLICY_SETTINGS_NAME ] = self::POLICY_SETTINGS_NAME;

			return $default_settings;
		}

		/**
		 * Add extension settings to the loop array
		 *
		 * @param array $loop_settings - Currently available settings array.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function settings_loop( array $loop_settings ) {
			array_push( $loop_settings, self::POLICY_SETTINGS_NAME );
			array_push( $loop_settings, 'specify-email_hotp' );

			/* @premium:start */
			array_push( $loop_settings, 'email-code-period' );
			/* @premium:end */

			return $loop_settings;
		}

		/**
		 * Extracts the selected value from the global settings (if set), and adds it to the output array
		 *
		 * @param array $output - The array with output values.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function return_default_selection( array $output ) {
			// No method is enabled, fall back to previous selected one - we don't want to break the logic.
			$email_enabled = WP2FA::get_wp2fa_setting( self::POLICY_SETTINGS_NAME );

			if ( $email_enabled ) {
				$output[ self::POLICY_SETTINGS_NAME ] = $email_enabled;
			}

			return $output;
		}

		/**
		 * Returns the status of the mail method (enabled | disabled)
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		public static function is_enabled(): bool {
			if ( null === self::$email_enabled ) {
				self::$email_enabled = empty( Settings::get_role_or_default_setting( self::POLICY_SETTINGS_NAME, 'current' ) ) ? false : true;
			}

			return self::$email_enabled;
		}

		/**
		 * Validate a user's code when setting up 2fa via the inline form.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function confirm_main_method_via_ajax() {
			\check_ajax_referer( 'nonce' );

			$user = User_Helper::get_user();

			User_Helper::set_enabled_method_for_user( self::METHOD_NAME, $user );
			User_Profile::delete_expire_and_enforced_keys( $user->ID );
			User_Helper::set_user_status( $user );

			// Send the response.
			\wp_send_json_success();
		}

		/**
		 * Returns the name of the method.
		 *
		 * @since 1.0.0
		 */
		public static function get_method_name(): string {
			return self::METHOD_NAME;
		}
	}
}
