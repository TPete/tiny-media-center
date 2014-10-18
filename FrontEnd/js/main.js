var main, sections = {};

sections["shows"] = (function(){
	"use strict";
	var init;
	

	function convertTitle(title){
		var result = title;
		result = result.toLowerCase();
		result = result.replace(/ /g, "-");
		
		return result;
	}
	
	function tryScraping(category, id, url){
		$.ajax({
			url: '/tv/shows/' + category + '/' + id + '/',
			type: 'POST',
			async: false,
			data: {url: url},
			success: function(data){
				if (data === 'Ok'){
					alert('Episodenliste aktualisiert');
					window.location.reload();
				}
				else{
					var newUrl = prompt('Die Episodenliste konnte nicht aktualisiert werden. Bitte die URL korrigieren: ', url);
		    		if (newUrl !== null){
		    			tryScraping(category, id, newUrl)
		    		}
				}
			},
			error: function(jqXHR, textStatus, errorThrown){
				alert(errorThrown);
			}
		});
	}
			
	init = function(){
		$('.scrapeBn')
	    .on('click', function(){
	    	var title = $('.header-caption').text(),
	    		converted, 
	    		url;
	    	if ($('#scrapeUrl').val().length > 0){
	    		url = $('#scrapeUrl').val();
	    	}
	    	else{
	    		converted = convertTitle(title);
	    		url = 'www.fernsehserien.de/' + converted + '/episodenguide';
	    	}
	    	tryScraping(sections.getSubcategory(), sections.getId(), url);
	    });
	};
	
	return {
		init: init
	};
}());

