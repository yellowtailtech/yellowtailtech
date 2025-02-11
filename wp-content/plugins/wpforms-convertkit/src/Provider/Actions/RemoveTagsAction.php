<?php

namespace WPFormsConvertKit\Provider\Actions;

use RuntimeException;

/**
 * Class RemoveTagsAction.
 *
 * @since 1.0.0
 */
class RemoveTagsAction extends Action {

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

		$tags = $this->field_mapper->get_not_required_field_value( 'tags' );

		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return;
		}

		foreach ( $tags as $tag ) {
			if ( $this->connection->remove_tag_from_subscriber_by_email( $tag, $email ) === false ) {
				throw new RuntimeException( esc_html( sprintf( 'Failed to remove tag %1$s from %2$s.', $tag, $email ) ), 400 );
			}
		}
	}
}
