/**
 * JS SCRIPTS file
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        script
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			09.05.2018
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
	// ===== CODEMIRROR LOADER ===============================================
	
	// ===== FILE FUNCTIONS ===============================================
	// ------------------------------------------------
	var func_remove_file = function(){
		var $e = $(this);
		var key = $e.parent().children('a').attr('href').split('key=')[1];
		var dataset = {
			'key': key
		};
		$('.dz-dropupload input[type="hidden"]').each(function(i,e){
			dataset[e.name]=e.value;
		});		
		var fchal = document.getElementById('fchal');
		dataset[fchal.getAttribute("name")] = fchal.value;
		
		//do ajax post request
		$.ajax({
			type: "POST",
			url: GLOBAL_RELATIVE+'files/delete',
			data: dataset,
			success: function(data){
				pdata = {};
				pdata = parseData(data);
				if(pdata.success == true){
					//add/update top
					var $p = $e.parent();
					console.log($p);
					$p.css({overflow: 'hidden'}).animate({ height: '0', padding: '0', opacity: 'toggle' }, 500, function(){
						$p.remove();
					});
					silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
				} else {
					silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
				}
			},
			error: postError
		});
	}
	
	// ===== DOCUMENT READY ===============================================
	$(document).ready(function(){
		// ------------------------------------------------
		var $dropform = $('.dz-dropupload');
		Dropzone.autoDiscover = false;
		if ($dropform.length > 0){
			$dropform.addClass('dropzone');
			dropz = new Dropzone('.dz-dropupload',{
		        url: GLOBAL_RELATIVE+'files/npupload',
				paramName: "file", // The name that will be used to transfer the file
				maxFilesize: 40, // MB
				params: function(){
					var obj = {};
					var fchal = document.getElementById('fchal');
					obj[fchal.getAttribute("name")] = fchal.value;
					return obj;
				},
				uploadMultiple: true,
				createImageThumbnails: true,
				clickable:true,
				ignoreHiddenFiles: true,
				maxFiles: 20,
				parallelUploads: 1,
				acceptedFiles: 'image/*,application/pdf,application/gzip,.doc,.xls,.docx,.xlsx,.txt,.odt,.csv,.css,.rtx,.xml,.tsv,.conf,.c,.h,.cpp,.hpp,.mp3,.wma,.acc,.wmv,.mp4,.tar,.zip,.css,.gtar,.ustar,.README',
				autoProcessQueue: true,
				addRemoveLinks: true,
				hiddenInputContainer: 'body',
				forceFallack: false,
				thumbnailWidth: 85,
				thumbnailHeight: 85,
				dictDefaultMessage: 'Dateien zum Anhängen hier hinein ziehen.<i class="mt-2 fa fa-upload fa-4x d-block"></i>',
				dictRemoveFile: 'Entfernen',
		        error: function (file, response) {
		            
		        }
			});
			dropz.on("successmultiple", function(e,f) {
				pdata = {};
				try {
					pdata = JSON.parse(f);
				} catch(e) {
					console.log(f);
					pdata.success=false;
					pdata.eMsg = ('Unerwarteter Fehler. Seite wird neu geladen...');
					auto_page_reload(5000);
				}
				if(pdata.success == true && pdata.task == 'add'){
					$('.silmph_nofile').remove();
					var $ul = $('.silmph_file_list');
					var $li = $('<li/>', {
						'class': 'list-group-item silmph_file_line',
						html: '<a href="'+GLOBAL_RELATIVE+'files/get?key='+pdata.hash+'">'+
							pdata.name+'</a>'+
							'<small class="form-text text-muted">'+
								'<span class="d-inline-block ml-3"><strong>Added: </strong>'+pdata.added+'</span>'+
								'<span class="d-inline-block ml-3" style="min-width: 90px;"><strong>Size: </strong>'+pdata.size+'</span>'+
								'<span class="d-inline-block ml-3"><strong>Mime: </strong>'+pdata.mime+'</span>'+
							'</small>'+
							'<button class="btn btn-outline-danger remove" type="button"></button>'
					});
					$li.find('button.remove').on('click', func_remove_file);
					$ul.append($li);
					silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
				} else if (pdata.success != true) {
					silmph__add_message(pdata.eMsg , MESSAGE_TYPE_WARNING, 5000);
				}
			});
			dropz.on("error", function(e,f) {
				if (typeof f == "string" && (f.substring(0, 15) == "File is too big" || f == "You can't upload files of this type." || "Upload canceled.")){
					silmph__add_message(f , MESSAGE_TYPE_INFO, 5000);
				} else {
					try {
						pdata = (typeof(f) == 'string')? JSON.parse(f) : JSON.parse(f.responseText);
						silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						if (dropz != null) dropz.removeFile(e);
					} catch(e) {
						console.log(f);
						silmph__add_message('Unerwarteter Fehler. Seite wird neu geladen...', MESSAGE_TYPE_WARNING, 5000);
						auto_page_reload(5000);
					}
					
				}
			});
	    }
		//-------------------------------
		$('.silmph_file_line button.remove').on('click', func_remove_file);
	});
})();
