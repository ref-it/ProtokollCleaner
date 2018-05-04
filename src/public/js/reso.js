(function(){
	$(document).ready(function(){
		//highlight id tag -------------------------------
		setTimeout(function(){
			//highlight id tag if it belongs to gallery
			if(window.location.hash && window.location.href.indexOf("/resolist#reso-") > -1) {
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
			$("#resotable .resolution").filter(function() {
				//remove text from togglebox, then search text
				$(this).toggle($(this).clone().find('.togglebox').remove().end().text().toLowerCase().indexOf(value) > -1)
			});
			if (l) $('#resotable .resolution:visible').prevUntil('tr.newweek:visible', '.newweek').show();
			$('#resotable tr.newweek:visible').prevUntil('tr.resolution:visible', '.newweek').hide()
			
		});
	});
	
})();