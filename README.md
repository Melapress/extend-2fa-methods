# WP 2FA by WP White Security

Easy to use two-factor authentication for your WordPress logins.

https://wordpress.org/plugins/wp-2fa/

# Extending 2FA methods documentation & information

### Primary methods extending

In order to create a new primary method in WP2FA you need the following:

*Note:*: important note about the naming convention used in this document - **method**, **provider** and **extension** meaning the same thing.

A new class with the namespace starting with `\WP2FA\Methods`
The method should have unique slug which can be used to separate it from other methods. If that slug is in use, the last added method with given slug will take precedence.

and implementing the following methods:

static method named `init` responsible for initializing all of the hooks for proper method workflow.

The method must implement the following methods hooks (this is an example code, you can have this implemented wherever you like as long as they are accessible for the PHP and WP):

The code below will presume you have the following set (you can achieve this the way you prefer):
self::POLICY_SETTINGS_NAME - The name of the policy setting that will be used to enable disable the method.

*Example:*

```
const POLICY_SETTINGS_NAME = 'enable_method';
```

and
self::METHOD_NAME - The name of the method.

*Example:*

```
const METHOD_NAME = 'new_2fa_method';
```

```
	\add_filter( WP_2FA_PREFIX . 'providers_translated_names', array( __CLASS__, 'name_translated' ) );

	\add_filter( WP_2FA_PREFIX . 'providers', array( __CLASS__, 'add_provider' ) );

	\add_filter( WP_2FA_PREFIX . 'default_settings', array( __CLASS__, 'add_default_settings' ) );

	\add_filter( WP_2FA_PREFIX . 'loop_settings', array( __CLASS__, 'settings_loop' ), 10, 1 );

	\add_filter( WP_2FA_PREFIX . 'no_method_enabled', array( __CLASS__, 'return_default_selection' ), 10, 1 );

	// add the new method to the list of available methods if enabled.
	\add_filter(
		WP_2FA_PREFIX . 'available_2fa_methods',
		function ( $available_methods ) {
			if ( ! empty( Settings::get_role_or_default_setting( self::POLICY_SETTINGS_NAME, 'current' ) ) ) {
				array_push( $available_methods, self::METHOD_NAME );
			}

			return $available_methods;
		}
	);
```

Following is more detailed explanation of every hook and its code implementation

```
	/**
	 * Adds translatable name
	 * Receives an array with all of the registered methods / providers.
	 * Using this you can add translatable name of your method to the global methods array supported by the plugin.
	 *
	 * @param array $providers - Array with all currently supported providers and their translated names.
	 *
	 * @return array - Array with all registered methods / providers
	 *
	 * @since latest
	 */
	public static function name_translated( array $providers ): array {
		$providers[ self::METHOD_NAME ] = esc_html__( 'Method Name', 'extend-2fa-methods' );

		return $providers;
	}
```

```
	/**
	 * Adds the method to the global providers / methods supported by the plugin.
	 * Receives array with all of the registered methods, use it to add / register your method giving its slug
	 *
	 * @param array $providers - Array with all currently supported providers.
	 *
	 * @return array - Array with all of the registered methods
	 *
	 * @since latest
	 */
	public static function add_provider( array $providers ): array {
		array_push( $providers, self::METHOD_NAME );

		return $providers;
	}
```

```
	/**
	 * Adds the method default settings to the main plugin settings.
	 * Receives array with all of the (currently) registered settings, and gives the ability for the method to add its own settings to global array.
	 * Use this method to add the settings default values your method needs, that way the plugin will take care of fall back the them if nothing is explicitly set.
	 *
	 * @param array $default_settings - Array with method default settings.
	 *
	 * @return array - Array with plugin settings.
	 *
	 * @since latest
	 */
	public static function add_default_settings( array $default_settings ): array {
		$default_settings[ self::METHOD_NAME . '_setting_name' ] = 'default_value';

		return $default_settings;
	}
```

```
	/**
	 * Add extension settings to the loop array.
	 * Plugin is using the method policy settings - these are settings related to the user specific iteration with the given method. These are shown in the policy settings screen of the plugin and related to users and (in paid version) user roles.
	 * Using these settings the admin can enable / disable the plugin, set additional options or remove them.
	 * This only sets names of the policy settings, there is no need to provide values for them - plugin will check for these settings names when settings are saved, and if found, the proper values will be stored against given names.
	 * Don't forget to use unique names there as well.
	 *
	 * @param array $loop_settings - Currently available settings array.
	 *
	 * @return array - Array with all the settings that plugin should store / check.
	 *
	 * @since latest
	 */
	public static function settings_loop( array $loop_settings ): array {
		array_push( $loop_settings, self::POLICY_SETTINGS_NAME );

		return $loop_settings;
	}
```

