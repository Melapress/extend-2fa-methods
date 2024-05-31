<?php
/**
 * Backup method extender class example
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
use WP2FA\Admin\Settings_Page;
use WP2FA\Utils\Settings_Utils;
use WP2FA\Admin\Helpers\User_Helper;
use WP2FA\Admin\Controllers\Settings;
use WP2FA\Methods\Wizards\Backup_Method_Wizard_Steps;
/**
 * Class for handling backup codes.
 *
 * @since 1.0.0
 *
 * @package extend-2fa-methods
 */
if ( ! class_exists( '\WP2FA\Methods\Backup_Method' ) ) {
	/**
	 * Backup code class, for handling backup code generation and such.
	 *
	 * @since 1.0.0
	 */
	class Backup_Method {

		/**
		 * Key used for backup codes.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		public const BACKUP_CODES_META_KEY = 'backup_method';

		/**
		 * The name of the method stored in the policy
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		public const SETTINGS_NAME = 'enable_backup_method';

		/**
		 * The name of the method.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		public const METHOD_NAME = 'backup_method';

		/**
		 * Holds the status of the backup codes functionality
		 *
		 * @var bool[]
		 *
		 * @since 1.0.0
		 */
		private static $backup_method_enabled = array();

		/**
		 * Default extension settings.
		 *
		 * @var array
		 *
		 * @since 1.0.0
		 */
		private static $settings = array(
			self::SETTINGS_NAME => 'yes',
		);

		/**
		 * Inits the backup codes class hooks
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			\add_filter( WP_2FA_PREFIX . 'backup_methods_list', array( __CLASS__, 'add_backup_method' ), 10, 2 );
			\add_filter( WP_2FA_PREFIX . 'backup_methods_enabled', array( __CLASS__, 'check_backup_method_for_role' ), 10, 2 );

			\add_action( WP_2FA_PREFIX . 'remove_backup_methods_for_user', array( __CLASS__, 'remove_backup_methods_for_user' ) );

			\add_filter( WP_2FA_PREFIX . 'loop_settings', array( __CLASS__, 'settings_loop' ), 10, 2 );

			\add_filter( WP_2FA_PREFIX . 'default_settings', array( __CLASS__, 'add_default_settings' ) );

			\add_filter( WP_2FA_PREFIX . 'providers', array( __CLASS__, 'backup_method' ) );

			\add_filter( WP_2FA_PREFIX . 'providers_translated_names', array( __CLASS__, 'fill_providers_array_with_method_name_translated' ) );

			\add_filter( WP_2FA_PREFIX . 'user_enabled_backup_methods', array( __CLASS__, 'method_enabled_for_user' ), 10, 2 );

			\add_action( 'wp_ajax_run_ajax_save_backup_method', array( __CLASS__, 'run_ajax_save_backup_method' ) );

			\add_action( 'wp_ajax_remove_backup_method', array( __CLASS__, 'remove_user_backup_method' ) );

			Backup_Method_Wizard_Steps::init();
		}

		/**
		 * Fills the array of the enabled backup methods is it is provided for the given user
		 *
		 * @param array    $array_methods - Array to fill if the method is enabled for user.
		 * @param \WP_User $user - The user to check for.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function method_enabled_for_user( array $array_methods, $user ): array {
			if ( self::is_enabled_for_user( $user ) ) {
				$array_methods[ self::METHOD_NAME ] = self::get_translated_name();
			}

			return $array_methods;
		}

		/**
		 * Adds Backup codes as a provider.
		 *
		 * @param array $providers - Array with all currently supported providers.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function backup_method( array $providers ) {
			array_push( $providers, self::METHOD_NAME );

			return $providers;
		}

		/**
		 * Adds Backup code as a provider.
		 *
		 * @param array $providers - Array with all currently supported providers and their translated names.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function fill_providers_array_with_method_name_translated( array $providers ) {
			$providers[ self::METHOD_NAME ] = self::get_translated_name();

			return $providers;
		}

		/**
		 * Returns the name of the provider
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_translated_name(): string {
			return esc_html__( 'Backup codes', 'extend-2fa-methods' );
		}

		/**
		 * Removes the backup method (user meta key) from the database.
		 *
		 * @param \WP_User,int,null $user - The user to remove method for.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function remove_backup_methods_for_user( $user ) {
			if ( ! Settings::is_provider_enabled_for_role( User_Helper::get_user_role( $user ), self::get_method_name() ) ) {
				\delete_user_meta( $user->ID, self::BACKUP_CODES_META_KEY );
			}
		}

		/**
		 * Delete code once its used.
		 *
		 * @param object $user        User data.
		 * @param string $code_hashed Code to delete.
		 *
		 * @since 1.0.0
		 */
		public static function delete_code( $user, $code_hashed ) {
			$backup_method = get_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, true );

			// Delete the current code from the list since it's been used.
			$backup_method = array_flip( $backup_method );
			unset( $backup_method[ $code_hashed ] );
			$backup_method = array_values( array_flip( $backup_method ) );

			// Update the backup code master list.
			\update_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, $backup_method );
		}

		/**
		 * Add the method to the existing backup methods array.
		 *
		 * @param array $backup_methods - Array with the currently supported backup methods.
		 *
		 * @since 1.0.0
		 */
		public static function add_backup_method( array $backup_methods ): array {
			return array_merge(
				$backup_methods,
				array(
					self::METHOD_NAME => array(
						'wizard-step' => '2fa-wizard-config-backup-method',
						'button_name' => esc_html__( 'Login with a backup method', 'extend-2fa-methods' ),
					),
				)
			);
		}

		/**
		 * Changes the global backup methods array - removes the method if it is not enabled.
		 *
		 * @param array    $backup_methods - Array with all global backup methods.
		 * @param \WP_User $user           - User to check for is that method enabled.
		 *
		 * @since 1.0.0
		 */
		public static function check_backup_method_for_role( array $backup_methods, \WP_User $user ): array {
			$enabled = self::are_backup_method_enabled_for_role( User_Helper::get_user_role( $user ) );

			if ( ! $enabled ) {
				unset( $backup_methods[ self::METHOD_NAME ] );
			}

			return $backup_methods;
		}

		/**
		 * Returns the name of the method.
		 *
		 * @since 1.0.0
		 */
		public static function get_method_name(): string {
			return self::METHOD_NAME;
		}

		/**
		 * Checks if the backup codes option is enabled for the role
		 *
		 * @param string $role - The role name.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public static function are_backup_method_enabled_for_role( $role = 'global' ) {

			$role = ( is_null( $role ) || empty( $role ) ) ? 'global' : $role;

			if ( ! isset( self::$backup_method_enabled[ $role ] ) ) {
				self::$backup_method_enabled[ $role ] = false;

				if ( 'global' === $role ) {
					$setting_value = Settings::get_role_or_default_setting( self::get_settings_name() );
				} else {
					$setting_value = Settings::get_role_or_default_setting( self::get_settings_name(), 'current', $role );
				}
				self::$backup_method_enabled[ $role ] = Settings_Utils::string_to_bool( $setting_value );
			}

			return self::$backup_method_enabled[ $role ];
		}

		/**
		 * Checks if the backup method are enabled for the user.
		 *
		 * @param int|\WP_User|null $user - The WP user we should extract the meta data for.
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 *
		 * @throws \LogicException - can not extract user from the given parameters.
		 */
		public static function is_enabled_for_user( $user ): bool {
			if ( $user->get( self::METHOD_NAME ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Adds settings names to the extraction array - grabs the values and stores them based on names.
		 *
		 * @param array $settings - Array with all the settings.
		 *
		 * @since 1.0.0
		 */
		public static function settings_loop( array $settings ): array {
			return array_merge( $settings, array_keys( self::$settings ) );
		}

		/**
		 * Adds the extension default settings to the main plugin settings.
		 *
		 * @param array $default_settings - Array with plugin default settings.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function add_default_settings( array $default_settings ) {
			return array_merge( $default_settings, self::$settings );
		}

		/**
		 * Returns the method settings name
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_settings_name(): string {
			return \array_key_first( self::$settings );
		}

		/**
		 * Returns the method settings default value
		 *
		 * @return mixed
		 *
		 * @since 1.0.0
		 */
		public static function get_settings_default_value() {
			return \reset( self::$settings );
		}

		/**
		 * Returns the backup codes for the user.
		 *
		 * @param \WP_User $user \WP_User - object of the logged-in user.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_backup_method_for_user( $user ): array {
			$backup_method = get_user_meta( $user->ID, self::BACKUP_CODES_META_KEY, true );

			if ( ! \is_array( $backup_method ) ) {
				return array();
			}

			return $backup_method;
		}

		/**
		 * Send email with fresh code, or to setup email 2fa.
		 *
		 * @param int    $user_id User id we want to send the message to.
		 * @param string $nominated_email_address - The user custom address to use (name of the meta key to check for).
		 *
		 * @return bool
		 *
		 * @since 1.0.0
		 */
		public static function send_backup_method_email( $user_id, $nominated_email_address = 'nominated_email_address' ) {

			// If we have a nonce posted, check it.
			if ( \wp_doing_ajax() && isset( $_POST['_wpnonce'] ) ) {
				$nonce_check = \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_wpnonce'] ) ), 'wp-2fa-send-backup-codes-email-nonce' );
				if ( ! $nonce_check ) {
					return false;
				}
			} else {
				\wp_die();
			}

			$user = User_Helper::get_user_object();

			$enabled_email_address = '';
			if ( ! empty( $nominated_email_address ) ) {
				if ( 'nominated_email_address' === $nominated_email_address ) {
					$enabled_email_address = User_Helper::get_nominated_email_for_user( $user );
				} else {
					$enabled_email_address = get_user_meta( $user->ID, WP_2FA_PREFIX . $nominated_email_address, true );
				}
			}

			if ( isset( $_POST['codes'] ) ) {
				$codes = substr( str_replace( '\\n', '<br>', \sanitize_text_field( \wp_unslash( $_POST['codes'] ) ) ), 1, -1 );

				$posted_codes = array_filter( \explode( '<br>', $codes ) );

				$stored_codes = self::get_backup_method_for_user( $user );

				foreach ( $posted_codes as $key => $check_code ) {
					$check_code = trim( \explode( ':', $check_code )[1] );
					if ( ! \wp_check_password( $check_code, $stored_codes[ $key ], $user->ID ) ) {

						\wp_die();
					}
				}
			} else {
				\wp_die();
			}

			$subject = wp_strip_all_tags( WP2FA::replace_email_strings( WP2FA::get_wp2fa_email_templates( 'user_backup_method_email_subject' ), $user->ID ) );
			$message = wpautop( WP2FA::replace_email_strings( WP2FA::get_wp2fa_email_templates( 'user_backup_method_email_body' ), $user->ID ) );

			$final_output = str_replace( '{backup_method}', $codes, $message );

			if ( ! empty( $enabled_email_address ) ) {
				$email_address = $enabled_email_address;
			} else {
				$email_address = $user->user_email;
			}

			return Settings_Page::send_email( $email_address, $subject, $final_output );
		}

		/**
		 * AJAX method for saving the email backup settings.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function run_ajax_save_backup_method() {
			$which_address = '';

			if ( isset( $_POST['user_id'] ) ) {
				$user_id = (int) $_POST['user_id'];
				$user    = get_user_by( 'id', sanitize_text_field( $user_id ) );
				if ( ! $user ) {
					$user = wp_get_current_user();
				}
			} else {
				$user = wp_get_current_user();
			}

			check_ajax_referer( 'wp-2fa-backup-codes-generate-json-' . $user->ID, 'nonce' );

			self::save_backup_method( $user );

			// Send the response.
			\wp_send_json_success();
		}

		/**
		 * Saves the methods into the users meta.
		 *
		 * @param \WP_User $user  - The user which address we must updated.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function save_backup_method( \WP_User $user ) {
			User_Helper::set_meta( self::METHOD_NAME, true, $user );
		}

		/**
		 * Removes the backup method from the users meta.
		 *
		 * @param \WP_User $user - The user which address we must update.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function remove_backup_method( \WP_User $user ) {
			User_Helper::remove_meta( self::METHOD_NAME, $user );
		}

		/**
		 * Remove user 2fa backup method via ajax request.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function remove_user_backup_method() {
			// Filter $_GET array for security.
			$get_array = filter_input_array( INPUT_GET );
			$nonce     = sanitize_text_field( $get_array['wp_2fa_nonce'] );

			if ( ! wp_verify_nonce( $nonce, 'wp-2fa-remove-user-backup-method-nonce' ) ) {
				exit( esc_html__( 'Nonce verification failed.', 'extend-2fa-methods' ) );
			}

			if ( isset( $get_array['user_id'] ) ) {
				$user_id = intval( $get_array['user_id'] );

				$current_user = User_Helper::get_user();

				if ( ! current_user_can( 'manage_options' ) && $current_user->ID !== $user_id ) {
					return;
				}

				self::remove_backup_method( $current_user );
			}
		}
	}
}
