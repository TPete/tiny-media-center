var main, sections = {};

sections["shows"] = (function(){
	"use strict";
	var init;
	
	init = function(){
		$('#edit-box-link')
		.off('click')
		.on('click', function(){
			var category = sections.getSubCategory(),
				id = sections.getId();
			console.log('/tv/' + category + '/edit/' + id);
			$.ajax({
				url: '/tv/shows/' + category + '/edit/' + id,
				success: function(data){
					if ($('#show-edit-box').length > 0){
						$('#show-edit-box').remove();
					}
					$('body').append(data);
					$('#show-edit-box').dialog({
						width: 'auto'
					});
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('.episode-link-present, .episode-link-missing')
		.off('click')
		.on('click', function(){
			var category = sections.getSubCategory(),
				id,
				link;
			$('#episode-details').show();
			$('.episode-link-present, .episode-link-missing').removeClass('selected');
			$(this).addClass('selected');
			$('#episode-play-link').remove();
			id = $(this).find('a').data('id');
			if ($(this).hasClass('episode-link-present')){
				link = $(this).find('a').data('href');
				$(this).prepend("<a id='episode-play-link' href='" + link + "' target='_blank' title='Play'>&#9654;</a>");
			}
			$.ajax({
				url: '/tv/shows/' + category + '/episodes/' + id,
				success: function(data){
					$('#episode-details').html(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('.episodes-wrapper')
		.off('scroll')
		.on('scroll', function(e){
			var top = $('.series-list > li:first').offset().top,
				titleHeight = $('.series-title').outerHeight(true),
				offset = $('.series-list > li:first > span').outerHeight(true),
				mh = $('.episodes-wrapper').innerHeight() - offset;

			if (top > titleHeight){
				$('#episode-details').css('top', (top + offset) + 'px');
				$('#episode-details').css('max-height', (mh - top) + 'px');
			}
			else{
				$('#episode-details').css('top', (titleHeight + offset) + 'px');
				$('#episode-details').css('max-height', (mh - titleHeight) + 'px');
			}
		}).
		trigger('scroll');
		
	};
	
	return {
		init: init
	};
}());


sections["movies"] = (function(){
	"use strict";
	var init;
		
	function showDetailsDialog(dbid, movieDBID, filename, type){
		var url = '/tv/movies/';
		if (type === 'lookup'){
			url = '/tv/movies/lookup/';
		}
		$.ajax({
			url: url + dbid,
			data: {
				output: 'edit',
				movieDBID: movieDBID
			},
			success: function(data){
				$('#movie-details-dialog').remove();
				$('body').append(data);
				$('#movie-details-dialog')
				.dialog({
					width: 650,
					height: 450,
					modal: true,
					buttons: [
						{text: "Speichern",
						 click : function(){
							$.ajax({
								url: '/tv/movies/' + dbid, 
								type: 'POST',
								context: this,
								data: {filename: filename,
									movieDBID: movieDBID},
								success: function(resp){
									if (resp.substr(0, 2) === "OK"){
										alert('Daten gespeichert');
									}
									else{
										alert('Fehler beim Speichern');
									}
									window.location.reload();
								},
								error: function(jqXHR, textStatus, errorThrown){
									alert(errorThrown);
									window.location.reload();
								}
							});
						}
						},
						{text: 'Abbrechen',
						 click: function(){
							$(this).dialog("close");
						}
						}
					]
					
				});
				$('#movie-id')
				.on('change', function(){
					showDetailsDialog(dbid, $(this).val(), filename, 'lookup');
				});
			},
			error: function(jqXHR, textStatus, errorThrown){
				alert(errorThrown);
			}
		});
	}
	
	function addPosterClickHandler(){
		$('#nm-movie-poster')
		.off('click')
		.on('click', function(){
			var src = $(this).find('img').attr('src'),
				div = '<div id="movie-poster-dialog"></div>',
				img,
				h = $('.content-wrapper').height(), 
				w = h/3*2;
			src = src.substr(0, src.lastIndexOf('_')) + '_big.jpg';
			img = '<img src="' + src + '">';
			if ($('#movie-poster-dialog').length > 0){
				$('#movie-poster-dialog').remove();
			}
			$('body').append(div);
			$('#movie-poster-dialog')
			.append(img)
			.dialog({
				height: h,
				width: w,
				modal: true,
				dialogClass: 'ui-dialog-no-title',
				open: function(event, ui){
					$('.ui-widget-overlay').bind('click', function(){
						$("#movie-poster-dialog").dialog('close');
					}); 
				}
			});
		});
	}
	
	function addEditLinkHandler(){
		$('#movie-edit-link')
		.on('click', function(e){			
			e.preventDefault();
			var dbid = $(this).data('id'),
				movieDBID = $(this).data('moviedbid'),
				filename = $(this).data('filename');
			showDetailsDialog(dbid, movieDBID, filename, 'store');
			
			return false;
		});
	}
	
	function updateMovieOverview(query, pushHistory){
		var loader = '<div id="loader-movie-overview">',
			ajaxUrl = '/tv/movies/',
			pushUrl = '/tv/movies/';
		if (query.length > 0){
			ajaxUrl += '?' + query + '&display=overview';
			pushUrl += '?' + query;
		}
		else{
			ajaxUrl += '?display=overview';
		}
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<div></div>';
		loader += '<br class="clear">';
		loader += '</div>';
		if (query.toLowerCase() !== 'javascript: void(0);'){
			$('#movie-overview').empty().append(loader);
			$.ajax({
				url: ajaxUrl,
				success: function(data){
					$('#movie-overview').html(data);
					if (pushHistory){
						history.pushState(null, '', pushUrl);
					}
					initHandlers();
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		}
	}
		
	function initHandlers(){
		window.onpopstate = function(event){
			updateMovieOverview(window.location.search.substring(1), false);
		}
		
		
		$('.movie-overview-poster')
		.on('click', function(){
			$('#nm-movie-details-wrapper').html('<div id="loader-movie-details"></div>');
			var id = $(this).data('id');
			$.ajax({
				url: '/tv/movies/' + id,
				success: function(data){
					$('#nm-movie-details-wrapper').html(data);
					addEditLinkHandler();
					addPosterClickHandler();
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('#movie-overview-prev, #movie-overview-next')
		.on('click', function(){
			var query = $(this).attr('href');
			updateMovieOverview(query, true);
			
			return false;
		});
		
		$('#search-box-link')
		.off('click')
		.on('click', function(){
			$.ajax({
				url: '/tv/movies/search/',
				success: function(data){
					var filter = $('#hidden-filter').val(),
						genres = $('#hidden-genres').val(),
						sort = $('#hidden-sort').val(),
						collection = $('#hidden-collection').val(),
						list = $('#hidden-list').val();
					if ($('#nm-movie-search-box').length > 0){
						$('#nm-movie-search-box').remove();
					}
					$('body').append(data);
					$('#filter').val(filter);
					$('#genres').val(genres);
					$('#sort').val(sort);
					$('#collection').val(collection);
					$('#list').val(list);
					$('#nm-movie-search-box').dialog({
						width: 'auto'
					});
					$('#genres').tagit({
						showAutocompleteOnFocus: true,
						allowSpaces: true,
						autocomplete: {
							source: '/tv/movies/genres/'
						}
					});
					
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
	}
	
	init = function(){
		initHandlers();
		$('.movie-overview-poster')
		.eq(0)
		.trigger('click');
	};
	
	return {
		init: init
	};
}());

sections.getCategory = function(){
	var path = window.location.pathname,
		split = path.split('/');
	
	if (split.length > 2){
		path = split[2];
	}
	else{
		path = null;
	}
			
	return path;
};

sections.getSubCategory = function(){
	var path = window.location.pathname,
	split = path.split('/');

	if (split.length > 3){
		path = split[3];
	}
	else{
		path = null;
	}
			
	return path;
}

sections.getId = function(){
	var path = window.location.pathname,
		split = path.split('/');
	
	if (split.length > 4){
		path = split[4];
	}
	else{
		path = null;
	}
			
	return path;
};

sections.init = (function(){
	var init;
		
	init = function(){
		var cat = sections.getCategory();		
		if (cat !== null){
			console.log("init " + cat);
			sections[cat].init();
		}
		else{
			console.log("no init");
		}
	}
	
	return init;
}());

main = (function(){
	"use strict";
	var init;	
	
	init = function(){
		sections.init();
	    
	    $('.jquery-ui-buttonset').buttonset();
	    $('.jquery-ui-button').button();
	};
	
	return {
		init: init
	};
}());

main.init();