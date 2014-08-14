(function ($) {
	"use strict";

	$(function () {

		var config = {
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
					search: 's',
					button: {
						classes: 'button',
						text: 'Search for videos or albums'
					},
				}
			},
			dom = {},

			thickboxForm = '<form id="' + config.form.id + '" class="' + config.form.classes + '">' +
								'<label class="screen-reader-text" for="vimeo-importer-search">Search Tags:</label>' +
								'<input type="search" id="vimeo-importer-search" name="' + config.form.search + '">' +
								'<input type="submit" class="' + config.form.button.classes + '" value="' + config.form.button.text + '">' +
							'</form>',

			thickboxHtml = '<div id="' + config.thickbox.id + '"><div class="' + config.thickbox.classes + '">' + thickboxForm + '</div></div>',
			buttonHtml = '<a title="' + config.thickbox.title + '" class="' + config.button.classes + ' thickbox" href="#TB_inline?width=' + config.thickbox.width + '&height=' + config.thickbox.height + '&inlineId=' + config.thickbox.id + '">' + config.button.text + '</a>';

		$(config.container).append(thickboxHtml + buttonHtml);

		dom.form = $('#' + config.form.id);
		dom.search = $('input[name="' + config.form.search + '"]', dom.form);

		dom.form.submit(function (event) {
			event.preventDefault();

			// Get keyword
			var keyword = dom.search.val();
		});

	});

}(jQuery));
