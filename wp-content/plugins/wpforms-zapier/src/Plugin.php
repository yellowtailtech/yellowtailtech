<?php

namespace WPFormsZapier;

use WPForms_Provider;
use WPForms_Updater;

/**
 * Zapier integration.
 *
 * @since 1.0.0
 */
class Plugin extends WPForms_Provider {

	/**
	 * Zapier docs link.
	 *
	 * @since 1.4.0
	 */
	const ZAPIER_DOCS_LINK = 'https://wpforms.com/docs/how-to-install-and-use-zapier-addon-with-wpforms/';

	/**
	 * Zapier SDK link.
	 *
	 * @since 1.5.0
	 */
	const ZAPIER_SDK_LINK = 'https://cdn.zapier.com/packages/partner-sdk/v0/zapier-elements';

	/**
	 * Returns the instance.
	 *
	 * @since 1.6.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->version  = WPFORMS_ZAPIER_VERSION;
		$this->name     = 'Zapier';
		$this->slug     = 'zapier';
		$this->priority = 60;
		$this->icon     = WPFORMS_ZAPIER_URL . 'assets/images/addon-icon-zapier.png';
		$this->type     = 'Zap';

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.5.0
	 */
	private function hooks() {

		add_action( 'init', [ $this, 'zapier_callback' ] );
		add_filter( 'wpforms_providers_zapier_configured', [ $this, 'builder_sidebar_configured' ] );
		add_action( 'wpforms_builder_enqueues_before', [ $this, 'builder_enqueues' ] );

		add_action( 'wpforms_updater', [ $this, 'updater' ] );
		add_filter( 'wpforms_form_handler_add_notices', [ $this, 'add_disconnected_warning' ], 10, 3 );
	}