sections["movies"] = (function(){
	"use strict";
	var init;
	
	function showDetailsDialog(id, filename, type){
		var url = '/tv/movies/';
		if (type === 'lookup'){
			url = '/tv/movies/lookup/';
		}
		$.ajax({
			url: url + id,
			data: {
				output: 'edit',
				filename: filename
			},
			success: function(data){
				$('#movie-details-dialog').remove();
				$('body').append(data);
				$('#movie-details-dialog')
				.dialog({
					width: 650,
					modal: true,
					buttons: [
						{text: "Speichern",
						 click : function(){
							var newId = $('#movie-id').val();
							if (newId === ""){
								//ignore
							}
							else {
								$.ajax({
									url: '/tvapi/movies/' + newId, 
									type: 'POST',
									context: this,
									data: {filename: filename},
									success: function(resp){
										if (resp.substr(0, 2) === "OK"){
											alert('Daten gespeichert');
										}
										else{
											alert('Fehler beim Speichern');
										}
										$(this).dialog("close");
									},
									error: function(jqXHR, textStatus, errorThrown){
										alert(errorThrown);
										$(this).dialog("close");
									}
								});
							}
						}
						},
						{text: 'Abbrechen',
						 click: function(){
							$(this).dialog("close");
						}
						}
					],
					close: function(){
						window.location.reload();
					}
					
				});
				$('#movie-id')
				.on('change', function(){
					showDetailsDialog($(this).val(), filename, 'lookup');
				});
			},
			error: function(jqXHR, textStatus, errorThrown){
				alert(errorThrown);
			}
		});
	}
	
	function getTabCount(index){
		var val = $('#movie-tabs-link-' + index).text();
		val = val.substring(val.indexOf('(') + 1, val.indexOf(')'));
		val = parseInt(val, 10);

		return val;
	}
	
	function getTabToActivate(){
		var tc;
		
		tc = getTabCount(1);
		if (tc > 0){
			return 0;
		}
		tc = getTabCount(2);
		if (tc > 0){
			return 1;
		}
		tc = getTabCount(3);
		if (tc > 0){
			return 2;
		}
		
		return 0;
	} 
	
	init = function(){
		var active = getTabToActivate();
		console.log('activate tab ' + active);
		
		$('#movie-tabs').tabs({active: active});
		
		$('#movie-sort')
	    .on('change', function(){
	    	$('#movie-form').submit();
	    });
				
		$('.movie-link').button({
			icons : {
				primary : "ui-icon-play"
			},
			text : false
		});
		
		$('.movie-info')
		.on('click', function(){
			var id = $(this).data('id');
			$.ajax({
				url: '/tv/movies/' + id,
				success: function(data){
					$('#movie-details').html(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('.collection-info')
		.on('click', function(){
			var id = $(this).data('id');
			$.ajax({
				url: '/tv/collections/' + id,
				success: function(data){
					$('#movie-details').html(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		$('.scrapeBn')
		.on('click', function(){
			var files = [], cnt = 0;
			$('.movie-list>li>a')
			.removeClass('pending')
			.removeClass('done')
			.removeClass('error')
			.removeClass('ignore');
			$('.movie-list>li>a.movie-info').each(function(){
				var id = parseInt($(this).data('id'), 10);
				if (id === -1){
					files.push({"filename": $(this).data('filename'), "title": $(this).text(), "element": this});
					$(this).addClass('pending');
				}
				else{
					$(this).addClass('ignore');
				}
			});
			$('#movie-progressbar')
			.show()
			.progressbar({max: files.length, value: 0});
			files.map(function(ele){
				var filename = ele["filename"],
					title = ele["title"].trim();
				filename = filename.substr(filename.lastIndexOf("/") + 1);
				$.ajax({
					url: '/tvapi/movies/scrape/',
					async: false,
					data: {
						title: title,
						filename: filename
					},
					success: function(data){
						console.log("file " + filename + " title " + title + " res " + data);
						cnt++;
						$('#movie-progressbar').progressbar("option", "value", cnt);
						if (data.substr(0, 2) === 'OK'){
							$(ele["element"])
							.removeClass('pending')
							.addClass('done');
						}
						else{
							$(ele["element"])
							.removeClass('pending')
							.addClass('error');
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						console.log(errorThrown);
						$(ele["element"])
						.removeClass('pending')
						.addClass('error');
					}
				});
			});
			window.location.reload();
		});
		
		$('.movie-info')
		.on('dblclick', function(){
			var id = $(this).data('id'),
				filename = $(this).data('filename');
			filename = filename.substr(filename.lastIndexOf('/') + 1);
			showDetailsDialog(id, filename, 'store');
		});
		
		$('.movie-list')
		.on('keyup', function(e){
			var ele = $('.movie-list>li>a.movie-info')
					.filter(function(){
						var t = $(this).text().trim();
						t = t.substr(0, 1);
						t = t.toUpperCase();
						return t === String.fromCharCode(e.which);
					}),
				con = $('.movie-list');
			if (ele.length > 0){
				var lower = ele.filter(function(){
					console.log($(this).position().top);
					return $(this).position().top - 2 > con.offset().top;
				});
				if (lower.length > 0){
					con.scrollTop(lower.offset().top - con.offset().top + con.scrollTop());
				}
				else{
					con.scrollTop(ele.offset().top - con.offset().top + con.scrollTop());
				}
			}
		});
		
		$('#movie-genre').tagit({
			autocomplete: {
				source: '/tvapi/movies/genres'
			}
		});
	};
	
	return {
		init: init
	};
}());

sections["newmovies"] = (function(){
	"use strict";
	var init;
	
	function searchBoxHandler(){
		$('#nm-movie-search-box>form')
		.on('submit', function(e){
			
		});
	}
		
	function initHandlers(){
		$('.movie-overview-poster')
		.on('click', function(){
			var id = $(this).data('id');
			$.ajax({
				url: '/tv/newmovies/' + id,
				success: function(data){
					$('#nm-movie-details-wrapper').html(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					alert(errorThrown);
				}
			});
		});
		
		
		$('#movie-overview-prev, #movie-overview-next')
		.on('click', function(){
			var query = $(this).attr('href');
			if (query.toLowerCase() !== 'javascript: void(0);'){
				$.ajax({
					url: '/tv/newmovies/' + query + '&display=overview',
					success: function(data){
						$('#movie-overview').html(data);
						initHandlers();
					},
					error: function(jqXHR, textStatus, errorThrown){
						alert(errorThrown);
					}
				});
			}
			
			return false;
		});
		
		$('#search-box-link')
		.off('click')
		.on('click', function(){
			$.ajax({
				url: '/tv/newmovies/search/',
				success: function(data){
					var filter = $('#hidden-filter').val(),
						genres = $('#hidden-genres').val(),
						sort = $('#hidden-sort').val();
					if ($('#nm-movie-search-box').length > 0){
						$('#nm-movie-search-box').remove();
					}
					$('body').append(data);
					$('#filter').val(filter);
					$('#genres').val(genres);
					$('#sort').val(sort);
					$('#nm-movie-search-box').dialog({
						width: 'auto'
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

sections.getSubcategory = function(){
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