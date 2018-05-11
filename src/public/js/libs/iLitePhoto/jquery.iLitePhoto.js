/* ================================================
 *	Class: iLitePhoto
 *	Description: Lightbox clone for jQuery
 *	Author: Michael Gnehr (Intertopia)
 *	Version: 1.0.1
 *	Copyright: All rights reserved (C) 2017
 *  Do not copy or redistribute.
 * ================================================ */
/* ---------- JQUERY iLitePhoto OBJECT ----------- */
if(!jQuery().iLitePhoto) {
	"use strict";
	(function($) {
		var $currentMarkup;
		var $content_body;
		var preloadList = [];
		var callimageunbind = false;
		var old_expanded = false;

		//variables 

		var photolist;
		/* ------------------------ Plugin ------------------------ */
		$.fn.iLitePhoto = function(methodOrOptions) {

			var base = this;
			var $base = $(this);

			var settings;
			var icount = 1;		
			var iindex = 0;
			var igroup = '__single';
			var oldiindex = -1;
			var itype = "image";

			// ----------- public available methods -----------
			var methods = {
				version : function() {
					return $.iLitePhoto = {version: '1.0.1'};
				},
				init : function(options) {
					settings = $.extend({}, $.fn.iLitePhoto.defaults, options );
					_gen_photolist();
					return base;
				},
				close : function(){
					if ($currentMarkup!=null){
						$currentMarkup.fadeOut(300, function() { $currentMarkup.remove(); _cleanup(); $.fn.iLitePhoto.defaults.close(); });
					}
				},
				open : function(){
					var $el = $(this);
					if ($el.length > 0){
						$el.get(0).click();
					}
				},
				prev : function(){
					if ($currentMarkup!=null){
						$currentMarkup.find('.navigation.prev').click();
					}
				},
				next : function(){
					if ($currentMarkup!=null){
						$currentMarkup.find('.navigation.next').click();
					}
				}
			};

			// ----------- private methods --------------------
			var _gen_makeup = function () {
				$currentMarkup = $('<div/>', {
					id: 'iLitePhoto_modal',
					css: {
						"position": 'fixed'
					}
				});
				$currentMarkup.addClass(settings.style);
				$currentMarkup.html(settings.markup);

				//style markeup ------------------------------
				bodysize = 0;
				if (settings.always_fullscreen){
					$currentMarkup.find('.ilp_wrapper').addClass('fullscreen');
				}
				if (settings.title_2_head){
					bodysize++;
					$currentMarkup.find('.texts .title').remove();
				} else {
					$currentMarkup.find('.modal_header .title').remove();
				}
				if (!settings.miniatures){
					$currentMarkup.find('.miniatures').remove();
				}
				if (icount == 1){
					$currentMarkup.find('.navigation').remove();
				}
				if (!settings.socials){
					$currentMarkup.find('.socials').remove();
				}
				if (settings.notexts){
					$currentMarkup.find('.overlays .texts').remove();
				} else {
					bodysize++;
				}
				if (bodysize == 1){
					$currentMarkup.find('.modal_body').addClass('one');
				} else if (bodysize == 2){
					$currentMarkup.find('.modal_body').addClass('two');
				}
				$content_body = $currentMarkup.find('.modal_body');
				$spinner_container = $currentMarkup.find('.spinner_container');
			}

			var _gen_clickHandler = function () {
				//close button -------------------------------------
				var $close_btn = $currentMarkup.find('.modal_header .modal_close');
				$close_btn.click(function(){
						methods['close']();
				});
				//close on background overlay click
				var $table_cell = $currentMarkup.children().children();
				$table_cell.click(function(e){
					if (e.target == $table_cell[0]){
						methods['close']();
					}
				});
				var $n = $currentMarkup.find('.navigation.next');
				$n.click(function(){
					_load_element_index((iindex+1+icount)%icount);
				});
				var $v = $currentMarkup.find('.navigation.prev');
				$v.click(function(){
					_load_element_index((iindex-1+icount)%icount);
				});
				if(settings.keyboard){
					$(document).on( "keydown.iLitePhoto_nextprev", function(e){
						if ($currentMarkup != null){
							switch(e.keyCode){
								case 37:
									_load_element_index((iindex-1+icount)%icount);
									e.preventDefault();
									return false;
									break;
								case 39:
									_load_element_index((iindex+1+icount)%icount);
									e.preventDefault();
									return false;
									break;
								case 27:
									methods['close']();
									e.preventDefault();
									return false;
									break;
							};
							return true;
						}
					} );
				}
			}
			var _gen_socialHandler = function () {
				//TODO generate safe click -> add class inactive on normal -> on click load them + color restore (remove inactive class) -> 2nd click do social stuff
			}

			var _gen_eventHandler = function () {
				if (settings.swipe){
					var $wrapper = $currentMarkup.find('div.ilp_wrapper');
					//$wrapper.on('touchmove.iLitePhoto_swipe', function () {
						//TODO TODO
					//});
				}
				//TODO+ swipe on touch
				//TODO unbind -> off
			}

			var _gen_imagemap = function() {
				//TODO generate and style image miniatures
			}

			var _elementClick = function (event){
				if (!event) var event = window.event; 
		  		event.cancelBubble = true;
		  		if (event.stopPropagation) event.stopPropagation();
		  		event.preventDefault();

		  		//get settings, image count, image group, image index
		  		igroup = "__single";
				if ($(this).attr('data-group') && this.dataset.group!=""){
					igroup = this.dataset.group;
					icount = photolist.groups[igroup].list.length;
					iindex = $.inArray(this, photolist.groups[igroup].list);
				} else {
					icount = 1;
					iindex = 0;
				}
				settings = photolist.groups[igroup].settings;

				_show();
				_load_element_index(iindex);
			}

			var _gen_photolist = function() {
				if (typeof photolist == 'undefined'){
					photolist = { groups: {} };
				}
				$base.each(function(i, e){
					var type = _get_element_type(e);
					if (type == null) return true;
					var g = "__single";
					if ($(e).attr('data-group') && e.dataset.group!=""){
						g = e.dataset.group;
					}
					if (!photolist.groups.hasOwnProperty(g)){
						photolist.groups[g] = {list:[], settings: settings};
					}
					photolist.groups[g].list.push(e);
					$(e).click(_elementClick);
				});
			}

			var _gen_current_images = function() {
				//TODO miniatures
			}

			var _show = function() {
				if ($currentMarkup==null){
					oldiindex = -1;
					_gen_makeup();
					_gen_clickHandler();

					if (settings.miniatures){
						_gen_current_images();
					}
					_gen_eventHandler();
					$('body').append($currentMarkup);
					$.fn.iLitePhoto.defaults.open();
				}
			}

			var _cleanup = function () {
				$currentMarkup = null;
				$(document).off('keydown.iLitePhoto_nextprev');
				if (old_expanded!= null){
					if (callimageunbind && $.fn.iLitePhoto.types[old_expanded.type].hasOwnProperty('unbindevents')){
						var $current = $content_body.children('.current');
						$.fn.iLitePhoto.types[old_expanded.type].unbindevents($current);
						callimageunbind = false;
					}
					old_expanded = null;
				}
				//TODO remove event handler for swipe if appended to window
			}

			var _get_element_type = function (el){
				var t = null;
				if ($(el).attr('data-type') && el.dataset.type!=""){
					t = el.dataset.type;
				} else {
					var url = el.href;
					for (var property in $.fn.iLitePhoto.types) {
					    if ($.fn.iLitePhoto.types.hasOwnProperty(property)) {
					    	console.log("url: ", url);
					        var regex = ($.fn.iLitePhoto.types[property].hasOwnProperty('reg_modifier'))? new RegExp($.fn.iLitePhoto.types[property].regex, $.fn.iLitePhoto.types[property].reg_modifier) : new RegExp($.fn.iLitePhoto.types[property].regex);
					    	if (url.match(regex)){
					    		t = property;
					    		break;
					    	}
					    }
					}
					console.log('t: ', t);
				}
				return t;
			}

			var _expand_element = function (e){
				var t = _get_element_type(e);
				var haschild = ($(e).children('img').length > 0);
				var url = ($.fn.iLitePhoto.types[t].hasOwnProperty('urlmodify'))? $.fn.iLitePhoto.types[t].urlmodify(e, e.href) : e.href;
				return {
					e: e,
					type: t,
					url: url,
					thumb: (haschild)? $(e).children('img')[0].src : false, //TODO miniatures + css replacements
					title: ((haschild && $(e).children('img')[0].title != "")? $(e).children('img')[0].title : e.title ),
					description: ((haschild && $(e).children('img')[0].alt != "")? $(e).children('img')[0].alt : "" ),
					dummyclass: ($.fn.iLitePhoto.types[t].hasOwnProperty('dummy')? $.fn.iLitePhoto.types[t].dummy : "dummy" + t)
				};
			}

			var _load_element_index = function (index){
				if (oldiindex != index){
					iindex = index;
					var el = photolist.groups[igroup].list[iindex];
					var ex = _expand_element(el);
					console.log(ex);
					var markup;

					var $current = $content_body.children('.current');
					var $next = $content_body.children('.container').not($current);
					
					if ($.fn.iLitePhoto.types[ex.type].hasOwnProperty('compatibility') && !$.fn.iLitePhoto.types[ex.type].compatibility(ex.type)){
						markup = '<div class="error">' + $.fn.iLitePhoto.language[settings.language].unsupported_format+"</div>";
					} else {
						markup = $.fn.iLitePhoto.types[ex.type].markup;
						markup = markup.replace('{TITLE}', ex.title)
							.replace('{DESC}', ex.description)
							.replace('{URL}', ex.url)
							.replace('{AUTOPLAY}', (settings.autoplay && $.fn.iLitePhoto.types[ex.type].hasOwnProperty('autoplay'))? $.fn.iLitePhoto.types[ex.type].autoplay : "" )
							.replace('{FALLBACK}', $.fn.iLitePhoto.language[settings.language].unsupported_format);
					}

					$next.html(markup);
					$spinner_container.show();
					var $title = $currentMarkup.find('.title');
					var $desc = $currentMarkup.find('.texts .description');

					var display_elem = function () {
						if (old_expanded != null && callimageunbind && $.fn.iLitePhoto.types[old_expanded.type].hasOwnProperty('unbindevents')){
							$.fn.iLitePhoto.types[old_expanded.type].unbindevents($current);
							callimageunbind = false;
						}

						$current.fadeOut(400, function(){
							$(this).removeClass('current');
							$(this).empty();
						});
						$next.fadeIn(400, function(){
							$(this).addClass('current');
						});

						$title.fadeOut(200, function(){
							$(this).html(ex.title);
							$(this).fadeIn(200);
						});

						$desc.fadeOut(200, function(){
							$(this).html(ex.description);
							$(this).fadeIn(200);
						});

						$currentMarkup.find()

						if (settings.socials) { //TODO generate social links 
							$currentMarkup.find('.socials').html(settings.socials_markup);
							_gen_socialHandler();
						}

						$spinner_container.hide();

						if ($.fn.iLitePhoto.types[ex.type].hasOwnProperty('bindevents')){
							$.fn.iLitePhoto.types[ex.type].bindevents($next, settings);
							callimageunbind = true;
						}
						oldiindex = iindex;
						old_expanded = ex;
					}
					if ($.fn.iLitePhoto.types[ex.type].hasOwnProperty('preload')) {
						$.fn.iLitePhoto.types[ex.type].preload(ex.url, function(){display_elem(); $spinner_container.hide(); });
					} else {
						display_elem();
					}
				}
			}

			if (typeof $.iLitePhoto == 'undefined'){
				$.extend({
			        iLitePhoto: function (par) {
			            if (par == "init" || par == "open"){ //not callable functions on non dom object (no elements)
			            	$.error( 'Method ' +  par + ' in not available within this scope.' );
							return;
			            }
			            if ( methods[par] ) {
							return methods[ par ].apply( this, Array.prototype.slice.call( arguments, 1 ));
						} else {
							$.error( 'Method ' +  par + ' does not exist on jQuery.iLitePhoto' );
							return;
						}
			        }
			    });
		    }

			if ( methods[methodOrOptions] ) {
				return methods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
			} else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
				// Default to "init"
				return methods.init.apply( this, arguments );
			} else {
				$.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.iLitePhoto' );
				return;
			}
		};

		// --------- DEFAULT PARAMETER ---------
		$.fn.iLitePhoto.defaults = {
			//options
			language: 'de',
			autoplay: true,		// automatically start videos: true/false
			notexts: false,		// hide title and description box/overlay
			text_hide_delay: 2000,	//hide title/description after n ms | false OR 0 to disable TODO unsupported
			title_2_head: false,	//write image title to litebox header, ignores notexts property
			slideshow: 5000, 	//interval time in ms | false or 0 to disale slideshow TODO unsupported
			autoplay_slideshow: false, 	// true/false TODO unsupported
			miniatures: false,		// show miniatures on hover: true/false TODO unsupported
			keyboard: true, 	// enable keyboard event handle
			always_fullscreen: false, //show modal as fullscreen
			swipe: false,	// enable mobile image swipe (propritary) TODO unsupported
			style: 'default',	//add an extra class name to surrounding div
			markup: '<div id="ilp_table"> \
						<div id="ilp_table-cell"> \
							<div class="ilp_wrapper"> \
								<div class="modal_header"> \
									<div class="title"></div> \
									<span class="modal_close">&times;</span> \
								</div> \
								<div class="modal_body"> \
									<div class="container" style="display: none;" data-link></div> \
									<div class="container current" data-link> \
										<div class="spinner"></div> \
									</div> \
									<div class="spinner_container"> \
										<div class="spinner"></div> \
									</div> \
								</div> \
								<div class="miniatures"></div> \
								<div class="socials"></div> \
								<div class="texts"><span class="title"></span><div class="description"></div></div> \
								<div class="navigation next svgchevrondown"></div><div class="navigation prev svgchevrondown"></div> \
							</div> \
						</div> \
					</div>',
			socials: false,		// show social buttons fot twitter, facebook, google+, TODO unsupported
			socials_markup: '<div class="fb"><a href="#"></a></div><div class="tw"><a href="#"></a></div><div class="go"><a href="#"></a></div>',
			//events
			open: function(){}, // called when iLitePhoto is opened
			close: function(){}, // called when iLitePhoto is closed
		}

		$.fn.iLitePhoto.language = {
			de: {unsupported_format: "Das gewählte Element kann in Ihrem Broser nicht dargestellt werden. Nutzen Sie bitte einen anderen/neueren Browser."},
			en: {unsupported_format: "Your browser dont't support this fileformat. Please try the latest version of your browser or use a different one."}
		}

		$.fn.iLitePhoto.types = {
			image: { 
				regex:'(.+)\.(jpg|png|gif|svg)$',
				reg_modifier:'i',
				swipe: true, //TODO is it needed here / disable on other formats? or leave it always on if enabled
				markup: '<div class="img" title="{TITLE}" alt="{DESC}" style="background-image: url({URL});"></div>',
				preload: function (link, callback){ 
					var img = new Image();
					img.onload = function() {
						callback(); var index = preloadList.indexOf(this);
				        if (index !== -1) { preloadList.splice(index, 1); /* remove for memory consumption reasons */}
				    }
				    preloadList.push(img); img.src = link; }
			},
			video: { 
				regex:'(.+)\.(mp4)' , 
				reg_modifier:'i', 
				markup: '<video controls controlsList="nodownload" {AUTOPLAY} preload="auto" title="{TITLE}" alt="{DESC}">'+
							'<source src="{URL}">'+
						'</video><div class="playpause"></div>',
				autoplay: 'autoplay',
				compatibility: function (type){
					return !!document.createElement('video').canPlayType;
				},
				bindevents: function (el, s) {
					var btn = el.children('.playpause'); var vid = el.children('video');

					//videopause on click
					vid.click(function(){this.paused?	this.play():this.pause();});
					btn.click(function(){ var vid = $(this).prev()[0]; vid.paused?vid.play():vid.pause();});

					if (s.keyboard){
						//video onpause on spacebar + svg overlay
						vid.hover(function() { this.focus(); }).keydown(function(e) {
		   					if(e.keyCode === 32){ if(this.paused) { this.play(); } else { this.pause(); };	return false; 	} });
						btn.hover(function() { vid.focus(); }).keydown(function(e) { 
							if(e.keyCode === 32){ if(vid.paused) { vid.play(); } else { vid.pause(); }; return false; } });
						var playpauselistener = function(e){
							if(this.paused) { $(this).next().fadeIn(300); } else { $(this).next().fadeOut(300); } };
						$(vid).each(function(i,e){
							$(e).on('play.iLitePhoto_video', playpauselistener);	$(e).on('pause.iLitePhoto_video', playpauselistener); });
					}
				},
				unbindevents: function (el){
					var vid = el.children('video');
					$(vid).each(function(i,e){ 
						$(e).off('play.iLitePhoto_video');
						$(e).off('pause.iLitePhoto_video');
					});
				}
			},
			youtube: { 
				regex:'((.*(youtu\.be|youtube).*(\/watch(.*)v(=|\%3d|\%3D)|\/embed\/|\/){1}))[a-zA-Z0-9_-]{11}' , 
				markup: '<iframe class="youtube" type="text/html" src="{URL}" title="{TITLE}" alt="{DESC}" frameborder="0" allowFullScreen></iframe>',
				autoplay: '?autoplay=1',
				urlmodify: function (element, url) { 
					var p = /((.*(youtu\.be|youtube).*(\/watch(.*)v(=|\%3d|\%3D)|\/embed\/|\/){1}))[a-zA-Z0-9_-]{11}/, match = url.match(p); 
					return (match && match.length > 2)? "https://www.youtube.com/embed/" + match[0].substr(match[1].length) + "{AUTOPLAY}" : false; }
			},
			pdf: {
				regex:'(.+)\.(pdf)' ,
				reg_modifier:'i', 
				markup: '<object data="{URL}" type="application/pdf" width="100%" height="100%">{FALLBACK}</object>'
			}
		}

	})(jQuery);
}