```
	/**
	 * The default policy for the given method.
	 * Sometimes plugin may need to know the default configuration for the methods (user tries to disable all methods, there are no methods setting etc.)
	 * That gives the ability to decide should the method be enabled or disabled by default in policy settings page.
	 *
	 * @param array $output - The array with output values.
	 *
	 * @return array
	 *
	 * @since latest
	 */
	public static function return_default_selection( array $output ) {

		$output[ self::POLICY_SETTINGS_NAME ] = (true|false);

		return $output;
	}
```

Main methods also include interface methods achieved by implementing wizard steps class. It has access to the following hooks:

```
	\add_filter( WP_2FA_PREFIX . 'methods_modal_options', array( __CLASS__, 'main_method_option' ), 10, 2 );
	\add_action( WP_2FA_PREFIX . 'modal_methods', array( __CLASS__, 'main_method_modal_configure' ) );
	\add_filter( WP_2FA_PREFIX . 'methods_re_configure', array( __CLASS__, 'main_method_re_configure' ), 10, 2 );
	\add_filter( WP_2FA_PREFIX . 'methods_settings', array( __CLASS__, 'main_method_wizard_settings' ), 10, 4 );
	\add_action( WP_2FA_PREFIX . 'login_form', array( __CLASS__, 'login_form' ), 10, 2 );
```

For more compatibility, that class should use `Methods_Wizards_Trait`, which gives the user ability to interact with methods. Proper ordering is one of them.

#### Filters in wizard

```
	/**
	 * Shows methods in order. Every method is called and its code and order is collected. That is used when there are no methods selected from the user.
	 *
	 * @param array - All the collected methods and their order.
	 * @param string $role - The role of the current user
	 *
	 * @since 2.6.0
	 */
	\apply_filters( WP_2FA_PREFIX . 'methods_modal_options', array(), $role );
```

This is called when user has no method selected. Showed in the modal window.

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L122)

```
	/**
	 * Add an option for external providers to add their own modal methods options.
	 *
	 * @since 2.0.0
	 */
	\do_action( WP_2FA_PREFIX . 'modal_methods' );
	
```

This hook is called when the user is presented with the modal window with all methods as options to choose from.

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L216)

```
	/**
	 * Option to re-configure the methods - all the methods are called and their order and code is collected. Then the currently selected method is positioned on top and methods are shown in order. That is called in the user profile page.
	 *
	 * @param array - All the collected methods and their order.
	 * @param string $role - The role of the current user
	 *
	 * @since 2.6.0
	 */
	\apply_filters( WP_2FA_PREFIX . 'methods_re_configure', array(), $role );
```

This hook is called when user decides to re-configure (switch to another or update the current one) method.

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L86)

```
	/**
	 * Shows methods in order. Every method is called and its code and order is collected. Used in the wizards.
	 *
	 * @param array - All the collected methods and their order.
	 * @param bool - Is that a setup wizard call or not?
	 * @param string - Additional HTML data attribute.
	 * @param string $role - The role, that is when global settings of the plugin are selected.
	 *
	 * @since 2.6.0
	 */
	\apply_filters( WP_2FA_PREFIX . 'methods_settings', array(), $setup_wizard, $data_role, $role );
```

This hook is called when the methods settings are presented to the administrator.

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L154)

```
	/**
	 * Allows 3rd parties to render their own 2FA "login" form.
	 *
	 * @param \WP_User $user - User for which the login form is shown.
	 * @param string $provider - The name of the provider.
	 *
	 * @since 2.0.0
	 */
	do_action( WP_2FA_PREFIX . 'login_form', $user, $provider );
```

This hook is after user is logging in (their username and pass is collected) and user have to finish the process using the method challenge.


[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L305)

### Backup methods extending

In order to create a new backup method in WP2FA you need the following:

*Note:*: important note about the naming convention used in this document - **method**, **provider** and **extension** meaning the same thing.

A new class with the namespace starting with `\WP2FA\Methods`
The method should have unique slug which can be used to separate it from other methods. If that slug is in use, the last added method with given slug will take precedence.

