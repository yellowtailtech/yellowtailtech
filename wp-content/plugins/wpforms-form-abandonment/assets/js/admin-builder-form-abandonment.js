'use strict';

/**
 * WPForms Builder Form Abandonment function.
 *
 * @since 1.0.0
 */
var WPFormsFormAbandonment = window.WPFormsFormAbandonment || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			app.conditionals();

			$( document ).on( 'change', '#wpforms-panel-field-settings-form_abandonment', function( e ) {
				app.notificationsCheck( $( this ) );
			} );
		},

		/**
		 * Register and load conditionals.
		 *
		 * @since 1.0.0
		 */
		conditionals: function() {

			$( '#wpforms-panel-field-settings-form_abandonment' ).conditions( [
				{
					conditions: {
						element:   '#wpforms-panel-field-settings-form_abandonment',
						type:      'checked',
						operator:  'is',
						condition: '1',
					},
					actions: {
						if: {
							element: '#wpforms-panel-field-settings-form_abandonment_fields-wrap,#wpforms-panel-field-settings-form_abandonment_duplicates-wrap',
							action: 'show',
						},
						else: {
							element: '#wpforms-panel-field-settings-form_abandonment_fields-wrap,#wpforms-panel-field-settings-form_abandonment_duplicates-wrap',
							action:  'hide',
						},
					},
					effect: 'appear',
				},
				{
					conditions: {
						element:   '#wpforms-panel-field-settings-form_abandonment',
						type:      'checked',
						operator:  'is',
						condition: '2',
					},
					actions: {
						if: {
							element: '.wpforms-panel-content-section-notifications [id*="-form_abandonment-wrap"]',
							action: 'show',
						},
						else: {
							element: '.wpforms-panel-content-section-notifications [id*="-form_abandonment-wrap"]',
							action:  'hide',
						},
					},
					effect: 'appear',
				},
			] );
		},

		/**
		 * Maybe uncheck notification setting.
		 *
		 * @since 1.5.0
		 *
		 * @param {object} elem JQuery element.
		 */
		notificationsCheck: function( elem ) {

			if ( ! elem.prop( 'checked' ) ) {
				$( '.wpforms-panel-content-section-notifications input[id*="-form_abandonment"]' ).prop( 'checked', false );
			}
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsFormAbandonment.init();
