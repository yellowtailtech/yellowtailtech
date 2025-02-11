<?php

namespace WPFormsConvertKit\Provider\Settings;

use Exception;
use RuntimeException;
use WPFormsConvertKit\Plugin;
use WPFormsConvertKit\Api\Connection;
use WPForms\Providers\Provider\Settings\FormBuilder as FormBuilderAbstract;

/**
 * Class FormBuilder handles functionality in the Form Builder.
 *
 * @since 1.0.0
 */
class FormBuilder extends FormBuilderAbstract {

	/**
	 * Register all hooks (actions and filters).
	 *
	 * @since 1.0.0
	 */
	protected function init_hooks() {

		parent::init_hooks();

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 *
	 * @uses FormBuilder::ajax_account_save()
	 * @uses FormBuilder::ajax_accounts_get()
	 * @uses FormBuilder::ajax_connections_get()
	 * @uses FormBuilder::ajax_account_template_get()
	 */
	private function hooks() {

		static $ajax_events = [
			'ajax_account_save',
			'ajax_accounts_get',
			'ajax_connections_get',
			'ajax_account_template_get',
		];

		array_walk(
			$ajax_events,
			static function ( $ajax_event, $key, $instance ) {

				add_filter(
					"wpforms_providers_settings_builder_{$ajax_event}_{$instance->core->slug}",
					[ $instance, $ajax_event ]
				);
			},
			$this
		);

		add_filter( 'wpforms_save_form_args', [ $this, 'save_form' ], 11, 3 );
		add_filter( 'wpforms_builder_strings', [ $this, 'strings' ], 10, 2 );
		add_filter( 'wpforms_builder_save_form_response_data', [ $this, 'refresh_connections' ], 10, 3 );
	}

	/**
	 * Pre-process provider data before saving it in form_data when editing a form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form Form array, usable with wp_update_post.
	 * @param array $data Data retrieved from $_POST and processed.
	 * @param array $args Custom data aren't intended to be saved.
	 *
	 * @return array
	 */
	public function save_form( $form, array $data, array $args ): array {

		$form_data = json_decode( stripslashes( $form['post_content'] ), true );

		if ( ! empty( $form_data['providers'][ Plugin::SLUG ] ) ) {
			$modified_form_data = $this->modify_form_data( $form_data );

			if ( ! empty( $modified_form_data ) ) {
				$form['post_content'] = wpforms_encode( $modified_form_data );

				return (array) $form;
			}
		}

		/*
		 * This part works when modification is locked or current filter was called on NOT the Providers panel.
		 * Then we need to restore provider connections from the previous form content.
		 */

		// Get a "previous" form content (current content is still not saved).
		$prev_form = ! empty( $data['id'] ) ? wpforms()->obj( 'form' )->get( $data['id'], [ 'content_only' => true ] ) : [];

		if ( ! empty( $prev_form['providers'][ Plugin::SLUG ] ) ) {
			$provider = $prev_form['providers'][ Plugin::SLUG ];

			if ( ! isset( $form_data['providers'] ) ) {
				$form_data = array_merge( $form_data, [ 'providers' => [] ] );
			}

			$form_data['providers'] = array_merge( (array) $form_data['providers'], [ Plugin::SLUG => $provider ] );
			$form['post_content']   = wpforms_encode( $form_data );
		}

		return (array) $form;
	}

	/**
	 * Prepare modifications for the form content if it's not locked.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form content.
	 *
	 * @return array
	 */
	protected function modify_form_data( array $form_data ): array {

		$lock = '__lock__';

		/**
		 * Connection is locked.
		 * Why? A user clicked the "Save" button when one of the AJAX requests
		 * for retrieving data from the API was in progress or failed.
		 */
		if (
			isset( $form_data['providers'][ Plugin::SLUG ][ $lock ] ) &&
			absint( $form_data['providers'][ Plugin::SLUG ][ $lock ] ) === 1
		) {
			return [];
		}

		// Modify content as we need, done by reference.
		foreach ( $form_data['providers'][ Plugin::SLUG ] as $connection_id => &$connection_data ) {
			if ( $connection_id === $lock ) {
				unset( $form_data['providers'][ Plugin::SLUG ][ $lock ] );

				continue;
			}

			$connection = isset( $connection_data['account_id'] ) ? wpforms_convertkit()->get( 'account' )->get_connection( $connection_data['account_id'] ) : null;

			if ( $connection === null ) {
				continue;
			}

			$connection_data = wpforms_convertkit()->get( 'sanitizer' )->connection( $connection_data, $connection );

			$this->create_connection_custom_fields( $connection_data, $connection );
			$this->create_connection_tags( $connection_data, $connection );
		}

		return $form_data;
	}

	/**
	 * Create custom fields for a connection.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $connection_data Connection data.
	 * @param Connection $connection      Connection.
	 */
	private function create_connection_custom_fields( array &$connection_data, Connection $connection ) {

		$new_custom_fields = $connection_data['new_custom_fields'] ?? [];

		unset( $connection_data['new_custom_fields'] );

		if ( empty( $new_custom_fields ) ) {
			return;
		}

		$created_custom_fields = $connection->create_custom_fields( array_keys( $new_custom_fields ) );

		foreach ( $created_custom_fields as $field_slug => $field_label ) {
			if ( ! isset( $new_custom_fields[ $field_label ] ) ) {
				continue;
			}

			$connection_data['custom_fields'][ $field_slug ] = $new_custom_fields[ $field_label ];
		}
	}

	/**
	 * Create tags for a connection.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $connection_data Connection data.
	 * @param Connection $connection      Connection.
	 */
	private function create_connection_tags( array &$connection_data, Connection $connection ) {

		$new_tags = $connection_data['new_tags'] ?? [];

		unset( $connection_data['new_tags'] );

		if ( empty( $new_tags ) ) {
			return;
		}

		$connection->create_tags( $new_tags );

		$tags = $connection->get_tags();

		foreach ( $new_tags as $tag_name ) {
			$tag_id = array_search( $tag_name, $tags, true );

			if ( ! $tag_id ) {
				continue;
			}

			$connection_data['tags'][] = (int) $tag_id;
		}
	}

	/**
	 * Refresh the builder to update data (e.g. load newly created tags and/or custom fields).
	 *
	 * @since 1.0.0
	 *
	 * @param array $response_data The data to be sent in the response.
	 * @param int   $form_id       Form ID.
	 * @param array $data          Form data.
	 *
	 * @return array
	 */
	public function refresh_connections( $response_data, int $form_id, array $data ): array {

		if ( empty( $data['providers'][ Plugin::SLUG ] ) ) {
			return $response_data;
		}

		$this->form_data = wpforms()->obj( 'form' )->get( $form_id, [ 'content_only' => true ] );

		$response_data[ Plugin::SLUG ] = $this->ajax_connections_get();

		return (array) $response_data;
	}

	/**
	 * Get the list of all saved connections.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function ajax_connections_get(): array {

		$connections = [
			'connections'    => array_reverse( $this->get_connections_data(), true ),
			'conditionals'   => [],
			'actions'        => $this->get_actions(),
			'actions_fields' => [
				'subscribe'   => $this->get_subscribe_fields(),
				'unsubscribe' => $this->get_unsubscribe_fields(),
				'remove_tags' => $this->get_remove_tags_fields(),
			],
		];

		foreach ( $connections['connections'] as $key => $connection ) {
			if ( empty( $connection['id'] ) ) {
				unset( $connections['connections'][ $key ] );
				continue;
			}

			// This will either return an empty placeholder or complete set of rules, as a DOM.
			$connections['conditionals'][ $connection['id'] ] = wpforms_conditional_logic()
				->builder_block(
					[
						'form'       => $this->form_data,
						'type'       => 'panel',
						'parent'     => 'providers',
						'panel'      => Plugin::SLUG,
						'subsection' => $connection['id'],
						'reference'  => __( 'Marketing provider connection', 'wpforms-convertkit' ),
					],
					false
				);
		}

		$accounts = $this->ajax_accounts_get();

		return array_merge( $connections, $accounts );
	}

	/**
	 * Get accounts data.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array with available accounts and custom fields, forms and tags related to that accounts.
	 *               May return an empty sub-array if account has no custom fields, forms and tags respectively.
	 */
	public function ajax_accounts_get(): array {

		$accounts = wpforms_convertkit()->get( 'account' )->get_all();
		$fields   = [];
		$forms    = [];
		$tags     = [];

		foreach ( $accounts as $account_id => $account_name ) {
			$connection = ! empty( $account_id ) ? wpforms_convertkit()->get( 'account' )->get_connection( $account_id ) : null;

			if ( $connection === null ) {
				continue;
			}

			$fields[ $account_id ] = $connection->get_custom_fields();
			$forms[ $account_id ]  = $connection->get_forms();
			$tags[ $account_id ]   = $connection->get_tags();
		}

		return [
			'accounts'      => $accounts,
			'custom_fields' => $fields,
			'forms'         => $forms,
			'tags'          => $tags,
		];
	}

	/**
	 * Content for Add New Account modal.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function ajax_account_template_get(): array {

		return [
			'title'   => sprintf( /* translators: %1$s - untranslatable brand name (Kit). */
				__( 'New %1$s Account', 'wpforms-convertkit' ),
				'Kit'
			),
			'content' => wpforms_render( WPFORMS_CONVERTKIT_PATH . 'templates/new-account-form' ),
			'type'    => 'blue',
		];
	}

	/**
	 * Save the data for a new account and validate it.
	 *
	 * @since 1.0.0
	 *
	 * phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing
	 */
	public function ajax_account_save() {

		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = ! empty( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : [];

		try {
			if ( empty( $data['api_key'] ) || empty( $data['api_secret'] ) ) {
				throw new RuntimeException(
					sprintf( /* translators: %1$s - untranslatable brand name (Kit). */
						esc_html__( 'Both %1$s API Key and API Secret fields are required.', 'wpforms-convertkit' ),
						'Kit'
					),
					400
				);
			}

			wpforms_convertkit()
				->get( 'account' )
				->add(
					sanitize_text_field( $data['api_key'] ),
					sanitize_text_field( $data['api_secret'] )
				);

			wp_send_json_success();
		} catch ( Exception $exception ) {
			wp_send_json_error(
				[
					'error_msg' => esc_html( $exception->getMessage() ),
				]
			);
		}
	}

	/**
	 * Get fields for the subscribe action.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_subscribe_fields(): array {

		return [
			'email'         => [
				'label'    => __( 'Email', 'wpforms-convertkit' ),
				'type'     => 'select',
				'map'      => 'email',
				'required' => true,
			],
			'new_email'     => [
				'label'    => __( 'New Email', 'wpforms-convertkit' ),
				'type'     => 'select',
				'map'      => 'email',
				'required' => false,
			],
			'first_name'    => [
				'label'    => __( 'First Name', 'wpforms-convertkit' ),
				'type'     => 'select',
				'map'      => 'name',
				'required' => false,
			],
			'form'          => [
				'label'       => __( 'Form', 'wpforms-convertkit' ),
				'type'        => 'select',
				'required'    => false,
				'placeholder' => __( '--- Select Form ---', 'wpforms-convertkit' ),
			],
			'tags'          => [
				'label'       => __( 'Tags', 'wpforms-convertkit' ),
				'type'        => 'select',
				'multiple'    => true,
				'required'    => false,
				'placeholder' => __( '--- Select Tags ---', 'wpforms-convertkit' ),
			],
			'new_tags'      => [
				'label'       => __( 'New Tags', 'wpforms-convertkit' ),
				'type'        => 'text',
				'required'    => false,
				'description' => sprintf( /* translators: %1$s - untranslatable brand name (Kit). */
					__( 'This field accepts a comma-separated list of tags. Tags listed in this field will be added to your %1$s account.', 'wpforms-convertkit' ),
					'Kit'
				),
			],
			'custom_fields' => [
				'label'    => __( 'Custom Fields', 'wpforms-convertkit' ),
				'type'     => 'custom-fields',
				'required' => false,
			],
		];
	}

	/**
	 * Get fields for the unsubscribe action.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_unsubscribe_fields(): array {

		return [
			'email' => [
				'label'    => __( 'Email', 'wpforms-convertkit' ),
				'type'     => 'select',
				'map'      => 'email',
				'required' => true,
			],
		];
	}

	/**
	 * Get fields for the remove subscriber's tags action.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_remove_tags_fields(): array {

		return [
			'email' => [
				'label'    => __( 'Email', 'wpforms-convertkit' ),
				'type'     => 'select',
				'map'      => 'email',
				'required' => true,
			],
			'tags'  => [
				'label'       => __( 'Tags', 'wpforms-convertkit' ),
				'type'        => 'select',
				'multiple'    => true,
				'required'    => true,
				'placeholder' => __( '--- Select Tags ---', 'wpforms-convertkit' ),
			],
		];
	}

	/**
	 * Retrieve saved provider connections data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_connections_data(): array {

		if ( ! isset( $this->form_data['providers'][ Plugin::SLUG ] ) ) {
			return [];
		}

		return (array) $this->form_data['providers'][ Plugin::SLUG ];
	}

	/**
	 * Get list of actions.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_actions(): array {

		return [
			'subscribe'   => __( 'Subscribe', 'wpforms-convertkit' ),
			'unsubscribe' => __( 'Unsubscribe', 'wpforms-convertkit' ),
			'remove_tags' => __( 'Remove subscriber\'s tags', 'wpforms-convertkit' ),
		];
	}

	/**
	 * Use this method to register own templates for form builder.
	 * Make sure, that you have `tmpl-` in template name in `<script id="tmpl-*">`.
	 *
	 * @since 1.0.0
	 */
	public function builder_custom_templates() {

		$templates = [
			'connection',
			'custom-fields',
			'error',
			'select-field',
			'text-field',
		];

		foreach ( $templates as $template ) {
			$template_name = ucwords( str_replace( '-', ' ', $template ) );
			$script_id     = 'tmpl-wpforms-' . esc_attr( Plugin::SLUG ) . '-builder-content-connection';

			if ( $template !== 'connection' ) {
				$script_id .= '-' . $template;
			}
			?>
			<!-- Single Kit connection block: <?php echo esc_attr( $template_name ); ?>. -->
			<script type="text/html" id="<?php echo esc_attr( $script_id ); ?>">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo wpforms_render( WPFORMS_CONVERTKIT_PATH . 'templates/builder/' . $template );
				?>
			</script>
		<?php
		}
	}

	/**
	 * Enqueue JavaScript and CSS files.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {

		parent::enqueue_assets();

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-convertkit-admin-builder',
			WPFORMS_CONVERTKIT_URL . "assets/js/builder{$min}.js",
			[ 'wpforms-admin-builder-providers', 'choicesjs' ],
			WPFORMS_CONVERTKIT_VERSION,
			true
		);

		wp_enqueue_style(
			'wpforms-convertkit-admin-builder',
			WPFORMS_CONVERTKIT_URL . "assets/css/builder{$min}.css",
			[],
			WPFORMS_CONVERTKIT_VERSION
		);
	}

	/**
	 * Add builder strings.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $strings Form Builder strings.
	 * @param object $form    Form data and settings.
	 *
	 * @return array
	 */
	public function strings( $strings, $form ): array {

		$strings['convertkit'] = [
			'subscribe_fields_error' => sprintf( /* translators: %1$s - untranslatable brand name (Kit). */
				esc_html__( 'To complete your form\'s %1$s integration please check that at least one Form or Tag has been selected.', 'wpforms-convertkit' ),
				'Kit'
			),
		];

		return (array) $strings;
	}
}
