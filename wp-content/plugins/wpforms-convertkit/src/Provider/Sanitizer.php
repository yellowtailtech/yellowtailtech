<?php

namespace WPFormsConvertKit\Provider;

use WPFormsConvertKit\Plugin;
use WPFormsConvertKit\Api\Connection;

/**
 * Class for sanitizing different data.
 *
 * @since 1.0.0
 */
class Sanitizer {

	/**
	 * List of custom fields existed in API.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $existing_custom_fields;

	/**
	 * Sanitize connection.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $connection_data Connection data.
	 * @param Connection $connection      Connection.
	 *
	 * @return array
	 */
	public function connection( array $connection_data, Connection $connection ): array {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'duplicate' ) {
			return $connection_data;
		}

		$this->existing_custom_fields = array_keys( $connection->get_custom_fields() );

		$connection_data = wp_parse_args(
			$connection_data,
			[
				'id'         => '',
				'name'       => '',
				'account_id' => '',
				'action'     => '',
				'email'      => -1,
			]
		);

		$connection_data['id']         = sanitize_text_field( $connection_data['id'] );
		$connection_data['name']       = sanitize_text_field( $connection_data['name'] );
		$connection_data['account_id'] = sanitize_text_field( $connection_data['account_id'] );
		$connection_data['action']     = sanitize_text_field( $connection_data['action'] );
		$connection_data['email']      = $this->sanitize_field_id( $connection_data['email'] );

		if ( $connection_data['action'] === 'remove_tags' ) {
			return $this->sanitize_remove_tags( $connection_data );
		}

		if ( $connection_data['action'] === 'subscribe' ) {
			return $this->sanitize_subscribe( $connection_data );
		}

		return $connection_data;
	}

	/**
	 * Sanitize fields ID knowing what Field ID can be 0.
	 * When field ID is set to empty string, we should return -1.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $field_id Field ID or empty string.
	 *
	 * @return int
	 */
	private function sanitize_field_id( $field_id ): int {

		return $field_id !== '' ? absint( $field_id ) : -1;
	}

	/**
	 * Sanitize fields which belongs to Remove Tags action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_data Connection data.
	 *
	 * @return array
	 */
	private function sanitize_remove_tags( array $connection_data ): array {

		$connection_data = wp_parse_args(
			$connection_data,
			[
				'tags' => [],
			]
		);

		$connection_data['tags'] = $this->sanitize_tags( $connection_data );

		return $connection_data;
	}

	/**
	 * Sanitize fields which belongs to Subscribe action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_data Connection data.
	 *
	 * @return array
	 */
	private function sanitize_subscribe( array $connection_data ): array {

		$connection_data = wp_parse_args(
			$connection_data,
			[
				'new_email'     => -1,
				'first_name'    => -1,
				'form'          => -1,
				'tags'          => [],
				'new_tags'      => '',
				'fields_meta'   => [],
				'custom_fields' => [],
			]
		);

		$sanitized_custom_fields = $this->sanitize_custom_fields( $connection_data['fields_meta'] );

		$connection_data['new_email']         = $this->sanitize_field_id( $connection_data['new_email'] );
		$connection_data['first_name']        = $this->sanitize_field_id( $connection_data['first_name'] );
		$connection_data['form']              = $this->sanitize_field_id( $connection_data['form'] );
		$connection_data['tags']              = $this->sanitize_tags( $connection_data );
		$connection_data['new_tags']          = $this->get_new_tags_field( $connection_data['new_tags'] );
		$connection_data['custom_fields']     = $this->get_custom_fields( $sanitized_custom_fields );
		$connection_data['new_custom_fields'] = $this->new_custom_fields( $sanitized_custom_fields );

		unset( $connection_data['fields_meta'] );

		return $connection_data;
	}

	/**
	 * Sanitize custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sanitized_custom_fields List of valid fields.
	 *
	 * @return array
	 */
	private function get_custom_fields( array $sanitized_custom_fields ): array {

		$custom_fields = [];

		foreach ( $sanitized_custom_fields as $field ) {
			if ( ! in_array( $field['slug'], $this->existing_custom_fields, true ) ) {
				continue;
			}

			$custom_fields[ $field['slug'] ] = $field['field_id'];
		}

		return $custom_fields;
	}

	/**
	 * Sanitize new custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sanitized_custom_fields List of valid fields.
	 *
	 * @return array
	 */
	private function new_custom_fields( array $sanitized_custom_fields ): array {

		$new_custom_fields = [];

		foreach ( $sanitized_custom_fields as $field ) {
			if ( in_array( $field['slug'], $this->existing_custom_fields, true ) ) {
				continue;
			}

			// We need to use labels for creating new custom fields.
			// The label will be replaced to a slug later.
			$new_custom_fields[ $field['name'] ] = $field['field_id'];
		}

		return $new_custom_fields;
	}

	/**
	 * Remove custom field if name or field_id is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Custom fields.
	 *
	 * @return array
	 */
	private function sanitize_custom_fields( array $fields ): array {

		return wpforms_chain( $fields )
			->map(
				static function ( $field ) {
					if (
						! isset( $field['name'], $field['field_id'] )
						|| wpforms_is_empty_string( trim( $field['name'] ) )
						|| wpforms_is_empty_string( trim( $field['field_id'] ) )
					) {
						return '';
					}

					$field['slug']     = trim( sanitize_text_field( str_replace( ' ', '_', mb_strtolower( $field['name'] ) ) ) );
					$field['name']     = trim( sanitize_text_field( $field['name'] ) );
					$field['field_id'] = absint( $field['field_id'] );

					return $field;
				}
			)
			->array_filter()
			->array_values()
			->value();
	}

	/**
	 * When saving data in the builder, the fields with multiple values (e.g. tags) will keep only the last value.
	 * For instance, the `providers[convertkit][123][forms][]` with values `1`, `3` and `5` will be saved with the `5` value.
	 * That's why we use `$_POST` directly instead of `$connection_data` here.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_data Connection data.
	 *
	 * @return array
	 */
	private function sanitize_tags( array $connection_data ): array {

		$connection_id = $connection_data['id'];

		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$form_post = ! empty( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : [];

		return wpforms_chain( $form_post )
			->map(
				static function ( $post_pair ) use ( $connection_id ) {

					$field_name = 'tags';

					$provider_slug = Plugin::SLUG;

					if (
						empty( $post_pair['name'] ) ||
						$post_pair['name'] !== "providers[$provider_slug][$connection_id][$field_name][]"
					) {
						return '';
					}

					return absint( $post_pair['value'] );
				}
			)
			->array_filter()
			->array_values()
			->value();
	}

	/**
	 * Sanitize the New Tags field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_tags_value The field data to be sanitized.
	 *
	 * @return array
	 */
	private function get_new_tags_field( string $new_tags_value ): array {

		return wpforms_chain( $new_tags_value )
			->explode( ',' )
			->map( 'trim' )
			->array_filter()
			->array_values()
			->value();
	}
}
