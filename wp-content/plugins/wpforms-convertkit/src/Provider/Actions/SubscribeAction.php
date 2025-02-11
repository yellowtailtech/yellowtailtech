<?php

namespace WPFormsConvertKit\Provider\Actions;

use RuntimeException;

/**
 * Class SubscribeAction.
 *
 * @since 1.0.0
 */
class SubscribeAction extends Action {

	/**
	 * Subscriber email.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $email = '';

	/**
	 * Subscriber name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $first_name = '';

	/**
	 * Form ID to subscribe to.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $form = -1;

	/**
	 * Tags to subscribe to.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $tags = [];

	/**
	 * Custom fields list.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $custom_fields = [];

	/**
	 * Failed requests list.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $failed_requests;

	/**
	 * Run action.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException Failed requests.
	 */
	public function run() {

		$this->email = $this->field_mapper->get_field_value_by_id( (int) $this->field_mapper->get_required_field_value( 'email' ) );

		if ( empty( $this->email ) || ! wpforms_is_email( $this->email ) ) {
			throw new RuntimeException( esc_html__( 'The email is a required argument.', 'wpforms-convertkit' ), 400 );
		}

		$this->map_custom_fields();

		$this->first_name      = $this->field_mapper->get_field_value_by_id( (int) $this->field_mapper->get_not_required_field_value( 'first_name' ) );
		$this->form            = $this->field_mapper->get_not_required_field_value( 'form' );
		$this->tags            = $this->field_mapper->get_not_required_field_value( 'tags' );
		$this->failed_requests = [
			'form' => [],
			'tags' => [],
		];

		$this->update_subscriber_email();

		$this->add_subscriber_form();
		$this->add_subscriber_tags();

		$this->process_failed_requests();
	}

	/**
	 * Add subscriber to the form.
	 *
	 * @since 1.0.0
	 */
	private function add_subscriber_form() {

		if ( $this->form === -1 ) {
			return;
		}

		if ( $this->connection->add_subscriber_to_form( $this->form, $this->email, $this->first_name, $this->custom_fields, $this->tags ) === false ) {
			$this->failed_requests['form'][] = $this->form;
		}
	}

	/**
	 * Add subscriber to tags.
	 *
	 * @since 1.0.0
	 */
	private function add_subscriber_tags() {

		if ( empty( $this->tags ) ) {
			return;
		}

		// All tags are added if at least one form is added.
		if ( $this->form !== -1 ) {
			return;
		}

		foreach ( $this->tags as $tag ) {
			if ( $this->connection->tag_subscriber( $tag, $this->email, $this->first_name, $this->custom_fields ) === false ) {
				$this->failed_requests['tags'][] = $tag;
			}
		}
	}

	/**
	 * Log failed requests.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException Failed requests.
	 */
	private function process_failed_requests() {

		$error_message = '';

		foreach ( $this->failed_requests as $failed_group_name => $failed_group_requests ) {
			if ( empty( $failed_group_requests ) ) {
				continue;
			}

			$error_message .= "\n" . ucfirst( $failed_group_name ) . ': ' . implode( ', ', $failed_group_requests );
		}

		if ( empty( $error_message ) ) {
			return;
		}

		throw new RuntimeException(
			sprintf(
				'Subscriber %1$s could not be subscribed to %2$s',
				esc_html( $this->email ),
				esc_html( $error_message )
			),
			400
		);
	}

	/**
	 * Map form fields.
	 *
	 * @since 1.0.0
	 */
	private function map_custom_fields() {

		if ( empty( $this->connection_data['custom_fields'] ) ) {
			return;
		}

		foreach ( $this->connection_data['custom_fields'] as $field_name => $field_id ) {
			$field_value = $this->field_mapper->get_field_value_by_id( (int) $field_id );

			if ( wpforms_is_empty_string( $field_value ) ) {
				continue;
			}

			$this->custom_fields[ $field_name ] = $field_value;
		}
	}

	/**
	 * Update a subscriber email.
	 *
	 * @since 1.0.0
	 */
	private function update_subscriber_email() {

		$new_email = $this->field_mapper->get_field_value_by_id( (int) $this->field_mapper->get_not_required_field_value( 'new_email' ) );

		if ( empty( $new_email ) || ! wpforms_is_email( $new_email ) ) {
			return;
		}

		if ( $new_email === $this->email ) {
			return;
		}

		$subscriber_id = $this->connection->get_subscriber_id( $this->email );

		if ( empty( $subscriber_id ) ) {
			return;
		}

		if ( $this->connection->update_subscriber( $subscriber_id, '', $new_email ) ) {
			$this->email = $new_email;
		}
	}
}
