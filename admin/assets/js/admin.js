(function ($) {
	"use strict";

	$(function () {

		var config = {
				api: {
					url: '/wp-content/plugins/vimeo-importer/api/',
					search: 'videos',
					albums: 'albums',
					importVideos: 'import_videos',
					per_page: 10
				},
				container: '.js-vimeo-importer',
				button: {
					id: 'vimeo-importer-button',
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
				tabs: {
					id: 'vimeo-importer-tabs',
					class: 'tab',
					videos: {
						id: 'vimeo-importer-tabs-videos',
						text: 'Search for videos',
					},
					albums: {
						id: 'vimeo-importer-tabs-albums',
						text: 'View albums'
					}
				},
				form: {
					id: 'vimeo-importer-form',
					classes: 'search-box',
					label: 'Search for videos',
					search: 's',
					page: 'p',
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
						},
						pagination: {
							previous: 'vimeo-importer-results-previous',
							next: 'vimeo-importer-results-next'
						}
					}
				},
				albums: {
					id: 'vimeo-importer-albums',
					radios: 'albums',
					form: {
						id: 'vimeo-importer-albums-form',
						page: 'p',
						button: {
							classes: 'button button-primary',
							text: 'Import selected'
						},
						pagination: {
							previous: 'vimeo-importer-albums-previous',
							next: 'vimeo-importer-albums-next'
						}
					}
				},
				feedback: {
					id: 'vimeo-importer-feedback'
				},
				waiting: 'Waiting for Vimeo...'
			},
			dom = {};

		var template = function () {
			var searchForm = '<form id="' + config.form.id + '" class="' + config.form.classes + '">' +
							'<label class="screen-reader-text" for="vimeo-importer-search">' + config.form.label + ':</label>' +
							'<input type="search" id="vimeo-importer-search" name="' + config.form.search + '">' +
							'<input type="hidden" name="' + config.form.page + '" value="1">' +
							'<input type="submit" class="' + config.form.button.classes + '" value="' + config.form.button.text + '">' +
						'</form>',

			results = '<div id="' + config.results.id + '"></div>',
			feedback = '<div id="' + config.feedback.id + '"></div>',

			tabs = '<div id="' + config.tabs.id + '">' +
						'<a class="' + config.tabs.class + ' active" href="#' + config.tabs.videos.id + '">' + config.tabs.videos.text + '</a> | ' +
						'<a class="' + config.tabs.class + '" href="#' + config.tabs.albums.id + '">' + config.tabs.albums.text + '</a>' +
					'</div>',

			thickboxHtml = '<div id="' + config.thickbox.id + '">' +
								'<div class="' + config.thickbox.classes + '">' +
									tabs +
									'<div id="' + config.tabs.videos.id + '">' +
										searchForm +
										results +
										feedback +
									'</div>' +
									'<div id="' + config.tabs.albums.id + '">' +
										'<div id="' + config.albums.id + '"></div>' +
									'</div>' +
								'</div>' +
							'</div>',

			buttonHtml = '<a id="' + config.button.id + '" title="' + config.thickbox.title + '" class="' + config.button.classes + ' thickbox" href="#TB_inline?width=' + config.thickbox.width + '&height=' + config.thickbox.height + '&inlineId=' + config.thickbox.id + '">' + config.button.text + '</a>';

			$(config.container).append(thickboxHtml + buttonHtml);

			dom.tabs = $('.' + config.tabs.class, '#' + config.tabs.id);
			dom.tabs.click(toggleTabs);

			dom.form = $('#' + config.form.id);
			dom.form.submit(formSubmit);

			dom.search = $('input[name="' + config.form.search + '"]', dom.form);
			dom.page = $('input[name="' + config.form.page + '"]', dom.form);

			dom.submit = $('[type="submit"]', dom.form);
			dom.submit.click(submitClick);

			dom.results = $('#' + config.results.id);
			dom.albums = $('#' + config.albums.id);
			dom.feedback = $('#' + config.feedback.id);
		},

		formSubmit = function (event) {
			event.preventDefault();
			searchFormReady(false);

			// Get query
			var query = dom.search.val(),
			data = {
				endpoint: config.api.search,
				per_page: config.api.per_page,
				page: dom.page.val()
			};

			if (query !== '') {
				data.query = query;
			}

			$.ajax({
				url: config.api.url,
				data: data
			})
			.done(function (response) {
				searchFormReady(true);
				if (!response.body || response.body.error) {
					showError(response, dom.results);
				} else {
					showResults(response.body);
				}
			});
		},

		submitClick = function (event) {
			// Reset page to one, button click means new search
			dom.page.val(1);
		},

		pageResults = function (event) {
			event.preventDefault();

			var direction = $(event.target).attr('id') == config.results.form.pagination.previous ? 'previous' : 'next';
			if (direction == 'previous') {
				dom.page.val(parseInt(dom.page.val(), 10) - 1);
				dom.form.submit();
			}
			else if (direction == 'next') {
				dom.page.val(parseInt(dom.page.val(), 10) + 1);
				dom.form.submit();
			}
		},

		showResults = function (results) {
			var i = 0,
				length = results.data.length,
				resultsHtml = '',
				previous = results.paging.previous,
				next = results.paging.next;

			// Video checkboxes
			for (i; i < length; i++) {
				var result = results.data[i],
					id = result.uri.split('/')[2];

				resultsHtml += '<input type="checkbox" id="vimeo-importer-video-' + id + '" name="' + config.results.checkboxes + '[]" value="' + id + '">' +
								'<label for="vimeo-importer-video-' + id + '">' + result.name + '</label>' +
								'<br>';
			}

			// Pagination
			var pagination = '';
			if (previous) {
				pagination += '<a id="' + config.results.form.pagination.previous +'" href data-page="' + previous + '">Previous</a> ';
				if (next) {
					pagination += '| ';
				}
			}
			if (next) {
				pagination += '<a id="' + config.results.form.pagination.next +'" href data-page="' + next + '">Next</a>';
			}

			// Results form
			var form = '<form id="' + config.results.form.id + '">' +
							resultsHtml +
							'<p>' + pagination + '</p>' +
							'<p><input type="submit" class="' + config.results.form.button.classes + '" value="' + config.results.form.button.text + '"></p>' +
						'</form>',

				total = '<p>Video results (' + results.total + ')</p>';

			dom.results.html(total + form);
			dom.resultsForm = $('#' + config.results.form.id);
			dom.resultsPrevious = $('#' + config.results.form.pagination.previous, dom.resultsForm);
			dom.resultsNext = $('#' + config.results.form.pagination.next, dom.resultsForm);
			dom.resultsImport = $('[type="submit"]', dom.resultsForm);

			dom.resultsPrevious.click(pageResults);
			dom.resultsNext.click(pageResults);

			dom.resultsForm.submit(results, importResults);
		},

		importResults = function (event) {
			event.preventDefault();

			// Minimum one video
			var checked = $('input[name="' + config.results.checkboxes + '[]"]:checked', dom.resultsForm);
			if (checked.length === 0) {
				return false;
			}

			// Loop checked videos
			var results = event.data,
				videos = [];

			checked.each(function(i, el) {
				var id = $(el).val();

				// Find data object from id
				$.each(results.data, function(i, result) {
					if (id === result.uri.split('/')[2]) {
						// Store required data
						var obj = {
							dsv_vimeo_id: id,
							post_title: result.name,
							post_content: result.description,
							dsv_vimeo_holdingframe_url: 'http://i.vimeocdn.com/video/' + result.pictures.uri.split('/')[4] + '.jpg',
							dsv_vimeo_link: 'vimeo.com/' + id
						};
						videos.push(obj);
					}
				});
			});

			resultsFormReady(false);

			$.ajax({
				type: 'POST',
				url: config.api.url,
				data: {
					endpoint: config.api.importVideos,
					videos: videos
				}
			})
			.done(function (response) {
				resultsFormReady(true);
				if (!response.body || response.body.error) {
					showError(response, dom.feedback);
				} else {
					showFeedback(response.body);
				}
			});
		},

		showFeedback = function (response) {
			var i = 0,
				length = response.data.length,
				feedbackHtml = '';

			// Feedback info
			if (length > 0) {
				for (i; i < length; i++) {
					var video = response.data[i];
					feedbackHtml += '<li>Video <strong>' + video.id + '</strong> ' + video.status + '.';
					if (video.image) {
						feedbackHtml += '<ul><li>Image <strong>' + video.image.id + '</strong> ' + video.image.message + '.</li></ul>';
					}
					feedbackHtml += '</li>';
				}
			} else if (response.data.message) {
				feedbackHtml += '<li>' + response.data.message + '.';
			}

			dom.feedback.html('<p>Feedback</p><ol>' + feedbackHtml + '</ol>');
		},

		showError = function (response, el) {
			// Print error verbatim
			var error = response.body && response.body.error ? response.body.error : 'Something went wrong.',
				errorHtml ='<p><strong>Error:</strong> ' + error + '</p>';
			el.html(errorHtml);
		},

		searchFormReady = function (enable) {
			// Enable form
			if (enable) {
				dom.submit.removeAttr('disabled');
			}
			// Disable form
			else {
				dom.submit.attr('disabled', 'disabled');
				dom.results.html('<p>' + config.waiting + '</p>');
				dom.feedback.html('');
			}
		},

		resultsFormReady = function (enable) {
			// Enable form
			if (enable) {
				dom.submit.removeAttr('disabled');
				dom.resultsImport.removeAttr('disabled');
				$('input', dom.resultsForm).removeAttr('disabled');
			}
			// Disable form
			else {
				dom.submit.attr('disabled', 'disabled');
				dom.resultsImport.attr('disabled', 'disabled');
				$('input', dom.resultsForm).attr('disabled', 'disabled');
				dom.feedback.html('<p>' + config.waiting + '</p>');
			}
		},

		albumsFormReady = function (enable) {
			// Enable form
			if (enable) {
				dom.albumsImport.removeAttr('disabled');
				$('input', dom.albumsForm).removeAttr('disabled');
			}
			// Disable form
			else {
				dom.albumsImport.attr('disabled', 'disabled');
				$('input', dom.albumsForm).attr('disabled', 'disabled');
			}
		},

		toggleTabs = function (event) {
			event.preventDefault();

			// Switch tabs
			$('#' + config.tabs.videos.id).hide();
			$('#' + config.tabs.albums.id).hide();

			var el = $(event.target);
			$(el.attr('href')).show();

			dom.tabs.removeClass('active');
			el.addClass('active');
		},

		getAlbums = function () {
			var page = dom.albumsPage ? dom.albumsPage.val() : 1;
			dom.albums.html('<p>' + config.waiting + '</p>');

			$.ajax({
				url: config.api.url,
				data: {
					endpoint: config.api.albums,
					per_page: config.api.per_page,
					page: page
				}
			})
			.done(function (response) {
				showAlbums(response.body);
			});
		},

		pageAlbums = function (event) {
			event.preventDefault();

			var direction = $(event.target).attr('id') == config.albums.form.pagination.previous ? 'previous' : 'next';
			if (direction == 'previous') {
				dom.albumsPage.val(parseInt(dom.albumsPage.val(), 10) - 1);
				getAlbums();
			}
			else if (direction == 'next') {
				dom.albumsPage.val(parseInt(dom.albumsPage.val(), 10) + 1);
				getAlbums();
			}
		},

		showAlbums = function (albums) {
			var i = 0,
				length = albums.data.length,
				albumsHtml = '',
				previous = albums.paging.previous,
				next = albums.paging.next,
				page = dom.albumsPage ? dom.albumsPage.val() : 1;

			// Album radios
			for (i; i < length; i++) {
				var album = albums.data[i],
					id = album.uri.split('/')[4];

				albumsHtml += '<input type="radio" id="vimeo-importer-video-' + id + '" name="' + config.albums.radios + '[]" value="' + id + '">' +
								'<label for="vimeo-importer-video-' + id + '">' + album.name + ' (' + album.metadata.connections.videos.total + ')</label>' +
								'<br>';
			}

			// Pagination
			var pagination = '';
			if (previous) {
				pagination += '<a id="' + config.albums.form.pagination.previous +'" href data-page="' + previous + '">Previous</a> ';
				if (next) {
					pagination += '| ';
				}
			}
			if (next) {
				pagination += '<a id="' + config.albums.form.pagination.next +'" href data-page="' + next + '">Next</a>';
			}

			// Results form
			var form = '<form id="' + config.albums.form.id + '">' +
							albumsHtml +
							'<p>' + pagination + '</p>' +
							'<input type="hidden" name="' + config.albums.form.page + '" value="' + page + '">' +
							'<p><input type="submit" class="' + config.albums.form.button.classes + '" value="' + config.albums.form.button.text + '"></p>' +
						'</form>';

			dom.albums.html('').append(form);
			dom.albumsForm = $('#' + config.albums.form.id);
			dom.albumsPage = $('input[name="' + config.albums.form.page + '"]', dom.albumsForm);
			dom.albumsPrevious = $('#' + config.albums.form.pagination.previous, dom.albumsForm);
			dom.albumsNext = $('#' + config.albums.form.pagination.next, dom.albumsForm);
			dom.albumsImport = $('[type="submit"]', dom.albumsForm);

			dom.albumsPrevious.click(pageAlbums);
			dom.albumsNext.click(pageAlbums);

			dom.albumsForm.submit(importAlbums);
		},

		importAlbums = function (event) {
			event.preventDefault();

			// Minimum one album
			var checked = $('input[name="' + config.albums.radios + '[]"]:checked', dom.albumsForm);
			if (checked.length === 0) {
				return false;
			}

			var id = checked.first().val();

			albumsFormReady(false);

			$.ajax({
				type: 'POST',
				url: config.api.url,
				data: {
					endpoint: config.api.importVideos,
					album: id
				}
			})
			.done(function (response) {
				albumsFormReady(true);
				console.log(response);
			});
		};

		// Init
		template();
		getAlbums();

	});

}(jQuery));
