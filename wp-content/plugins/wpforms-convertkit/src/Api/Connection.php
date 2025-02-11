<?php

namespace WPFormsConvertKit\Api;

use Exception;
use WPFormsConvertKit\Vendor\ConvertKit_API\ConvertKit_API;

/**
 * Class Connection.
 *
 * @since 1.0.0
 */
class Connection extends ConvertKit_API {

	/**
	 * Failed requests list.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $failed_requests = [];

	/**
	 * Create tags.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tags Array of tags.
	 */
	public function create_tags( array $tags ) {

		$existed_tags = array_values( $this->get_tags() );
		$tags         = array_filter(
			$tags,
			function ( $tag ) use ( $existed_tags ) {
				return ! in_array( $tag, $existed_tags, true );
			}
		);

		if ( empty( $tags ) ) {
			return [];
		}

		$this->call_parent_method( 'create_tags', $tags );
	}

	/**
	 * Create custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $labels Custom field names.
	 */
	public function create_custom_fields( array $labels ): array {

		$custom_fields = $this->call_parent_method( 'create_custom_fields', $labels );

		if ( empty( $custom_fields ) ) {
			return [];
		}

		if ( is_object( $custom_fields ) ) {
			$custom_fields = [ $custom_fields ];
		}

		return wp_list_pluck( $custom_fields, 'label', 'key' );
	}

	/**
	 * Get custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_custom_fields(): array {

		$response = $this->call_parent_method( 'get_custom_fields' );

		if ( ! is_object( $response ) || ! property_exists( $response, 'custom_fields' ) ) {
			return [];
		}

		return wp_list_pluck( $response->custom_fields, 'label', 'key' );
	}

	/**
	 * Gets all forms.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_forms(): array {

		$forms = $this->call_parent_method( 'get_forms' );

		if ( empty( $forms ) ) {
			return [];
		}

		return wp_list_pluck( $forms, 'name', 'id' );
	}

	/**
	 * Gets all tags.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_tags(): array {

		$tags = $this->call_parent_method( 'get_tags' );

		if ( empty( $tags ) ) {
			return [];
		}

		return wp_list_pluck( $tags, 'name', 'id' );
	}

	/**
	 * Gets the current account.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_account(): array {

		$account_data = $this->call_parent_method( 'get_account' );

		if ( empty( $account_data ) ) {
			return [];
		}

		return (array) $account_data;
	}

	/**
	 * Call parent method in the try/catch construction to prevent SDK exceptions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method_name Method name.
	 * @param mixed  ...$args     Method arguments.
	 *
	 * @return mixed Returns null when exception is thrown.
	 */
	private function call_parent_method( string $method_name, ...$args ) {

		try {
			return parent::$method_name( ...$args );
		} catch ( Exception $exception ) {
			wpforms_log(
				'Error while connecting to Kit API',
				[
					'message' => $exception->getMessage(),
				],
				[
					'type' => [ 'provider', 'error' ],
				]
			);

			$this->failed_requests[ $method_name ] = [
				'code'    => $exception->getCode(),
				'message' => $exception->getMessage(),
			];

			return null;
		}
	}

	/**
	 * We can check if API key is valid by trying to send requests where API key is required (e.g. get_forms).
	 * In case if API key is invalid, we will get an exception and return it here.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function check_valid_api_key() {

		$method_name = 'get_forms';

		$this->call_parent_method( $method_name );

		return $this->failed_requests[ $method_name ] ?? [];
	}
}
