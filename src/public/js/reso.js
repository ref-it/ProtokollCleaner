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
		//get parameter filter ------------------------------
		$('.resol_legend .legend-order').on('click', function(){
			this.dataset.filter = (this.dataset.filter=='1')? '0': '1';
			//chevron
			$chevron = $(this).children('.color').children('i');
			if ($chevron.hasClass($chevron.data('class0'))) $chevron.removeClass($chevron.data('class0'));
			if ($chevron.hasClass($chevron.data('class1'))) $chevron.removeClass($chevron.data('class1'));
			$chevron.addClass($chevron.data('class'+this.dataset.filter));
			//text
			$name = $(this).children('.name');
			$name.text($name.data('text'+this.dataset.filter));
			//get parameter
			var newvalue = this.dataset.filter == 1 ? 'ASC':'';
			var key = this.dataset.type;
			window.location.href = setGetParameter(window.location.href, key, newvalue);
		});
		$('.resol_legend .legend-year select').on('change', function(){
			//get parameter
			var newvalue = this.value;
			var key = this.parentNode.dataset.type;
			window.location.href = setGetParameter(window.location.href, key, newvalue);
		});
		//filter resolution table ---------------------------
		var last_value = '';
		var last_search_type = '';
		var $searchbase = $("#resotable .resolution");
		var $resocounter = $('.resocounter');
		var headline_timeout = null;
		$newweek = $('#resotable tr.newweek');
		//init searchset - do not convert element text on every letter, only do it once
		setTimeout(function(){
			var text = '';
			for (var i = 0; i < $searchbase.length; i++){
				text = $searchbase.eq(i).clone().find('.togglebox').remove().end().text().toLowerCase();
				$searchbase.eq(i).data('search', text+'');
			}
			$("#resofilter").fadeIn();
		}, 500);
		$("#resofilter").on("keyup", function() {
			//current seach value
			var value = $(this).val().toLowerCase();
			var search_type = false;
			//filter resolution type
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
			//reset searchset needed?
			var l = (value.indexOf(last_value) == -1 || last_search_type != search_type)? true : false; 
			//remember last search arguments
			last_value = value;
			last_search_type = search_type;
			//filter elements
			var $searchon;
			if (search_type != false){
				$searchon = $searchbase;
				$searchon.filter('.resotype-'+search_type+'').show();
				$searchon.filter(':not(.resotype-'+search_type+')').hide();
				$searchon = $searchbase.filter(':visible');
			} else {
				$searchon = (l)? $searchbase : $searchbase.filter(':visible');
			}
			//filter results
			$searchon.filter(function() {
				// search text
				$(this).toggle($(this).data('search').indexOf(value) > -1)
			});
			//debounce headline and counter update
			if (headline_timeout != null) {
				clearTimeout(headline_timeout);
				headline_timeout = null;
			}
			headline_timeout = setTimeout( function(){
				var $visible = $searchbase.filter(':visible');
				//update protocol date + link headlines
				$newweek.hide();
				$visible.prevUntil('.newweek').prev('.newweek').show();
				$visible.prev('.newweek').show();
				//update counter
				$resocounter.text($visible.length);
			}, 500)
		});
	});
	
})();
