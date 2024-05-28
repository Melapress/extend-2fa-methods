In order to create a new method in WP2FA you need the following:

*Note:*: important note about the naming convention used in this document - **method**, **provider** and **extension** meaning the same thing.

A new class with the namespace starting with `\WP2FA\Methods`
The method should have unique slug which can be used to separate it from other methods. If that slug is in use, the last added method with given slug will take precedence.

and implementing the following methods:

static method named `init` responsible for initializing all of the hooks reposonsible for proper method workflow.

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
		$providers[ self::METHOD_NAME ] = esc_html__( 'Method Name', 'wp-2fa' );

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