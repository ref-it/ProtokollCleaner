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
		$("#resofilter").on("keyup", function() {
			var value = $(this).val().toLowerCase();
			$("#resotable .resolution").filter(function() {
				//remove text from togglebox, then search text
				$(this).toggle($(this).clone().find('.togglebox').remove().end().text().toLowerCase().indexOf(value) > -1)
			});
		});
	});
	
})();