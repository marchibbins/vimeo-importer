(function ($) {
	"use strict";

	$(function () {

		var config = {
				api: {
					url: '/wp-content/plugins/vimeo-importer/api/',
					search: 'videos',
					create: 'create',
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
				},
				feedback: {
					id: 'vimeo-importer-feedback'
				}
			},
			dom = {},

			searchForm = '<form id="' + config.form.id + '" class="' + config.form.classes + '">' +
							'<label class="screen-reader-text" for="vimeo-importer-search">' + config.form.label + ':</label>' +
							'<input type="search" id="vimeo-importer-search" name="' + config.form.search + '">' +
							'<input type="submit" class="' + config.form.button.classes + '" value="' + config.form.button.text + '">' +
						'</form>',

			results = '<div id="' + config.results.id + '"></div>',
			feedback = '<div id="' + config.feedback.id + '"></div>',

			thickboxHtml = '<div id="' + config.thickbox.id + '"><div class="' + config.thickbox.classes + '">' + searchForm + results + feedback + '</div></div>',
			buttonHtml = '<a title="' + config.thickbox.title + '" class="' + config.button.classes + ' thickbox" href="#TB_inline?width=' + config.thickbox.width + '&height=' + config.thickbox.height + '&inlineId=' + config.thickbox.id + '">' + config.button.text + '</a>';

		$(config.container).append(thickboxHtml + buttonHtml);

		dom.form = $('#' + config.form.id);
		dom.submit = $('[type="submit"]', dom.form);
		dom.results = $('#' + config.results.id);
		dom.feedback = $('#' + config.feedback.id);
		dom.search = $('input[name="' + config.form.search + '"]', dom.form);

		dom.form.submit(function (event) {
			event.preventDefault();

			// Disable form
			dom.submit.attr('disabled', 'disabled');
			dom.results.html('<p>Waiting for Vimeo...</p>');
			dom.feedback.html('');

			// Get query
			var query = dom.search.val();
			$.ajax({
				url: config.api.url,
				data: {
					endpoint: config.api.search,
					per_page: config.api.per_page,
					name: query
				}
			})
			.done(function (response) {
				// Enable form
				dom.submit.removeAttr('disabled');

				if (!response.body || response.body.error) {
					showError(response, dom.results);
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
			dom.import = $('[type="submit"]', dom.resultsForm);

			dom.resultsForm.submit(function (event) {
				event.preventDefault();

				// Disable form
				dom.submit.attr('disabled', 'disabled');
				dom.import.attr('disabled', 'disabled');
				dom.feedback.html('<p>Waiting for Vimeo...</p>');

				// Loop selected videos
				var videos = [];
				$('input[name="' + config.results.checkboxes + '[]"]:checked').each(function() {
					id = $(this).val();

					// Find data object from id
					$.each(results.data, function(i, result) {
						if (id === result.uri.split('/')[2]) {
							// Store required data
							var obj = {
								dsv_vimeo_id: id,
								post_title: result.name,
								post_content: result.description,
								dsv_vimeo_holdingframe_url: 'http://i.vimeocdn.com/video/' + id + '.jpg',
								dsv_vimeo_link: 'vimeo.com/' + id
							};
							videos.push(obj);
						}
					});
				});

				$.ajax({
					type: 'POST',
					url: config.api.url,
					data: {
						endpoint: config.api.create,
						videos: videos
					}
				})
				.done(function (response) {
					// Enable form
					dom.submit.removeAttr('disabled');
					dom.import.removeAttr('disabled');

					if (!response.body || response.body.error) {
						showError(response, dom.feedback);
					} else {
						var i = 0,
							length = response.body.data.length,
							feedbackHtml = '';

						for (i; i < length; i++) {
							var video = response.body.data[i];
							feedbackHtml += '<li>Video <strong>' + video.id + '</strong> ' + video.status + '.';
							if (video.image) {
								feedbackHtml += '<ul><li>Image <strong>' + video.image.id + '</strong> ' + video.image.message + '.</li></ul>';
							}
							feedbackHtml += '</li>';
						}

						dom.feedback.html('<p>Feedback</p><ol>' + feedbackHtml + '</ol>');
					}
				});
			});
		},

		showError = function (response, el) {
			var error = response.body && response.body.error ? response.body.error : 'Something went wrong.',
				errorHtml ='<p><strong>Error:</strong> ' + error + '</p>';
			el.html(errorHtml);
		};

	});

}(jQuery));
