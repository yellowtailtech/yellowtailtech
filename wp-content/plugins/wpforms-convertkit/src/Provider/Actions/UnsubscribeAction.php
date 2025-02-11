<?php

namespace WPFormsConvertKit\Provider\Actions;

use RuntimeException;

/**
 * Class UnsubscribeAction.
 *
 * @since 1.0.0
 */
class UnsubscribeAction extends Action {

	/**
	 * Run action.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException Invalid response.
	 */
	public function run() {

		$email = $this->field_mapper->get_field_value_by_id( (int) $this->field_mapper->get_required_field_value( 'email' ) );

		if ( empty( $email ) || ! wpforms_is_email( $email ) ) {
			throw new RuntimeException( esc_html__( 'The email is a required argument.', 'wpforms-convertkit' ), 400 );
		}

		if ( $this->connection->unsubscribe( $email ) === false ) {
			throw new RuntimeException( esc_html( sprintf( 'Failed to unsubscribe %1$s.', $email ) ), 400 );
		}
	}
}
