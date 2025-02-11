<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpforms-builder-provider-connection-block">
	<h4>{{ data.field.label }}<# if ( data.field.required ) { #><span class="required">*</span><# } #></h4>
	<select
		class="wpforms-builder-convertkit-provider-connection-{{data.name}} <# if ( data.field.multiple ) { #> choicesjs-select<# } #><# if ( data.field.map ) { #> wpforms-field-map-select<# } #><# if ( data.field.required ) { #> wpforms-required<# } #>"
		name="providers[{{ data.provider.slug }}][{{ data.connection.id }}][{{ data.name }}]<# if ( data.field.multiple ) { #>[]<# } #>"
		<# if ( data.field.map ) { #>
			data-field-map-allowed="{{ data.field.map }}"
			data-field-map-placeholder="<?php esc_html_e( '--- Select Form Field ---', 'wpforms-convertkit' ); ?>"
		<# } #>
		<# if ( data.field.multiple ) { #>multiple<# } #>
	>
		<# connectionData = _.isArray( data.connection[data.name] ) ? data.connection[data.name] : [ data.connection[data.name] ]; #>
		<option value="" <# if ( data.field.multiple ) { #>disabled<# } #> >{{ data.field.placeholder }}</option>
		<# options = typeof data.field.options !== 'undefined' ? data.field.options : data.options; #>
		<# _.each( options, function( formField, key ) {
				id = _.isObject( formField ) ? formField.id : key;
				selected = _.isArray( data.connection[data.name] ) ?
					_.find( connectionData, function( item ) { return +item === +id; } ) :
					+data.connection[data.name] === +id; #>
				<option value="{{ id }}" <# if ( ! _.isEmpty( connectionData ) && selected ) { #> selected<# } #> >
					{{ _.isObject( formField ) ? formField.label : formField }}
				</option>
			<# } ) #>
	</select>
</div>
