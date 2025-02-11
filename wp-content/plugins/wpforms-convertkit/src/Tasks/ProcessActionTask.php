<?php

namespace WPFormsConvertKit\Tasks;

use Exception;
use WPForms\Tasks\Task;
use WPForms\Tasks\Meta;
use WPFormsConvertKit\Provider\FieldMapper;

/**
 * Class ProcessActionTask.
 *
 * @since 1.0.0
 */
class ProcessActionTask extends Task {

	/**
	 * Async task action.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ACTION = 'wpforms_convertkit_process_action';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_action( self::ACTION, [ $this, 'process' ] );
	}

	/**
	 * Process the addon async tasks.
	 *
	 * @since 1.0.0
	 *
	 * @param int $meta_id Task meta ID.
	 */
	public function process( int $meta_id ) {

		$task_meta = new Meta();
		$meta      = $task_meta->get( $meta_id );

		// We should actually receive something.
		if ( empty( $meta ) || empty( $meta->data ) || ! is_array( $meta->data ) || count( $meta->data ) !== 4 ) {
			return;
		}

		// We expect a certain metadata structure for this task.
		list( $connection_data, $fields, $form_data, $entry_id ) = $meta->data;

		$this->run_action( $connection_data, $fields, $form_data, $entry_id );
	}

	/**
	 * Process the addon run action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $connection_data Connection data.
	 * @param array $fields          Array of form fields.
	 * @param array $form_data       Form data and settings.
	 * @param int   $entry_id        ID of a saved entry.
	 */
	private function run_action( array $connection_data, array $fields, array $form_data, int $entry_id ) {

		$connection = isset( $connection_data['account_id'] ) ? wpforms_convertkit()->get( 'account' )->get_connection( $connection_data['account_id'] ) : null;

		if ( $connection === null ) {
			$this->log_errors(
				sprintf(
					'Invalid connection %s',
					esc_html( $connection_data['name'] )
				),
				$connection_data,
				$entry_id,
				$form_data['id']
			);

			return;
		}

		$class = '\WPFormsConvertKit\Provider\Actions\\' . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $connection_data['action'] ) ) ) . 'Action';

		if ( ! class_exists( $class ) ) {
			$this->log_errors(
				sprintf(
					'Can\'t find the %s action class',
					esc_html( $connection_data['action'] )
				),
				$connection_data,
				$entry_id,
				$form_data['id']
			);

			return;
		}

		try {
			$action = new $class(
				$connection,
				new FieldMapper( $fields, $form_data, $connection_data ),
				$connection_data,
				$form_data,
				$fields
			);

			$action->run();
		} catch ( Exception $exception ) {
			$this->log_errors(
				$exception->getMessage(),
				$connection_data,
				$entry_id,
				$form_data['id']
			);
		}
	}

	/**
	 * Log an API-related error with all the data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message         Message.
	 * @param array  $connection_data Specific connection data that errored.
	 * @param int    $entry_id        Entry ID.
	 * @param int    $form_id         Form ID.
	 */
	protected function log_errors( string $message, array $connection_data, int $entry_id, int $form_id ) {

		wpforms_log(
			"Submission to Kit failed (#{$entry_id}).",
			[
				'message'    => $message,
				'connection' => $connection_data,
			],
			[
				'type'    => [ 'provider', 'error' ],
				'parent'  => $entry_id,
				'form_id' => $form_id,
			]
		);
	}
}
