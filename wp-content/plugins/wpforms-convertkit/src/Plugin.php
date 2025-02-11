<?php

namespace WPFormsConvertKit;

use stdClass;
use WPForms_Updater;
use WPForms\Providers\Providers;
use WPFormsConvertKit\Provider\Core;
use WPFormsConvertKit\Provider\Account;
use WPFormsConvertKit\Provider\Sanitizer;
use WPFormsConvertKit\Tasks\ProcessActionTask;

/**
 * WPForms Kit main class.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Provider slug.
	 *
	 * @since 1.0.0
	 */
	const SLUG = 'convertkit';

	/**
	 * Account class.
	 *
	 * @since 1.0.0
	 *
	 * @var Account
	 */
	private $account;

	/**
	 * Sanitizer class.
	 *
	 * @since 1.0.0
	 *
	 * @var Sanitizer
	 */
	private $sanitizer;

	/**
	 * Plugin constructor.
	 * This method is empty and private, so others can't initialize a new instance of it.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 */
	private function init() {

		$this->hooks();
		$this->load_dependencies();
	}

	/**
	 * Plugin hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {

		add_filter( 'wpforms_helpers_templates_include_html_located', [ $this, 'register' ], 10, 2 );
	}

	/**
	 * Get property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property_name Property name.
	 *
	 * @return object
	 */
	public function get( string $property_name ) {

		return property_exists( $this, $property_name ) ? $this->{$property_name} : new stdClass();
	}

	/**
	 * Get a single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new Plugin();

			$instance->init();
		}

		return $instance;
	}

	/**
	 * All the actual plugin loading is done here.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {

		$this->account   = new Account();
		$this->sanitizer = new Sanitizer();

		( new ProcessActionTask() )->hooks();

		Providers::get_instance()->register(
			Core::get_instance()
		);
	}

	/**
	 * Register addon location.
	 *
	 * @since 1.0.0
	 *
	 * @param string $located  Template location.
	 * @param string $template Template.
	 *
	 * @return string
	 */
	public function register( $located, string $template ): string {

		// Checking if `$template` is an absolute path and passed from this plugin.
		if (
			strpos( $template, WPFORMS_CONVERTKIT_PATH ) === 0 &&
			is_readable( $template )
		) {
			return $template;
		}

		return $located;
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 * @deprecated 1.1.0
	 *
	 * @todo Remove with core 1.9.2
	 *
	 * @param string $key License key.
	 */
	public function updater( string $key ) {

		_deprecated_function( __METHOD__, '1.1.0 of the WPForms Kit plugin' );

		new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Kit',
				'plugin_slug' => 'wpforms-convertkit',
				'plugin_path' => plugin_basename( WPFORMS_CONVERTKIT_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_CONVERTKIT_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_CONVERTKIT_VERSION,
				'key'         => $key,
			]
		);
	}
}
