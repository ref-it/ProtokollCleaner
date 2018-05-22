(function(){
	$(document).ready(function(){
		//highlight id tag -------------------------------
		setTimeout(function(){
			//highlight id tag
			if(window.location.hash && window.location.href.indexOf(GLOBAL_RELATIVE+'resolist#reso-') > -1) {
				// Fragment exists
				if(window.location.hash.lastIndexOf('#reso-', 0) === 0){
					$(window.location.hash).addClass("bg-warning");
					$('html, body').animate({
						scrollTop: $(window.location.hash).offset().top-100
					}, 50);
				}
			} 
		}, 200);
		//filter table
		var last_val_length = 0;
		$("#resofilter").on("keyup", function() {
			var value = $(this).val().toLowerCase();
			var l = (value.length >= last_val_length)? false: true; 
			last_val_length = value.length;
			var search_type = false;
			if (value.length > 0 && value.charAt(0) == '#'){
				var tmp_type = '';
				var split_pos = value.indexOf(' ');
				if (split_pos >= 0){
					search_type = value.substr(1, split_pos);
					value = value.substr(split_pos + 1);
				} else {
					search_type = value.substr(1);
					value = '';
				}
				search_type = search_type.charAt(0).toUpperCase() + search_type.slice(1);
			}
			var $searchon;
			if (search_type != false){
				$searchon = $("#resotable .resolution");
				$searchon.filter('.resotype-'+search_type+'').show();
				$searchon.filter(':not(.resotype-'+search_type+')').hide();
				$searchon = $("#resotable .resolution:visible");
			} else {
				$searchon = (l)? $("#resotable .resolution") : $("#resotable .resolution:visible");
			}
			$searchon.filter(function() {
				//remove text from togglebox, then search text
				$(this).toggle($(this).clone().find('.togglebox').remove().end().text().toLowerCase().indexOf(value) > -1)
			});
			if (l) $('#resotable .resolution:visible').prevUntil('tr.newweek:visible', '.newweek').show();
			/*if (l){
				$('#resotable .resolution:visible').each(function(i, e){
					$l = $(e);
					while(true){
						$l = $l.prev();
						if ($l.is(':visible')) return;
						if ($l.hasClass('newweek')){
							$l.show();
							return;
						}
					}
				});
			}*/
			if (!l) $('#resotable tr.newweek:visible').prevUntil('tr.resolution:visible', '.newweek').hide();
			//
			/**/
		});
	});
	
})();
