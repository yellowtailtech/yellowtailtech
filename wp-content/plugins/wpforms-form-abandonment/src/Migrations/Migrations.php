<?php

namespace WPFormsFormAbandonment\Migrations;

use WPForms\Migrations\Base;

/**
 * Class Migrations handles addon upgrade routines.
 *
 * @since 1.7.0
 */
class Migrations extends Base {

	/**
	 * WP option name to store the migration versions.
	 *
	 * @since 1.7.0
	 */
	const MIGRATED_OPTION_NAME = 'wpforms_form_abandonment_versions';

	/**
	 * Current plugin version.
	 *
	 * @since 1.7.0
	 */
	const CURRENT_VERSION = WPFORMS_FORM_ABANDONMENT_VERSION;

	/**
	 * Name of plugin used in log messages.
	 *
	 * @since 1.7.0
	 */
	const PLUGIN_NAME = 'WPForms Form Abandonment';

	/**
	 * Upgrade classes.
	 *
	 * @since 1.7.0
	 */
	const UPGRADE_CLASSES = [
		'Upgrade170',
		'Upgrade1100',
	];
}
