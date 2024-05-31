<?php
/**
 * Responsible for Backup Method user interaction.
 *
 * @package    extend-2fa-methods
 * @subpackage methods-wizard
 * @since      1.0.0
 * @copyright  2024 Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 * @see       https://wordpress.org/plugins/wp-2fa/
 */

declare(strict_types=1);

namespace WP2FA\Methods\Wizards;

use WP2FA\Authenticator\Login;
use WP2FA\Utils\Generate_Modal;
use WP2FA\Methods\Backup_Method;
use WP2FA\Admin\Helpers\WP_Helper;
use WP2FA\Admin\Views\Wizard_Steps;
use WP2FA\Admin\Helpers\User_Helper;
use WP2FA\Admin\Controllers\Settings;
use WP2FA\Admin\Methods\Traits\Methods_Wizards_Trait;
use WP2FA\Extensions\RoleSettings\Role_Settings_Controller;

/**
 * Class for handling method codes.
 *
 * @since 1.0.0
 *
 * @package extend-2fa-methods
 */
if ( ! class_exists( '\WP2FA\Methods\Wizards\Backup_Method_Wizard_Steps' ) ) {
	/**
	 * Backup method class.
	 *
	 * @since 1.0.0
	 */
	class Backup_Method_Wizard_Steps extends Wizard_Steps {

		use Methods_Wizards_Trait;

		/**
		 * Keeps the main class method name, so we can call it when needed.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private static $main_class = Backup_Method::class;

		/**
		 * Inits the class hooks.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			\add_action( WP_2FA_PREFIX . 'additional_settings_steps', array( __CLASS__, 'backup_method_wizard_step' ), 10 );
			\add_action( WP_2FA_PREFIX . 'login_form', array( __CLASS__, 'backup_method_authentication_page' ), 10, 2 );
			\add_action( WP_2FA_PREFIX . 'login_html_after_backup_providers', array( __CLASS__, 'provider_link' ), 10, 5 );
			\add_action( WP_2FA_PREFIX . 'after_backup_methods_setup', array( __CLASS__, 'backup_method_wizard_settings' ), 10, 3 );
			\add_action( WP_2FA_PREFIX . 'methods_wizards', array( __CLASS__, 'backup_method_wizard_window' ) );

			\add_filter( WP_2FA_PREFIX . 'additional_form_buttons', array( __CLASS__, 'backup_method_user_form_button' ) );
		}

		/**
		 * Shows final step when email backup method is selected.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function backup_method_wizard_step() {
			if ( ! Settings::get_role_or_default_setting( self::get_main_class()::SETTINGS_NAME, User_Helper::get_user_object(), null, true ) ) {
				return;
			}
			$redirect = Wizard_Steps::determine_redirect_url();

			?>
			<div class="wizard-step" id="wizard-backup-method-selected">
				<div class="option-pill">
					<?php esc_html_e( 'Backup Method', 'extend-2fa-methods' ); ?>
				</div>
				<div class="option-pill">
					<label for="use_wp_email_as_backup">
						<span><?php esc_html_e( 'Enable backup method', 'extend-2fa-methods' ); ?></span>
					</label>
				</div>
				<div class="wp2fa-setup-actions">
					<button class="button button-primary wp-2fa-button-primary" name="next_step_setting" value="<?php esc_attr_e( 'Save email backup options', 'extend-2fa-methods' ); ?>" data-trigger-save-backup-method <?php echo WP_Helper::create_data_nonce( self::json_nonce() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-user-id="<?php echo esc_attr( User_Helper::get_user_object()->ID ); ?>">
						<?php esc_html_e( 'Save 2FA backup method', 'extend-2fa-methods' ); ?>
					</button>
					<a href="<?php echo esc_url( $redirect ); ?>" class="button button-secondary wp-2fa-button-secondary close-first-time-wizard">
							<?php esc_html_e( 'Close wizard', 'extend-2fa-methods' ); ?>
					</a>
				</div>
			</div>
			<?php

			add_action( 'wp_footer', array( __CLASS__, 'js_backup_method_user_setup' ) );
			add_action( 'admin_footer', array( __CLASS__, 'js_backup_method_user_setup' ) );
		}

		/**
		 * Prints the form that prompts the user to authenticate.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_User $user     - WP_User object of the logged-in user.
		 * @param string  $provider - The name of the provider.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function backup_method_authentication_page( $user, $provider ) {
			if ( self::get_main_class()::get_method_name() === $provider ) {

				require_once ABSPATH . '/wp-admin/includes/template.php';
				?>
					<p><?php esc_html_e( 'Login:', 'extend-2fa-methods' ); ?></p><br/>
					<p style="padding-bottom: 20px;"><?php esc_html_e( 'Check the backup method challenge or show login button and let the user in', 'extend-2fa-methods' ); ?></p> 
				<?php
			}
		}

		/**
		 * Generates link for the backup method.
		 *
		 * @param \WP_User $user        - The user for which we need to create backup method link.
		 * @param string   $provider    - The name of the current provider - add link only if it is not this provider.
		 * @param string   $login_nonce - The nonce to add.
		 * @param string   $redirect_to - Redirect to setting for user.
		 * @param bool     $rememberme  - Remember me status for the user.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function provider_link( $user, string $provider, string $login_nonce, string $redirect_to, $rememberme ) {
			if ( self::get_main_class()::is_enabled_for_user( User_Helper::get_user_object() ) ) {
				$login_url = Login::login_url(
					array(
						'action'        => 'backup_2fa',
						'provider'      => self::get_main_class()::get_method_name(),
						'wp-auth-id'    => $user->ID,
						'wp-auth-nonce' => $login_nonce,
						'redirect_to'   => $redirect_to,
						'rememberme'    => $rememberme,
					)
				);
				?>
				<div class="backup-methods-wrap">
					<p class="backup-methods">
						<a href="<?php echo esc_url( $login_url ); ?>">
							<?php esc_html_e( 'Or, log me in with backup method.', 'extend-2fa-methods' ); ?>
						</a>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Settings page and first time wizard settings render.
		 *
		 * @param bool   $setup_wizard - Is that the first time setup wizard.
		 * @param string $data_role    - Additional HTML data attribute.
		 * @param mixed  $role         - Name of the role.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function backup_method_wizard_settings( bool $setup_wizard, string $data_role, $role = null ) {
			$name_prefix = WP_2FA_POLICY_SETTINGS_NAME;
			$data_role   = 'data-role="global"';
			$role_id     = '-global';
			if ( null !== $role ) {
				if ( '' !== trim( (string) $role ) ) {
					$name_prefix .= "[{$role}]";
					$data_role    = 'data-role="' . $role . '"';
					$role_id      = '-' . $role;
				}
			}
			?>
			<br />
			<label for="<?php echo esc_attr( self::get_main_class()::SETTINGS_NAME ); ?><?php echo \esc_attr( $role_id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( self::get_main_class()::SETTINGS_NAME ); ?><?php echo \esc_attr( $role_id ); ?>" name="<?php echo \esc_attr( $name_prefix ); ?>[<?php echo esc_attr( self::get_main_class()::SETTINGS_NAME ); ?>]" value="<?php echo esc_attr( self::get_main_class()::SETTINGS_NAME ); ?>"
				<?php
					$enabled_settings = '';
					echo $data_role; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<?php
				if ( null !== $role ) {
					$enabled_settings = Role_Settings_Controller::get_setting( $role, self::get_main_class()::SETTINGS_NAME );
					?>
					<?php checked( $enabled_settings, self::get_main_class()::SETTINGS_NAME ); ?>
					<?php
				} else {
					$use_role_setting = null;
					if ( null === $role || '' === trim( (string) $role ) ) {
						$use_role_setting = \WP_2FA_PREFIX . 'no-user';
					}

					$enabled_settings = Settings::get_role_or_default_setting( self::get_main_class()::SETTINGS_NAME, $use_role_setting, $role, true, true );
					?>
					<?php checked( $enabled_settings, self::get_main_class()::SETTINGS_NAME ); ?>
				<?php } ?>
				/>
					<?php esc_html_e( 'Allow users to use backup method', 'extend-2fa-methods' ); ?>
			</label>
			<?php
		}

		/**
		 * Add configure button to the user profile page.
		 *
		 * @param string $form - The HTML form shown in the User profile.
		 *
		 * @since 1.0.0
		 */
		public static function backup_method_user_form_button( string $form ): string {
			if ( Settings::get_role_or_default_setting( self::get_main_class()::SETTINGS_NAME, User_Helper::get_user_object(), null, true ) ) {
				$on_click = 'backupMethodConfigure();';

				$mail_label = __( 'Configure backup method', 'extend-2fa-methods' );
				if ( self::get_main_class()::is_enabled_for_user( User_Helper::get_user_object() ) ) {
					$mail_label = __( 'Remove backup method', 'extend-2fa-methods' );

					$on_click = 'MicroModal.show(\'confirm-remove-backup-method\')';

					echo Generate_Modal::generate_modal( // phpcs:ignore
						'confirm-remove-backup-method',
						__( 'Remove Backup Method?', 'extend-2fa-methods' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						__( 'Are you sure you want to remove the backup method?', 'extend-2fa-methods' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						array(
							'<a href="#" class="button wp-2fa-button-primary button-confirm" data-trigger-remove-backup-method ' . WP_Helper::create_data_nonce( 'wp-2fa-remove-user-backup-method-nonce' ) . ' data-user-id="' . User_Helper::get_user()->ID . '">' . \esc_html__( 'Yes', 'extend-2fa-methods' ) . '</a>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'<button class="button wp-2fa-button-secondary button-decline" data-close-2fa-modal aria-label="Close this dialog window">' . \esc_html__( 'No', 'extend-2fa-methods' ) . '</button>',
						)
					);
				}

				return $form . '<div style="margin-top:5px;"><a href="#" class="button button-primary wp-2fa-button-primary remove-2fa" data-trigger-generate-backup-codes ' . WP_Helper::create_data_nonce( self::json_nonce() ) . ' data-user-id="' . esc_attr( User_Helper::get_user_object()->ID ) . '" onclick="' . $on_click . '">' . $mail_label . '</a></div>';
			}

			return $form;
		}

		/**
		 * Shows the backup method settings wizard JS.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function backup_method_wizard_window() {
			?>
			<div>
				<script>
					function backupMethodConfigure(){
						jQuery( '.verification-response span' ).remove();
						jQuery( '#configure-2fa .wizard-step.active, #configure-2fa .step-setting-wrapper.active' ).removeClass( 'active' );
						jQuery( '#wizard-backup-method-selected' ).addClass( 'active' );
						jQuery( '.modal__content input:not([type="radio"]):not([type="hidden"])' ).val( '' );
						MicroModal.show( 'configure-2fa' );
					}
				</script>
			</div>
			<?php
		}

		/**
		 * Adds JS to the backup wizard step.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function js_backup_method_user_setup() {
			?>
			<script>
				window.addEventListener('load', (event) => {
					jQuery( document ).ready( function() {

						jQuery( document ).on( 'click', '[data-trigger-remove-backup-method]', function() {
							const nonce = jQuery( this ).attr( 'data-nonce' );
							const account = jQuery( this ).attr( 'data-user-id' );
							jQuery.ajax( {
								url: wp2faData.ajaxURL,
								data: {
									action: 'remove_backup_method',
									user_id: account,
									wp_2fa_nonce: nonce
								},
								complete: function( data ) {
									location.reload();
								},
							} );
						} );

						jQuery( 'body' ).on( 'click', '.button[data-trigger-save-backup-method]', function( e ) {
							e.preventDefault();
							const actionToRun = 'run_ajax_save_backup_method';
							const nonceValue = jQuery( this ).attr( 'data-nonce' );
							const userID = jQuery( this ).attr( 'data-user-id' );
							jQuery.ajax( {
								type: 'POST',
								dataType: 'json',
								url: wp2faData.ajaxURL,
								data: {
									action: actionToRun,
									_wpnonce: nonceValue,
									user_id: userID,
								},
								complete: function( data ) {
									if ( jQuery('[data-close-2fa-modal-and-refresh]').length ) {
										jQuery('[data-close-2fa-modal-and-refresh]').trigger('click');
									} else {
										window.location = jQuery('.close-first-time-wizard').attr('href');
									}
								}
							}, );
						});

					});
				});
			</script>
			<?php
		}
	}
}
