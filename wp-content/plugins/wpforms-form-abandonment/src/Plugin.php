<?php
/**
 * Main plugin file.
 */

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection AutoloadingIssuesInspection */

namespace WPFormsFormAbandonment;

use WP_Post;
use WPForms_Builder_Panel_Settings;
use WPForms_Updater;
use WPFormsFormAbandonment\Migrations\Migrations;

/**
 * Form Abandonment.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Get a single instance of the addon.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();

			$instance->init();
		}

		return $instance;
	}

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public function init() {

		( new Migrations() )->init();

		$this->hooks();

		return $this;
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.8.0
	 */
	private function hooks() {

		// Admin related Actions/Filters.
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_enqueues' ] );
		add_filter( 'wpforms_builder_settings_sections', [ $this, 'settings_register' ], 20, 2 );
		add_action( 'wpforms_form_settings_panel_content', [ $this, 'settings_content' ], 20, 2 );
		add_action( 'wpforms_form_settings_notifications_single_after', [ $this, 'notification_settings' ], 10, 2 );
		add_filter( 'wpforms_entries_table_counts', [ $this, 'entries_table_counts' ], 10, 2 );
		add_filter( 'wpforms_entries_table_views', [ $this, 'entries_table_views' ], 10, 3 );
		add_filter( 'wpforms_entries_table_column_status', [ $this, 'entries_table_column_status' ], 10, 2 );
		add_filter( 'wpforms_entry_details_sidebar_details_status', [ $this, 'entries_details_sidebar_status' ], 10, 3 );
		add_filter( 'wpforms_entry_details_sidebar_actions_link', [ $this, 'entries_details_sidebar_actions' ], 10, 3 );
		add_action( 'wp_ajax_wpforms_form_abandonment', [ $this, 'process_entries' ] );
		add_action( 'wp_ajax_nopriv_wpforms_form_abandonment', [ $this, 'process_entries' ] );
		add_filter( 'wpforms_entry_email_process', [ $this, 'process_email' ], 50, 5 );
		add_action( 'wpforms_process_entry_saved', [ $this, 'process_complete' ], 10, 4 );
		add_filter( 'wpforms_process_before_form_data_form_abandonment', [ $this, 'prepare_repeater_form_data' ], 10, 2 );

		// Front-end related Actions.
		add_action( 'wpforms_frontend_container_class', [ $this, 'form_container_class' ], 10, 2 );
		add_action( 'wpforms_wp_footer', [ $this, 'frontend_enqueues' ] );

		add_action( 'wpforms_updater', [ $this, 'updater' ] );
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key WPForms license key.
	 */
	public function updater( $key ) {

		new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Form Abandonment',
				'plugin_slug' => 'wpforms-form-abandonment',
				'plugin_path' => plugin_basename( WPFORMS_FORM_ABANDONMENT_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_FORM_ABANDONMENT_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_FORM_ABANDONMENT_VERSION,
				'key'         => $key,
			]
		);
	}

	/*****************************
	 * Admin-side functionality. *
	 *****************************/

	/**
	 * Enqueue assets for the builder.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueues() {

		$suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'WPFORMS_DEBUG' ) && WPFORMS_DEBUG ) ) ? '' : '.min';

		wp_enqueue_script(
			'wpforms-builder-form-abandonment',
			WPFORMS_FORM_ABANDONMENT_URL . 'assets/js/admin-builder-form-abandonment' . $suffix . '.js',
			[ 'jquery' ],
			WPFORMS_FORM_ABANDONMENT_VERSION
		);
	}

	/**
	 * Form Abandonment settings register section.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sections  Settings page sections list.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function settings_register( $sections, $form_data ) {

		$sections['form_abandonment'] = esc_html__( 'Form Abandonment', 'wpforms-form-abandonment' );

		return $sections;
	}

	/**
	 * Form Abandonment settings content.
	 *
	 * @since 1.0.0
	 *
	 * @param WPForms_Builder_Panel_Settings $instance WPForms_Builder_Panel_Settings class instance.
	 */
	public function settings_content( $instance ) {

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-form_abandonment">';

		printf(
			'<div class="wpforms-panel-content-section-title">%s</div>',
			esc_html__( 'Form Abandonment', 'wpforms-form-abandonment' )
		);

		wpforms_panel_field(
			'toggle',
			'settings',
			'form_abandonment',
			$instance->form_data,
			esc_html__( 'Enable Form Abandonment Lead Capture', 'wpforms-form-abandonment' )
		);

		wpforms_panel_field(
			'radio',
			'settings',
			'form_abandonment_fields',
			$instance->form_data,
			'',
			[
				'options' => [
					''    => [
						'label' => esc_html__( 'Save only if email address or phone number is provided', 'wpforms-form-abandonment' ),
					],
					'all' => [
						'label'   => esc_html__( 'Always save abandoned entries', 'wpforms-form-abandonment' ),
						'tooltip' => esc_html__( 'We believe abandoned form entries are only helpful if you have some way to contact the user. However this option is good for users that have anonymous form submissions.', 'wpforms-form-abandonment' ),
					],
				],
			]
		);

		wpforms_panel_field(
			'toggle',
			'settings',
			'form_abandonment_duplicates',
			$instance->form_data,
			esc_html__( 'Prevent duplicate abandon entries', 'wpforms-form-abandonment' ),
			[
				'tooltip' => esc_html__( 'When checked only the most recent abandoned entry from the user is saved. See the Form Abandonment documentation for more info regarding this setting.', 'wpforms-form-abandonment' ),
			]
		);

		echo '</div>';
	}

	/**
	 * Add select to form notification settings.
	 *
	 * @since 1.0.0
	 *
	 * @param WPForms_Builder_Panel_Settings $settings WPForms_Builder_Panel_Settings class instance.
	 * @param int                            $id       Subsection ID.
	 */
	public function notification_settings( $settings, $id ) {

		wpforms_panel_field(
			'toggle',
			'notifications',
			'form_abandonment',
			$settings->form_data,
			esc_html__( 'Enable for abandoned forms entries', 'wpforms-form-abandonment' ),
			[
				'parent'      => 'settings',
				'class'       => ! $this->has_form_abandonment( $settings->form_data ) ? 'wpforms-hidden' : '',
				'input_class' => 'wpforms-radio-group wpforms-radio-group-' . $id . '-notification-by-status wpforms-radio-group-item-form_abandonment wpforms-notification-by-status-alert',
				'subsection'  => $id,
				'tooltip'     => wp_kses(
					__( 'When enabled this notification will <em>only</em> be sent for abandoned form entries. This setting should only be used with <strong>new</strong> notifications.', 'wpforms-form-abandonment' ),
					[
						'em'     => [],
						'strong' => [],
					]
				),
				'data'        => [
					'radio-group'    => $id . '-notification-by-status',
					'provider-title' => esc_html__( 'Form Abandonment entries', 'wpforms-form-abandonment' ),
				],
			]
		);
	}

	/**
	 * Lookup and store counts for abandoned entries.
	 *
	 * @since 1.0.0
	 *
	 * @param array $counts    Entries count list.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function entries_table_counts( $counts, $form_data ) {

		if ( $this->has_form_abandonment( $form_data ) ) {
			$counts['abandoned'] = wpforms()->get( 'entry' )->get_entries(
				[
					'form_id' => absint( $form_data['id'] ),
					'status'  => 'abandoned',
				],
				true
			);
		}

		return $counts;
	}

	/**
	 * Create view for abandoned entries.
	 *
	 * @since 1.0.0
	 *
	 * @param array $views     Filters for entries various states.
	 * @param array $form_data Form data.
	 * @param array $counts    Entries count list.
	 *
	 * @return array
	 */
	public function entries_table_views( $views, $form_data, $counts ) {

		if ( $this->has_form_abandonment( $form_data ) ) {

			$base = add_query_arg(
				[
					'page'    => 'wpforms-entries',
					'view'    => 'list',
					'form_id' => absint( $form_data['id'] ),
				],
				admin_url( 'admin.php' )
			);

			$current   = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$abandoned = '&nbsp;<span class="count">(<span class="abandoned-num">' . $counts['abandoned'] . '</span>)</span>';

			$views['abandoned'] = sprintf(
				'<a href="%s" %s>%s</a>',
				esc_url( add_query_arg( 'status', 'abandoned', $base ) ),
				$current === 'abandoned' ? 'class="current"' : '',
				esc_html__( 'Abandoned', 'wpforms-form-abandonment' ) . $abandoned
			);
		}

		return $views;
	}

	/**
	 * Enable the Status column for forms that are using form abandonment.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $show      Whether to show the Status column or not.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function entries_table_column_status( $show, $form_data ) {

		if ( $this->has_form_abandonment( $form_data ) ) {
			return true;
		}

		return $show;
	}

	/**
	 * Enable the displaying status for forms that are using form abandonment.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $show      Whether to show the Status column or not.
	 * @param object $entry     Entry information.
	 * @param array  $form_data Form data.
	 *
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function entries_details_sidebar_status( $show, $entry, $form_data ) {

		if ( $this->has_form_abandonment( $form_data ) ) {
			return true;
		}

		return $show;
	}

	/**
	 * For abandoned entries remove the link to resend email notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links     List of links in sidebar.
	 * @param object $entry     Entry information.
	 * @param array  $form_data Form data.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function entries_details_sidebar_actions( $links, $entry, $form_data ) {

		if ( $this->has_form_abandonment( $form_data ) ) {
			$links['notifications']['disabled']      = true;
			$links['notifications']['disabled_by'][] = __( 'Form Abandonment Lead Capture', 'wpforms-form-abandonment' );
		}

		return $links;
	}

	/**
	 * Process the abandoned entries via AJAX.
	 *
	 * @since 1.0.0
	 */
	public function process_entries() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		// Make sure we have required data.
		if ( empty( $_POST['forms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error();
		}

		// User UID is required.
		if ( empty( $_COOKIE['_wpfuuid'] ) || ! wpforms_is_collecting_cookies_allowed() ) {
			wp_send_json_error();
		}

		// Grab posted data and decode.
		$data  = json_decode( stripslashes( $_POST['forms'] ) ); // phpcs:ignore
		$forms = [];

		// Compile all posted data into an array.
		foreach ( $data as $form_id => $form ) {

			$fields    = [];
			$form_vars = '';

			foreach ( $form as $post_input_data ) {
				$form_vars .= $post_input_data->name . '=' . rawurlencode( $post_input_data->value ) . '&';
			}

			parse_str( $form_vars, $fields );

			$forms[ $form_id ] = $fields['wpforms'];
		}

		// Go through the data for each form abandoned (if multiple) and process.
		foreach ( $forms as $form_id => $entry ) {

			wpforms()->get( 'process' )->fields = [];

			// Get the form settings for this form.
			$form = wpforms()->get( 'form' )->get( $form_id );

			// Form must be real and active (published).
			if ( ! $form || $form->post_status !== 'publish' ) {
				wp_send_json_error();
			}

			// If the honeypot was triggers we assume this is a spammer.
			if ( ! empty( $entry['hp'] ) ) {
				wp_send_json_error();
			}

			// Formatted form data.
			// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
			$form_data = apply_filters( 'wpforms_process_before_form_data_form_abandonment', wpforms_decode( $form->post_content ), $entry );

			// Check if form has entries disabled.
			if ( ! empty( $form_data['settings']['disable_entries'] ) ) {
				wp_send_json_error();
			}

			// Pre-process filter.
			// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
			$entry = apply_filters( 'wpforms_process_before_filter_form_abandonment', $entry, $form_data );

			// We don't have a global $post when processing ajax requests.
			// Therefore, it's needed to set a global $post manually for compatibility with functions used in smart tag processing.
			if ( isset( $entry['post_id'] ) ) { // phpcs:ignore
				global $post;
				$post = WP_Post::get_instance( absint( $entry['post_id'] ) ); // phpcs:ignore
			}

			// Set defaults.
			$exists          = false;
			$avoid_dupes     = ! empty( $form_data['settings']['form_abandonment_duplicates'] );
			$fields_required = empty( $form_data['settings']['form_abandonment_fields'] );
			$phone           = false;
			$email           = false;

			// Add submitted quantity.
			if ( isset( $entry['quantities'] ) ) {
				$form_data['quantities'] = wpforms_json_decode( wp_json_encode( $entry['quantities'] ), true );
			}

			// Format fields.
			foreach ( $form_data['fields'] as $field ) {

				$field_id     = $field['id'];
				$field_type   = $field['type'];
				$field_submit = isset( $entry['fields'][ $field_id ] ) ? $entry['fields'][ $field_id ] : '';

				// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation
				if ( $field_type === 'password' && ! apply_filters( 'wpforms_form_abandonment_process_entries_save_password', false, $form_data ) ) {
					continue;
				}

				// Don't support these fields for abandonment tracking.
				if ( in_array( $field_type, [ 'file-upload', 'signature' ], true ) ) {
					continue;
				}

				// If a phone field has been filled out, then set the $phone variable to true.
				if ( $field_type === 'phone' && ! empty( $field_submit ) ) {
					$phone = true;
				}

				// If an email field has been filled out and is valid, then set the $email variable to true.
				if ( $field_type === 'email' ) {
					$email_value = is_array( $field_submit ) && ! empty( $field_submit['primary'] ) ? $field_submit['primary'] : $field_submit;
					$email       = $email || wpforms_is_email( $email_value );
				}

				// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
				do_action( "wpforms_process_format_{$field_type}", $field_id, $field_submit, $form_data );
			}

			// If the form has phone/email required, but neither is present, then stop processing.
			if ( $fields_required && ! $email && ! $phone ) {
				continue;
			}

			// Post-process filter.
			// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
			$fields = apply_filters( 'wpforms_process_filter_form_abandonment', wpforms()->get( 'process' )->fields, $entry, $form_data );

			//Modified by YTT

				if ($form_id == 40550) {

					$url = 'https://hooks.zapier.com/hooks/catch/7864477/2ryafoj/';

					//The data you want to send via POST
					$fields = [
					    'name'      => $fields[1]['value'],
					    'email'      => $fields[2]['value'],
					    'phone'      => $fields[3]['value'],
					];

					//url-ify the data for the POST
					$fields_string = http_build_query($fields);

					//open connection
					$ch = curl_init();

					//set the url, number of POST vars, POST data
					curl_setopt($ch,CURLOPT_URL, $url);
					curl_setopt($ch,CURLOPT_POST, true);
					curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

					//So that curl_exec returns the contents of the cURL; rather than echoing it
					curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

					//execute post
					curl_exec($ch);
			    }


			// Post-process hook.
			// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
			do_action( 'wpforms_process_form_abandonment', $fields, $entry, $form_data );

			// Here we check to see if the user has had another abandoned entry
			// for this form within the last hour. If so, then update the
			// existing entry instead of creating a new one.
			if ( $avoid_dupes ) {

				global $wpdb;

				$user_uuid      = ! empty( $_COOKIE['_wpfuuid'] ) ? sanitize_key( $_COOKIE['_wpfuuid'] ) : '';
				$hours_interval = 1 + (int) get_option( 'gmt_offset' );
				$exists         = $wpdb->get_row( // phpcs:ignore
					$wpdb->prepare(
						"SELECT entry_id FROM {$wpdb->prefix}wpforms_entries WHERE `form_id` = %d AND `user_uuid` = %s AND `status` = 'abandoned' AND `date` >= DATE_SUB(%s,INTERVAL %d HOUR) LIMIT 1;",
						absint( $form_id ),
						preg_replace( '/[^a-z0-9_\s-]+/i', '', $user_uuid ),
						current_time( 'mysql' ),
						$hours_interval
					)
				);
			}


			if ( ! empty( $exists ) ) {
				/*
				 * Updating a previous abandoned entry made within the last hour.
				 */

				$entry_id = $exists->entry_id;

				// Prepare the args to be updated.
				$data = [
					'viewed' => 0,
					'fields' => wp_json_encode( $fields ),
					// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'date'   => date( 'Y-m-d H:i:s' ),
				];

				// Update.
				wpforms()->get( 'entry' )->update( $entry_id, $data, '', '', [ 'cap' => false ] );

			} else {
				/*
				 * Adding a new abandoned entry.
				 */

				// Get the user details.
				$user_id = is_user_logged_in() ? get_current_user_id() : 0;
				$user_ip = wpforms_get_ip();

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 256 ) : '';
				$user_uuid  = ! empty( $_COOKIE['_wpfuuid'] ) ? sanitize_key( $_COOKIE['_wpfuuid'] ) : '';

				// Prepare the args to be saved.
				$data = [
					'form_id'    => absint( $form_id ),
					'user_id'    => absint( $user_id ),
					'status'     => 'abandoned',
					'fields'     => wp_json_encode( $fields ),
					'ip_address' => sanitize_text_field( $user_ip ),
					'user_agent' => sanitize_text_field( $user_agent ),
					'user_uuid'  => sanitize_text_field( $user_uuid ),
				];

				


				// Save entry.
				$entry_obj = wpforms()->get( 'entry' );
				$entry_id  = $entry_obj ? $entry_obj->add( $data ) : 0;

				// Save entry fields.
				$entry_fields_obj = wpforms()->get( 'entry_fields' );

				if ( $entry_fields_obj ) {
					$entry_fields_obj->save( $fields, $form_data, $entry_id );
				}

				// Send notification emails if configured.
				$process_obj = wpforms()->get( 'process' );

				if ( $process_obj ) {
					$process_obj->entry_email( $fields, [], $form_data, $entry_id, 'abandoned' );
				}
			}

			// Boom.
			// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
			do_action( 'wpforms_process_complete_form_abandonment', $fields, $entry, $form_data, $entry_id );
		}

		wp_send_json_success();
	}

	/**
	 * Logic that helps decide if we should send abandoned entries notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $process         Whether to process or not.
	 * @param array  $fields          Form fields.
	 * @param array  $form_data       Form data.
	 * @param int    $notification_id Notification ID.
	 * @param string $context         The context of the current email process.
	 *
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function process_email( $process, $fields, $form_data, $notification_id, $context ) {

		if ( ! $process ) {
			return false;
		}

		if ( $context === 'abandoned' && ! $this->has_form_abandonment( $form_data ) ) {
			// If form abandonment for the form is disabled, never send notifications for form abandonment.
			return false;
		}

		if ( $context === 'abandoned' ) {
			// Notifications triggered due to abandoned entry, don't send unless
			// the notification is enabled for form abandonment.
			if ( empty( $form_data['settings']['notifications'][ $notification_id ]['form_abandonment'] ) ) {
				return false;
			}
		} elseif ( ! empty( $form_data['settings']['notifications'][ $notification_id ]['form_abandonment'] ) ) {
			// Notifications triggered due to normal entry, don't send if
			// notification is enabled for form abandonment.
			return false;
		}

		return $process;
	}

	/**
	 * Delete abandoned entries when user completes the form submit.
	 *
	 * @since 1.4.1
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $entry     The post data submitted by the form.
	 * @param array $form_data The information for the form.
	 * @param int   $entry_id  Entry ID.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function process_complete( $fields, $entry, $form_data, $entry_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		global $wpdb;

		if (
			empty( $_COOKIE['_wpfuuid'] ) ||
			empty( $form_data['settings']['form_abandonment'] ) ||
			! wpforms_is_collecting_cookies_allowed()
		) {
			return;
		}

		$user_uuid      = sanitize_key( $_COOKIE['_wpfuuid'] );
		$hours_interval = 1 + (int) get_option( 'gmt_offset' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$abandonment_entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpforms_entries
         				WHERE `form_id` = %d AND `user_uuid` = %s AND `status` = 'abandoned' AND
         				      `date` >= DATE_SUB(%s,INTERVAL %d HOUR)",
				(int) $form_data['id'],
				preg_replace( '/[^a-z0-9_\s-]+/i', '', $user_uuid ),
				current_time( 'mysql' ),
				$hours_interval
			)
		);

		$entry_handler = wpforms()->get( 'entry' );

		if ( ! $entry_handler || ! $abandonment_entries ) {
			return;
		}

		foreach ( $abandonment_entries as $abandonment_entry ) {
			$entry_handler->delete( $abandonment_entry->entry_id );
		}
	}

	/**
	 * Prepare the form data to process the Repeater field.
	 *
	 * @since 1.12.0
	 *
	 * @param array|mixed  $form_data Form data.
	 * @param array|object $entry     Entry data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function prepare_repeater_form_data( $form_data, $entry ): array {

		$form_data = (array) $form_data;
		$process   = wpforms()->get( 'repeater_process' );

		if ( ! $process || ! method_exists( $process, 'prepare_form_data' ) ) {
			return $form_data;
		}

		return $process->prepare_form_data( $form_data, $entry );
	}

	/****************************
	 * Front-end functionality. *
	 ****************************/

	/**
	 * Add form class if form abandonment is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param array $class     List of HTML classes.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function form_container_class( $class, $form_data ) {

		if ( $this->has_form_abandonment( $form_data ) ) {
			$class[] = 'wpforms-form-abandonment';
		}

		return $class;
	}

	/**
	 * Enqueue assets in the frontend. Maybe.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms Page forms data and settings.
	 */
	public function frontend_enqueues( $forms ) {

		global $wp;

		$enabled = false;

		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
		$global = apply_filters( 'wpforms_global_assets', wpforms_setting( 'global-assets', false ) );
		$suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'WPFORMS_DEBUG' ) && WPFORMS_DEBUG ) ) ? '' : '.min';

		foreach ( $forms as $form ) {
			if ( $this->has_form_abandonment( $form ) ) {
				$enabled = true;

				break;
			}
		}

		if ( ! $enabled && ! $global ) {
			return;
		}

		/**
		 * Mouseleave js event timeout.
		 *
		 * Mouse leave timeout to abandon the entries when the user's mouse leaves the page.
		 *
		 * @since 1.7.0
		 *
		 * @param integer $var Timeout in milliseconds (0 by default).
		 */
		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation
		$mouse_leave_timeout = apply_filters( 'wpforms_form_abandonment_mouse_leave_timeout', 0 );

		/**
		 * Phone browser tabbed/close js event timeout.
		 *
		 * Phone browser leave timeout to abandon the entries when the user's mouse leaves the page.
		 *
		 * @since 1.8.0
		 *
		 * @param integer $var Timeout in milliseconds (0 by default).
		 */
		$phone_tabbed_timeout = apply_filters( 'wpforms_form_abandonment_phone_tabbed_timeout', 0 );

		/*
		 * If a form on the page has form abandonment enabled or global asset
		 * loading is turned on load mobile-detect lib and our js.
		 */

		// MobileDetect library.
		wp_enqueue_script(
			'wpforms-mobile-detect',
			WPFORMS_FORM_ABANDONMENT_URL . 'assets/js/vendor/mobile-detect' . $suffix . '.js',
			[],
			'1.4.3'
		);

		wp_enqueue_script(
			'wpforms-form-abandonment',
			WPFORMS_FORM_ABANDONMENT_URL . 'assets/js/wpforms-form-abandonment' . $suffix . '.js',
			[ 'jquery', 'wpforms-mobile-detect' ],
			WPFORMS_FORM_ABANDONMENT_VERSION
		);

		wp_localize_script(
			'wpforms-form-abandonment',
			'wpforms_form_abandonment',
			[
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'home_url'             => home_url(),
				'page_url'             => home_url( add_query_arg( $_GET, $wp->request ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'page_title'           => wpforms_process_smart_tags( '{page_title}', [], [], '' ),
				'page_id'              => wpforms_process_smart_tags( '{page_id}', [], [], '' ),
				'mouse_leave_timeout'  => $mouse_leave_timeout,
				'phone_tabbed_timeout' => $phone_tabbed_timeout,
			]
		);
	}

	/*********
	 * Misc. *
	 *********/

	/**
	 * Helper function that checks if form abandonment is enabled on a form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function has_form_abandonment( $form_data = [] ) {

		return ! empty( $form_data['settings']['form_abandonment'] );
	}
}
