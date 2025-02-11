/* global WPForms, wpforms_builder, Choices, wpforms_builder_providers, wpf */

/**
 * WPForms Providers Builder Kit module.
 *
 * @since 1.0.0
 */
WPForms.Admin.Builder.Providers.ConvertKit = WPForms.Admin.Builder.Providers.ConvertKit || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * CSS selectors.
		 *
		 * @since 1.0.0
		 *
		 * @type {Object}
		 */
		selectors: {
			providersPanel: '#wpforms-panel-providers',
			connection: '.wpforms-panel-content-section-convertkit .wpforms-builder-provider-connection',
			actionData: '.wpforms-builder-convertkit-provider-actions-data',
			connections: '.wpforms-panel-content-section-convertkit .wpforms-builder-provider-connection',
			accountField: '.js-wpforms-builder-convertkit-provider-connection-account',
			actionField: '.js-wpforms-builder-convertkit-provider-connection-action',
			emailField: '.wpforms-builder-convertkit-provider-connection-email',
			formField: '.wpforms-builder-convertkit-provider-connection-form',
			tagsField: '.wpforms-builder-convertkit-provider-connection-tags',
			customFields: {
				nameFields: '.js-wpforms-builder-provider-connection-field-name',
				closeFields: '.js-wpforms-builder-convertkit-new-custom-field-close',
			},
			newAccountFormError: '.wpforms-convertkit-admin-form-error',
			choiceJS: '.choicesjs-select',
		},

		/**
		 * jQuery elements.
		 *
		 * @since 1.0.0
		 *
		 * @type {Object}
		 */
		$elements: {
			$builder: $( '#wpforms-builder' ),
			$panel: $( '#convertkit-provider' ),
			$connections: $( '#convertkit-provider .wpforms-builder-provider-connections' ),
			$addConnectionBtn: $( '#convertkit-provider .wpforms-builder-provider-title-add' ),
		},

		/**
		 * Current provider slug.
		 *
		 * @since 1.0.0
		 *
		 * @type {string}
		 */
		provider: 'convertkit',

		/**
		 * This is a shortcut to the WPForms.Admin.Builder.Providers object,
		 * that handles the parent all-providers functionality.
		 *
		 * @since 1.0.0
		 *
		 * @type {Object}
		 */
		Providers: {},

		/**
		 * This is a shortcut to the WPForms.Admin.Builder.Templates object,
		 * that handles all the template management.
		 *
		 * @since 1.0.0
		 *
		 * @type {Object}
		 */
		Templates: {},

		/**
		 * This is a shortcut to the WPForms.Admin.Builder.Providers.cache object,
		 * that handles all the cache management.
		 *
		 * @since 1.0.0
		 *
		 * @type {Object}
		 */
		Cache: {},

		/**
		 * This is a flag for ready state.
		 *
		 * @since 1.0.0
		 *
		 * @type {boolean}
		 */
		isReady: false,

		/**
		 * Start the engine.
		 *
		 * Run initialization on providers panel only.
		 *
		 * @since 1.0.0
		 */
		init() {
			// We are requesting/loading a Providers panel.
			if ( wpf.getQueryString( 'view' ) === 'providers' ) {
				$( app.selectors.providersPanel ).on( 'WPForms.Admin.Builder.Providers.ready', app.ready );
			}

			// We have switched to Providers panel.
			$( document ).on( 'wpformsPanelSwitched', function( event, panel ) {
				if ( panel === 'providers' ) {
					app.ready();
				}
			} );
		},

		/**
		 * Initialized once the DOM and Providers are fully loaded.
		 *
		 * @since 1.0.0
		 */
		ready() {
			if ( app.isReady ) {
				return;
			}

			// Done by reference, so we are not doubling memory usage.
			app.Providers = WPForms.Admin.Builder.Providers;
			app.Templates = WPForms.Admin.Builder.Templates;
			app.Cache = app.Providers.cache;

			// Register custom Underscore.js templates.
			app.Templates.add( [
				'wpforms-' + app.provider + '-builder-content-connection',
				'wpforms-' + app.provider + '-builder-content-connection-custom-fields',
				'wpforms-' + app.provider + '-builder-content-connection-error',
				'wpforms-' + app.provider + '-builder-content-connection-select-field',
				'wpforms-' + app.provider + '-builder-content-connection-text-field',
				'wpforms-' + app.provider + '-builder-content-connection-conditionals',
			] );

			// Register a handler for the "Add New Account" process.
			app.Providers.ui.account.registerAddHandler( app.provider, app.ui.accountForm.add );

			// Events registration.
			app.bindUIActions();
			app.bindTriggers();

			app.processInitial();

			// Save a flag for ready state.
			app.isReady = true;
		},

		/**
		 * Process various events as a response to UI interactions.
		 *
		 * @since 1.0.0
		 */
		bindUIActions() {
			app.$elements.$panel
				.on( 'connectionCreate', app.connection.create )
				.on( 'connectionDelete', app.connection.delete )
				.on( 'change', app.selectors.accountField, app.ui.accountField.change )
				.on( 'change', app.selectors.actionField, app.ui.action.change )
				.on( 'change', app.selectors.customFields.nameFields, app.ui.customFields.change )
				.on( 'click', app.selectors.customFields.closeFields, app.ui.customFields.close );

			app.$elements.$builder.on( 'wpformsSaved', app.connection.refresh );
		},

		/**
		 * Fire certain events on certain actions, specific for related connections.
		 * These are not directly caused by user manipulations.
		 *
		 * @since 1.0.0
		 */
		bindTriggers() {
			app.$elements.$connections.on( 'connectionsDataLoaded', function( event, data ) {
				if ( _.isEmpty( data.connections ) ) {
					return;
				}

				for ( const connectionId in data.connections ) {
					app.connection.generate( {
						connection: data.connections[ connectionId ],
						conditional: data.conditionals[ connectionId ],
					} );
				}
			} );

			app.$elements.$connections.on( 'connectionGenerated', function( event, data ) {
				const $connection = app.connection.getById( data.connection.id );

				if ( _.has( data.connection, 'isNew' ) && data.connection.isNew ) {
					// Run replacing temporary connection ID, if it's a new connection.
					app.connection.replaceIds( data.connection.id, $connection );
					return;
				}

				$( app.selectors.actionField, $connection ).trigger( 'change', [ $connection ] );
			} );
		},

		/**
		 * Compile template with data if any and display them on a page.
		 *
		 * @since 1.0.0
		 */
		processInitial() {
			app.$elements.$connections.prepend( app.tmpl.commonsHTML() );
			app.connection.dataLoad();
		},

		/**
		 * Connection property.
		 *
		 * @since 1.0.0
		 */
		connection: {

			/**
			 * Sometimes we might need to a get a connection DOM element by its ID.
			 *
			 * @since 1.0.0
			 *
			 * @param {string} connectionId Connection ID to search for a DOM element by.
			 *
			 * @return {jQuery} jQuery object for connection.
			 */
			getById( connectionId ) {
				return app.$elements.$connections.find( '.wpforms-builder-provider-connection[data-connection_id="' + connectionId + '"]' );
			},

			/**
			 * Sometimes in DOM we might have placeholders or temporary connection IDs.
			 * We need to replace them with actual values.
			 *
			 * @since 1.0.0
			 *
			 * @param {string} connectionId New connection ID to replace to.
			 * @param {Object} $connection  jQuery DOM connection element.
			 */
			replaceIds( connectionId, $connection ) {
				// Replace old temporary %connection_id% from PHP code with the new one.
				$connection.find( 'input, select, label' ).each( function() {
					const $this = $( this );

					if ( $this.attr( 'name' ) ) {
						$this.attr( 'name', $this.attr( 'name' ).replace( /%connection_id%/gi, connectionId ) );
					}

					if ( $this.attr( 'id' ) ) {
						$this.attr( 'id', $this.attr( 'id' ).replace( /%connection_id%/gi, connectionId ) );
					}

					if ( $this.attr( 'for' ) ) {
						$this.attr( 'for', $this.attr( 'for' ).replace( /%connection_id%/gi, connectionId ) );
					}

					if ( $this.attr( 'data-name' ) ) {
						$this.attr( 'data-name', $this.attr( 'data-name' ).replace( /%connection_id%/gi, connectionId ) );
					}
				} );
			},

			/**
			 * Create a connection using the user entered name.
			 *
			 * @since 1.0.0
			 *
			 * @param {Object} event Event object.
			 * @param {string} name  Connection name.
			 */
			create( event, name ) {
				const connectionId = new Date().getTime().toString( 16 ),
					connection = {
						id: connectionId,
						name,
						isNew: true,
					};

				app.Cache.addTo( app.provider, 'connections', connectionId, connection );

				app.connection.generate( {
					connection,
				} );
			},

			/**
			 * Connection is deleted - delete a cache as well.
			 *
			 * @since 1.0.0
			 *
			 * @param {Object} event       Event object.
			 * @param {Object} $connection jQuery DOM element for a connection.
			 */
			delete( event, $connection ) {
				const $eHolder = app.Providers.getProviderHolder( app.provider );

				if ( ! $connection.closest( $eHolder ).length ) {
					return;
				}

				const connectionId = $connection.data( 'connection_id' );

				if ( _.isString( connectionId ) ) {
					app.Cache.deleteFrom( app.provider, 'connections', connectionId );
				}
			},

			/**
			 * Get the template and data for a connection and process it.
			 *
			 * @since 1.0.0
			 *
			 * @param {Object} data Connection data.
			 *
			 * @return {void}
			 */
			generate( data ) {
				const accounts = app.Cache.get( app.provider, 'accounts' ),
					actions = app.Cache.get( app.provider, 'actions' );

				/*
				 * We may or may not receive accounts previously.
				 * If yes - render instantly, if no - request them via AJAX.
				 */
				if ( ! _.isEmpty( accounts ) && app.account.isAccountExists( data.connection.account_id, accounts ) ) {
					return app.connection.renderConnections( accounts, actions, data );
				}

				app.ui.accountForm.dataLoad( data );
			},

			/**
			 * Refresh builder to update a new spreadsheet or a new list.
			 *
			 * @since 1.0.0
			 *
			 * @param {Event}  e        Event.
			 * @param {Object} response Ajax response.
			 */
			refresh( e, response ) {
				if ( ! Object.hasOwn( response, app.provider ) ) {
					return;
				}

				const data = response[ app.provider ];

				[
					'connections',
					'accounts',
					'custom_fields',
					'forms',
					'tags',
				].forEach( ( dataType ) => {
					if ( ! _.isEmpty( data[ dataType ] ) ) {
						app.Cache.set( app.provider, dataType, jQuery.extend( {}, data[ dataType ] ) );
					}
				} );

				app.$elements.$connections.html( '' );

				for ( const connectionId in data.connections ) {
					app.connection.generate( {
						connection: data.connections[ connectionId ],
						conditional: data.conditionals[ connectionId ],
					} );
				}

				app.connection.validate();
			},

			/**
			 * Check that at least one of the Kit Form or Tags fields has a value.
			 *
			 * @since 1.0.0
			 */
			validate() {
				// If another modal is open, bail early.
				if ( _.has( window, 'jconfirm' ) && ! _.isEmpty( window.jconfirm.instances ) ) {
					return;
				}

				const connections = $( app.selectors.connections );

				connections.each( function( index, connection ) {
					const $connection = $( connection ),
						// eslint-disable-next-line @wordpress/no-unused-vars-before-return
						$action = $connection.find( app.selectors.actionField ),
						$email = $connection.find( app.selectors.emailField );

					if ( _.isEmpty( $email.val() ) || _.isEmpty( $action.val() ) ) {
						return;
					}

					if ( $action.val().trim() !== 'subscribe' ) {
						return;
					}

					const $form = $( app.selectors.formField, $connection ),
						$tags = $( app.selectors.tagsField, $connection );

					if (
						_.isEmpty( $form.val() ) &&
						_.isEmpty( $tags.val() )
					) {
						app.modal( wpforms_builder.convertkit.subscribe_fields_error );
					}
				} );
			},

			/**
			 * Render connections.
			 *
			 * @since 1.0.0
			 *
			 * @param {Object} accounts List of accounts.
			 * @param {Object} actions  List of actions.
			 * @param {Object} data     Connection data.
			 */
			renderConnections( accounts, actions, data ) {
				if ( ! app.account.isAccountExists( data.connection.account_id, accounts ) ) {
					return;
				}

				const tmplConnection = app.Templates.get( 'wpforms-' + app.provider + '-builder-content-connection' ),
					tmplConditional = app.Templates.get( 'wpforms-' + app.provider + '-builder-content-connection-conditionals' ),
					conditional = _.has( data.connection, 'isNew' ) && data.connection.isNew ? tmplConditional() : data.conditional;

				app.$elements.$connections
					.prepend(
						tmplConnection( {
							accounts,
							actions,
							connection: data.connection,
							conditional,
							provider: app.provider,
						} ) );

				// When we are done adding a new connection with its accounts - trigger next steps.
				app.$elements.$connections.trigger( 'connectionGenerated', [ data ] );
			},

			/**
			 * Fire AJAX-request to retrieve the list of all saved connections.
			 *
			 * @since 1.0.0
			 */
			dataLoad() {
				app
					.Providers.ajax
					.request( app.provider, {
						data: {
							task: 'connections_get',
						},
					} )
					.done( function( response ) {
						if (
							! response.success ||
							! _.has( response.data, 'connections' )
						) {
							return;
						}

						[
							'connections',
							'conditionals',
							'actions',
							'actions_fields',
							'accounts',
							'custom_fields',
							'forms',
							'tags',
						].forEach( ( dataType ) => {
							app.Cache.set( app.provider, dataType, jQuery.extend( {}, response.data[ dataType ] ) );
						} );

						app.$elements.$connections.trigger( 'connectionsDataLoaded', [ response.data ] );
					} );
			},
		},

		/**
		 * Account property.
		 *
		 * @since 1.0.0
		 */
		account: {

			/**
			 * Check if provided account is listed inside accounts list.
			 *
			 * @since 1.0.0
			 *
			 * @param {string} connectionAccID Connection account ID to check.
			 * @param {Object} accounts        Array of objects, usually received from API.
			 *
			 * @return {boolean} True if account exists.
			 */
			isAccountExists( connectionAccID, accounts ) {
				if ( _.isEmpty( accounts ) ) {
					return false;
				}

				// New connections, that have not been saved don't have the account ID yet.
				if ( _.isEmpty( connectionAccID ) ) {
					return true;
				}

				return _.has( accounts, connectionAccID );
			},
		},

		/**
		 * All methods that modify UI of a page.
		 *
		 * @since 1.0.0
		 */
		ui: {

			/**
			 * Account form methods.
			 *
			 * @since 1.0.0
			 */
			accountForm: {

				// eslint-disable-next-line jsdoc/require-returns-check
				/**
				 * Process the account creation in FormBuilder.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} modal jQuery-Confirm modal object.
				 *
				 * @return {boolean} False if there are errors.
				 */
				add( modal ) {
					const $confirmBtn = modal.$$add;

					if ( $confirmBtn.prop( 'disabled' ) ) {
						return false;
					}

					$confirmBtn.prop( 'disabled', 'disabled' );

					const requestData = app.ui.accountForm.validateFields( modal );

					if ( requestData === false ) {
						$confirmBtn.removeAttr( 'disabled' );

						return false;
					}

					const $error = modal.$content.find( app.selectors.newAccountFormError );

					modal.setType( 'blue' );
					$error.hide();

					app
						.Providers.ajax
						.request( app.provider, {
							data: {
								task: 'account_save',
								data: requestData,
							},
						} )
						.done( function( response ) {
							if ( response.success ) {
								app.$elements.$addConnectionBtn.toggleClass( 'hidden' );
								modal.close();

								return true;
							}

							if ( _.has( response, 'data' ) && _.has( response.data, 'error_msg' ) ) {
								$error.html( response.data.error_msg );
							}

							$error.show();
							$confirmBtn.removeAttr( 'disabled' );
						} );

					return false;
				},

				/**
				 * Validate required fields.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} modal jQuery-Confirm modal object.
				 *
				 * @return {{object}|boolean} Returns field data if there are no errors or false if there are errors.
				 */
				validateFields( modal ) {
					const $requiredFields = modal.$content.find( 'input.wpforms-required' ),
						requestData = {};

					let hasErrors = false;

					$requiredFields.each( function( index, el ) {
						const $requiredField = $( el ),
							$requiredFieldError = $requiredField.siblings( '.error' ),
							requiredFieldName = $requiredField.attr( 'name' );

						requestData[ requiredFieldName ] = $requiredField.val().trim();

						if ( _.isEmpty( requestData[ requiredFieldName ] ) ) {
							$requiredField.addClass( 'wpforms-error' );
							$requiredFieldError.text( wpforms_builder_providers.required_field ).show();

							hasErrors = true;

							return;
						}

						$requiredField.removeClass( 'wpforms-error' );
						$requiredFieldError.hide();
					} );

					if ( hasErrors === true ) {
						return false;
					}

					return requestData;
				},

				/**
				 * Retrieve list of required new accounts data for generating connections.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} data Connection data.
				 */
				dataLoad( data ) {
					const actions = app.Cache.get( app.provider, 'actions' );

					app
						.Providers.ajax
						.request( app.provider, {
							data: {
								task: 'accounts_get',
							},
						} )
						.done( function( response ) {
							[
								'accounts',
								'custom_fields',
								'forms',
								'tags',
							].forEach( ( dataType ) => {
								if ( ! _.isEmpty( response.data[ dataType ] ) ) {
									app.Cache.set( app.provider, dataType, jQuery.extend( {}, response.data[ dataType ] ) );
								}
							} );

							app.connection.renderConnections( response.data.accounts, actions, data );
						} );
				},
			},

			/**
			 * Account field methods.
			 *
			 * @since 1.0.0
			 */
			accountField: {

				/**
				 * Callback-function on change event.
				 *
				 * @since 1.0.0
				 */
				change() {
					const $this = $( this ),
						$connection = $this.closest( app.selectors.connection ),
						$actionName = $( app.selectors.actionField, $connection );

					$( app.selectors.actionData, $connection ).html( '' );
					$actionName.prop( 'selectedIndex', 0 );

					// If account is empty.
					if ( _.isEmpty( $this.val() ) ) {
						// Block `Action` select box.
						$actionName.prop( 'disabled', true );

						return;
					}

					// Unblock `Action` select box.
					$actionName.prop( 'disabled', false );
					$this.removeClass( 'wpforms-error' );
				},
			},

			/**
			 * Action methods.
			 *
			 * @since 1.0.0
			 */
			action: {

				/**
				 * Callback-function on change event.
				 *
				 * @since 1.0.0
				 */
				change() {
					const $this = $( this ),
						$connection = $this.closest( app.selectors.connection ),
						$account = $( app.selectors.accountField, $connection ),
						$action = $( app.selectors.actionField, $connection );

					$this.removeClass( 'wpforms-error' );

					app.ui.action.render( {
						action: 'action',
						target: $this,
						/* eslint-disable camelcase */
						account_id: $account.val(),
						action_name: $action.val(),
						connection_id: $connection.data( 'connection_id' ),
						/* eslint-enable camelcase */
					} );
				},

				/**
				 * Render HTML.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} args Arguments.
				 */
				render( args ) {
					const fields = wpf.getFields(),
						requiredFields = app.tmpl.renderActionFields( args, fields ),
						$connection = app.connection.getById( args.connection_id ),
						$connectionData = $( app.selectors.actionData, $connection );

					$connectionData.html( requiredFields );

					app.$elements.$connections.trigger( 'connectionRendered', [ app.provider,
						args.connection_id ] );

					// Load ChoicesJS fields.
					app.ui.action.initChoicesJS( $connection );
				},

				/**
				 * Initialize Choices.js library.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} $connection jQuery connection selector.
				 */
				initChoicesJS( $connection ) {
					// Load if function exists.
					if ( typeof window.Choices !== 'function' ) {
						return;
					}

					const $choices = $( app.selectors.choiceJS, $connection );

					$choices.each( function( index, element ) {
						const $this = $( element );

						// Return if already initialized.
						if ( 'undefined' !== typeof $this.data( 'choicesjs' ) ) {
							return;
						}

						$this.data( 'choicesjs', new Choices( $this[ 0 ], {
							shouldSort: false,
							removeItemButton: true,
							fuseOptions:{
								threshold: 0.1,
								distance: 1000,
							},
							callbackOnInit() {
								wpf.initMultipleSelectWithSearch( this );
								wpf.showMoreButtonForChoices( this.containerOuter.element );
							},
						} ) );
					} );
				},
			},

			/**
			 * Custom fields methods.
			 *
			 * @since 1.0.0
			 */
			customFields: {

				/**
				 * Change function for custom field name select.
				 *
				 * @since 1.0.0
				 */
				change() {
					const $this = $( this ),
						$td = $this.closest( 'td' ),
						isNewCustomField = $this.val() === 'new_custom_field',
						$input = $td.find( 'input[type="text"]' ),
						toggleClass = 'wpforms-builder-convertkit-is-new-custom-field';

					if ( isNewCustomField ) {
						$td.addClass( toggleClass );
						$input.attr( 'name', $this.attr( 'name' ) );

						return;
					}

					$td.removeClass( toggleClass );
					$input.attr( 'name', '' );
				},

				/**
				 * Click function for custom field close button.
				 *
				 * @since 1.0.0
				 *
				 * @param {Object} e Event object.
				 */
				close( e ) {
					e.preventDefault();

					const $this = $( this ),
						$td = $this.closest( 'td' ),
						$select = $td.find( 'select' ),
						$input = $td.find( 'input[type="text"]' );

					$select.prop( 'selectedIndex', 0 );
					$td.removeClass( 'wpforms-builder-convertkit-is-new-custom-field' );
					$input.attr( 'name', '' ).val( '' );
				},
			},
		},

		/**
		 * All methods for JavaScript templates.
		 *
		 * @since 1.0.0
		 */
		tmpl: {
			/**
			 * Compile and retrieve an HTML for common elements.
			 *
			 * @since 1.0.0
			 *
			 * @return {string} Compiled HTML.
			 */
			commonsHTML() {
				const tmplError = app.Templates.get( 'wpforms-' + app.provider + '-builder-content-connection-error' );

				return tmplError();
			},

			/**
			 * Compile and retrieve an HTML for "Custom Fields Table".
			 *
			 * @since 1.0.0
			 *
			 * @param {Object} args   Arguments
			 * @param {Object} fields Fields
			 *
			 * @return {string} Compiled HTML.
			 */
			renderActionFields( args, fields ) {
				const actionsFields = app.Cache.get( app.provider, 'actions_fields' );

				let fieldHTML = '';

				$.each( actionsFields[ args.target.val() ], function( key, field ) {
					if ( [ 'form', 'tags', 'custom_fields' ].includes( key ) ) {
						field.options = app.Cache.getById(
							app.provider, key === 'form' ? 'forms' : key, // The cache and field names are the same for all fields except of the form field.
							args.account_id
						);
					}

					const templateName = field.type === 'custom-fields'
						? 'wpforms-' + app.provider + '-builder-content-connection-' + field.type
						: 'wpforms-' + app.provider + '-builder-content-connection-' + field.type + '-field';
					const tmplField = app.Templates.get( templateName );

					fieldHTML += tmplField( {
						connection: app.Cache.getById( app.provider, 'connections', args.connection_id ),
						name: key,
						field,
						provider: {
							slug: app.provider,
							fields: actionsFields[ args.target.val() ],
						},
						options: fields,
					} );
				} );

				return fieldHTML;
			},
		},

		/**
		 * Modal.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} content Modal content.
		 */
		modal( content ) {
			// Checking required data.
			if ( ! content ) {
				return;
			}

			$.alert( {
				title: wpforms_builder.heads_up,
				content,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPForms.Admin.Builder.Providers.ConvertKit.init();