and implementing the following methods:

static method named `init` responsible for initializing all of the hooks for proper method workflow.

The method must implement the following methods hooks (this is an example code, you can have this implemented wherever you like as long as they are accessible for the PHP and WP):

The code below will presume you have the following set (you can achieve this the way you prefer):
self::BACKUP_METHOD_META_KEY - The name of the meta key stored for the given user.

*Example:*

```
const BACKUP_METHOD_META_KEY = 'backup_method_user_meta_name';
```

and
self::METHOD_NAME - The name of the method.

*Example:*

```
const METHOD_NAME = 'new_2fa_method';
```

```
	\add_filter( WP_2FA_PREFIX . 'backup_methods_list', array( __CLASS__, 'add_backup_method' ), 10, 2 );

	\add_filter( WP_2FA_PREFIX . 'backup_methods_enabled', array( __CLASS__, 'check_backup_method_for_role' ), 10, 2 );

	\add_action( WP_2FA_PREFIX . 'remove_backup_methods_for_user', array( __CLASS__, 'remove_backup_methods_for_user' ) );

	\add_filter( WP_2FA_PREFIX . 'loop_settings', array( __CLASS__, 'settings_loop' ), 10, 2 );

	\add_filter( WP_2FA_PREFIX . 'default_settings', array( __CLASS__, 'add_default_settings' ) );

	\add_filter( WP_2FA_PREFIX . 'providers', array( __CLASS__, 'add_provider' ) );

	\add_filter( WP_2FA_PREFIX . 'providers_translated_names', array( __CLASS__, 'name_translated' ) );

	\add_filter( \WP_2FA_PREFIX . 'backup_methods_meta_keys', array( __CLASS__, 'meta_user_backup_method' ) );

	\add_filter( \WP_2FA_PREFIX . 'backup_methods_report_settings', array( __CLASS__, 'backup_method_report_settings' ) );
```

Following is more detailed explanation of every hook and its code implementation

```
	/**
	 * Add the method to the existing backup methods array.
	 * Array must contain the following:
	 * [backup_method_slug] - [
	 *          'wizard-step' - The name (HTML friendly as it will be used in the tags) of the plugin wizard step.
	 *          'button_name' - The button name shown in the wizard - language translated.
	 * ]
	 *
	 * @param array $backup_methods - Array with the currently supported backup methods.
	 *
	 * @return array - Array of backup methods.
	 *
	 * @since latest
	 */
	public static function add_backup_method( array $backup_methods ): array {
		return array_merge(
			$backup_methods,
			array(
				self::METHOD_NAME => array(
					'wizard-step' => '2fa-wizard-' . self::METHOD_NAME,
					'button_name' => sprintf(
						esc_html__( 'Login with a backup method', 'extend-2fa-methods' )
					),
				),
			)
		);
	}
```

```
	/**
	 * Changes the user enabled backup methods array - removes the method if it is not enabled.
	 * That is for role, the user object is used to extract the role.
	 * UserHelper is a helper class available in 2FA plugin which could be used for role extraction or other user operations.
	 *
	 * @param array    $backup_methods - Array with all backup methods available to user.
	 * @param \WP_User $user           - User to check for is that method enabled.
	 *
	 * @return array - Array with all backup methods enabled for the role.
	 *
	 * @since latest
	 */
	public static function check_backup_method_for_role( array $backup_methods, \WP_User $user ): array {

		// Implement the necessarily checks based on the role provided, implement some caching, as there could be multiple calls.
		$enabled = self::are_backup_method_enabled_for_role( User_Helper::get_user_role( $user ) );

		if ( ! $enabled ) {
			unset( $backup_methods[ self::METHOD_NAME ] );
		}

		return $backup_methods;
	}
```

```
	/**
	 * Removes the backup method (user meta key) from the database.
	 *
	 * @param \WP_User $user - The user to remove method for.
	 *
	 * @return void
	 *
	 * @since latest
	 */
	public static function remove_backup_methods_for_user( $user ) {
		if ( ! Settings::is_provider_enabled_for_role( User_Helper::get_user_role( $user ), self::METHOD_NAME ) ) {
			\delete_user_meta( $user->ID, self::BACKUP_METHOD_META_KEY );
		}
	}
```

