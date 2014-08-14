(function ( $ ) {
	"use strict";

	$(function () {

		var config = {
				container: '.js-vimeo-importer',
				button: {
					classes: 'button button-primary button-large',
					text: 'Import Vimeo videos to Showreel'
				},
				thickbox: {
					id: 'vimeo-importer-thickbox'
				}
			};

		var thickboxHtml = '<div id="' + config.thickbox.id + '"><h1>Vimeo Importer</h1></div>',
			buttonHtml = '<a class="' + config.button.classes + ' thickbox" href="#TB_inline?width=600&height=550&inlineId=' + config.thickbox.id + '">' + config.button.text + '</a>';

		$(config.container).append(thickboxHtml + buttonHtml);

	});

}(jQuery));