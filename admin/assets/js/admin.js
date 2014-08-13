(function ( $ ) {
	"use strict";

	$(function () {

		var config = {
			'container': '.js-vimeo-importer',
			'button': {
				'classes': 'button button-primary button-large',
				'text': 'Import Vimeo videos to Showreel'
			}
		};

		var buttonHtml = '<button class="' + config.button.classes + '">' + config.button.text + '</button>';
		$(config.container).append(buttonHtml);

	});

}(jQuery));