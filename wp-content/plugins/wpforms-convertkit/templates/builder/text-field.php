<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpforms-builder-provider-connection-block wpforms-builder-convertkit-provider-{{ data.name }}">
	<h4>{{ data.field.label }}</h4>
	<input
		type="text"
		class="wpforms-builder-provider-connection-field-value"
		name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][{{ data.name }}]"
		value=""
	>

	<# if ( _.has( data.field, 'description' ) ) { #>
		<p class="description">{{ data.field.description }}</p>
	<# } #>
</div>
