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
	// ===== HELPER FUNCTIONS -- Create Form element ===============================================
	// create formular elements 
	//
	// list parameters:
	// field (input) 'tag'
	// field (input) 'tagClose'
	// field (input) 'placeholder'
	// field (input) 'hidden'
	// field (input) overwrite 'defaultClass'
	// field (input) append 'class'
	// field (input) 'type'
	// field (input) other 'attr'ibutes
	// field (input) 'value'
	// field (input) data 'alias'
	// field (select) 'options',
	// 'labelClass'
	// 'label' text
	// 'small' text
	// 'smallClass'
	// overwrite 'inner' class
	// overwrite 'outer' class
	var func_create_form_elem = function (list, params){
		var s = {
			startIndex: 0,	
			outerClass:'form-row',
			innerClass:'col mt-3',
			fieldIdPrefix:'frmVal',
			fieldDefaultTag:'input',
			fieldDefaultTagClose:'>',
			fieldDefaultClass: 'form-control',
			fieldDefaultType: 'text',
			labelDefaultClass: 'col-md-4 control-label',
			smallDefaultClass: 'form-text text-muted help-block'
		};
		$.extend(s, params);
		var out = '';
		for (var i = 0; i < list.length; i++){
			out += '<div class="'+(list[i].hasOwnProperty('outer')?list[i].outer:s.outerClass)+'">'
					+ '<div class="'+(list[i].hasOwnProperty('inner')?list[i].outer:s.innerClass)+'">'
						+ '<label class="'
							+ (list[i].hasOwnProperty('labelClass')?list[i].labelClass:s.labelDefaultClass)
							+'" for="'+s.fieldIdPrefix +(''+i+s.startIndex).padEnd(2, '0')+'">'
							+ (list[i].hasOwnProperty('label')?list[i].label:'')
							+'</label>'
						+ '<'+(list[i].hasOwnProperty('tag')?list[i].tag:s.fieldDefaultTag)
							+' id="'+s.fieldIdPrefix +(''+i+s.startIndex).padEnd(2, '0')+'" '
								+(list[i].hasOwnProperty('placeholder')?' placeholder="'+list[i].placeholder+'"':'')
							+' class="'
								+ (list[i].hasOwnProperty('defaultClass')?list[i].defaultClass:s.fieldDefaultClass)
								+ (list[i].hasOwnProperty('class')?' '+list[i].class:'')
								+ (list[i].hasOwnProperty('hidden')&&(list[i]==true||list[i]==1)?' d-none':'')
							+'" type="'
								+ (list[i].hasOwnProperty('type')?list[i].type:s.fieldDefaultType)+'"'
							+ (list[i].hasOwnProperty('value')?' value="'+list[i].value+'"':'')
							+ ((list[i].hasOwnProperty('alias'))?
									' data-alias="'+list[i].alias+'"':'');
			if (list[i].hasOwnProperty('attr')){
				for (prop in list[i].attr){
					if (list[i].attr.hasOwnProperty(prop)){
						out += ' '+prop+'="'+list[i].attr[prop]+'"';
					}
				}
			}
			if (list[i].hasOwnProperty('options') && list[i].options != ''){
				out += '>';
				var fieldOptions = list[i].options;
				if (typeof(fieldOptions) == 'string'){
					out += fieldOptions;
				} else if (Array.isArray(fieldOptions)){
					for (var ii = 0; ii < fieldOptions.length; ii++){
						if (typeof(fieldOptions[ii]) == 'string'){
							out += fieldOptions[ii];
						} else {
							var tout = $('<'+fieldOptions[ii].type+'/>', fieldOptions[ii].attr);
							out += tout.prop('outerHTML');
						}
					}
				}
			}
			out +=	(list[i].hasOwnProperty('tagClose')?list[i].tagClose:s.fieldDefaultTagClose)
						+ ((list[i].hasOwnProperty('small'))?
							'<small class="'
								+ (list[i].hasOwnProperty('smallClass')?list[i].smallClass:s.smallDefaultClass)+'" >'
								+ list[i].small
							+'</small>':'')
					+ '</div>'
				+ '</div>';
		}
		return out;
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
			setTimeout(function(){
				callback(param);
			}, 600);
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
			$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', GLOBAL_RELATIVE+'js/libs/codemirror/lib/codemirror.css') );
			appendCodemirrorAddonCss(GLOBAL_RELATIVE+'js/libs/codemirror/addon/', {
			    'dialog': 'dialog', 'display' : 'fullscreen', 'scroll' : 'simplescrollbars', 'search' : 'matchesonscrollbar'
			});
			loadScripts([
				GLOBAL_RELATIVE+'js/libs/codemirror/lib/codemirror.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/mode/loadmode.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/mode/multiplex.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/mode/overlay.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/mode/simple.js',
				
				GLOBAL_RELATIVE+'js/libs/codemirror/mode/doku/dokuwiki.js',
				
				GLOBAL_RELATIVE+'js/libs/codemirror/keymap/sublime.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/dialog/dialog.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/display/panel.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/codemirror-buttons/buttons.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/edit/closebrackets.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/edit/matchbrackets.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/display/fullscreen.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/runmode/runmode.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/search/search.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/search/searchcursor.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/search/search.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/search/jump-to-line.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/search/match-highlighter.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/selection/active-line.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/scroll/annotatescrollbar.js',
				GLOBAL_RELATIVE+'js/libs/codemirror/addon/scroll/simplescrollbars.js',
				
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
				lineWrapping: true,
				keyMap: 'sublime',
				extraKeys: {
					"F11": function(cm) {
						cm.setOption("fullScreen", !cm.getOption("fullScreen"));
					},
					"Esc": function(cm) {
						if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
					}
				},
				buttons: [
		          {
		              hotkey: 'Ctrl-B',
		              class: 'bold btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-bold" title="Bold"></i>',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection('**' + selection + '**');
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 2);
		                  }
		              }
		          },
		          {
		              hotkey: 'Ctrl-I',
		              class: 'italic btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-italic" title="Kursiv"></i>',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection('//' + selection + '//');
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 2);
		                  }
		              }
		          },
		          {
		              hotkey: 'Ctrl-U',
		              class: 'underline btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-underline" title="Underline"></i>',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection('__' + selection + '__');
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 2);
		                  }
		              }
		          },
		          {
		              class: 'strike btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-strikethrough" title="Strikethrough"></i>',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection('<del>' + selection + '</del>');
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 6);
		                  }
		              }
		          },
		          {
		              class: 'hrline btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-window-minimize" title="Line"></i>',
		              callback: function (cm) {
		                  cm.replaceSelection("\n----\n" + cm.getSelection());
		              }
		          },
		          {
		              class: 'inline-code btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-code"  title="Code"></i>',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection("''" + selection + "''");
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 2);
		                  }
		              }
		          },
		          {
		              class: 'alink btn btn btn-outline-secondary mb-1 mr-3 p-1',
		              label: '<i class="fa fa-fw fa-link"  title="Code"></i>',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  var text = '';
		                  var link = '';
		
		                  if (selection.match(/^https?:\/\//)) {
		                      link = selection;
		                  } else {
		                      text = selection;
		                  }
		                  cm.replaceSelection('[[' + link + '|' + text + ']]');
		                  var cursorPos = cm.getCursor();
		                  if (!selection) {
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 3);
		                  } else if (link) {
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 2);
		                  } else {
		                      cm.setCursor(cursorPos.line, cursorPos.ch - (3 + text.length));
		                  }
		              }
		          },
		          {
		              class: 'ol btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-list-ol" title="List OL"></i>',
		              callback: function (cm) {
		            	  var cursorPos = cm.getCursor();
		            	  var line=  cursorPos.line
		            	  var cpos = cursorPos.ch;
		            	  var content = ''+cm.getLine(line);
		            	  content = content.trim();
		            	  var add = '';
		            	  if (content.length > 0 && content[0] == '-'){
		            		  cpos += 2;
		            		  add = '  ';
		            	  } else {
		            		  cpos += 4;
		            		  add = '  - ';
		            	  }
		            	  cm.setCursor(line, 0);
		            	  cm.replaceSelection(add + cm.getSelection() );
		            	  cm.setCursor(line, cpos);
		              }
		          },
		          {
		              class: 'ul btn btn btn-outline-secondary mb-1 mr-3 p-1',
		              label: '<i class="fa fa-fw fa-list-ul" title="List UL"></i>',
		              callback: function (cm) {
		            	  var cursorPos = cm.getCursor();
		            	  var line=  cursorPos.line
		            	  var cpos = cursorPos.ch;
		            	  var content = ''+cm.getLine(line);
		            	  content = content.trim();
		            	  var add = '';
		            	  if (content.length > 0 && content[0] == '*'){
		            		  cpos += 2;
		            		  add = '  ';
		            	  } else {
		            		  cpos += 4;
		            		  add = '  * ';
		            	  }
		            	  cm.setCursor(line, 0);
		            	  cm.replaceSelection(add + cm.getSelection() );
		            	  cm.setCursor(line, cpos);
		              }
		          },
		          {
		              class: 'beschlussStura btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-users" title="Stura Beschluss"></i>S',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection("{{template>:vorlagen:stimmen|Titel=|J=|N=|E=|S=angenommen oder abgelehnt}}" + selection);
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 39);
		                  }
		              }
		          },
		          {
		              class: 'beschlussSturaExtern btn btn btn-outline-secondary mb-1 mr-1 p-1',
		              label: '<i class="fa fa-fw fa-users" title="Stura Extern"></i>E',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection("{{template>:vorlagen:stimmen|Titel=Der StuRa beschließt eine Risikofinanzierung in Höhe von XXX EUR für das Projekt YYY entsprechend der Förderrichtlinie und der Kreditrichtlinie des Studierendenrates sowie dem vorliegenden Finanzplan. Davon werden ZZZ EUR als Vorkasse ausgezahlt.|J=|N=|E=|S=angenommen oder abgelehnt}}" + selection);
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 285);
		                  }
		              }
		          },
		          {
		              class: 'beschlussSturaIntern btn btn btn-outline-secondary mb-1 mr-0 p-1',
		              label: '<i class="fa fa-fw fa-users" title="Stura Intern"></i>I',
		              callback: function (cm) {
		                  var selection = cm.getSelection();
		                  cm.replaceSelection("{{template>:vorlagen:stimmen|Titel=Der StuRa beschließt ein Budget in Höhe von XXX EUR für das Projekt YYY.|J=|N=|E=|S=angenommen oder abgelehnt}}" + selection);
		                  if (!selection) {
		                      var cursorPos = cm.getCursor();
		                      cm.setCursor(cursorPos.line, cursorPos.ch - 111);
		                  }
		              }
		          },
		        ],
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
					var fchal = document.getElementById('fchal');
					dataset[fchal.getAttribute("name")] = fchal.value;
					
					$.ajax({
						type: "POST",
						url: GLOBAL_RELATIVE+'invite/mdelete',
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
		var $e = $(this).parent().prev().prev().children('input');
		var $ej = $(this).parent().prev().children('input');
		var val_name = $e.val().trim();
		var val_job = $ej.val().trim();
		
		var error = false;
		if (val_name.length != 0 && val_name.length < 3){
			silmph__add_message('Der Name muss mindestens 3 Zeichen lang sein.', MESSAGE_TYPE_WARNING, 5000);
			error = true;
		}
		formError($e, error);
		
		if(!error && val_name.length > 0){
			var dataset = {
				mname: val_name,
				mjob: val_job,
				committee: 'stura'
			};
			var fchal = document.getElementById('fchal');
			dataset[fchal.getAttribute("name")] = fchal.value;
			
			$.ajax({
				type: "POST",
				url: GLOBAL_RELATIVE+'invite/madd',
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
									'" data-job="'+(pdata.newmember.job!=''?'('+pdata.newmember.job+')':'')+
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
		var fchal = document.getElementById('fchal');
		dataset[fchal.getAttribute("name")] = fchal.value;
		
		//do ajax post request
		$.ajax({
			type: "POST",
			url: GLOBAL_RELATIVE+'invite/tsort',
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
					var fchal = document.getElementById('fchal');
					dataset[fchal.getAttribute("name")] = fchal.value;
					//do ajax post request
					$.ajax({
						type: "POST",
						url: GLOBAL_RELATIVE+'invite/tdelete',
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
		var fchal = document.getElementById('fchal');
		dataset[fchal.getAttribute("name")] = fchal.value;
		
		//do ajax post request
		$.ajax({
			type: "POST",
			url: GLOBAL_RELATIVE+'invite/tpause',
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
		var $b = $(this);
		if ($e.hasClass('showlist')){
			$e.removeClass('showlist');
		}
		else {
			$e.addClass('showlist');
		}
		var text = $b.text();
		$b.text($b.data('texttoggle'));
		$b.data('texttoggle', text);
	};
	// ------------------------------------------------
	var func_top_to_edit = function(){
		var $e = $(this).parent().parent();
		createTEdit($e[0].dataset.tid);
	};
	// ===== HELPER - MULTISELECT ===============================================
	var func_multiselect_close = function(event) {
		var $e = $('.silmph_multicheckbox');
		if(event == true || (!$(event.target).closest('.silmph_goal_inp').length
				&& !$(event.target).closest('.silmph_multicheckbox').length)) {
			if (!$e.hasClass('hiderow')){
				$e.addClass('hiderow');
				$(document).off('click', func_multiselect_close);
				$(document).off('keypress', func_multiselect_key);
			}
	    }
	};
	// ---------------------
	var func_multiselect_key = function (evt) {
		var labels = $('.silmph_multicheckbox:not(.hiderow) label');
		if (evt.keyCode==40 || evt.keyCode == 9 || evt.keyCode==38){ //arrow down || tab  || array up 
			if (labels.length > 0){
				var focus_next = false;
				var overflow = false;
				var j = 0;
				$('#frmIpt05')
				for (var i = 0; i < labels.length + 1; i++){
					if (evt.keyCode==38) {
						j = (labels.length - i) % labels.length;
						if (j < 0) j = -j;
					} else {
						j = i % labels.length;
					}
					if (i == labels.length && evt.keyCode == 9 && focus_next == true) {
						overflow = true;
						focus_next = false;
					} else if (i == labels.length){
						focus_next = true;
					}
					if (focus_next){
						labels.eq(j).focus();
						labels[j].focus();
						labels[j].blur();
						if (!labels.eq(j).hasClass("focus")) labels.eq(j).addClass("focus");
						break;
					}
					if (labels.eq(j).is(":focus") || labels.eq(j).hasClass("focus")){
						if (labels.eq(j).hasClass("focus")) labels.eq(j).removeClass("focus");
						focus_next = true;
					}
				}
				if (! overflow ){
					evt.stopPropagation();
					evt.preventDefault();
				} else {
					func_multiselect_close(true);
				}
			}
		} else if (evt.keyCode==13){ //enter
			for (var i = 0; i < labels.length; i++){
				if (labels.eq(i).is(":focus") || labels.eq(i).hasClass("focus")){
					labels.eq(i).click();
					break;
				}
			}
			evt.stopPropagation();
			evt.preventDefault();
		}
	};	
	// ===== TOP FUNCTIONS - CREATE|MODIFY ===============================================
	var func_top_create_update = function(top) {
		var box = $('<div/>',{
			'class': 'card border-secondary silmph_top'+((top.skip_next > 0)?' skipnext':'')+((top.guest > 0)?' guest':'')+((top.intern > 0)?' internal':'')+((top.resort != null && top.resort.id > 0)?' resort':''),
			'data-tid': top.id,
			'data-hash': top.hash,
		});
		var head = $('<div/>',{
			'class': 'card-header headline',
			'data-resort': (top.resort != null && top.resort.id > 0)?top.resort.id:'',
			'data-level': top.level,
			'data-headline': JSON.stringify([top.headline]),
			html: ((!(top.resort != null && top.resort.id > 0))? 
					'<span class="top_counter"></span>' : 
					'<span class="top_resort">'+top.resort.type+' '+top.resort.name+'</span>')
				+ '<span>'+top.headline+'</span>'
		});
		box.append(head);
		var info = $('<div/>', {
			'class': 'topinfo text-secondary',
			html: '<span class="added">'+top.addedOn+'</span>'
				+ '<span class="duration">'+top.expected_duration+' min.</span>'
				+ '<span class="person">'+top.person+'</span>'
				+ '<span class="goal">'+top.goal+'</span>'
				+ '<span class="filecount"><a href="'+GLOBAL_RELATIVE+'files/npuploader?committee='+top.gname+'&tid='+top.id+'&gui=1&hash='+top.hash+'">'+top.filecounter+'</a></span>'
				+ '<span class="guest">Gast</span>'
				+ '<span class="internal">Intern</span>'
				+ '<span class="skipn">Auf nächste Woche verschoben</span>'	
		});
		box.append(info);		
		var body = $('<div/>', {
			'class': 'card-body',
			html: '<div class="text">'+top.text+'</div>'
				+ '<input class="d-none" id="texttoggle_top_cb_'+top.id+'" type="checkbox" checked="checked">'
				+ '<div class="text_rendered"></div>'
				+ '<label class="texttoggle btn btn-outline-secondary" for="texttoggle_top_cb_'+top.id+'"></label>'
		});
		box.append(body);
		var buttons = $('<div/>', {
			'class': 'buttons',
			html: '<div class="edit btn btn-outline-secondary" title="Bearbeiten"></div><div class="remove btn btn-outline-danger" title="Löschen"></div><div class="skipn btn btn-outline-secondary" title="Auf nächste Woche verschieben"></div>'
		});
		box.append(buttons);
		// -------------
		if (top.isNew){
			before = false;
			if ($('.silmph_toplist .silmph_top.resort').length > 0){
				before = $('.silmph_toplist .silmph_top.resort').eq(0);
			} else if ($('.silmph_toplist .silmph_top.skipnext').length > 0){
				before = $('.silmph_toplist .silmph_top.skipnext').eq(0);
			}
			if (before != false){
				box.insertBefore(before);
			} else {
				$('.silmph_toplist').append(box);
			}
		} else {		
			$('.silmph_toplist .silmph_top[data-tid="'+top.id+'"]').replaceWith(box);
		}
		handle_top(box);
	};
	// ------------------------------------------------
	var createTEdit = function(id){
		var dataset_get = { committee: 'stura' };
		if (typeof(id)!='undefined' && id > 0){
			dataset_get['tid'] = id;
		}
		jQuery.get( GLOBAL_RELATIVE+'invite/tedit' , dataset_get, function(data){
			$.modaltools({
				headerClass: 'bg-success',
				text: data, 
				ptag: false,
				headlineText: ((typeof(id)!='undefined' && id > 0)?'Top Bearbeiten':'Neues Top erstellen'),
				buttons: {'abort': 'Abbrechen', 'ok':((typeof(id)!='undefined' && id > 0)?'Übernehmen':'Erstellen')},
				callback: {'ok':function(obj){
					var dataset_put = {
						committee: 'stura',
						headline: obj.modal.find('#frmIpt01').val(),
						resort: obj.modal.find('#frmIpt02').val(),
						person: obj.modal.find('#frmIpt03').val(),
						duration: obj.modal.find('#frmIpt04').val(),
						goal: obj.modal.find('#frmIpt05').val(),
						guest: obj.modal.find('#frmIpt06')[0].checked? '1': '0',
						intern: obj.modal.find('#frmIpt08')[0].checked? '1': '0',
						text: obj.modal.find('#frmIpt07').val(),
						hash: (typeof(id)!='undefined' && id > 0)? obj.modal.find('.silmph_edit')[0].dataset.hash : ''
					};
					if (typeof(id)!='undefined' && id > 0){
						dataset_put['tid'] = id;
					} else {
						dataset_put['tid'] = 0;
					}
					var fchal = document.getElementById('fchal');
					dataset_put[fchal.getAttribute("name")] = fchal.value;
					
					//do ajax post request
					$.ajax({
						type: "POST",
						url: GLOBAL_RELATIVE+'invite/tupdate',
						data: dataset_put,
						success: function(data){
							pdata = {};
							pdata = parseData(data);
							if(pdata.success == true){
								//add/update top
								func_top_create_update(pdata.top);
								$('.silmph_toplist').sortable('refresh');
								silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
								obj.close();
							} else {
								silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
							}
						},
						error: postError
					});
					
				}, 'abort': function(obj){ obj.close(); }}
			}).open();
			// ----------------------------------
			//combobox top edit - resort list
			if (typeof($( ".combobox_resort" ).combobox) == 'function'){
				$( ".combobox_resort" ).combobox();
			}
			// ----------------------------------
			//multiselect
			$('.silmph_edit .silmph_goal_inp').find('input').on('focus', function(){
				var $inp = $(this);
				var $e = $inp.closest('.silmph_goal_inp').next();
				if ($e.hasClass('hiderow')){
					$e.removeClass('hiderow');
					$(document).on('click', func_multiselect_close);
					$(document).on('keypress', func_multiselect_key);
				}
			});
			$('.silmph_edit .silmph_goal_inp').find('input').on('change', function(){
				var $inp = $(this);				
				var vals = $inp.val().split(/ *, */g);
				$('.silmph_edit .silmph_multicheckbox input + label').each(function(i, e){
					var inp = $(e).prev()[0];
					if (vals.indexOf(e.innerHTML) >= 0){
						if(!inp.checked) inp.checked = true;
					} else {
						if(inp.checked) inp.checked = false;
					}
				});
			});
			$('.silmph_edit .silmph_multicheckbox input').on('change', function(){
				var out_str = '';
				$('.silmph_edit .silmph_multicheckbox input:checked + label').each(function(i, e){
					if (out_str != '') out_str+= ', ';
					out_str += e.innerHTML;
				});
				$('.silmph_edit .silmph_multicheckbox').prev().find('input').val(out_str);
			});
			// ----------------------------------
			//codemirror
			loadCodemirror($('.silmph_edit textarea.wikitext'), function(){}, null);
			// ----------------------------------
			// autocomplete
			var availableTags = [];
			$('.silmph_memberbox .membername').each(function(i, e){
				availableTags.push({value: e.dataset.name, label: e.dataset.name+' '+e.dataset.job});
			});
			$('input#frmIpt03').autocomplete({
				source: availableTags,
				classes: {
					"ui-autocomplete": "highlight silmph_npautoc list-group",
					"ui-menu-item": "ui-menu-item"
				}
			});
		});
	};
	// ===== NEW PROTOCOL ===============================================
	var func_np_create_listelem = function (data){
		var out = $('<div/>', {
			'class': 'nprotoelm row p-2',
			'data-id': data.id,
			'data-hash': data.hash,
			'data-m': data.m,
			'data-p': data.p,
			'html': '<div class="col-3">'+data.date+'</div>'+
					'<div class="col-3">'+data.stateLong+'</div>'+
					'<div class="col-6">'+
					((data.state < 2)?
						' <button class="infoedit btn btn-outline-secondary" title="Info | Bearbeiten" type="button"><i class="fa fa-fw fa-info"></i></button>'+
						' <button class="send'+ ((data.inviteMailDone)? ' resend':'')+' btn btn-outline-secondary" title="Einladung'+ ((data.inviteMailDone)? ' erneut':'')+' versenden" type="button"><i class="fa fa-fw fa-envelope"></i></button>'+
						' <button class="createp btn btn-outline-secondary" title="Protokoll erstellen" type="button">'+
							'<span class="fa-stack2 fa-fw"><i class="fa fa-wikipedia-w fa-stack-1x"></i><i class="fa fa-pencil fa-stack-1x text-success"></i></span>'+
						'</button>'+
						' <button class="cancel btn btn-outline-secondary" title="Planung entfernen" type="button"><i class="fa fa-fw fa-times"></i></button>'
					:
						' <button class="restore btn btn-outline-secondary" data-href="'+data.generatedUrl+'" title="Zum Protokoll" type="button"><i class="fa fa-fw fa-link"></i></button>'+
						((!data.disableRestore)?' <button class="restore btn btn-outline-secondary" title="Wiederherstellen" type="button"><i class="fa fa-fw fa-refresh"></i></button>':'')
					)+
					'</div>'
		});
		return out;
	}
	// delete newproto entry
	var func_newproto_delete = function (){
		var $e = $(this).closest('.nprotoelm');
		$.modaltools({
			headerClass: 'bg-danger',
			text: 'Soll die Sitzung(splanung) am <strong>'+$e.children('div').eq(0).text()+'</strong> wirklich gelöscht werden?', 
			ptag: false,	
			headlineText: 'Sitzung löschen',
			buttons: {'abort': 'Abbrechen', 'ok': 'Protokoll löschen'},
			callback: {'ok':function(obj){
				var dataset = {
					committee: 'stura',
					hash: 		$e[0].dataset.hash,
					npid: 		$e[0].dataset.id,
				};
				var fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				
				//do ajax post request
				$.ajax({
					type: "POST",
					url: GLOBAL_RELATIVE+'invite/npdelete',
					data: dataset,
					success: function(data){
						pdata = {};
						pdata = parseData(data);
						if(pdata.success == true){
							//delete newprotocol
							silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
							$e.css({overflow: 'hidden'}).animate({ height: '0', padding: '0', opacity: 'toggle' }, 500, function(){
								$e.remove();
							});
							obj.close();
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: postError
				});
			}, 'abort': function(obj){ obj.close(); }}
		}).open();
	}
	// delete newproto entry
	var func_newproto_restore = function (){
		var $e = $(this).closest('.nprotoelm');
		$.modaltools({
			headerClass: 'bg-warning',
			text: 'Soll die Sitzung(splanung) am <strong>'+$e.children('div').eq(0).text()+'</strong> wiederhergestellt?', 
			ptag: false,	
			headlineText: 'Wiederherstellen',
			buttons: {'abort': 'Abbrechen', 'ok': 'Wiederherstellen'},
			callback: {'ok':function(obj){
				var dataset = {
					committee: 'stura',
					hash: 		$e[0].dataset.hash,
					npid: 		$e[0].dataset.id,
				};
				var fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				
				//do ajax post request
				$.ajax({
					type: "POST",
					url: GLOBAL_RELATIVE+'invite/nprestore',
					data: dataset,
					success: function(data){
						pdata = {};
						pdata = parseData(data);
						if(pdata.success == true){
							//delete newprotocol
							silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
							obj.close();
							auto_page_reload(3000);
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: postError
				});
			}, 'abort': function(obj){ obj.close(); }}
		}).open();
	}
	// send invitations for newproto entry
	var func_newproto_invite = function (){
		var $e = $(this).closest('.nprotoelm');
		$.modaltools({
			headerClass: 'bg-warning',
			text: '<p><strong>Soll die Sitzungeinladung manuell versendet werden?</strong></p><p>Eine <strong>automatische Einladung</strong> erfolgt ca. 24 Stunden vor entsprechender Sitzung.</p><p>Nach einer Einladung benötigen neue Tops einen Beschluss, um noch auf der Sitzung vorgebracht zu werden.</p><div class="form-group"><label for="comment">Zusätzliche Nachricht:</label><textarea class="form-control" rows="5" id="comment"></textarea></div>', 
			ptag: false,	
			headlineText: 'Sitzungseinladung versenden',
			buttons: {'abort': 'Abbrechen', 'ok': 'Einladung versenden.'},
			callback: {'ok':function(obj){
				var dataset = {
					committee: 'stura',
					hash: 		$e[0].dataset.hash,
					npid: 		$e[0].dataset.id,
					text: 		obj.modal.find('textarea#comment').val(),
				};
				var fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				
				//do ajax post request
				$.ajax({
					type: "POST",
					url: GLOBAL_RELATIVE+'invite/npinvite',
					data: dataset,
					success: function(data){
						pdata = {};
						pdata = parseData(data);
						if(pdata.success == true){
							//delete newprotocol
							silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
							$e.children('div').eq(1).text('Eingeladen');
							obj.close();
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: postError
				});
			}, 'abort': function(obj){ obj.close(); }}
		}).open();
	}
	// write newproto to wiki
	var func_newproto_towiki = function (ee, fdata, dataset_){
		//setup variables , get information -------------------------------
		var $e = $(ee).closest('.nprotoelm');
		var proposal = func_newproto_proposal();
		var id = $e[0].dataset.id;
		var dset = (typeof(dataset_)=='object')? true: false;
		
		var old = $('.silmph_nprotolist .nprotoelm[data-id="'+id+'"]');
		var members = {}; 
		$('.silmph_memberbox li.member span.membername').each(function(i, e){
			members['id'+e.dataset.id] = {id: e.dataset.id, name: e.dataset.name}
		});
		if (old.length > 0){
			proposal['npid'] = old[0].dataset.id,
			proposal['hash'] = old[0].dataset.hash,
			proposal['date'] = $.format.date(stringToDate(old.children('div').eq(0).text().split(' ')[0]), 'yyyy-MM-dd'),
			proposal['time'] = (old.children('div').eq(0).text().split(' '))[1],
			proposal['mana'] = members.hasOwnProperty('id'+old[0].dataset.m)? members['id'+old[0].dataset.m].name: '',
			proposal['prot'] = members.hasOwnProperty('id'+old[0].dataset.p)? members['id'+old[0].dataset.p].name: '';
		}
		// build modal content ---------------------------------------------
		var ttext = (typeof(fdata) == 'undefined' || fdata==false || fdata==null || (typeof(fdata)=='object' && (!fdata.hasOwnProperty('reask') || fdata.reask == false)))? $('<div/>', {
			html: '<h4><strong>Das Sitzungsprotokoll wird nun im Wiki erzeugt.</strong></h4>'
		}) : $('<p/>', {html: '<strong>'+fdata.msg+'</strong>'});
		// append to modal content
		ttext
		.append('<span><strong>'+$e.children('div').eq(0).text()+'</strong></span>')
		.append(func_create_form_elem([
		    {alias: 'legi', placeholder: 'Legislatur', label:'Legislaturperiode', type:'number', attr:{min:0, step:1}, value: (dset)? dataset_.legislatur: $('.silmph_nprotolist').data('legislatur')},
		    {alias: 'nthp', placeholder: 'Sitzung', label:'Sitzungsnummer', type:'number', attr:{min:0, step:1}, value: (dset)? dataset_.nthproto: $('.silmph_nprotolist').data('nthproto')},
		    {alias: 'mana', placeholder: (proposal.management.name!=''?'Vorschlag: '+proposal.management.name:''), label:'Wer leitet die Sitzung?', value: (dset)? dataset_.management : ( (proposal.hasOwnProperty('mana')?proposal.mana:''))},
			{alias: 'prot', placeholder: (proposal.protocol.name!=''?'Vorschlag: '+proposal.protocol.name:''), label:'Wer protokolliert?', value: (dset)? dataset_.protocol : ( (proposal.hasOwnProperty('prot')?proposal.prot:''))}  
		], {fieldIdPrefix: 'frmM2WVal'}))
		.append((function(){ // Anwesenheit -----------------------
			var out = [];
			out.push($('<p/>'));
			out.push($('<h4/>', {html: '<strong>Anwesenheit</strong>'}));
			var memberboxbody = $('<div/>', {'class': 'card-body'});
			$('.silmph_memberbox .member .membername').each(function (i, elm){
				var chk = (dset && dataset_.member.hasOwnProperty(''+elm.dataset.id))? dataset_.member[(''+elm.dataset.id)] : 0;
				var radioOptions = ['Fixme', 'J', 'E', 'N'];
				var opts = [];
				for (var ci = 0; ci < radioOptions.length; ci++){
					var at = { value: ''+ci, id: 'frmMember_'+i+'_radioid_'+ci, type: 'radio', 'class': 'col-2 col-sm-1',  name: 'frmMember_'+i+'_radio'};
					if (chk == ci) at['checked'] = 'checked';
					opts.push({type:'input', attr: at });
					opts.push({type:'label', attr: { 'for': 'frmMember_'+i+'_radioid_'+ci, 'class': 'col-10 col-sm-5 col-md-2', text: radioOptions[ci] }},);
				}
				memberboxbody.append( func_create_form_elem( 
						[{label: elm.dataset.name, 
							type: '', tag:'fieldset', 
							tagClose: '</fieldset>',
							'class': 'member_to_wiki',
							attr: {'data-id': elm.dataset.id,'data-pos': i}, 
							options: opts
						}],
						{fieldIdPrefix: 'frmMember_'+i+'_', innerClass: 'form-row mt-3 w-100 hover-bg-gray', labelDefaultClass: 'col-md-4 control-label mt-1', fieldDefaultClass: 'form-control col-md-8 onelineRadios hover-bg-transparent'}) );
			});
			var memberbox = $('<div/>', {'class': 'card'});
			memberbox.append(memberboxbody);
			out.push(memberbox);
			return out;
		})())
		.append((function(){
			var out = [];
			out.push($('<p/>'));
			out.push($('<h4/>', {html: '<strong>Tagesordnung eingereichter Tops</strong>'}));
			var ul = $('<ul/>');
			ul.append($('<li/>', { html: '(Protokollkontrolle)' }));
			$('.silmph_toplist .silmph_top:not(.skipnext):not(.resort) .headline span:last-child').each(function(i, e){
				ul.append($('<li/>', { html: 'Top '+(i+1)+': '+ e.innerHTML }));
			});
			var refli = $('<li/>', { html: 'Berichte aus Referaten, AGs und von Angestellten' });
			if ($('.silmph_toplist .silmph_top.resort:not(.skipnext) .headline span:last-child').length > 0){
				var ul2 = $('<ul/>');
				$('.silmph_toplist .silmph_top.resort:not(.skipnext) .headline span:last-child').each(function(i,e){
					var tmp_e = $(e).prev();
					ul2.append($('<li/>', { html: tmp_e.text()+': '+ e.innerHTML }));
				});
				refli.append(ul2);
			}
			ul.append(refli);
			ul.append($('<li/>', { html: 'Sonstiges' }));
			out.push(ul);
			return out;
		})());
		$.modaltools({
			headerClass: 'bg-warning',
			text: ttext, 
			ptag: false,	
			headlineText: 'Sitzungsprotokoll erzeugen',
			buttons: {'abort': 'Abbrechen', 'ok': ((typeof(fdata) != 'undefined' && fdata != false && fdata != null && typeof(fdata) == 'object' && fdata.hasOwnProperty('reask') && fdata.reask == true)? 'Fortfahren': 'Protokoll schreiben')},
			callback: {'ok':function(obj){
				var dataset = {
					committee: 'stura',
					hash: 		$e[0].dataset.hash,
					npid: 		$e[0].dataset.id,
					management: obj.modal.find('[id^=frmM2WVal][data-alias=mana]').val(),
					protocol: 	obj.modal.find('[id^=frmM2WVal][data-alias=prot]').val(),
					legislatur: obj.modal.find('[id^=frmM2WVal][data-alias=legi]').val(),
					nthproto: 	obj.modal.find('[id^=frmM2WVal][data-alias=nthp]').val(),
					reaskdone:  (typeof(fdata) != 'undefined' && fdata != false && fdata != null && typeof(fdata) == 'object' && fdata.hasOwnProperty('reask') && fdata.reask == true)? 1: 0,
					member: {}
				};
				obj.modal.find('fieldset.member_to_wiki').each(function(fi, fe){
					dataset.member[''+fe.dataset.id] = $(fe).children('input:checked').val();
				});
				var fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				
				console.log(dataset);//TODO
				var modal = $.modaltools({
					text: '<strong>Anfrage wird verarbeitet. Bitte warten.</strong></p><p><div class="multifa center"><span class="fa fa-cog sym-spin"></span><span class="fa fa-cog sym-spin-reverse"></span></div>', 
					buttons: {}
				}).open();
				//do ajax post request
				$.ajax({
					type: "POST",
					url: GLOBAL_RELATIVE+'invite/nptowiki',
					data: dataset,
					success: function(data){
						modal.close();
						pdata = {};
						pdata = parseData(data);
						console.log(pdata);
						if(pdata.success == true){
							if (pdata.hasOwnProperty('reask') && pdata.reask == true){
								obj.close(function(){
									func_newproto_towiki(ee, pdata, dataset);
								});
							} else {
								silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
								obj.close();
								auto_page_reload(3000);
							}
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: function (data){ modal.close(); postError(data); }
				});
			}, 'abort': function(obj){ obj.close(); }}
		}).open();
	}
	// add btn events to newproto elements
	var func_newproto_add_events = function ($e){
		// info / edit button
		$e.find('.infoedit').on('click', function(){
			func_newproto_editadd_modal($e[0].dataset.id);
		});
		// delete proto
		$e.find('.cancel').on('click', func_newproto_delete);
		// send/resend invitation
		$e.find('.send').on('click', func_newproto_invite);
		// write to wiki
		$e.find('.createp').on('click', function () {
			func_newproto_towiki(this, false);
		});
		// link to wiki
		// restore TODO js + php
		$e.find('.restore').on('click', func_newproto_restore);
	}
	// create /or update newProto list entry
	var func_np_create_update = function (data){
		var newLine = func_np_create_listelem(data);
		// add events
		func_newproto_add_events(newLine);
		
		if (data.isNew){//replace
			var old = $('.silmph_nprotolist .nprotoelm[data-id="'+data.id+'"]');
			old.replaceWith(newLine);
		} else { //prepend new
			$('.silmph_nprotolist .npbody').prepend(newLine);
		}
	}
	// get new protocol proposal for management person and protocol person
	var func_newproto_proposal = function () {
		var d = new Date();
		d.setDate(d.getDate() + (3 + 7 - d.getDay()) % 7);
		
		var out = {
			legislatur: $('.silmph_nprotolist')[0].dataset.legislatur,
			time: $('.silmph_nprotolist')[0].dataset.meetinghour+':00',
			date: $.format.date(d, 'yyyy-MM-dd'),
			management: {counter: -1, name: '', id: 0},
			protocol: {counter: -1, name: '', id: 0}
		};
		$('.silmph_memberbox li.member span.membername').each(function(i, e){
			if (out.management.counter < 0 || out.management.counter > e.dataset.management ){
				out.management.counter = e.dataset.management;
				out.management.name = e.dataset.name;
			} else if (out.protocol.counter < 0 || out.protocol.counter > e.dataset.protocol ){
				out.protocol.counter = e.dataset.protocol;
				out.protocol.name = e.dataset.name;
			}
		});
		return out;
	};
	// create info/edit/create newproto modal content
	var func_newproto_getbox = function (proposal) {
		var out = $('<div/>', {
			'class': 'silmph_createnewproto card',
			'data-npid': (proposal.hasOwnProperty('npid')?proposal.npid:0),
			'data-hash': (proposal.hasOwnProperty('hash')?proposal.hash:''),
			html: '<div class="card-body">'
				+ func_create_form_elem([
				   {alias: 'date', placeholder: 'Datum', label:'Datum der Sitzung', type:'date', value: proposal.date},
				   {alias: 'time', placeholder: 'Uhrzeit', label:'Uhrzeit der Uhrzeit', type:'time', value: proposal.time},
				   {alias: 'mana', placeholder: (proposal.management.name!=''?'Vorschlag: '+proposal.management.name:''), label:'Wer leitet die Sitzung?', value: (proposal.hasOwnProperty('mana')?proposal.mana:'')},
				   {alias: 'prot', placeholder: (proposal.protocol.name!=''?'Vorschlag: '+proposal.protocol.name:''), label:'Wer protokolliert?', value: (proposal.hasOwnProperty('prot')?proposal.prot:'')}
				  ], {fieldIdPrefix: 'frmNpVal'})
				+ '</div>'
		});
		var availableTags = [];
		$('.silmph_memberbox .membername').each(function(i, e){
			availableTags.push({value: e.dataset.name, label: e.dataset.name+' '+e.dataset.job});
		});
		out.find('[id^=frmNpVal][data-alias=mana]').autocomplete({
			source: availableTags,
			classes: {
				"ui-autocomplete": "highlight silmph_npautoc list-group",
				"ui-menu-item": "ui-menu-item"
			}
		});
		out.find('[id^=frmNpVal][data-alias=prot]').autocomplete({
			source: availableTags,
			classes: {
				"ui-autocomplete": "highlight silmph_npautoc list-group",
				"ui-menu-item": "ui-menu-item"
			}
		});
		return out;
	};
	// open info/edit/create newproto modal + handle events
	var func_newproto_editadd_modal = function(id) {
		var proposal = func_newproto_proposal();
		if (typeof(id) != 'undefined' && id > 0){
			var old = $('.silmph_nprotolist .nprotoelm[data-id="'+id+'"]');
			var members = {}; 
			$('.silmph_memberbox li.member span.membername').each(function(i, e){
				members['id'+e.dataset.id] = {id: e.dataset.id, name: e.dataset.name}
			});
			if (old.length > 0){
				proposal['npid'] = old[0].dataset.id,
				proposal['hash'] = old[0].dataset.hash,
				proposal['date'] = $.format.date(stringToDate(old.children('div').eq(0).text().split(' ')[0]), 'yyyy-MM-dd'),
				proposal['time'] = (old.children('div').eq(0).text().split(' '))[1],
				proposal['mana'] = members.hasOwnProperty('id'+old[0].dataset.m)? members['id'+old[0].dataset.m].name: '',
				proposal['prot'] = members.hasOwnProperty('id'+old[0].dataset.p)? members['id'+old[0].dataset.p].name: '';
			}
			console.log(proposal);
		}
		$.modaltools({
			headerClass: 'bg-success',
			text: func_newproto_getbox(proposal), 
			ptag: false,	
			headlineText: 'Neue Sitzung planen',
			buttons: {'abort': 'Abbrechen', 'ok':(typeof(id) != 'undefined' && id > 0?'Protokoll aktualisieren':'Protokoll Anlegen')},
			callback: {'ok':function(obj){
				var dateNew = $.format.date(stringToDate(obj.modal.find('[id^=frmNpVal][data-alias=date]').val()), 'yyyy-MM-dd');
				if (dateNew.substr(0,3) == 'NaN'){
					silmph__add_message('Datum im Format dd.mm.yyyy angeben.', MESSAGE_TYPE_WARNING, 5000);
					return false;
				}
				var dataset = {
					committee: 'stura',
					date: 		$.format.date(stringToDate(obj.modal.find('[id^=frmNpVal][data-alias=date]').val()), 'yyyy-MM-dd'),
					time: 		obj.modal.find('[id^=frmNpVal][data-alias=time]').val(),
					management: obj.modal.find('[id^=frmNpVal][data-alias=mana]').val(),
					protocol: 	obj.modal.find('[id^=frmNpVal][data-alias=prot]').val(),
					hash: 		obj.modal.find('.silmph_createnewproto')[0].dataset.hash,
					npid: 		obj.modal.find('.silmph_createnewproto')[0].dataset.npid,
				};
				var fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				
				//do ajax post request
				$.ajax({
					type: "POST",
					url: GLOBAL_RELATIVE+'invite/npupdate',
					data: dataset,
					success: function(data){
						pdata = {};
						pdata = parseData(data);
						if(pdata.success == true){
							//add/update top
							silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
							func_np_create_update(pdata.np);
							obj.close();
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: postError
				});
				
			}, 'abort': function(obj){ obj.close(); }}
		}).open();
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
			items : '.silmph_top:not(.skipnext)',
			handle: '.card-header',
			axis: 'y'
		});
		$('.silmph_top').each(function(i,e){
			$e = $(e);
			handle_top($e);
		});
		// newproto -------------
		$('.silmph_create_np').on('click', function () { 
			func_newproto_editadd_modal(0);
		});
		$('.silmph_nprotolist .nprotoelm').each(function (i, e) { 
			func_newproto_add_events($(e));
		});
		// ------------------------------------------------
		$('.silmph_tcreate_btn').on('click', function(){
			createTEdit(0);
		});
	});
})();
