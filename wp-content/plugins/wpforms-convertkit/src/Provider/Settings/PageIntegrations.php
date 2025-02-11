<?php

namespace WPFormsConvertKit\Provider\Settings;

use Exception;
use RuntimeException;
use WPForms\Providers\Provider\Settings\PageIntegrations as PageIntegrationsAbstract;

/**
 * Class PageIntegrations handles functionality on the Settings > Integrations page.
 *
 * @since 1.0.0
 */
class PageIntegrations extends PageIntegrationsAbstract {

	/**
	 * AJAX to add a provider from the settings integrations tab.
	 *
	 * @since 1.0.0
	 *
	 * phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing
	 */
	public function ajax_connect() {

		parent::ajax_connect();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$data = wp_unslash( wp_parse_args( $_POST['data'] ) );

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

			$account = wpforms_convertkit()
				->get( 'account' )
				->add(
					sanitize_text_field( $data['api_key'] ),
					sanitize_text_field( $data['api_secret'] )
				);

			ob_start();
			$this->display_connected_account( $account['key'], $account );
			wp_send_json_success(
				[
					'html' => ob_get_clean(),
				]
			);
		} catch ( Exception $exception ) {
			wp_send_json_error(
				[
					'error_msg' => esc_html( $exception->getMessage() ),
				]
			);
		}
	}

	/**
	 * Display fields that will store ConvertKit account details.
	 *
	 * @since 1.0.0
	 */
	protected function display_add_new_connection_fields() {

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render( WPFORMS_CONVERTKIT_PATH . 'templates/new-account-form' );
	}
}