	/**
	 * Add warning to the form / form template that Zapier was disconnected after duplication.
	 *
	 * @since 1.6.0
	 *
	 * @param array $notices       Array with notices.
	 * @param array $new_form_data Form data.
	 * @param int   $form_id       Original form ID.
	 *
	 * @return array
	 */
	public function add_disconnected_warning( $notices, array $new_form_data, int $form_id ): array {

		// Check if original form had any Zaps connected.
		$is_zapier_connected = get_post_meta( $form_id, 'wpforms_zapier', true );

		if ( ! $is_zapier_connected ) {
			return $notices;
		}

		$notices['zapier'] = [
			'title'   => __( 'Zaps Have Been Disabled', 'wpforms-zapier' ),
			'message' => sprintf( /* translators: %s - URL to the list of Zaps. */
				__( 'Head over to the Zapier settings in the Marketing tab or visit your <a href="%s" target="_blank" rel="noopener noreferrer">Zapier account</a> to restore them.', 'wpforms-zapier' ),
				esc_url( 'https://zapier.com/app/zaps' )
			),
		];

		return $notices;
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Zapier',
				'plugin_slug' => 'wpforms-zapier',
				'plugin_path' => plugin_basename( WPFORMS_ZAPIER_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_ZAPIER_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_ZAPIER_VERSION,
				'key'         => $key,
			]
		);
	}

	/**
	 * Forms configured with Zapier do not have the connection information
	 * stored in the form_data, so the default indicator that shows if the form
	 * is configured will not work. Instead we filter the indicator and check
	 * the correct data location.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function builder_sidebar_configured() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0;

		if ( ! empty( $form_id ) ) {
			$zaps = get_post_meta( $form_id, 'wpforms_zapier', true );

			if ( ! empty( $zaps ) ) {
				return 'configured';
			}
		}

		return '';
	}

	/**
	 * Enqueue scripts in the Form Builder.
	 *
	 * @since 1.5.0
	 */
	public function builder_enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-admin-builder-zapier',
			WPFORMS_ZAPIER_URL . "assets/css/admin-builder-zapier{$min}.css",
			[ 'wpforms-builder' ],
			WPFORMS_ZAPIER_VERSION
		);

		wp_enqueue_script(
			'wpforms-admin-builder-zapier',
			WPFORMS_ZAPIER_URL . "assets/js/admin-builder-zapier{$min}.js",
			[ 'jquery', 'wpforms-builder', 'wpforms-utils' ],
			WPFORMS_ZAPIER_VERSION,
			true
		);
	}

	/**
	 * Define formatted fields delimiter.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_format_fields_delimiter() {

		/**
		 * Allow developers to filter delimiter format.
		 *
		 * @since 1.4.0
		 *
		 * @param string $delimiter Delimiter.
		 */
		return apply_filters( 'wpforms_zapier_get_format_fields_delimiter', ', ' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Build the entry field information to send to Zapier.
	 *
	 * @since 1.0.0
	 *
	 * @param array|int  $form_data Form data or Form ID.
	 * @param array      $entry     Entry details.
	 * @param string|int $entry_id  Entry ID.
	 *
	 * @return array
	 */
	public function format_fields( $form_data, $entry = '', $entry_id = '' ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded

		$data   = [];
		$fields = wpforms_get_form_fields( $form_data );

		if ( empty( $fields ) ) {
			return $data;
		}

		if ( ! empty( $entry_id ) ) {
			$data['id'] = absint( $entry_id );
		}

		foreach ( $fields as $field_id => $field ) {

			/* translators: %s - field id. */
			$label  = ! empty( $field['label'] ) ? sanitize_text_field( $field['label'] ) : sprintf( esc_html__( 'Field #%s', 'wpforms-zapier' ), $field_id );
			$extras = [];

			$has_value = isset( $entry[ $field_id ]['value'] ) && ! wpforms_is_empty_string( $entry[ $field_id ]['value'] );

			if ( $has_value ) {
				$value = wpforms_sanitize_textarea_field( $entry[ $field_id ]['value'] );
			} else {
				$value = '';
			}

			if ( in_array( $field['type'], [ 'checkbox', 'select' ], true ) ) {
				$value = '';

				if ( $has_value && is_array( $entry ) && is_array( $entry[ $field_id ] ) ) {
					$value = implode( $this->get_format_fields_delimiter(), explode( "\n", trim( $entry[ $field_id ]['value'] ) ) );
				}
			}

			// Allow multiple file uploading.
			if ( $field['type'] === 'file-upload' ) {

				$value = ( ! empty( $entry[ $field_id ]['value'] ) ) ? explode( "\n", stripslashes( $entry[ $field_id ]['value'] ) ) : [];
			}

			if ( empty( $entry ) ) {
				$data[] = [
					'key'   => 'field' . $field_id,
					'label' => $label,
					'type'  => 'unicode',
				];
			} else {
				$data[ 'field' . $field_id ] = $value;
			}

			// Add additional sub fields.
			if ( $field['type'] === 'name' ) {

				$extras = [
					'first'  => esc_html__( 'First', 'wpforms-zapier' ),
					'middle' => esc_html__( 'Middle', 'wpforms-zapier' ),
					'last'   => esc_html__( 'Last', 'wpforms-zapier' ),
				];

			} elseif ( $field['type'] === 'checkbox' ) {

				foreach ( $field['choices'] as $choice_id => $choice ) {
					$choice['value'] = sanitize_text_field( $choice['value'] );
					$choice['label'] = sanitize_text_field( $choice['label'] );

					if ( empty( $choice['label'] ) ) {
						if (
							( count( $field['choices'] ) === 1 && $value === 'Checked' ) ||
							( count( $field['choices'] ) > 1 && 'Choice ' . $choice_id === $value )
						) {
							$choice_checked = 'checked';
						} else {
							$choice_checked = '';
						}
					} else {
						$choice_checked = ( strpos( $value, $choice['label'] ) !== false ) ? 'checked' : '';
					}
					if ( empty( $entry ) ) {
						$data[] = [
							'key'   => 'field' . $field_id . '_choice' . $choice_id,
							'label' => $choice['label'],
							'type'  => 'unicode',
						];
					} else {
						$choice['value'] = $has_value ? $choice['value'] : $choice['label'];
						$choice['value'] = ( ! empty( $choice_checked ) ) ? $choice['value'] : '';

						$data[ 'field' . $field_id . '_choice' . $choice_id ] = ( ! empty( $field['show_values'] ) && 1 === (int) $field['show_values'] ) ? $choice['value'] : $choice_checked;
					}
				}
			} elseif ( $field['type'] === 'address' ) {

				$extras = [
					'address1' => esc_html__( 'Line 1', 'wpforms-zapier' ),
					'address2' => esc_html__( 'Line 2', 'wpforms-zapier' ),
					'city'     => esc_html__( 'City', 'wpforms-zapier' ),
					'state'    => esc_html__( 'State', 'wpforms-zapier' ),
					'region'   => esc_html__( 'Region', 'wpforms-zapier' ),
					'postal'   => esc_html__( 'Postal', 'wpforms-zapier' ),
					'country'  => esc_html__( 'Country', 'wpforms-zapier' ),
				];

			} elseif ( $field['type'] === 'date-time' ) {

				$extras = [
					'date' => esc_html__( 'Date', 'wpforms-zapier' ),
					'time' => esc_html__( 'Time', 'wpforms-zapier' ),
					'unix' => esc_html__( 'Unix Timestamp', 'wpforms-zapier' ),
				];

			} elseif ( in_array( $field['type'], [ 'payment-total', 'payment-single', 'payment-multiple', 'payment-select' ], true ) ) {

				// Decode for currency symbols.
				if ( ! empty( $entry ) ) {
					$data[ 'field' . $field_id ] = html_entity_decode( $value );
				}

				// Send raw amount.
				$extras = [
					'amount_raw' => esc_html__( 'Plain Amount', 'wpforms-zapier' ),
				];
			}

			// Add extra fields.
			if ( ! empty( $extras ) ) {
				foreach ( $extras as $extra_key => $extra ) {
					$extra_value = ! empty( $entry[ $field_id ][ $extra_key ] ) ? sanitize_text_field( $entry[ $field_id ][ $extra_key ] ) : '';
					$extra_label = sprintf( '%s (%s)', $label, $extra );

					if ( empty( $entry ) ) {
						$data[] = [
							'key'   => 'field' . $field_id . '_' . $extra_key,
							'label' => $extra_label,
							'type'  => 'unicode',
						];
					} else {
						$data[ 'field' . $field_id . '_' . $extra_key ] = $extra_value;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Final/sanitized submitted field data.
	 * @param array $entry     Copy of original $_POST.
	 * @param array $form_data Form data and settings.
	 * @param int   $entry_id  Entry ID.
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) {

		// Only run if this form has connections for this provider and entry has fields.
		$zaps = get_post_meta( $form_data['id'], 'wpforms_zapier', true );

		if ( empty( $zaps ) || empty( $fields ) ) {
			return;
		}

		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
		$data = apply_filters( 'wpforms_zapier_process_entry_data', $this->format_fields( $form_data, $fields, $entry_id ), $entry_id, $form_data );

		/*
		 * Fire for each Zap.
		 */

		foreach ( $zaps as $zap_id => $zap ) :

			// Only process this Zap if it is enabled.
			if ( empty( $zap['hook'] ) ) {
				continue;
			}

			$post_data = [
				'ssl'     => true,
				'body'    => wp_json_encode( $data ),
				'headers' => [
					'X-WPForms-Zapier-Version' => WPFORMS_ZAPIER_VERSION,
				],
			];
			$response  = wp_remote_post( $zap['hook'], $post_data );

			// Check for errors.
			if ( is_wp_error( $response ) ) {
				wpforms_log(
					esc_html__( 'Zapier Zap error', 'wpforms-zapier' ),
					$post_data,
					[
						'type'    => [ 'provider', 'error' ],
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					]
				);
			}

		endforeach;
	}

	/**
	 * Return WPForms Zapier API key.
	 *
	 * If one hasn't been generated yet then we create one and save it.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_apikey() {

		$key = get_option( 'wpforms_zapier_apikey' );

		if ( empty( $key ) ) {

			$chars = array_merge( range( 0, 9 ), range( 'a', 'z' ) );
			$key   = '';

			for ( $i = 0; $i < 20; $i ++ ) {
				$key .= $chars[ wp_rand( 0, count( $chars ) - 1 ) ];
			}

			update_option( 'wpforms_zapier_apikey', $key );
		}

		return $key;
	}

	/************************************************************************
	 * API methods - these methods interact directly with the provider API. *
	 ************************************************************************/

	/**
	 * Callback to provide Zapier with specific information for forms and fields.
	 *
	 * @since 1.0.0
	 *
	 * @noinspection NonSecureUniqidUsageInspection
	 */
	public function zapier_callback() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded

		$data = [];

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$wpforms_zapier = isset( $_GET['wpforms_zapier'] ) ?
			sanitize_text_field( wp_unslash( $_GET['wpforms_zapier'] ) ) :
			'';

		$wpforms_action = isset( $_GET['wpforms_action'] ) ?
			sanitize_text_field( wp_unslash( $_GET['wpforms_action'] ) ) :
			'';

		$wpforms_form = isset( $_GET['wpforms_form'] ) ?
			absint( wp_unslash( $_GET['wpforms_form'] ) ) :
			0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// WPForms Zapier API key is required.
		if ( empty( $wpforms_zapier ) ) {
			return;
		}

		// Callback action is required.
		if ( empty( $wpforms_action ) ) {
			return;
		}

		// Validate provided API Key.
		$apikey = get_option( 'wpforms_zapier_apikey' );

		if ( empty( $apikey ) || trim( $wpforms_zapier ) !== $apikey ) {
			// Key is incorrect or missing.
			nocache_headers();
			header( 'HTTP/1.1 401 Unauthorized' );
			echo wp_json_encode(
				[
					'error' => esc_html__( 'Invalid WPForms Zapier API key', 'wpforms-zapier' ),
				]
			);
			exit;
		}

		// Provide available forms.
		if ( $wpforms_action === 'forms' ) {

			$form_handler = wpforms()->get( 'form' );
			$args         = [];

			// @WPFormsBackCompatStart User Generated Templates since WPForms v1.8.8
			if ( defined( get_class( $form_handler ) . '::POST_TYPES' ) ) {
				$args['post_type'] = $form_handler::POST_TYPES;
			}
			// @WPFormsBackCompatEnd

			$forms = wpforms()->get( 'form' )->get( '', $args );

			if ( ! empty( $forms ) ) {

				foreach ( $forms as $form ) {
					$data['forms'][] = [
						'id'   => $form->ID,
						'name' => wpforms_decode_string( $form->post_title ),
					];
				}
			}
		}

		// Provide available fields from a recent form entry.
		if ( $wpforms_action === 'entries' && ! empty( $wpforms_form ) ) {

			$entries = wpforms()->get( 'entry' )->get_entries(
				[
					'form_id' => $wpforms_form,
				]
			);

			if ( ! empty( $entries ) ) {
				foreach ( $entries as $entry ) {
					$fields = json_decode( $entry->fields, true );
					$data[] = $this->format_fields( $wpforms_form, $fields, $entry->entry_id );
				}
			}
		}

		// Provide available fields.
		if ( $wpforms_action === 'entry' && ! empty( $wpforms_form ) ) {

			$data = $this->format_fields( $wpforms_form );
		}

		// Subscribe/Add Zap.
		if ( $wpforms_action === 'subscribe' ) {

			$form_id = $wpforms_form;

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$hook = ! empty( $_GET['hook_url'] ) ? esc_url_raw( wp_unslash( $_GET['hook_url'] ) ) : '';
			$name = ! empty( $_GET['zap_name'] ) ? sanitize_text_field( wp_unslash( $_GET['zap_name'] ) ) : '';
			$link = ! empty( $_GET['zap_link'] ) ? esc_url_raw( wp_unslash( $_GET['zap_link'] ) ) : '';
			$live = ! empty( $_GET['zap_live'] ) && strtolower( sanitize_text_field( wp_unslash( $_GET['zap_live'] ) ) ) === 'true';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			$id = uniqid();

			$zaps = get_post_meta( $form_id, 'wpforms_zapier', true );

			if ( empty( $zaps ) ) {
				$zaps = [];
			}

			$zaps[ $id ] = [
				'name' => $name,
				'hook' => $hook,
				'link' => $link,
				'live' => $live,
				'date' => time(),
			];

			update_post_meta( $form_id, 'wpforms_zapier', $zaps );

			$data = [
				'status' => 'subscribed',
			];
		}

		// Unsubscribe/Delete Zap.
		if ( $wpforms_action === 'unsubscribe' ) {

			$form_id = $wpforms_form;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url = ! empty( $_GET['hook_url'] ) ? esc_url_raw( wp_unslash( $_GET['hook_url'] ) ) : '';

			$zaps = get_post_meta( $form_id, 'wpforms_zapier', true );

			if ( ! empty( $zaps ) ) {
				foreach ( $zaps as $zap_id => $zap ) {
					if ( $url === $zap['hook'] ) {
						unset( $zaps[ $zap_id ] );
					}
				}
				if ( empty( $zaps ) ) {
					delete_post_meta( $form_id, 'wpforms_zapier' );
				} else {
					update_post_meta( $form_id, 'wpforms_zapier', $zaps );
				}
			}

			$data = [
				'status' => 'unsubscribed',
			];
		}

		// If data is empty something went wrong, so we stop.
		if ( empty( $data ) ) {
			$data = [
				'error' => esc_html__( 'No data', 'wpforms-zapier' ),
			];
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Post entry to Zapier webhook.
	 *
	 * @since 1.0.0
	 */
	public function zapier_post() {
	}

	/********************************************************
	 * Builder methods - these methods _build_ the Builder. *
	 ********************************************************/

	/**
	 * Custom Zapier builder content.
	 *
	 * @since 1.0.0
	 */
	public function builder_output() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = ! empty( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$zaps    = get_post_meta( $form_id, 'wpforms_zapier', true );

		?>

		<div class="wpforms-panel-content-section wpforms-panel-content-section-<?php echo esc_attr( $this->slug ); ?>" id="<?php echo esc_attr( $this->slug ); ?>-provider">

			<div class="wpforms-panel-content-section-title">

				<?php echo esc_html( $this->name ); ?>

			</div>

			<div class="wpforms-provider-connections-wrap wpforms-clear">

				<div class="wpforms-provider-connections">

					<?php
					$this->builder_output_apikey();
					$this->builder_output_apiurl();
					?>

					<?php
					if ( empty( $zaps ) ) {

						echo '<p>';
						esc_html_e( 'Zapier automatically moves info between WPForms and other apps you use every day, so you can save time, reduce tedious tasks, and focus on your most important work. Set up automated workflows (called Zaps) that send form entries to your CRM, email app, marketing automation tool, and much more — no manual work or coding required.', 'wpforms-zapier' );
						echo '</p>';

						$this->builder_output_doc_link();

					} else {

						foreach ( $zaps as $zap ) {

							echo '<div class="wpforms-provider-connection">';

							$name = ! empty( $zap['name'] ) ? sanitize_text_field( $zap['name'] ) : esc_html__( 'No name', 'wpforms-zapier' );
							$live = $zap['live'] ? esc_html__( 'Yes', 'wpforms-zapier' ) : esc_html__( 'No', 'wpforms-zapier' );

							echo '<div class="wpforms-provider-connection-header"><span>' . esc_html( $name ) . '</span></div>
								<div class="wpforms-provider-connection-content">
									<p>
										<strong>' . esc_html__( 'Date Connected', 'wpforms-zapier' ) . '</strong>
										<span>' . esc_html( wpforms_date_format( $zap['date'], '', true ) ) . '</span>
									</p>
									<p>
										<strong>' . esc_html__( 'Live', 'wpforms-zapier' ) . '</strong>
										<span>' . esc_html( $live ) . '</span>
									</p>

								</div>
							</div>
							';
						}

						$this->builder_output_doc_link();
					}

					/**
					 * Filter featured zaps list.
					 *
					 * @since 1.3.0
					 *
					 * @param array $featured_zaps Featured Zaps list.
					 */
					$zaps = (array) apply_filters( // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
						'wpforms_zapier_featured_zaps',
						[
							412343, // MailerLite.
							412371, // HubSpot.
							412378, // ConvertKit.
							412383, // Zoho CRM.
							14480,  // Infusionsoft.
							14477,  // Trello.
							413887, // Twilio SMS.
							14475,  // Google Calendar.
							14481,  // Slack.
						]
					);

					if ( ! empty( $zaps ) ) {
						$zaps = implode( ',', array_map( 'intval', $zaps ) );

						// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
						printf(
							'<h3 style="margin-top: 30px;">%1$s</h3>
							<script id="wpforms-zapier-builder-embed-js" type="module" data-src="%2$s"></script>
							<link id="wpforms-zapier-builder-embed-css" rel="stylesheet" href="" data-href="%3$s" />
							<zapier-zap-templates ids="%4$s" limit="5" use-this-zap="show" theme="light"></zapier-zap-templates>',
							esc_html__( 'Popular ways to use WPForms + Zapier', 'wpforms-zapier' ),
							esc_attr( self::ZAPIER_SDK_LINK . '/zapier-elements.esm.js' ),
							esc_attr( self::ZAPIER_SDK_LINK . '/zapier-elements.css' ),
							esc_attr( $zaps )
						);
						// phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
					}
					?>

				</div>

			</div>

		</div>
		<?php
	}

	/**
	 * Output documentation paragraph.
	 *
	 * @since 1.3.0
	 */
	public function builder_output_doc_link() {

		echo '<p><a href="' . esc_url( wpforms_utm_link( self::ZAPIER_DOCS_LINK, 'Marketing Integrations', 'Zapier Documentation' ) ) . '" target="_blank" rel="noopener noreferrer">' .
			esc_html__( 'Get started connecting WPForms with Zapier.', 'wpforms-zapier' ) .
			'</a></p>';
	}

	/**
	 * Output API key paragraph.
	 *
	 * @since 1.3.0
	 */
	public function builder_output_apikey() {

		printf(
			/* translators: %s - API key. */
			'<p>' . esc_html__( 'Your WPForms Zapier API key: %s', 'wpforms-zapier' ) . '</p>',
			'<code>' . esc_html( $this->get_apikey() ) . '</code>'
		);
	}

	/**
	 * Output API URL paragraph.
	 *
	 * @since 1.5.0
	 */
	public function builder_output_apiurl() {

		printf(
			/* translators: %s - site home URL. */
			'<p>' . esc_html__( 'Your website URL for Zapier configuration: %s', 'wpforms-zapier' ) . '</p>',
			'<code>' . esc_url( home_url() ) . '</code>'
		);
	}

	/*************************************************************************
	 * Integrations tab methods - these methods relate to the settings page. *
	 *************************************************************************/

	/**
	 * Add custom Zapier panel to the Settings Integrations tab.
	 *
	 * @since 1.0.0
	 *
	 * @param array $active   Active integrations.
	 * @param array $settings Configuration settings for all providers.
	 */
	public function integrations_tab_options( $active, $settings ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$forms = get_posts(
			[
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'posts_per_page' => 999,
				'post_type'      => 'wpforms',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					[
						'key'     => 'wpforms_zapier',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		$slug      = esc_attr( $this->slug );
		$name      = esc_html( $this->name );
		$connected = ! empty( $forms );
		$class     = $connected ? 'connected' : '';
		$arrow     = 'right';

		// This lets us highlight a specific service by a special link.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$wpforms_integration = isset( $_GET['wpforms-integration'] ) ?
			sanitize_text_field( wp_unslash( $_GET['wpforms-integration'] ) ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $wpforms_integration ) ) {
			if ( $this->slug === $wpforms_integration ) {
				$class .= ' focus-in';
				$arrow  = 'down';
			} else {
				$class .= ' focus-out';
			}
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<div id="wpforms-integration-<?php echo $slug; ?>" class="wpforms-settings-provider wpforms-clear <?php echo $slug; ?> <?php echo $class; ?>">

			<div class="wpforms-settings-provider-header wpforms-clear" data-provider="<?php echo $slug; ?>">

				<div class="wpforms-settings-provider-logo">
					<i title="<?php esc_attr_e( 'Show Accounts', 'wpforms-zapier' ); ?>" class="fa fa-chevron-<?php echo esc_attr( $arrow ); ?>" aria-hidden="true"></i>
					<img src="<?php echo esc_url( $this->icon ); ?>" alt="">
				</div>

				<div class="wpforms-settings-provider-info">
					<h3><?php echo $name; ?></h3>
					<p>
						<?php
						printf( /* translators: %s - provider name. */
							esc_html__( 'Integrate %s with WPForms', 'wpforms-zapier' ),
							$name
						);
						?>
					</p>
					<span class="connected-indicator green">
						<i class="fa fa-check-circle-o" aria-hidden="true"></i>&nbsp;<?php esc_html_e( 'Connected', 'wpforms-zapier' ); ?>
					</span>
				</div>

			</div>

			<div class="wpforms-settings-provider-accounts" id="provider-<?php echo $slug; ?>">

				<p>
					<?php
					printf( /* translators: %s - API key. */
						esc_html__( 'Your WPForms Zapier API key: %s', 'wpforms-zapier' ),
						'<code>' . esc_attr( $this->get_apikey() ) . '</code>'
					);
					?>
				</p>

				<p>
					<?php
					printf( /* translators: %s - site home URL. */
						esc_html__( 'Your website URL for Zapier configuration: %s', 'wpforms-zapier' ),
						'<code>' . esc_url( home_url() ) . '</code>'
					);
					?>
				</p>

				<?php
				if ( empty( $forms ) ) {
					echo '<p>' . esc_html__( 'No forms are currently connected.', 'wpforms-zapier' ) . '</p>';
					echo '<p><a href="' . esc_url( wpforms_utm_link( self::ZAPIER_DOCS_LINK, 'Settings - Integration', 'Zapier Documentation' ) ) . '" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Click here for documentation on connecting WPForms with Zapier.', 'wpforms-zapier' ) .
						'</a></p>';

				} else {
					echo '<p>' . esc_html__( 'The forms below are currently connected to Zapier.', 'wpforms-zapier' ) . '</p>';
					echo '<div class="wpforms-settings-provider-accounts-list">';
					echo '<ul>';
					foreach ( $forms as $form ) {
						echo '<li class="wpforms-clear">';
						echo '<span class="label">' . esc_html( $form->post_title ) . '</span>';
						echo '<span class="date">';
						echo esc_html(
							sprintf( /* translators: %1$s - Connection date. */
								__( 'Connected on: %1$s', 'wpforms-zapier' ),
								wpforms_date_format( $form->post_date, '', true )
							)
						);
						echo '</span>';
						echo '</li>';
					}
					echo '</ul>';
					echo '</div>';
				}
				?>

			</div>

		</div>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
