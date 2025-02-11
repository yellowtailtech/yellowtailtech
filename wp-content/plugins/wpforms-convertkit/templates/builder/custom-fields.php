<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpforms-builder-provider-connection-block wpforms-builder-provider-connection-fields">
	<h4 id="custom-fields"><?php esc_html_e( 'Custom Fields', 'wpforms-convertkit' ); ?></h4>
	<table class="wpforms-builder-provider-connection-fields-table" aria-describedby="custom-fields">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Custom Field Name', 'wpforms-convertkit' ); ?></th>
				<th colspan="3"><?php esc_html_e( 'Form Field Value', 'wpforms-convertkit' ); ?></th>
			</tr>
		</thead>
		<tbody>

		<# let index = 0;#>
		<# if ( ! _.isEmpty( data.connection.custom_fields ) ) { #>
			<# _.each( data.connection.custom_fields, function( item, meta_id ) { #>
				<tr class="wpforms-builder-provider-connection-fields-table-row">
					<td>
						<select
							class="js-wpforms-builder-provider-connection-field-name wpforms-builder-provider-connection-field-name"
							name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][fields_meta][{{ index }}][name]"
							data-here="{{ meta_id }}"
						>
							<option value=""><# if ( ! _.isEmpty( data.provider.placeholder ) ) { #>{{ data.provider.placeholder }}<# } else { #><?php esc_html_e( '--- Select Field ---', 'wpforms-convertkit' ); ?><# } #></option>
							<option value="new_custom_field"><?php esc_html_e( 'New Custom Field', 'wpforms-convertkit' ); ?></option>

							<# _.each( data.field.options, function( field_name, field_id ) { #>
								<option value="{{ field_id }}" <# if ( field_id === meta_id ) { #>selected="selected"<# } #>>
									{{ field_name }}
								</option>
							<# } ); #>
						</select>

						<input type="text" class="wpforms-builder-provider-connection-field-name" placeholder="<?php esc_attr_e( 'Field Name', 'wpforms-convertkit' ); ?>"/>

						<a href="#" class="js-wpforms-builder-convertkit-new-custom-field-close wpforms-builder-convertkit-new-custom-field-close fa fa-close"></a>
					</td>
					<td>
						<select
							class="wpforms-builder-provider-connection-field-value"
							name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][fields_meta][{{ index }}][field_id]"
						>
							<option value=""><?php esc_html_e( '--- Select Form Field ---', 'wpforms-convertkit' ); ?></option>

							<# _.each( data.options, function( field, key ) { #>
								<option value="{{ field.id }}" <# if ( field.id === item ) { #>selected="selected"<# } #>>
									<# if ( ! _.isUndefined( field.label ) && field.label.toString().trim() !== '' ) { #>
										{{ field.label.toString().trim() }}
									<# } else { #>
										{{ wpforms_builder.field + ' #' + key }}
									<# } #>
								</option>
							<# } ); #>
						</select>
					</td>
					<td class="add">
						<button
							class="button-secondary js-wpforms-builder-provider-connection-fields-add"
							title="<?php esc_attr_e( 'Add Another', 'wpforms-convertkit' ); ?>"
						>
							<i class="fa fa-plus-circle"></i>
						</button>
					</td>
					<td class="delete">
						<button
							class="button js-wpforms-builder-provider-connection-fields-delete <# if ( index === 0 ) { #>hidden<# } #>"
							title="<?php esc_attr_e( 'Remove', 'wpforms-convertkit' ); ?>"
						>
							<i class="fa fa-minus-circle"></i>
						</button>
					</td>
				</tr>
			<# index++; #>
			<# } ); #>
		<# } else { #>
			<tr class="wpforms-builder-provider-connection-fields-table-row">
			<td>
				<select
					class="js-wpforms-builder-provider-connection-field-name wpforms-builder-provider-connection-field-name"
					name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][fields_meta][0][name]"
				>
					<option value=""><# if ( ! _.isEmpty( data.provider.placeholder ) ) { #>{{ data.provider.placeholder }}<# } else { #><?php esc_html_e( '--- Select Field ---', 'wpforms-convertkit' ); ?><# } #></option>
					<option value="new_custom_field"><?php esc_html_e( 'New Custom Field', 'wpforms-convertkit' ); ?></option>

					<# _.each( data.field.options, function( field_name, field_id ) { #>
					<option value="{{ field_id }}">
						{{ field_name }}
					</option>
					<# } ); #>

				</select>

				<input type="text" class="wpforms-builder-provider-connection-field-name" placeholder="<?php esc_attr_e( 'Field Name', 'wpforms-convertkit' ); ?>"/>

				<a href="#" class="js-wpforms-builder-convertkit-new-custom-field-close wpforms-builder-convertkit-new-custom-field-close fa fa-close"></a>
			</td>
			<td>
				<select
					class="wpforms-builder-provider-connection-field-value"
					name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][fields_meta][0][field_id]"
				>
					<option value=""><?php esc_html_e( '--- Select Form Field ---', 'wpforms-convertkit' ); ?></option>

					<# _.each( data.options, function( field, key ) { #>
						<option value="{{ field.id }}">
							<# if ( ! _.isUndefined( field.label ) && field.label.toString().trim() !== '' ) { #>
								{{ field.label.toString().trim() }}
							<# } else { #>
								{{ wpforms_builder.field + ' #' + key }}
							<# } #>
						</option>
					<# } ); #>
				</select>
			</td>
			<td class="add">
				<button
					class="button-secondary js-wpforms-builder-provider-connection-fields-add"
					title="<?php esc_attr_e( 'Add Another', 'wpforms-convertkit' ); ?>"
				>
					<i class="fa fa-plus-circle"></i>
				</button>
			</td>
			<td class="delete">
				<button
					class="button js-wpforms-builder-provider-connection-fields-delete hidden"
					title="<?php esc_attr_e( 'Delete', 'wpforms-convertkit' ); ?>"
				>
					<i class="fa fa-minus-circle"></i>
				</button>
			</td>
		</tr>
		<# } #>
		</tbody>
	</table><!-- /.wpforms-builder-provider-connection-fields-table -->

	<p class="description">
		<?php
		echo esc_html(
			sprintf( /* translators: %1$s - current provider name. */
				__( 'Map %1$s custom fields to form fields values. New custom fields will be created in %1$s when the form is saved.', 'wpforms-convertkit' ),
				'Kit'
			)
		);
		?>
	</p>

</div><!-- /.wpforms-builder-provider-connection-fields -->
