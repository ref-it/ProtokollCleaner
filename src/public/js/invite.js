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
	// ------------------------------------------------
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
	// ------------------------------------------------
	if (typeof(String.prototype.renderWiki != 'function')){
		(function() {
			// make sure we trim BOM and NBSP too
			var rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
			String.prototype.trim = function (){
				return this.replace(rtrim, '');
			};
		})();
	}
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
	$(document).ready(function(){
		// ------------------------------------------------
		$('.silmph_top').each(function(i,e){
			var $e = $(e);
			var c = $e.children('.card-body');
			var t = c.children('.text');
			var text = ''+t.html();
			c.children('.text_rendered').html(text.wiki2html());
			t.hide();
		});
		// ------------------------------------------------
		$('.silmph_top .remove').on('click', function(){
			var $e = $(this).closest('.silmph_top');
			
			if(confirm('Soll das Top: "'+ $e.children('.headline').children('span').eq(1).text()+'" wirklich gelöscht werden?')){
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
		});
		// ------------------------------------------------
		$('.silmph_top .skipn').on('click', function(){
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
		});
		// ------------------------------------------------
		$('.silmph_toplist .silmph_top .card-header').disableSelection();
		$('.silmph_toplist').sortable({
			update: sortCallback,
			items : '.silmph_top:not(.resort)',
			handle: '.card-header',
			axis: 'y'
		});
		// ------------------------------------------------
		$('.showtoggle').on('click', function(){
			var $e = $(this).parent();
			if ($e.hasClass('showlist')) $e.removeClass('showlist');
			else $e.addClass('showlist');
		});
		// ------------------------------------------------
		var deleteMember = function(){
			var $e = $(this).prev();
			
			if(confirm('Soll das Mitglied: "'+ $e[0].dataset.name+'" wirklich gelöscht werden? Alle verknüpften Protokolle werden gelöscht.')){
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
		};
		$('.silmph_memberbox.editmember .delete').on('click', deleteMember);
		// ------------------------------------------------
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
		$('.silmph_memberbox.editmember .newmember_name').keypress(function(e){
			if(e.keyCode==13) $('.silmph_memberbox.editmember .newmemberbtn').click();
		});
		$('.silmph_memberbox.editmember .newmemberbtn').on('click', function(){
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
								newli.children('.delete').on('click', deleteMember);
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
		});
	});
})();