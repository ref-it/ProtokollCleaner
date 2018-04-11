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
	if (typeof(String.prototype.renderWiki != 'function')){
		(function() {
			// make sure we trim BOM and NBSP too
			var rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
			String.prototype.trim = function (){
				return this.replace(rtrim, '');
			};
		})();
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
						pdata = {};
						try {
							pdata = JSON.parse(data);
						} catch(e) {
							console.log(data);
							pdata.success=false;
							pdata.eMsg = ('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...');
							auto_page_reload(5000);
						}
						if(pdata.success == true){
							$e.animate({ height: 'toggle', opacity: 'toggle' }, 1400, function(){
								$e.remove();
							});
							silmph__add_message(pdata.msg + ((typeof(pdata.timing) == 'number')? ' (In '+pdata.timing.toFixed(2)+' Sekunden)' : ''), MESSAGE_TYPE_SUCCESS, 3000);
							if(dataset.value==0 && $e.parent().hasClass('done')) $e.parent().removeClass('done');
							else if (dataset.value==1 && !$e.parent().hasClass('done')) $e.parent().addClass('done');
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
							$e[0].checked = (dataset.value==0);
						}
					},
					error: function(data){
						console.log(data);
						$e[0].checked = false;
						try {
							pdata = JSON.parse(data.responseText);
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						} catch(e) {
							silmph__add_message('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...', MESSAGE_TYPE_WARNING, 5000);
							auto_page_reload(5000);
						}
					}
				});
			}
		});
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
					try {
						pdata = JSON.parse(data);
					} catch(e) {
						console.log(data);
						pdata.success=false;
						pdata.eMsg = ('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...');
						auto_page_reload(5000);
					}
					if(pdata.success == true){
						if(pdata.skipnext && !$e.hasClass('skipnext')){
							$e.addClass('skipnext');
						} else if (!pdata.skipnext && $e.hasClass('skipnext')) {
							$e.removeClass('skipnext');
						}
						//silmph__add_message(pdata.msg + ((typeof(pdata.timing) == 'number')? ' (In '+pdata.timing.toFixed(2)+' Sekunden)' : ''), MESSAGE_TYPE_SUCCESS, 3000);
						if(dataset.value==0 && $e.parent().hasClass('done')) $e.parent().removeClass('done');
						else if (dataset.value==1 && !$e.parent().hasClass('done')) $e.parent().addClass('done');
					} else {
						silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						$e[0].checked = (dataset.value==0);
					}
				},
				error: function(data){
					console.log(data);
					$e[0].checked = false;
					try {
						pdata = JSON.parse(data.responseText);
						silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
					} catch(e) {
						silmph__add_message('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...', MESSAGE_TYPE_WARNING, 5000);
						auto_page_reload(5000);
					}
				}
			});
		});
	});
})();