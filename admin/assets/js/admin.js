(function ($) {
	"use strict";

	$(function () {

		var config = {
				api: {
					url: '/wp-content/plugins/vimeo-importer/api/',
					endpoint: 'videos',
					per_page: 10
				},
				container: '.js-vimeo-importer',
				button: {
					classes: 'button button-primary button-large',
					text: 'Import Vimeo videos to Showreel'
				},
				thickbox: {
					id: 'vimeo-importer-thickbox',
					classes: 'vimeo-importer-thickbox',
					title: 'Vimeo Importer',
					height: 550,
					width: 600
				},
				form: {
					id: 'vimeo-importer-form',
					classes: 'search-box',
					label: 'Search for videos',
					search: 's',
					button: {
						classes: 'button',
						text: 'Search'
					}
				},
				results: {
					id: 'vimeo-importer-results',
					checkboxes: 'videos',
					form: {
						id: 'vimeo-importer-results-form',
						button: {
							classes: 'button button-primary',
							text: 'Import selected'
						}
					}
				}
			},
			dom = {},

			searchForm = '<form id="' + config.form.id + '" class="' + config.form.classes + '">' +
							'<label class="screen-reader-text" for="vimeo-importer-search">' + config.form.label + ':</label>' +
							'<input type="search" id="vimeo-importer-search" name="' + config.form.search + '">' +
							'<input type="submit" class="' + config.form.button.classes + '" value="' + config.form.button.text + '">' +
						'</form>',

			results = '<div id="' + config.results.id + '"></div>',

			thickboxHtml = '<div id="' + config.thickbox.id + '"><div class="' + config.thickbox.classes + '">' + searchForm + results + '</div></div>',
			buttonHtml = '<a title="' + config.thickbox.title + '" class="' + config.button.classes + ' thickbox" href="#TB_inline?width=' + config.thickbox.width + '&height=' + config.thickbox.height + '&inlineId=' + config.thickbox.id + '">' + config.button.text + '</a>';

		$(config.container).append(thickboxHtml + buttonHtml);

		dom.form = $('#' + config.form.id);
		dom.submit = $('[type="submit"]', dom.form);
		dom.results = $('#' + config.results.id);
		dom.search = $('input[name="' + config.form.search + '"]', dom.form);

		dom.form.submit(function (event) {
			event.preventDefault();

			// Disable form
			dom.submit.attr('disabled', 'disabled');
			dom.results.html('<p>Waiting for Vimeo...</p>');

			// Get query
			var query = dom.search.val();
			$.ajax({
				url: config.api.url,
				data: {
					endpoint: config.api.endpoint,
					per_page: config.api.per_page,
					name: query
				}
			})
			.done(function (response) {
				// Enable form
				dom.submit.removeAttr('disabled');

				if (response.body.error) {
					showError(response.body.error);
				} else {
					showResults(response.body);
				}
			});
		});

		var showResults = function (results) {
			var i = 0,
				length = results.data.length,
				resultsHtml = '';

			for (i; i < length; i++) {
				var result = results.data[i],
					id = result.uri.split('/')[2];

				resultsHtml += '<input type="checkbox" id="vimeo-importer-video-' + id + '" name="' + config.results.checkboxes + '[]" value="' + id + '">' +
								'<label for="vimeo-importer-video-' + id + '">' + result.name + '</label>' +
								'<br>';
			}

			var form = '<form id="' + config.results.form.id + '">' +
							resultsHtml +
							'<p><input type="submit" class="' + config.results.form.button.classes + '" value="' + config.results.form.button.text + '"></p>' +
						'</form>',

				total = '<p>Video results (' + results.total + ')</p>';

			dom.results.html(total + form);
			dom.resultsForm = $('#' + config.results.form.id);

			dom.resultsForm.submit(function (event) {
				event.preventDefault();

				// Get Vimeo ids
				var ids = [];
				$('input[name="' + config.results.checkboxes + '[]"]:checked').each(function() {
					ids.push($(this).val());
				});

				console.log(ids);
			});
		},

		showError = function (error) {
			var errorHtml ='<p><strong>Error:</strong> ' + response.body.error + '</p>';
			dom.results.html(errorHtml);
		};

	});

}(jQuery));
