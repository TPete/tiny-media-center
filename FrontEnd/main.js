var main, sections = {}, host = $('#host').val();

sections["main"] = (function(){
	"use strict";
	var init;
	
	init = function(){
		$('#setup-menu')
		.hide();
		$('#setup-box-link')
		.off('click')
		.on('click', function(){
			if ($('#setup-menu').is(':visible')){
				$('#setup-menu').hide();
				$('#setup-box-link').removeClass('header-buttons-active');
			}
			else{
				$('#setup-menu').show();
				$('#setup-box-link').addClass('header-buttons-active');
			}
		});
	};
	
	return {
		init: init
	};
}());

sections["shows"] = (function(){
	"use strict";
	var init;
	
	init = function(){
		$('#edit-box-link')
		.off('click')
		.on('click', function(){
			var category = sections.getSubCategory(),
				id = sections.getId();
			$.ajax({
				url: 'http://' + host + '/shows/' + category + '/edit/' + id,
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
				url: 'http://' + host + '/shows/' + category + '/episodes/' + id,
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
		var url = 'http://' + host + '/movies/';
		if (type === 'lookup'){
			url = 'http://' + host + '/movies/lookup/';
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
								url: 'http://' + host + '/movies/' + dbid, 
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
			ajaxUrl = 'http://' + host + '/movies/',
			pushUrl = 'http://' + host + '/movies/';
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
				url: 'http://' + host + '/movies/' + id,
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
				url: 'http://' + host + '/movies/search/',
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
							source: 'http://' + host + '/movies/genres/'
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

sections['install'] = (function(){
	"use strict";
	var init;
	
	function markValid(obj){
		$(obj)
		.removeClass('highlight-valid')
		.removeClass('highlight-invalid')
		.addClass('highlight-valid');
	}
	
	function markInvalid(obj){
		$(obj)
		.removeClass('highlight-valid')
		.removeClass('highlight-invalid')
		.addClass('highlight-invalid');
	}
	
	function showMessageBox(id, msg, referenceId){
		$('.content-wrapper').append('<div id="' + id + '" class="config-box">' + msg + '</div>');						
		$('#' + id).css('top', $('#' + referenceId).position().top);
	}
	
	init = function(){
		$('#restUrl')
		.on('change', function(){
			var obj = this,
				id = $(this).attr('id'),
				data = {};
			data[id] = $(obj).val();
			$.ajax({
				url: 'http://' + host + '/install/check/restUrl',
				data: data,
				success: function(data){
					if (data === 'Ok'){
						markValid(obj);
					}
					else{
						markInvalid(obj);
					}
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		$('#dbHost, #dbName, #dbUser, #dbPassword')
		.on('change', function(){
			var restUrl = $('#restUrl').val(),
				dbHost = $('#dbHost').val(),
				dbName = $('#dbName').val(),
				dbUser = $('#dbUser').val(),
				dbPassword = $('#dbPassword').val();
			$('#db-box').remove();
			if (restUrl.length > 0 && dbHost.length > 0 && dbName.length > 0 && dbUser.length > 0 && dbPassword.length > 0){
				$.ajax({
					url: 'http://' + host + '/install/check/db',
					data: {
						dbHost: dbHost,
						dbName: dbName,
						dbUser: dbUser,
						dbPassword: dbPassword,
						restUrl: restUrl
					},
					success: function(data){
						var msg;
						data = JSON.parse(data);
						if (data['dbAccess'] === 'Ok'){
							markValid('#dbHost');
							markValid('#dbName');
							markValid('#dbUser');
							markValid('#dbPassword');
							if (data['dbSetup'] === 'Ok'){
								msg = 'All required database tables are present.';
							}
							else{
								msg = 'The database setup is incomplete. Would you like to setup the database now?';
								msg += '<br><form method="POST" action="install/db" id="install-db-form"><button tpye="submit">Setup DB</button></form'
							}
							showMessageBox('db-box', msg, 'dbHost');
						}
						else{
							markInvalid('#dbHost');
							markInvalid('#dbName');
							markInvalid('#dbUser');
							markInvalid('#dbPassword');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
		});
		$('#pathMovies, #aliasMovies')
		.on('change', function(){
			var restUrl = $('#restUrl').val(),
				pathMovies = $('#pathMovies').val(),
				aliasMovies = $('#aliasMovies').val();
			if (restUrl.length > 0 && pathMovies.length > 0 && aliasMovies.length > 0){
				$.ajax({
					url: 'http://' + host + '/install/check/movies',
					data: {
						pathMovies: pathMovies,
						aliasMovies: aliasMovies,
						restUrl: restUrl
					},
					success: function(data){
						if (data === 'Ok'){
							markValid('#pathMovies');
							markValid('#aliasMovies');
						}
						else{
							markInvalid('#pathMovies');
							markInvalid('#aliasMovies');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
		});
		$('#pathShows, #aliasShows')
		.on('change', function(){
			var restUrl = $('#restUrl').val(),
				pathShows = $('#pathShows').val(),
				aliasShows = $('#aliasShows').val();
			$('#shows-box').remove();
			if (restUrl.length > 0 && pathShows.length > 0 && aliasShows.length > 0){
				$.ajax({
					url: 'http://' + host + '/install/check/shows',
					data: {
						pathShows: pathShows,
						aliasShows: aliasShows,
						restUrl: restUrl
					},
					success: function(data){
						var msg = 'The following sub folders where found and will be used as categories: ',
							top;
						data = JSON.parse(data);
						if (data['result'] === 'Ok'){
							data['folders'].forEach(function(element){
								msg += element + ', ';
							});
							msg = msg.substr(0, msg.length - 2);
							markValid('#pathShows');
							markValid('#aliasShows');
							showMessageBox('shows-box', msg, 'pathShows');
						}
						else{
							markInvalid('#pathShows');
							markInvalid('#aliasShows');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
		});
		
		$('#restUrl, #dbHost, #pathMovies, #pathShows').trigger('change');
	};
	
	return {
		init: init
	};
}());

sections.getPath = function(){
	var path = window.location.href,
	host = $('#host').val(),
	split;
	path = path.substr(path.indexOf(host) + host.length);
	if (path.substr(0, 1) === '/'){
		path = path.substr(1);
	}
	
	return path.split('/');
};

sections.getCategory = function(){
	var path = this.getPath();
			
	return path[0];
};

sections.getSubCategory = function(){
	var path = this.getPath();
	
	return path[1];
}

sections.getId = function(){
	var path = this.getPath();
	
	return path[2];
};

sections.init = (function(){
	var init;
		
	init = function(){
		var cat = sections.getCategory();
		if (cat.length === 0){
			cat = 'main';
		}
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