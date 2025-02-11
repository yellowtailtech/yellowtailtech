<?php

namespace WPFormsConvertKit\Provider;

use WPFormsConvertKit\Plugin;
use WPFormsConvertKit\Tasks\ProcessActionTask;

/**
 * Class Process handles entries processing using the provider settings and configuration.
 *
 * @since 1.0.0
 */
class Process extends \WPForms\Providers\Provider\Process {

	/**
	 * Receive all wpforms_process_complete params and do the actual processing.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Array of form fields.
	 * @param array $entry     Submitted form content.
	 * @param array $form_data Form data and settings.
	 * @param int   $entry_id  ID of a saved entry.
	 */
	public function process( $fields, $entry, $form_data, $entry_id ) {

		if ( empty( $form_data['providers'][ Plugin::SLUG ] ) ) {
			return;
		}

		foreach ( $form_data['providers'][ Plugin::SLUG ] as $key => $connection_data ) {

			if ( $key === '__lock__' ) {
				continue;
			}

			$this->process_each_connection( $connection_data, $fields, $form_data, $entry_id );
		}
	}

	/**
	 * Iteration loop for connections - add action for each connection.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_data Connection data.
	 * @param array $fields          Array of form fields.
	 * @param array $form_data       Form data and settings.
	 * @param int   $entry_id        ID of a saved entry.
	 */
	protected function process_each_connection( array $connection_data, array $fields, array $form_data, int $entry_id ) {

		if ( empty( $connection_data['action'] ) || empty( $connection_data['account_id'] ) ) {
			return;
		}

		// Check for conditional logic.
		if ( ! $this->condition_passed( $connection_data, $fields, $form_data, $entry_id ) ) {
			return;
		}

		wpforms()
			->obj( 'tasks' )
			->create( ProcessActionTask::ACTION )
			->async()
			->params( $connection_data, $fields, $form_data, $entry_id )
			->register();
	}

	/**
	 * Process Conditional Logic for the provided connection.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_data Connection data.
	 * @param array $fields          Array of form fields.
	 * @param array $form_data       Form data and settings.
	 * @param int   $entry_id        ID of a saved entry.
	 *
	 * @return bool
	 */
	private function condition_passed( array $connection_data, array $fields, array $form_data, int $entry_id ): bool {

		$pass = $this->process_conditionals( $fields, $form_data, $connection_data );

		// Check for conditional logic.
		if ( ! $pass ) {
			wpforms_log(
				sprintf( 'The Kit connection %s was not processed due to conditional logic.', $connection_data['name'] ?? '' ),
				$fields,
				[
					'type'    => [ 'provider', 'conditional_logic' ],
					'parent'  => $entry_id,
					'form_id' => $form_data['id'],
				]
			);
		}

		return $pass;
	}
}