```
	/**
	 * Add extension settings to the loop array.
	 * Plugin is using the method policy settings - these are settings related to the user specific iteration with the given method. These are shown in the policy settings screen of the plugin and related to users and (in paid version) user roles.
	 * Using these settings the admin can enable / disable the plugin, set additional options or remove them.
	 * This only sets names of the policy settings, there is no need to provide values for them - plugin will check for these settings names when settings are saved, and if found, the proper values will be stored against given names.
	 * Don't forget to use unique names there as well.
	 *
	 * @param array $loop_settings - Currently available settings array.
	 *
	 * @return array - Array with all the settings that plugin should store / check.
	 *
	 * @since latest
	 */
	public static function settings_loop( array $loop_settings ): array {
		array_push( $loop_settings, self::POLICY_SETTINGS_NAME );

		return $loop_settings;
	}
```

```
	/**
	 * Adds the method default settings to the main plugin settings.
	 * Receives array with all of the (currently) registered settings, and gives the ability for the method to add its own settings to global array.
	 * Use this method to add the settings default values your method needs, that way the plugin will take care of fall back the them if nothing is explicitly set.
	 *
	 * @param array $default_settings - Array with method default settings.
	 *
	 * @return array - Array with plugin settings.
	 *
	 * @since latest
	 */
	public static function add_default_settings( array $default_settings ): array {
		$default_settings[ self::METHOD_NAME . '_setting_name' ] = 'default_value';

		return $default_settings;
	}
```

```
	/**
	 * Adds the method to the global providers / methods supported by the plugin.
	 * Receives array with all of the registered methods, use it to add / register your method giving its slug
	 *
	 * @param array $providers - Array with all currently supported providers.
	 *
	 * @return array - Array with all of the registered methods
	 *
	 * @since latest
	 */
	public static function add_provider( array $providers ): array {
		array_push( $providers, self::METHOD_NAME );

		return $providers;
	}
```

```
	/**
	 * Adds translatable name
	 * Receives an array with all of the registered methods / providers.
	 * Using this you can add translatable name of your method to the global methods array supported by the plugin.
	 *
	 * @param array $providers - Array with all currently supported providers and their translated names.
	 *
	 * @return array - Array with all registered methods / providers
	 *
	 * @since latest
	 */
	public static function name_translated( array $providers ): array {
		$providers[ self::METHOD_NAME ] = esc_html__( 'Method Name', 'extend-2fa-methods' );

		return $providers;
	}
```

```
	/**
	 * Marks methods as secondary. Backup methods are secondary methods in plugin, so mark yours as one.
	 *
	 * @return boolean
	 *
	 * @since latest
	 */
	public static function is_secondary() {
		return true;
	}
```

```
	/**
	 * Fulfills the array of the report with all the values needed. Reports related - used for checking the user meta and determine if the given method is enabled or not.
	 *
	 * @param array $settings - The currently collected settings.
	 *
	 * @return array
	 *
	 * @since latest
	 */
	public static function backup_method_report_settings( array $settings ): array {
		$settings[] = array(
			'user_meta_key' => self::BACKUP_METHOD_META_KEY,
			'slug'          => self::METHOD_NAME,
		);
		return $settings;
	}
```

```
	/**
	 * Adds the backup method meta key which will be check in the users meta and shown in the report if enabled.
	 *
	 * @param array $backup_meta_keys - Array with the collected meta keys form backup methods.
	 *
	 * @return array
	 *
	 * @since latest
	 */
	public static function meta_user_backup_method( array $backup_meta_keys ) {
		$backup_meta_keys[] = self::BACKUP_METHOD_META_KEY;

		return $backup_meta_keys;
	}
```

### Attach methods to the main plugin

In order to be visible to the main plugin, the new methods must be attached to the plugin's logic. You can achieve this by using hte following action:
`wp_2fa_add_to_class_map`

Example:

```
\add_action(
	'wp_2fa_add_to_class_map',
	/**
	* Adds sensors classes to the Class Helper
	*
	* @return void
	*
	* @since latest
	*/
	function () {
		Classes_Helper::add_to_class_map(
			array(
				Main_Method::class => ( new \ReflectionClass( Main_Method::class ) )->getFileName(),
			)
		);
	}
);
```

Where:
`Classes_Helper` is core 2FA plugin class responsible for some PHP class operations.
`Main_Method::class` is the name of your class ( method you are implementing )

This action is called right before some extractions are performed using the `Classes_Helper` class, which is point you need to attach your custom logic.
The method accepts array with class names as keys and absolute paths as values.
