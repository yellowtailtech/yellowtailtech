<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phone number field.
 *
 * @since 1.0.0
 */
class WPForms_Field_Phone extends WPForms_Field {

	/**
	 * International Telephone Input library CSS.
	 *
	 * @since 1.6.3
	 */
	const INTL_VERSION = '21.2.8';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information.
		$this->name     = esc_html__( 'Phone', 'wpforms' );
		$this->keywords = esc_html__( 'telephone, mobile, cell', 'wpforms' );
		$this->type     = 'phone';
		$this->icon     = 'fa-phone';
		$this->order    = 50;
		$this->group    = 'fancy';

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_phone', [ $this, 'field_properties' ], 5, 3 );

		// Form frontend CSS enqueues.
		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_frontend_css' ] );

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );

		// Add frontend strings.
		add_filter( 'wpforms_frontend_strings', [ $this, 'add_frontend_strings' ] );

		// Admin form builder enqueues.
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_builder_enqueues' ] );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.3.8
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {

		$properties['inputs']['primary']['attr']['aria-label'] = $field['label'] ?? esc_html__( 'Phone number', 'wpforms' );

		// Smart: add validation rule and class.
		if ( $field['format'] === 'smart' ) {
			$properties['inputs']['primary']['class'][]                        = 'wpforms-smart-phone-field';
			$properties['inputs']['primary']['data']['rule-smart-phone-field'] = 'true';
		}

		// US: add input mask and class.
		if ( $field['format'] === 'us' ) {
			$properties['inputs']['primary']['class'][]                     = 'wpforms-masked-input';
			$properties['inputs']['primary']['data']['inputmask']           = "'mask': '(999) 999-9999'";
			$properties['inputs']['primary']['data']['rule-us-phone-field'] = 'true';
			$properties['inputs']['primary']['data']['inputmask-inputmode'] = 'tel';
		}

		// International: add validation rule and class.
		if ( $field['format'] === 'international' ) {
			$properties['inputs']['primary']['data']['rule-int-phone-field'] = 'true';
		}

		return $properties;
	}

	/**
	 * Form frontend CSS enqueues.
	 *
	 * @since 1.5.2
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_frontend_css( $forms ) {

		if ( ! wpforms()->obj( 'frontend' )->assets_global() && ! $this->has_smart_format( $forms ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		// International Telephone Input library CSS.
		wp_enqueue_style(
			'wpforms-smart-phone-field',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/phone/intl-tel-input{$min}.css",
			[],
			self::INTL_VERSION
		);
	}

	/**
	 * Enqueue script for the admin form builder.
	 *
	 * @since 1.9.2
	 */
	public function admin_builder_enqueues() {

		$min = wpforms_get_min_suffix();

		// JavaScript.
		wp_enqueue_script(
			'wpforms-builder-phone-field',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/phone{$min}.js",
			[ 'jquery', 'wpforms-builder' ],
			WPFORMS_VERSION,
			false
		);
	}

	/**
	 * Form frontend JS enqueues.
	 *
	 * @since 1.5.2
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_frontend_js( $forms ) {

		if ( ! wpforms()->obj( 'frontend' )->assets_global() && ! $this->has_smart_format( $forms ) ) {
			return;
		}

		// Load International Telephone Input library - https://github.com/jackocnr/intl-tel-input.
		wp_enqueue_script(
			'wpforms-smart-phone-field',
			WPFORMS_PLUGIN_URL . 'assets/pro/lib/intl-tel-input/module.intl-tel-input.min.js',
			[],
			self::INTL_VERSION,
			$this->load_script_in_footer()
		);
	}

	/**
	 * Find phone field with smart format.
	 *
	 * @since 1.6.3
	 *
	 * @param array $forms Form data of forms on the current page.
	 *
	 * @return bool
	 */
	private function has_smart_format( $forms ) {

		foreach ( $forms as $form_data ) {
			if ( empty( $form_data['fields'] ) ) {
				continue;
			}

			foreach ( $form_data['fields'] as $field ) {
				if ( 'phone' === $field['type'] && isset( $field['format'] ) && 'smart' === $field['format'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add phone validation error to frontend strings.
	 *
	 * @since 1.5.8
	 *
	 * @param array $strings Frontend strings.
	 *
	 * @return array Frontend strings.
	 */
	public function add_frontend_strings( $strings ) {

		$strings['val_phone'] = wpforms_setting( 'validation-phone', esc_html__( 'Please enter a valid phone number.', 'wpforms' ) );

		return $strings;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data.
	 */
	public function field_options( $field ) {
		/*
		 * Basic field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'basic-options', $field, $args );

		// Label.
		$this->field_option( 'label', $field );

		// Format.
		$lbl  = $this->field_element(
			'label',
			$field,
			[
				'slug'    => 'format',
				'value'   => esc_html__( 'Format', 'wpforms' ),
				'tooltip' => esc_html__( 'Select format for the phone form field', 'wpforms' ),
			],
			false
		);

		$fld  = $this->field_element(
			'select',
			$field,
			[
				'slug'    => 'format',
				'value'   => ! empty( $field['format'] ) ? esc_attr( $field['format'] ) : 'smart',
				'options' => [
					'smart'         => esc_html__( 'Smart', 'wpforms' ),
					'us'            => esc_html__( 'US', 'wpforms' ),
					'international' => esc_html__( 'International', 'wpforms' ),
				],
			],
			false
		);

		$args = [
			'slug'    => 'format',
			'content' => $lbl . $fld,
		];

		$this->field_element( 'row', $field, $args );

		// Description.
		$this->field_option( 'description', $field );

		// Required toggle.
		$this->field_option( 'required', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'basic-options', $field, $args );

		/*
		 * Advanced field options.
		 */

		// Options open markup.
		$args = [
			'markup' => 'open',
		];

		$this->field_option( 'advanced-options', $field, $args );

		// Size.
		$this->field_option( 'size', $field );

		// Placeholder.
		$this->field_option( 'placeholder', $field );

		// Default value.
		$this->field_option( 'default_value', $field );

		// Custom CSS classes.
		$this->field_option( 'css', $field );

		// Hide Label.
		$this->field_option( 'label_hide', $field );

		// Options close markup.
		$args = [
			'markup' => 'close',
		];

		$this->field_option( 'advanced-options', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @since 1.9.2 Added wrapper for the primary input for the rich preview in Smart format.
	 *
	 * @param array $field Field data.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder   = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		$default_value = ! empty( $field['default_value'] ) ? $field['default_value'] : '';
		$format        = ! empty( $field['format'] ) ? $field['format'] : 'smart';
		$size          = ! empty( $field['size'] ) ? $field['size'] : 'medium';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input inside container for Smart format preview.
		printf(
			'<div class="wpforms-field-phone-input-container" data-format="%1$s">
				<input type="text" placeholder="%2$s" value="%3$s" class="primary-input wpforms-field-%4$s" readonly>
				<div class="wpforms-field-phone-country-container">
					<div class="wpforms-field-phone-flag"></div>
					<div class="wpforms-field-phone-arrow"></div>
				</div>
			</div>',
			esc_attr( $format ),
			esc_attr( $placeholder ),
			esc_attr( $default_value ),
			esc_attr( $size )
		);

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Get preview option.
	 *
	 * @since 1.9.3
	 *
	 * @param string $option Option name.
	 * @param array  $field  Field data.
	 * @param array  $args   Additional arguments.
	 * @param bool   $echo   Echo or return.
	 */
	public function field_preview_option( $option, $field, $args = [], $echo = true ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound

		// Skip preview option for the editor.
		if ( wpforms_is_editor_page() ) {
			return;
		}

		parent::field_preview_option( $option, $field, $args, $echo );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		if ( wpforms_is_editor_page() ) {
			$this->field_preview( $field );

			return;
		}

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Allow an input type to be changed for this particular field.
		$type = apply_filters( 'wpforms_phone_field_input_type', 'tel' );

		// Primary field.
		printf(
			'<input type="%s" %s %s>',
			esc_attr( $type ),
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}

	/**
	 * Validate field on form submit.
	 *
	 * @since 1.5.8
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$form_id = $form_data['id'];
		$value   = $this->sanitize_value( $field_submit );

		// If field is marked as required, check for entry data.
		if (
			! empty( $form_data['fields'][ $field_id ]['required'] ) &&
			empty( $value )
		) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = wpforms_get_required_label();
		}

		if (
			empty( $value ) ||
			empty( $form_data['fields'][ $field_id ]['format'] )
		) {
			return;
		}

		$value  = preg_replace( '/[^\d]/', '', $value );
		$length = strlen( $value );

		if ( $form_data['fields'][ $field_id ]['format'] === 'us' ) {
			$error = $length !== 10;
		} else {
			$error = $length === 0;
		}

		if ( $error ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = wpforms_setting( 'validation-phone', esc_html__( 'Please enter a valid phone number.', 'wpforms' ) );
		}
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.5.8
	 *
	 * @param int    $field_id     Field id.
	 * @param string $field_submit Submitted value.
	 * @param array  $form_data    Form data.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$name = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '';

		// Set final field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => sanitize_text_field( $name ),
			'value' => $this->sanitize_value( $field_submit ),
			'id'    => wpforms_validate_field_id( $field_id ),
			'type'  => $this->type,
		];
	}

	/**
	 * Sanitize the value.
	 *
	 * @since 1.5.8
	 *
	 * @param string $value The Phone field submitted value.
	 *
	 * @return string
	 */
	private function sanitize_value( $value ) {

		return preg_replace( '/[^-+0-9() ]/', '', $value );
	}
}

new WPForms_Field_Phone();
