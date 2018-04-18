/**
 * JS SCRIPTS invite
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        script
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			09.04.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

(function(){
	// ===== HELPER FUNCTIONS -- AJAX ===============================================
	var postError = function(data){
		console.log(data);
		try {
			pdata = JSON.parse(data.responseText);
			silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
		} catch(e) {
			silmph__add_message('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...', MESSAGE_TYPE_WARNING, 5000);
			auto_page_reload(5000);
		}
	}; 
	var parseData = function (data, reload) {
		var r = {}
		if (typeof(data.success) != 'undefined' ){
			r = data;
		} else {
			try {
				r = JSON.parse(data);
			} catch(e) {
				r.success=false;
				r.eMsg = ('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...');
				if (typeof (reload) == 'undefined' || reload == true){
					auto_page_reload(5000);
				}
			}
		}
		return r;
	}
	// ===== HELPER FUNCTIONS -- TRIM ===============================================
	if (typeof(String.prototype.trim != 'function')){
		(function() {
			// make sure we also trim BOM and NBSP
			var rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
			String.prototype.trim = function (){
				return this.replace(rtrim, '');
			};
		})();
	}
	// ===== HELPER FUNCTIONS -- FORM ERROR BACKGROUND COLOR ==========================
	var formError = function ($element, isError){
		if (isError){
			if (!$element.hasClass('bg-danger')){
				$element.addClass('bg-danger');
				$element.addClass('text-white');
			}
		} else {
			if ($element.hasClass('bg-danger')){
				$element.removeClass('bg-danger');
				$element.removeClass('text-white');
			}
		}
	}
	// ===== JS SCRIPT LOADER ===============================================
	// append scripts to head
	// ---------------
	var loadCounter={};
	var loadScripts = function(scripts, callback, param){
		if (scripts.constructor === Array){
			var g = 'g'+Math.floor((Math.random() * 1000) + 1);
			loadCounter[g]=0;
			var l = scripts.length;
			if (l > 0){
				for (var i = 0; i<l; i++){
					_loadScript(scripts[i], l - 1, g, callback, param);
				}
			} else {
				callback(param);
			}
		}
	}
	// ---------------
	var _loadScript = function (src, counter, group, callback, param){
		script = document.createElement('script');
		script.type = 'text/javascript';
		script.async = false;
		script.onload = function(){
	        // remote script has loaded
			_loadCallback(counter, group, callback, param);
	    };
		script.src = src;
		document.getElementsByTagName('head')[0].appendChild(script);
	}
	// ---------------
	var _loadCallback = function(counter, group, callback, param){
		//skip loading codemirror
		if (loadCounter[group] < counter){
			loadCounter[group]++;
		} else if(loadCounter[group] == counter)  {
			loadCounter[group]++;
			callback(param);
		}
	};
	// ===== CODE MIRROR LOADER ===============================================
	// CodeMirror loader
	var Codemirror_loaded = false;
	var cmEditor = {};
	var appendCodemirrorAddonCss = function (basepath, list) {
		for(var prop in list) {
			if (list.hasOwnProperty(prop)){
				$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', basepath + prop + '/' + list[prop] + '.css') );
			}
		}
	}
	var loadCodemirror = function(target, callback, param){
		if (Codemirror_loaded==false){
			Codemirror_loaded = true;
			$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', '/js/libs/codemirror/lib/codemirror.css') );
			appendCodemirrorAddonCss('/js/libs/codemirror/addon/', {
			    'dialog': 'dialog', 'display' : 'fullscreen', 'scroll' : 'simplescrollbars', 'search' : 'matchesonscrollbar'
			});
			loadScripts([
				'/js/libs/codemirror/lib/codemirror.js',
				'/js/libs/codemirror/addon/mode/loadmode.js',
				'/js/libs/codemirror/addon/mode/multiplex.js',
				'/js/libs/codemirror/addon/mode/overlay.js',
				'/js/libs/codemirror/addon/mode/simple.js',
				
				'/js/libs/codemirror/mode/properties/properties.js',
				'/js/libs/codemirror/mode/doku/dokuwiki.js',
				'/js/libs/codemirror/mode/dokuwiki/dokuwiki.js',
				
				'/js/libs/codemirror/keymap/sublime.js',
				'/js/libs/codemirror/addon/dialog/dialog.js',
				'/js/libs/codemirror/addon/edit/closebrackets.js',
				'/js/libs/codemirror/addon/edit/matchbrackets.js',
				'/js/libs/codemirror/addon/display/fullscreen.js',
				'/js/libs/codemirror/addon/runmode/runmode.js',
				'/js/libs/codemirror/addon/search/search.js',
				'/js/libs/codemirror/addon/search/searchcursor.js',
				'/js/libs/codemirror/addon/search/search.js',
				'/js/libs/codemirror/addon/search/jump-to-line.js',
				'/js/libs/codemirror/addon/search/match-highlighter.js',
				'/js/libs/codemirror/addon/selection/active-line.js',
				'/js/libs/codemirror/addon/scroll/annotatescrollbar.js',
				'/js/libs/codemirror/addon/scroll/simplescrollbars.js',
				
			], _loadCodemirror, {t: target, c: callback, p: param});
		} else {
			 _loadCodemirror({t: target, c: callback, p: param});
		}
	}
	var _loadCodemirror = function (tcp){
		var $t = tcp.t;
		//append codemirror to targets
		$t.each(function(i, e){
			var textarea = this;
			var e_sid = 'e_' + textarea.dataset.sid;
			cmEditor = CodeMirror.fromTextArea(textarea, {
				mode: 'doku',
				lineNumbers: true,
				keyMap: 'sublime',
				extraKeys: {
					"F11": function(cm) {
						cm.setOption("fullScreen", !cm.getOption("fullScreen"));
					},
					"Esc": function(cm) {
						if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
					}
				}
			});
			cmEditor.on('change', function(){
				cmEditor.save();
			});
			
		});
		//callback
		var callback = tcp.c;
		var param = tcp.p;
		callback(param);
	}
	// ===== MEMBER FUNCTIONS ===============================================
	// ------------------------------------------------
	var func_deleteMember = function(){
		var $e = $(this).prev();
		$.modaltools({
			headerClass: 'bg-danger',
			text: 'Soll das Mitglied: <strong>"'+ $e[0].dataset.name+'"</strong> wirklich gelöscht werden? Alle verknüpften Protokolle werden gelöscht.', 
			single_callback: function(key, obj){
				if (key == 'ok'){
					var dataset = {
						mid: $e[0].dataset.id,
						committee: 'stura'
					};
					fchal = document.getElementById('fchal');
					dataset[fchal.getAttribute("name")] = fchal.value;
					
					$.ajax({
						type: "POST",
						url: '/invite/mdelete',
						data: dataset,
						success: function(data){
							pdata = parseData(data);
							if(pdata.success == true){
								var $p = $e.parent();
								$p.css({overflow: 'hidden'}).animate({ height: '0', padding: '0', opacity: 'toggle' }, 500, function(){
									$p.remove();
								});
								silmph__add_message(pdata.msg + ((typeof(pdata.timing) == 'number')? ' (In '+pdata.timing.toFixed(2)+' Sekunden)' : ''), MESSAGE_TYPE_SUCCESS, 3000);
							} else {
								silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
							}
						},
						error: postError
					});
				}
			}
		}).open();
	};
	// ------------------------------------------------
	var func_add_member_btn = function(){
		var $e = $(this).parent().prev().children('input');
		var val = $e.val().trim();
		
		var error = false;
		if (val.length != 0 && val.length < 3){
			silmph__add_message('Der Name muss mindestens 3 Zeichen lang sein.', MESSAGE_TYPE_WARNING, 5000);
			error = true;
		}
		formError($e, error);
		
		if(!error && val.length > 0){
			var dataset = {
				mname: val,
				committee: 'stura'
			};
			fchal = document.getElementById('fchal');
			dataset[fchal.getAttribute("name")] = fchal.value;
			
			$.ajax({
				type: "POST",
				url: '/invite/madd',
				data: dataset,
				success: function(data){
					pdata = parseData(data);
					formError($e, !pdata.success);
					if(pdata.success == true){
						//append new element
						var newli = $('<li/>', {
							'class':'member p-2 list-group-item',
							html: '<span class="membername"'+
									' data-id="'+pdata.newmember.id+
									'" data-name="'+pdata.newmember.name+
									'" data-management="0" data-protocol="0"></span>'
						});
						if ($e.closest('.silmph_memberbox').hasClass('editmember')){
							newli.append('<span class="delete btn btn-outline-danger"></span>');
							newli.children('.delete').on('click', func_deleteMember);
						}
						var $list = $e.closest('.silmph_memberbox').children('ul');
						$list.append(newli);
						//sort member list by name
						var sort_member = function(a, b){
							 return ($(b).children('.membername').data('name')) < ($(a).children('.membername').data('name')) ? 1 : -1;    
						}
						$list.children('li').sort(sort_member) // sort elements
						                  .appendTo($list); // append again to the list
						silmph__add_message(pdata.msg + ((typeof(pdata.timing) == 'number')? ' (In '+pdata.timing.toFixed(2)+' Sekunden)' : ''), MESSAGE_TYPE_SUCCESS, 3000);
						$e.val('');
						$e.focus();
					} else {
						silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
					}
				},
				error: postError
			});
		}
	};
	// ===== TOP FUNCTIONS  ===============================================
	// ------------------------------------------------
	function sortCallback(evt, ui) {
		var list = $('.silmph_top:not(.resort)').map(function() {
		    return $(this).data("tid");
		}).get();
		var dataset = {
			list: list,
			committee: 'stura'
		};
		fchal = document.getElementById('fchal');
		dataset[fchal.getAttribute("name")] = fchal.value;
		
		//do ajax post request
		$.ajax({
			type: "POST",
			url: '/invite/tsort',
			data: dataset,
			success: function(data){
				pdata = parseData(data);
				if(pdata.success == true){
					ui.item.effect("highlight", {}, 1000);
				} else {
					silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
				}
			},
			error: postError
		});
	}
	// ---------------------
	var handle_top = function ($e) {
		func_render_top_wikitext($e);
		$e.find('.remove').on('click', func_topremove);
		$e.find('.skipn').on('click', func_skiptop_on_next_proto);
		$e.find('.card-header').disableSelection();
		$e.parent().sortable('refresh');
		$e.find('.buttons .edit').on('click', func_top_to_edit);
	}
	// ---------------------
	var func_render_top_wikitext = function($e){
		var c = $e.children('.card-body');
		var t = c.children('.text');
		var text = ''+t.html();
		c.children('.text_rendered').html(text.wiki2html());
		t.hide();
	}
	// ------------------------------------------------
	var func_topremove = function(){
		var $e = $(this).closest('.silmph_top');
		$.modaltools({
			headerClass: 'bg-danger',
			text: 'Soll das Top: <strong>"'+ $e.children('.headline').children('span').eq(1).text()+'"</strong> wirklich gelöscht werden?', 
			single_callback: function(key, obj){
				if (key == 'ok'){
					var dataset = {
						tid: $e[0].dataset.tid,
						hash: $e[0].dataset.hash,
						committee: 'stura'
					};
					fchal = document.getElementById('fchal');
					dataset[fchal.getAttribute("name")] = fchal.value;
					//do ajax post request
					$.ajax({
						type: "POST",
						url: '/invite/tdelete',
						data: dataset,
						success: function(data){
							pdata = parseData(data);
							if(pdata.success == true){
								$e.animate({ height: 'toggle', opacity: 'toggle' }, 1400, function(){
									$e.remove();
								});
								silmph__add_message(pdata.msg + ((typeof(pdata.timing) == 'number')? ' (In '+pdata.timing.toFixed(2)+' Sekunden)' : ''), MESSAGE_TYPE_SUCCESS, 3000);
							} else {
								silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
							}
						},
						error: postError
					});	
				}
			}
		}).open();
	};
	// ------------------------------------------------
	var func_skiptop_on_next_proto = function(){
		var $e = $(this).closest('.silmph_top');
		var dataset = {
			tid: $e[0].dataset.tid,
			hash: $e[0].dataset.hash,
			committee: 'stura'
		};
		fchal = document.getElementById('fchal');
		dataset[fchal.getAttribute("name")] = fchal.value;
		
		//do ajax post request
		$.ajax({
			type: "POST",
			url: '/invite/tpause',
			data: dataset,
			success: function(data){
				pdata = {};
				pdata = parseData(data);
				if(pdata.success == true){
					if(pdata.skipnext && !$e.hasClass('skipnext')){
						$e.addClass('skipnext');
					} else if (!pdata.skipnext && $e.hasClass('skipnext')) {
						$e.removeClass('skipnext');
					}
					$('.silmph_toplist').sortable('refresh');
				} else {
					silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
				}
			},
			error: postError
		});
	};
	// ------------------------------------------------
	var func_top_showtoggle = function(){
		var $e = $(this).parent();
		if ($e.hasClass('showlist')) $e.removeClass('showlist');
		else $e.addClass('showlist');
	};
	// ------------------------------------------------
	var func_top_to_edit = function(){
		var $e = $(this).parent().parent();
		console.log($e[0].dataset.tid);
		createTEdit($e[0].dataset.tid);
	};
	// ===== TOP FUNCTIONS - CREATE|MODIFY ===============================================
	var createTEdit = function(id){
		var dataset_get = { committee: 'stura' };
		if (typeof(id)!='undefined' && id > 0){
			dataset_get['tid'] = id;
		}
		jQuery.get( '/invite/tedit' , dataset_get, function(data){
			$.modaltools({
				headerClass: 'bg-success',
				text: data, 
				ptag: false,
				headlineText: ((typeof(id)!='undefined' && id > 0)?'Neues Top erstellen':'Top Bearbeiten'),
				buttons: {'abort': 'Abbrechen', 'ok':'Erstellen'},
				callback: {'ok':function(obj){
					var dataset_put = {
						committee: 'stura',
						headline: obj.modal.find('#frmIpt01').val(),
						resort: obj.modal.find('#frmIpt02').val(),
						person: obj.modal.find('#frmIpt03').val(),
						duration: obj.modal.find('#frmIpt04').val(),
						goal: obj.modal.find('#frmIpt05').val(),
						guest: obj.modal.find('#frmIpt06')[0].checked,
						text: obj.modal.find('#frmIpt07').val()
					};
					if (typeof(id)!='undefined' && id > 0){
						dataset_put['tid'] = id;
					}
					fchal = document.getElementById('fchal');
					dataset_put[fchal.getAttribute("name")] = fchal.value;
					
					console.log(dataset_put);
					console.log('ok-button');
					obj.close();
				}, 'abort': function(obj){ obj.close(); }}
			}).open();
			// ----------------------------------
			//combobox top edit - resort list
			if (typeof($( ".combobox_resort" ).combobox) == 'function'){
				$( ".combobox_resort" ).combobox();
			}
			// ----------------------------------
			//multiselect
			
			// ----------------------------------
			//codemirror
			loadCodemirror($('.silmph_edit textarea.wikitext'), function(){}, null);
		});
	};
	// ===== DOCUMENT READY ===============================================
	$(document).ready(function(){
		// member func ----------
		$('.silmph_memberbox.editmember .delete').on('click', func_deleteMember);
		$('.silmph_memberbox.editmember .newmember_name').keypress(function(e){
			if(e.keyCode==13) $('.silmph_memberbox.editmember .newmemberbtn').click();
		});
		$('.silmph_memberbox.editmember .newmemberbtn').on('click', func_add_member_btn);
		$('.silmph_memberbox .showtoggle').on('click', func_top_showtoggle);
		// top func -------------
		$('.silmph_toplist').sortable({
			update: sortCallback,
			items : '.silmph_top:not(.resort)',
			handle: '.card-header',
			axis: 'y'
		});
		$('.silmph_top').each(function(i,e){
			$e = $(e);
			handle_top($e);
		});
		// ------------------------------------------------
		$('.silmph_tcreate_btn').on('click', function(){
			createTEdit(0);
		});
	});
})();