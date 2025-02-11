/**
 * WPForms Zapier function.
 *
 * @since 1.5.0
 */
'use strict';

const WPFormsZapier = window.WPFormsZapier || ( function( document, window, $ ) {

	/**
	 * Whether zapier script is loaded.
	 *
	 * @since 1.5.0
	 *
	 * @type {boolean}
	 */
	let isWidgetLoaded = false;

	/**
	 * Public functions and properties.
	 *
	 * @since 1.5.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.5.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Initialized once the DOM and Providers are fully loaded.
		 *
		 * @since 1.5.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Process various events as a response to UI interactions.
		 *
		 * @since 1.5.0
		 */
		events: function() {

			$( document ).on( 'wpformsPanelSectionSwitch', function( e, section ) {

				if ( section !== 'zapier' || isWidgetLoaded ) {
					return;
				}

				app.loadZapierWidget();
			} );
		},

		/**
		 * Load Zapier widget.
		 *
		 * @since 1.5.0
		 */
		loadZapierWidget: function() {

			if ( isWidgetLoaded ) {
				return;
			}

			const $script = $( '#wpforms-zapier-builder-embed-js' );
			const $style  = $( '#wpforms-zapier-builder-embed-css' );

			$script.attr( 'src', $script.data( 'src' ) );
			$style.attr( 'href', $style.data( 'href' ) );

			isWidgetLoaded = true;
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsZapier.init();
