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
	function setGetParameter(inp, paramName, paramValue)
	{
	    var url = inp;
	    var hash = location.hash;
	    url = url.replace(hash, '');
	    if (url.indexOf("?") < 0){
	    	if (paramValue != '' && paramValue!=null) url += "?" + paramName + "=" + paramValue;
	    } else if (url.indexOf('?' + paramName + "=") >= 0 || url.indexOf('&' + paramName + "=") >= 0) {
	    	var identer;
	    	if (url.indexOf('&' + paramName + "=") >= 0){
	    		identer = '&' + paramName + "="; 
	    	} else {
	    		identer = '?' + paramName + "="; 
	    	}
	    	var paramPos = url.indexOf(identer);
	    	var prefix = url.substring(0, paramPos);
	    	var suffix = url.substring(paramPos + identer.length);
	    	suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
	    	
	    	if (paramValue != '' && paramValue!=null) url = prefix + identer + paramValue + suffix;
	    	else {
	    		if (suffix.length > 0 && identer.charAt(0) == '?') suffix = '?' + suffix.substring(1);
	    		url = prefix + suffix;
	    	}
	    } else {
	    	if (paramValue != '' && paramValue!=null) url += "&" + paramName + "=" + paramValue;
	    }
	    return url + hash;
	}
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
			fchal = document.getElementById('fchal');
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