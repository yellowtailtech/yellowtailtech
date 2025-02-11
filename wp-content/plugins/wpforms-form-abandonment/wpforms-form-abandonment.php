<?php
/**
 * Plugin Name:       WPForms Form Abandonment
 * Plugin URI:        https://wpforms.com
 * Description:       Form abandonment lead capture with WPForms.
 * Requires at least: 5.5
 * Requires PHP:      7.0
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.12.0
 * Text Domain:       wpforms-form-abandonment
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

use WPFormsFormAbandonment\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
const WPFORMS_FORM_ABANDONMENT_VERSION = '1.12.0';

/**
 * Plugin file.
 *
 * @since 1.0.0
 */
const WPFORMS_FORM_ABANDONMENT_FILE = __FILE__;

/**
 * Plugin path.
 *
 * @since 1.0.0
 */
define( 'WPFORMS_FORM_ABANDONMENT_PATH', plugin_dir_path( WPFORMS_FORM_ABANDONMENT_FILE ) );

/**
 * Plugin URL.
 *
 * @since 1.0.0
 */
define( 'WPFORMS_FORM_ABANDONMENT_URL', plugin_dir_url( WPFORMS_FORM_ABANDONMENT_FILE ) );

/**
 * Check addon requirements.
 *
 * @since 1.5.0
 * @since 1.8.0 Uses requirements feature.
 */
function wpforms_form_abandonment_load() {

	$requirements = [
		'file'    => WPFORMS_FORM_ABANDONMENT_FILE,
		'wpforms' => '1.8.9',
	];

	if ( ! function_exists( 'wpforms_requirements' ) || ! wpforms_requirements( $requirements ) ) {
		return;
	}

	wpforms_form_abandonment();
}

add_action( 'wpforms_loaded', 'wpforms_form_abandonment_load' );

/**
 * Get the instance of the addon main class.
 *
 * @since 1.0.0
 *
 * @return Plugin
 */
function wpforms_form_abandonment() {

	require_once WPFORMS_FORM_ABANDONMENT_PATH . 'vendor/autoload.php';

	return Plugin::get_instance();
}
