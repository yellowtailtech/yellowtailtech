<?php
/**
 * Plugin Name:       WPForms Kit
 * Plugin URI:        https://wpforms.com
 * Description:       Kit integration with WPForms.
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.1.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Text Domain:       wpforms-convertkit
 * Domain Path:       /languages
 *
 * WPForms is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WPForms is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WPForms. If not, see <https://www.gnu.org/licenses/>.
 */

use WPFormsConvertKit\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Version.
 *
 * @since 1.0.0
 */
const WPFORMS_CONVERTKIT_VERSION = '1.1.0';

/**
 * Plugin FILE.
 *
 * @since 1.0.0
 */
const WPFORMS_CONVERTKIT_FILE = __FILE__;

/**
 * Plugin PATH.
 *
 * @since 1.0.0
 */
define( 'WPFORMS_CONVERTKIT_PATH', plugin_dir_path( WPFORMS_CONVERTKIT_FILE ) );

/**
 * Plugin URL.
 *
 * @since 1.0.0
 */
define( 'WPFORMS_CONVERTKIT_URL', plugin_dir_url( WPFORMS_CONVERTKIT_FILE ) );

/**
 * Load the plugin files.
 *
 * @since 1.0.0
 */
function wpforms_convertkit_load() {

	$requirements = [
		'file'    => WPFORMS_CONVERTKIT_FILE,
		'wpforms' => '1.9.1',
	];

	if ( ! function_exists( 'wpforms_requirements' ) || ! wpforms_requirements( $requirements ) ) {
		return;
	}

	wpforms_convertkit();
}
add_action( 'wpforms_loaded', 'wpforms_convertkit_load' );

/**
 * Get the instance of the `\WPFormsConvertKit\Plugin` class.
 * This function is useful for quickly grabbing data used throughout the plugin.
 *
 * @since 1.0.0
 *
 * @return Plugin
 */
function wpforms_convertkit(): Plugin {

	// Actually, load the ConvertKit addon now, as we met all the requirements.
	require_once WPFORMS_CONVERTKIT_PATH . 'vendor/autoload.php';

	return Plugin::get_instance();
}
