<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_key_placeholder = sprintf( /* translators: %1$s - current provider name. */
	__( '%1$s API Key', 'wpforms-convertkit' ),
	'Kit'
);

$api_secret_placeholder = sprintf( /* translators: %1$s - current provider name. */
	__( '%1$s API Secret', 'wpforms-convertkit' ),
	'Kit'
);
?>
<p>
	<label>
		<input type="text" name="api_key" class="wpforms-required" required placeholder="<?php echo esc_attr( $api_key_placeholder ) . ' *'; ?>">
		<span class="error" style="display: none;"></span>
	</label>
	<label>
		<input type="text" name="api_secret" class="wpforms-required" required placeholder="<?php echo esc_attr( $api_secret_placeholder ) . ' *'; ?>">
		<span class="error" style="display: none;"></span>
	</label>
</p>
<p class="description">
	<?php
	printf(
		wp_kses( /* translators: %1$s - current provider name, %2$s - URL to the Kit Getting started page. */
			__( 'The API Key and API Secret can be found in your %1$s account settings. <a href="%2$s" target="_blank" rel="noopener noreferrer">Learn More</a>', 'wpforms-convertkit' ),
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
			]
		),
		'Kit',
		esc_url(
			wpforms_utm_link(
				'https://wpforms.com/docs/convertkit-addon/',
				'Integration Settings',
				'ConvertKit Documentation'
			)
		)
	);
	?>
</p>
<p class="error wpforms-convertkit-admin-form-error" style="display: none">
	<?php esc_html_e( 'Something went wrong while performing an AJAX request.', 'wpforms-convertkit' ); ?>
</p>
