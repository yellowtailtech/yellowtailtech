<?php
/**
 * Plugin Name:       WPForms Zapier
 * Plugin URI:        https://wpforms.com
 * Description:       Zapier integration with WPForms.
 * Requires at least: 5.5
 * Requires PHP:      7.0
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.6.0
 * Text Domain:       wpforms-zapier
 * Domain Path:       languages
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

use WPFormsZapier\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.6.0
 */
const WPFORMS_ZAPIER_VERSION = '1.6.0';

/**
 * Plugin file.
 *
 * @since 1.6.0
 */
const WPFORMS_ZAPIER_FILE = __FILE__;

/**
 * Plugin path.
 *
 * @since 1.6.0
 */
define( 'WPFORMS_ZAPIER_PATH', plugin_dir_path( WPFORMS_ZAPIER_FILE ) );

/**
 * Plugin URL.
 *
 * @since 1.6.0
 */
define( 'WPFORMS_ZAPIER_URL', plugin_dir_url( WPFORMS_ZAPIER_FILE ) );

/**
 * Check addon requirements.
 *
 * @since 1.0.0
 * @since 1.6.0 Renamed from wpforms_zapier_required to wpforms_zapier_load.
 * @since 1.6.0 Uses requirements feature.
 */
function wpforms_zapier_load() {

	$requirements = [
		'file'    => WPFORMS_ZAPIER_FILE,
		'wpforms' => '1.8.4',
	];

	if ( ! function_exists( 'wpforms_requirements' ) || ! wpforms_requirements( $requirements ) ) {
		return;
	}

	wpforms_zapier();
}

add_action( 'wpforms_loaded', 'wpforms_zapier_load' );

/**
 * Get the instance of the addon main class.
 *
 * @since 1.0.0
 *
 * @return Plugin;
 */
function wpforms_zapier() {

	require_once WPFORMS_ZAPIER_PATH . 'vendor/autoload.php';

	return Plugin::get_instance();
}
