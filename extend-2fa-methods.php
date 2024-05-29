<?php
/**
 * WP 2FA - Extending methods example.
 *
 * @copyright Copyright (C) 2013-2024, Melapress - support@melapress.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: WP 2FA - Extending methods example
 * Version:     1.0.0
 * Plugin URI:  https://melapress.com/
 * Description: Easily add an additional layer of security to your WordPress login pages. Enable Two-Factor Authentication for you and all your website users with this easy to use plugin.
 * Author:      Melapress
 * Author URI:  https://melapress.com/
 * Text Domain: extend-2fa-methods
 * Domain Path: /languages/
 * License:     GPL v3
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Network: true
 *
 * @package WP2FA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use WP2FA\Methods\Main_Method;
use WP2FA\Admin\Helpers\Classes_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_EXT_BASE', plugin_basename( __FILE__ ) );
define( 'WP_EXT_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( WP_EXT_BASE ) . DIRECTORY_SEPARATOR );

// Require Composer autoloader if it exists.
if ( file_exists( WP_EXT_PATH . 'vendor/autoload.php' ) ) {
	require_once WP_EXT_PATH . 'vendor/autoload.php';
}

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
