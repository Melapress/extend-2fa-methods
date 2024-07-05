# WP 2FA by Melapress<!-- omit from toc -->

Easy to use two-factor authentication for your WordPress logins.

https://melapress.com/wordpress-2fa/

## How to add supplementary 2FA methods: Documentation and information<!-- omit from toc -->

WP 2FA offers several 2FA methods straight out of the box, including:

* TOTP 
* Email HOTP
* Email link
* SMS (via a number of third-party services)
* Push notifications (via a third-party service)

It also offers a selection of secondary authentication methods, which are used as a backup method should the primary method become unavailable (such as the user’s phone running out of battery).

Using the documentation presented below, you can add your own primary and secondary authentication methods. Whether you are looking to offer users more methods or integrate methods you already use within your organization, this documentation will help you get there.

## Table of contents<!-- omit from toc -->

- [About this documentation](#about-this-documentation)
- [Adding supplementary primary 2FA methods](#adding-supplementary-primary-2fa-methods)
	- [Implementation](#implementation)
		- [Add a translatable name](#add-a-translatable-name)
		- [Add the method to the global providers/methods supported by the plugin](#add-the-method-to-the-global-providersmethods-supported-by-the-plugin)
		- [Add the method's default settings to the main plugin settings](#add-the-methods-default-settings-to-the-main-plugin-settings)
		- [Add extension settings to the loop array](#add-extension-settings-to-the-loop-array)
		- [Set the default policy for the given method](#set-the-default-policy-for-the-given-method)
	- [Wizard filters and hooks](#wizard-filters-and-hooks)
		- [methods\_modal\_options](#methods_modal_options)
		- [modal\_methods](#modal_methods)
		- [methods\_re\_configure](#methods_re_configure)
		- [methods\_settings](#methods_settings)
		- [login\_form](#login_form)
	- [Adding supplementary 2FA backup methods](#adding-supplementary-2fa-backup-methods)
	- [Implementation](#implementation-1)
	- [Implementing hooks](#implementing-hooks)
		- [add\_backup\_method](#add_backup_method)
		- [check\_backup\_method\_for\_role](#check_backup_method_for_role)
		- [remove\_backup\_methods\_for\_user](#remove_backup_methods_for_user)
		- [settings\_loop](#settings_loop)
		- [add\_default\_settings](#add_default_settings)
		- [add\_provider](#add_provider)
		- [name\_translated](#name_translated)
		- [is\_secondary](#is_secondary)
		- [backup\_method\_report\_settings](#backup_method_report_settings)
		- [meta\_user\_backup\_method](#meta_user_backup_method)
	- [Add methods to the plugin](#add-methods-to-the-plugin)


## About this documentation

This documentation is split into two parts. The first part explains how to add primary methods, while the second part explains how to add secondary (backup) methods.

**Note**: For the purposes of this document, the following definitions apply:

* **Provider**: This is the 2FA service provider. For example, Authy.
* **Method**: This is the custom extension that you develop which will integrate with WP 2FA. For example: My 2FA Method.
* **Extension**: This is the 2FA method that the user can choose when configuring 2FA. For example, Push notification.

Do note that the names have no bearing on the functionality and as such, you can choose whichever names suit your requirements.

## Adding supplementary primary 2FA methods

To create a new primary 2FA method in WP 2FA, you will need the following:

A new class with a namespace that starts with \WP2FA\Methods. The method should have a unique slug that can be used to separate it from other methods. If the given slug is in use, the last added method will take precedence.

This new class will implement the following methods:

`init`: a static method responsible for initializing all of the hooks for proper method workflow

- self::POLICY_SETTINGS_NAME: Where POLICY_SETTINGS_NAME is the name of the policy setting that will be used to store the method’s configuration. Usage: const POLICY_SETTINGS_NAME = 'enable_method';

- self::METHOD_NAME: Where METHOD_NAME is the name of the method’s slug. Usage: const METHOD_NAME = 'new_2fa_method';

The `init` method is used to implement both self::POLICY_SETTINGS_NAME and self::METHOD_NAME, both of which need to be accessible by PHP and WordPress.

### Implementation

The WP 2FA uses methods within a class to implement the hooks needed to achieve the required functionality. As a developer, you can implement these as they best fit your requirements.

Below is a sample implementation:


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

In the next section, we will look at all available hooks in more detail, including their code implementation.

#### Add a translatable name

```
	/**
	 * This hook adds a translatable name
	 * It receives an array with all of the registered methods/providers.
	 * When using this hook, you can add a translatable name of your method to the global methods array supported by the plugin.
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
#### Add the method to the global providers/methods supported by the plugin

```
	/**
	 * This hook adds the method to the global providers/methods supported by the plugin.
	 * It receives an array with all of the registered methods. Use it to add/register your method, giving its slug
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
#### Add the method's default settings to the main plugin settings

```
	/**
	 * This hook adds the method default settings to the main plugin settings.
	 * It receives an array with all of the (currently) registered settings, and provides the ability for the method to add its own settings to global array.
	 * Use this method to add the settings' default values your method needs. This way, the plugin will handle fallback if nothing is explicitly set.
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
#### Add extension settings to the loop array

```
	/**
	 * This hook adds the extension settings to the loop array.
	 * WP 2FA uses policies to apply user settings, which apply to the user-specific iteration within the given method. You can view these settings in WP 2FA’s policy settings screen as related to users and, in the paid version, user roles.
	 * By using these settings, you can enable or disable the plugin, set additional options, or remove them.
	 * This hook only sets the names of the policy settings, and as such, there is no need to provide values. WP 2FA will check for these settings' names when settings are saved, and if found, the proper values will be stored against the given names.
	 * Don't forget to use unique names here as well.
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
#### Set the default policy for the given method

```
	/**
	 * This hook sets the default policy for the given method.
	 * Sometimes WP 2FA may need to know the default configuration for the methods, such as when a user tries to disable all methods, settings, etc.
	 * This allows you to decide whether the method should be enabled or disabled by default on the policy settings page.
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

Main methods also include interface methods, which can be added by implementing the wizard steps class. They have access to the following hooks:

```
	\add_filter( WP_2FA_PREFIX . 'methods_modal_options', array( __CLASS__, 'main_method_option' ), 10, 2 );
	\add_action( WP_2FA_PREFIX . 'modal_methods', array( __CLASS__, 'main_method_modal_configure' ) );
	\add_filter( WP_2FA_PREFIX . 'methods_re_configure', array( __CLASS__, 'main_method_re_configure' ), 10, 2 );
	\add_filter( WP_2FA_PREFIX . 'methods_settings', array( __CLASS__, 'main_method_wizard_settings' ), 10, 4 );
	\add_action( WP_2FA_PREFIX . 'login_form', array( __CLASS__, 'login_form' ), 10, 2 );
```

For increased compatibility, the class should use the `Methods_Wizards_Trait` method, which gives the user the ability to interact with methods. This includes proper ordering of the methods as they appear to the end user.

### Wizard filters and hooks

#### methods_modal_options
This filter is called when the user has no method selected. Showed in the modal window.

```
	/**
	 * Shows all 2FA methods in order. Every method is called, and its code and order are collected. This filter is used when the user has not selected any 2FA methods.
	 *
	 * @param array - All the collected methods and their order.
	 * @param string $role - The role of the current user
	 *
	 * @since 2.6.0
	 */
	\apply_filters( WP_2FA_PREFIX . 'methods_modal_options', array(), $role );
```

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L122)

#### modal_methods
This hook is called when the user is presented with the modal window with all methods as available options.

```
	/**
	 * Add an option for external providers to add their own modal methods options.
	 *
	 * @since 2.0.0
	 */
	\do_action( WP_2FA_PREFIX . 'modal_methods' );

```

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L216)

#### methods_re_configure
This hook is called when the user chooses to reconfigure their 2FA method (switch to a different method or update the current one).

```
	/**
	 * Option to re-configure the methods - all the methods are called, and their order and codes are collected. Then the currently selected method is positioned on top, and the remaining methods are shown in order. That is called in the user profile page.
	 *
	 * @param array - All the collected methods and their order.
	 * @param string $role - The role of the current user
	 *
	 * @since 2.6.0
	 */
	\apply_filters( WP_2FA_PREFIX . 'methods_re_configure', array(), $role );
```

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L86)

#### methods_settings
This hook is called when the method's settings are presented to the administrator.

```
	/**
	 * Shows all 2FA methods in order. Every method is called, and its code and order are collected. This hook is used in the wizards.
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

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L154)

#### login_form
This hook is used after the user completes their first authentication method using their username and password. It forces the user to finish the login process using the 2FA method challenge.

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

[Usage example code](https://github.com/wpwhitesecurity/extend-2fa-methods/blob/b89592bdaee494a8da35a32e857c59ad6a18e027/classes/wizards/class-main-method-wizard-steps.php#L305)

### Adding supplementary 2FA backup methods

Backup 2FA methods enable users to use 2FA even if their primary 2FA method becomes unavailable (such as if their phone runs out of battery. By configuring a backup 2FA method, you can ensure users are still able to safely log in to WordPress without requiring the help of your support team.

To create a new secondary 2FA method in WP 2FA, you will need the following:

A new class with a namespace that starts with `\WP2FA\Methods`. This class should be separate from the primary method class, if this is used. The method should have a unique slug that can be used to separate it from other methods, including the primary method if this is used. If the given slug is in use, the last added method will take precedence.

This new class will implement the following methods:

`init`: a static method responsible for initializing all of the hooks for proper method workflow

- self::BACKUP_METHOD_META_KEY: Where BACKUP_METHOD_META_KEY is the name of the meta key stored for the given user. Usage: const BACKUP_METHOD_META_KEY = 'backup_method_user_meta_name';;

- self::POLICY_SETTINGS_NAME - same as the policy settings name in the main method. Usage: const POLICY_SETTINGS_NAME = 'enable_method';
- self::METHOD_NAME: Where METHOD_NAME is the name of the method. Usage: const METHOD_NAME = 'secondary_2fa_method';

The `init` method is used to implement both self::BACKUP_METHOD_META_KEY and self::METHOD_NAME, both of which need to be accessible by PHP and WordPress.

### Implementation
The WP 2FA uses methods within a class to implement the hooks needed to achieve the required functionality. As a developer, you can implement these as they best fit your requirements.

*Below is a sample implementation:*

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

In the next section, we will look at all available hooks in more detail, including their code implementation.

### Implementing hooks

#### add_backup_method
This hook adds the backup method to this plugin’s list of backup methods.

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

#### check_backup_method_for_role
This hook checks if the backup method should be made available to a specific user role.

```
	/**
	 * Changes the user-enabled backup methods array and removes the method if it is not enabled.
	 * This hook is used for secondary methods enabled by role. The user object is used to extract the role.
	 * UserHelper is a helper class available in WP 2FA that can be used for role extraction or other user operations.
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

#### remove_backup_methods_for_user
This hook removes specific backup methods for a given user.

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

#### settings_loop
This hook passes an array that the method extends with settings values. When the admin clicks the Save button from the UI, the values are checked and then stored.

```
	/**
	 * Add extension settings to the loop array.
	 * WP 2FA uses the method policy settings to store settings related to the user-specific iteration with a given method. These are shown in the plugin's policy settings screen and relate to users and (in premium version) user roles.
	 * By using these settings, the admin can set additional method options, or remove them.
	 * This only sets the name of the policy settings, there is no need to provide any values as the plugin will check for these settings names when settings are saved, and if found, the proper values will be stored against given names.
	 * Don't forget to use unique names here as well.
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

#### add_default_settings
This hook defines the method’s default settings

```
	/**
	 * Adds the method default settings to the main plugin settings.
	 * Receives an array with all of the (currently) registered settings and enables the method to add its own settings to the global array.
	 * Use this method to define the settings' default values your method will use if nothing is explicitly set.
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

#### add_provider
This hook adds the provider defined in the method to the list of providers/methods supported by the plugin

```
	/**
	 * Adds the method to the global providers/methods list supported by the plugin.
	 * Receives array with all of the registered methods, use it to add/register your method giving its slug
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

#### name_translated
This hook sets the proper values in the language files so the name can be translated to a different language.

```
	/**
	 * Adds translatable name
	 * Receives an array with all of the registered methods/providers.
	 * By using this hook, you can add the translatable name of your method to the global methods array supported by the plugin.
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

#### is_secondary
This hook lists the 2FA method as a backup method

```
	/**
	 * Lists the 2FA method as a backup method
	 *
	 * @return boolean
	 *
	 * @since latest
	 */
	public static function is_secondary() {
		return true;
	}
```

#### backup_method_report_settings
This hook includes the status of backup methods in Reports

```
	/**
	 * Populates the report array with all required values. It is used to check the user meta and determine whether the given method is enabled or not when running user 2FA reports.
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

#### meta_user_backup_method
Adds an entry in the user’s meta table, which is used for WP 2FA reporting purposes.

```
	/**
	 * Adds the backup method meta key, which will be checked in the user's meta and shown in the report if enabled.
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

### Add methods to the plugin

In order to be visible to the plugin, the new methods must be attached to the plugin's logic. You can achieve this by using the `wp_2fa_add_to_class_map` action. This tells WP 2FA that there are extensions that need to be loaded.

Sample code:

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

Where `Classes_Helper` is the core 2FA plugin class responsible for several PHP class operations. The `Main_Method::class` is the name of your class (the method you are implementing )

This action is called right before extractions are performed using the `Classes_Helper` class, at which point you need to attach your custom logic. The method accepts an array with class names as keys and absolute paths as values.
