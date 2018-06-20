/**
 * JS SCRIPTS todo
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        script
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			04.04.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

(function(){
	// calendar filter clicks
	$(document).ready(function(){
		$('.todo_legend .legendelem').on('click', function(){
			this.dataset.filter = (this.dataset.filter=='1')? '0': '1';
			var newvalue = this.dataset.filter;
			var key = this.dataset.type;
			window.location.href = setGetParameter(window.location.href, key, newvalue);
		});
		$('.todolist .todoentry input+label').on('click', function(){
			$g = $(this);
			$e = $g.prev();
			var dataset = {
				pid: $e[0].dataset.pid,
				hash: $e[0].dataset.hash,
				value: ($e[0].checked)? 0 : 1,
				committee: 'stura'
			};
			var fchal = document.getElementById('fchal');
			dataset[fchal.getAttribute("name")] = fchal.value;
			
			//do ajax post request
			$.ajax({
				type: "POST",
				url: GLOBAL_RELATIVE+'todo/update',
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

		});
		
	});
})();
