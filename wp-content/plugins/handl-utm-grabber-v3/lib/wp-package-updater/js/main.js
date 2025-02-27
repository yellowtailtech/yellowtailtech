/* version 1.4.0 */
/* global WP_PackageUpdater */
jQuery(document).ready(function($) {

	var labelTheme = $('.appearance_page_theme-license .wrap-license label');
	
	labelTheme.css('display', 'block');
	labelTheme.css('margin-bottom', '10px');
	$('.appearance_page_theme-license .wrap-license input[type="text"]').css('width', '50%');
	$('.appearance_page_theme-license .postbox').show();

	$('.wrap-license .activate-license').on('click', function(e) {
		e.preventDefault();

		var licenseContainer = $(this).parent().parent(),
			data             = {
			'nonce' : licenseContainer.data('nonce'),
			'license_key' : licenseContainer.find('.license').val(),
			'package_slug' : licenseContainer.data('package_slug'),
			'action' : WP_PackageUpdater.action_prefix + '_activate_license'
		};
		var handl_notice_sec = licenseContainer.parent();
		licenseContainer.find('.activate-license').attr('disabled', true)

		$.ajax({
			url: WP_PackageUpdater.ajax_url,
			data: data,
			type: 'POST',
			success: function(response) {

				if (response.success) {
					licenseContainer.find('.current-license').html(licenseContainer.find('.license').val());
					licenseContainer.find('.current-license-error').hide();
					licenseContainer.find('.license-message').show();
					licenseContainer.find('.license').attr('readonly', true)
					licenseContainer.find('.deactivate-license').attr('disabled', false)
					handl_notice_sec.removeClass('notice-warning').addClass('notice-success')

					$( '.license-error-' + licenseContainer.data('package_slug') + '.notice' ).hide();
				} else {
					var errorContainer = licenseContainer.find('.current-license-error');
					
					errorContainer.html(response.data[0].message + '<br/>');
					errorContainer.show();
					licenseContainer.find('.license-message').show();
					licenseContainer.find('.license').attr('readonly', false)
					licenseContainer.find('.activate-license').attr('disabled', false)
				}

				if ('' === licenseContainer.find('.current-license').html()) {
					licenseContainer.find('.current-license-label').hide();
					licenseContainer.find('.current-license').hide();
				} else {
					licenseContainer.find('.current-license-label').show();
					licenseContainer.find('.current-license').show();
				}
			}
		});
	});

	$('.wrap-license .deactivate-license').on('click', function(e) {
		e.preventDefault();

		var licenseContainer = $(this).parent().parent(),
			data             = {
			'nonce' : licenseContainer.data('nonce'),
			'license_key' : licenseContainer.find('.license').val(),
			'package_slug' : licenseContainer.data('package_slug'),
			'action' : WP_PackageUpdater.action_prefix + '_deactivate_license'
		};
		var handl_notice_sec = licenseContainer.parent();
		licenseContainer.find('.deactivate-license').attr('disabled', true)

		$.ajax({
			url: WP_PackageUpdater.ajax_url,
			data: data,
			type: 'POST',
			success: function(response) {

				if (response.success) {
					licenseContainer.find('.current-license').html('');
					licenseContainer.find('.current-license-error').hide();
					licenseContainer.find('.license-message').hide();
					licenseContainer.find('.license').val('')
					licenseContainer.find('.license').attr('readonly', false)
					licenseContainer.find('.activate-license').attr('disabled', false)
					handl_notice_sec.removeClass('notice-success').addClass('notice-warning')
				} else {
					var errorContainer = licenseContainer.find('.current-license-error');

					errorContainer.html(response.data[0].message + '<br/>');
					errorContainer.show();
					licenseContainer.find('.license-message').show();
					licenseContainer.find('.deactivate-license').attr('disabled', false)
				}

				if ('' === licenseContainer.find('.current-license').html()) {
					licenseContainer.find('.current-license-label').hide();
					licenseContainer.find('.current-license').hide();
				} else {
					licenseContainer.find('.current-license-label').show();
					licenseContainer.find('.current-license').show();
				}
			}
		});
	});
});