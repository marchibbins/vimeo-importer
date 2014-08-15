(function ($) {
	"use strict";

	$(function () {

		var config = {
				apiUrl: '/wp-content/plugins/vimeo-importer/api/',
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
					},
					output: 'vimeo-importer-output'
				}
			},
			dom = {},

			searchForm = '<form id="' + config.form.id + '" class="' + config.form.classes + '">' +
							'<label class="screen-reader-text" for="vimeo-importer-search">' + config.form.label + ':</label>' +
							'<input type="search" id="vimeo-importer-search" name="' + config.form.search + '">' +
							'<input type="submit" class="' + config.form.button.classes + '" value="' + config.form.button.text + '">' +
						'</form>' +
						'<div id="' + config.form.output + '"></div>',

			thickboxHtml = '<div id="' + config.thickbox.id + '"><div class="' + config.thickbox.classes + '">' + searchForm + '</div></div>',
			buttonHtml = '<a title="' + config.thickbox.title + '" class="' + config.button.classes + ' thickbox" href="#TB_inline?width=' + config.thickbox.width + '&height=' + config.thickbox.height + '&inlineId=' + config.thickbox.id + '">' + config.button.text + '</a>';

		$(config.container).append(thickboxHtml + buttonHtml);

		dom.form = $('#' + config.form.id);
		dom.output = $('#' + config.form.output);
		dom.search = $('input[name="' + config.form.search + '"]', dom.form);

		dom.form.submit(function (event) {
			event.preventDefault();

			// Get query
			var query = dom.search.val();
			$.ajax({
				url: config.apiUrl,
				data: { endpoint: 'albums', query: query }
			})
			.done(function (response) {
				if (response.body.error) {
					dom.output.html('<strong>Error:</strong> ' + response.body.error);
				} else {
					dom.output.html('<strong>Results found:</strong> ' + response.body.total);
				}
			});
		});

	});

}(jQuery));
