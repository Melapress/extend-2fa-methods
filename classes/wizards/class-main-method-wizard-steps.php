<?php
/**
 * Responsible for WP2FA user's Email manipulation.
 *
 * @package    wp2fa
 * @subpackage methods-wizard
 * @since      1.0.0
 * @copyright  %%YEAR%% Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 *
 * @see       https://wordpress.org/plugins/wp-2fa/
 */

declare(strict_types=1);

namespace WP2FA\Methods\Wizards;

use WP2FA\Methods\Main_Method;
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
 * @package WP2FA
 */
if ( ! class_exists( '\WP2FA\Methods\Wizards\Main_Method_Wizard_Steps' ) ) {
	/**
	 * Email code class, for handling method code generation and such.
	 *
	 * @since 1.0.0
	 */
	class Main_Method_Wizard_Steps extends Wizard_Steps {

		use Methods_Wizards_Trait;

		/**
		 * Keeps the main class method name, so we can call it when needed.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private static $main_class = Main_Method::class;

		/**
		 * The default value of the method order in the wizards.
		 *
		 * @var integer
		 *
		 * @since 1.0.0
		 */
		private static $order = 6;

		/**
		 * Inits the class hooks
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			\add_filter( WP_2FA_PREFIX . 'methods_modal_options', array( __CLASS__, 'main_method_option' ), 10, 2 );
			\add_action( WP_2FA_PREFIX . 'modal_methods', array( __CLASS__, 'main_method_modal_configure' ) );
			\add_filter( WP_2FA_PREFIX . 'methods_re_configure', array( __CLASS__, 'main_method_re_configure' ), 10, 2 );
			\add_filter( WP_2FA_PREFIX . 'methods_settings', array( __CLASS__, 'main_method_wizard_settings' ), 10, 4 );
			\add_action( WP_2FA_PREFIX . 'login_form', array( __CLASS__, 'login_form' ), 10, 2 );
		}

		/**
		 * Shows the option for main method reconfiguring (if applicable)
		 *
		 * @param array  $methods - Array of methods collected.
		 * @param string $role - The name of the role to show option to.
		 *
		 * @since 1.0.0 - Parameter $methods is added, parameter $role (name) is added and array is now returned
		 *
		 * @return array
		 */
		public static function main_method_re_configure( array $methods, string $role ): array {

			if ( ! self::$main_class::is_enabled() ) {
				return $methods;
			}
			\ob_start();
			?>
			<div class="option-pill">
				<h3><?php echo \esc_html__( 'Setting up Main Method Example', 'wp-2fa' ); ?></h3>
				<p><?php echo \esc_html__( 'This is an intro text for the user', 'wp-2fa' ); ?></p>
				<div class="wp2fa-setup-actions">
					<a class="button button-primary wp-2fa-button-primary" data-name="next_step_setting_modal_wizard" data-user-id="<?php echo esc_attr( User_Helper::get_user_object()->ID ); ?>" data-next-step="2fa-wizard-<?php echo \esc_attr( self::$main_class::METHOD_NAME ); ?>"><?php esc_html_e( 'Change Main Method Example', 'wp-2fa' ); ?></a>
				</div>
			</div>
			<?php
				$output = ob_get_contents();
				ob_end_clean();

				$methods[ self::get_order( $role, $methods ) ] = array(
					'name'   => self::$main_class::METHOD_NAME,
					'output' => $output,
				);

				return $methods;
		}

		/**
		 * Shows the initial method setup options based on enabled methods
		 *
		 * @param array  $methods - Array of methods collected.
		 * @param string $role - The name of the role to show option to.
		 *
		 * @since 1.0.0 - Parameter $methods is added, parameter $role (name) is added and array is now returned
		 *
		 * @return array
		 */
		public static function main_method_option( array $methods, string $role ): array {
			if ( self::$main_class::is_enabled() ) {
				\ob_start();
				?>
					<div class="option-pill">
						<label>
							<input name="wp_2fa_enabled_methods" type="radio" value="email">
						<?php \esc_html__( 'Main method Example', 'wp-2fa' ); ?>
						</label>
					</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();

				$methods[ self::get_order( $role, $methods ) ] = $output;
			}

			return $methods;
		}

		/**
		 * Settings page and first time wizard settings render
		 *
		 * @param array   $methods - Array with all the methods in which we have to add this one.
		 * @param boolean $setup_wizard - Is that the first time setup wizard.
		 * @param string  $data_role - Additional HTML data attribute.
		 * @param mixed   $role - Name of the role.
		 *
		 * @return array - The array with the methods with all the methods wizard steps.
		 *
		 * @since 1.0.0
		 */
		public static function main_method_wizard_settings( array $methods, bool $setup_wizard, string $data_role, $role = null ) {
			$name_prefix = WP_2FA_POLICY_SETTINGS_NAME;
			$role_id     = '';
			if ( null !== $role && '' !== trim( (string) $role ) ) {
				$name_prefix .= "[{$role}]";
				$data_role    = 'data-role="' . $role . '"';
				$role_id      = '-' . $role;
			}
			\ob_start();
			?>
				<div id="<?php echo \esc_attr( self::$main_class::METHOD_NAME ); ?>-method-wrapper" class="method-wrapper">
					<?php echo self::hidden_order_setting( $role ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<label for="<?php echo \esc_attr( self::$main_class::METHOD_NAME . $role_id ); ?>" style="margin-bottom: 0 !important;">
						<input type="checkbox" id="<?php echo \esc_attr( self::$main_class::METHOD_NAME . $role_id ); ?>" name="<?php echo \esc_attr( $name_prefix ); ?>[<?php echo esc_attr( self::$main_class::POLICY_SETTINGS_NAME ); ?>]" value="<?php echo \esc_attr( self::$main_class::POLICY_SETTINGS_NAME ); ?>"
						<?php echo $data_role; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( null !== $role && ! empty( $role ) ) { ?>
							<?php checked( self::$main_class::POLICY_SETTINGS_NAME, Role_Settings_Controller::get_setting( $role, self::$main_class::POLICY_SETTINGS_NAME ), true ); ?>
							<?php
						} else {
							$use_role_setting = null;
							if ( null === $role || '' === trim( (string) $role ) ) {
								$use_role_setting = \WP_2FA_PREFIX . 'no-user';
							}

							$enabled_settings = Settings::get_role_or_default_setting( self::$main_class::POLICY_SETTINGS_NAME, $use_role_setting, $role, true );
							?>
							<?php checked( $enabled_settings, self::$main_class::POLICY_SETTINGS_NAME ); ?>
						<?php } ?>
						>
						<?php
						esc_html_e( 'Example Main Method', 'wp-2fa' );
						?>
					</label>
					<?php
					if ( $setup_wizard ) {
						echo '<p class="description">' . esc_html__( 'This is an example only.', 'wp-2fa' ) . '</p>';
					}
					?>
					<?php
					if ( null !== $role ) {
						$enabled_settings = Role_Settings_Controller::get_setting( $role, self::$main_class::POLICY_SETTINGS_NAME );
					} else {
						$enabled_settings = Settings::get_role_or_default_setting( self::$main_class::POLICY_SETTINGS_NAME, ( ( null !== $role && '' !== $role ) ? '' : false ), $role, true, true );
					}
					?>
				</div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();

			$methods[ self::get_order( $role, $methods ) ] = $output;

			return $methods;
		}

		/**
		 * Reconfigures method form
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function main_method_modal_configure() {

			if ( ! self::$main_class::is_enabled() ) {
				return;
			}
			?>
			<div class="wizard-step" id="2fa-wizard-<?php echo \esc_attr( self::$main_class::METHOD_NAME ); ?>">
				<fieldset>
					<div class="step-setting-wrapper active">
						<div class="mb-20">
							<h3><?php echo \esc_html__( 'Setting up Main Method Example', 'wp-2fa' ); ?></h3>
							<p><?php echo \esc_html__( 'This is an intro text for the user', 'wp-2fa' ); ?></p>
						</div>

						<div class="wp2fa-setup-actions">
							<button class="button button-primary wp-2fa-button-primary" data-confirm-main-method-ajax name="next_step_setting_confirm" value="<?php esc_attr_e( 'I\'m Ready', 'wp-2fa' ); ?>" data-user-id="<?php echo esc_attr( User_Helper::get_user_object()->ID ); ?>" <?php echo WP_Helper::create_data_nonce( 'nonce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> type="button"><?php esc_html_e( 'I\'m Ready', 'wp-2fa' ); ?></button>
							<a class="button button-primary wp-2fa-button-primary modal_cancel"><?php esc_attr_e( 'Cancel', 'wp-2fa' ); ?></a>
						</div>
					</div>
					<script>
						jQuery( document ).on( 'click', '[data-confirm-main-method-ajax]', function( e ) {
							e.preventDefault();
							const thisButton = jQuery( this );
							let actionToRun = 'confirm_main_method_via_ajax';
							let authcode = false;

							if( jQuery('#wp-2fa-totp-authcode').length && jQuery('#wp-2fa-totp-authcode').val().length ) {
								authcode = true;
							}
							if (typeof jQuery(this).data('oob-test') !== 'undefined') {
								actionToRun = 'validate_oob_authcode_via_ajax';
							}
							const nonceValue = jQuery( this ).attr( 'data-nonce' );
							
							const currentPageURL = window.location.href;
							
							jQuery.ajax( {
								type: 'POST',
								dataType: 'json',
								url: wp2faData.ajaxURL,
								data: {
									action: actionToRun,
									_wpnonce: nonceValue,
								},
								complete: function( data ) {
									if ( false === data.responseJSON.success ) {
										jQuery( thisButton ).parent().find( '.verification-response' ).html( `<span style="color:red">${data.responseJSON.data['error']}</span>` );
									}
									if ( true === data.responseJSON.success ) {
										let nextSubStep = jQuery( '#2fa-wizard-config-backup-codes' );
										if ( authcode ) {
											if ( jQuery('#2fa-wizard-backup-methods').length ) {
												nextSubStep = jQuery( '#2fa-wizard-backup-methods' );
											} else if ( jQuery( '#2fa-wizard-email-backup-selected' ).length ) {
												nextSubStep = jQuery( '#2fa-wizard-email-backup-selected' );
											}
										}
										jQuery( this ).parent().parent().find( '.active' ).not( '.step-setting-wrapper' ).removeClass( 'active' );
										jQuery( '.wizard-step.active' ).removeClass( 'active' );
										jQuery( nextSubStep ).addClass( 'active' );

										jQuery( document ).on( 'click', '#select-backup-method', function( e ) {
											e.preventDefault();
											var backupRadio = jQuery("input[name=backup_method_select]:checked");

											jQuery( '.wizard-step.active' ).removeClass( 'active' );
											jQuery( '#'+backupRadio.data('step') ).addClass( 'active' );
										} );
									}
								}
							}, );
						}
						);
					</script>
				</fieldset>
			</div>
			<?php
		}

		/**
		 * Shows final step when method is selected. Checks for the provided credentials and logs in/out the user.
		 *
		 * @param \WP_User $user - WP_User object of the logged-in user.
		 * @param string   $provider - The name of the provider.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function login_form( $user, $provider ) {
			if ( self::$main_class::METHOD_NAME === $provider ) {

				?>
				<p style="margin-bottom: 3em;"><?php echo \esc_html__( 'Thank you for using the example method to login.', 'wp-2fa' ); ?></p>
				<hr style="margin-bottom: 3em;" />
				<?php
				// Include submit_button function and set the submit button show to true - your method may not need that. Changes based on your needs.
				require_once ABSPATH . '/wp-admin/includes/template.php';

				\add_filter( WP_2FA_PREFIX . 'login_disable_submit_button', '\__return_false' );
			}
		}
	}
}