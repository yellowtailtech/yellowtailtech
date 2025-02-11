<?php

namespace WPFormsConvertKit\Provider;

use RuntimeException;

/**
 * Field Mapper class.
 *
 * @since 1.0.0
 */
class FieldMapper {

	/**
	 * List of fields.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Form data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $form_data;

	/**
	 * Connection data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $connection_data;

	/**
	 * FieldMapper constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields          Form fields.
	 * @param array $form_data       Form data and settings.
	 * @param array $connection_data Connection data.
	 */
	public function __construct( array $fields, array $form_data, array $connection_data ) {

		$this->fields          = $fields;
		$this->form_data       = $form_data;
		$this->connection_data = $connection_data;
	}

	/**
	 * Get required field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name.
	 *
	 * @return string|int|array
	 *
	 * @throws RuntimeException Required argument is missing.
	 */
	public function get_required_field_value( string $name ) {

		if ( ! isset( $this->connection_data[ $name ] ) || wpforms_is_empty_string( trim( $this->connection_data[ $name ] ) ) ) {
			throw new RuntimeException(
				sprintf(
					'The "%1$s" required field is missing',
					esc_html( $name )
				),
				400
			);
		}

		return $this->connection_data[ $name ];
	}

	/**
	 * Get not required field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name.
	 *
	 * @return string|int|array
	 */
	public function get_not_required_field_value( string $name ) {

		return $this->connection_data[ $name ] ?? '';
	}

	/**
	 * Get field value by field ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $field_id Field ID.
	 *
	 * @return string
	 */
	public function get_field_value_by_id( int $field_id ): string {

		if ( $field_id === -1 || empty( $this->fields[ $field_id ] ) ) {
			return '';
		}

		return trim( $this->join_lines( $this->fields[ $field_id ] ) );
	}

	/**
	 * All Kit custom fields are regular text fields which can't have line lines.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data.
	 *
	 * @return string
	 */
	private function join_lines( array $field ): string {

		$field_type = $field['type'] ?? '';

		if ( $field_type === 'richtext' ) {
			return (string) $field['value'];
		}

		// If payment type fields.
		if ( in_array( $field_type, [ 'payment-checkbox', 'payment-multiple', 'payment-select' ], true ) ) {
			// Make a delimiter like in `Checkbox` field.
			return str_replace( "\r\n", ' ', (string) $field['value_choice'] );
		}

		if ( in_array( $field_type, [ 'payment-single', 'payment-total' ], true ) ) {
			// Additional conversion for correct currency symbol display.
			return wpforms_decode_string( $field['value'] );
		}

		$new_lines = [ "\r\n", "\r", "\n" ];

		if ( in_array( $field_type, [ 'textarea', 'name', 'date-time' ], true ) ) {
			return str_replace( $new_lines, ' ', (string) $field['value'] );
		}

		return str_replace( $new_lines, ', ', (string) $field['value'] );
	}
}
