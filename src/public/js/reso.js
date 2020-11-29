(function(){
	$(document).on('ready', function(){
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
			this.dataset.filter = (this.dataset.filter==='1')? '0': '1';
			//chevron
			let $chevron = $(this).children('.color').children('i');
			if ($chevron.hasClass($chevron.data('class0'))) $chevron.removeClass($chevron.data('class0'));
			if ($chevron.hasClass($chevron.data('class1'))) $chevron.removeClass($chevron.data('class1'));
			$chevron.addClass($chevron.data('class'+this.dataset.filter));
			//text
			let $name = $(this).children('.name');
			$name.text($name.data('text'+this.dataset.filter));
			//get parameter
			let newvalue = this.dataset.filter === '1' ? 'ASC':'';
			let key = this.dataset.type;
			window.location.href = setGetParameter(window.location.href, key, newvalue);
		});
		$('.resol_legend .legend-year select').on('change', function(){
			//get parameter
			let newvalue = this.value;
			let key = this.parentNode.dataset.type;
			window.location.href = setGetParameter(window.location.href, key, newvalue);
		});
		//filter resolution table ---------------------------
		let last_value = '';
		let last_search_type = '';
		const $resocounter = $('.resocounter');
		const $search_field = $("#resofilter");
		const $newweek = $('#resotable tr.newweek');
		const $searchbase = $("#resotable .resolution");
		const searchbase_length = $searchbase.length;
		const searchtext = {};
		const reso_type_key_map = { // reso type to index list key
			t: 't',
			tagesordnung: 't',
			p: 'p',
			protokoll: 'p',
			o: 'o',
			ordnungen: 'o',
			w: 'w',
			wahl: 'w',
			f: 'f',
			h: 'h',
			finanzen: 'finanzen',
			s: 's',
			i: 'i',
			intern: 'i',
			sonstiges: 's',
		};
		const reso_type_index_by_key = {};	// index list by reso type key
		for (const p in reso_type_key_map) { // init list
			if (reso_type_key_map.hasOwnProperty(p) && !reso_type_index_by_key.hasOwnProperty(reso_type_key_map[p])){
				reso_type_index_by_key[reso_type_key_map[p]] = [];
				reso_type_index_by_key['not_'+reso_type_key_map[p]] = [];
			}
		}
		let timeout_headline_update = null; // timeout for headline update
		let timeout_debounce_search = null; // debounce search filter

		//init searchset - do not convert element text on every letter, only do it once
		setTimeout(function(){
			let text = '';
			for (let i = 0; i < $searchbase.length; i++){
				let reso = $searchbase.eq(i);
				text = reso.clone().find('.togglebox').remove().end().text().toLowerCase() + '';
				searchtext[i] = text;
				// init search reso indexes
				for (let p in reso_type_index_by_key) {
					if (reso_type_index_by_key.hasOwnProperty(p)){
						if (p.indexOf('not_') === 0) continue;
						if (reso.hasClass('resotype-'+p.charAt(0).toUpperCase() + p.slice(1))){
							reso_type_index_by_key[p].push(i);
						} else {
							reso_type_index_by_key['not_'+p].push(i);
						}
					}
				}
			}
			$search_field.fadeIn();
		}, 500);

		const update_search_result_event = function() {
			//current seach value
			let value = $(this).val().toLowerCase();
			let search_type = false;
			//filter resolution type
			if (value.length > 0 && value.charAt(0) === '#'){
				let split_pos = value.indexOf(' ');
				if (split_pos >= 0){
					search_type = value.substr(1, split_pos);
					value = value.substr(split_pos + 1);
				} else {
					search_type = value.substr(1);
					value = '';
				}
				// get search_type list key
				if (reso_type_key_map.hasOwnProperty(search_type)){
					search_type = reso_type_key_map[search_type];
				} else {
					search_type = false;
				}
			}
			//remember last search arguments
			last_value = value;
			last_search_type = search_type;
			// filter elements
			if (search_type !== false){
				for (let i = 0; i < reso_type_index_by_key['not_'+search_type].length; i++) {
					let j = reso_type_index_by_key['not_'+search_type][i];
					$searchbase.eq(j).hide();
				}
				for (let i = 0; i < reso_type_index_by_key[search_type].length; i++) {
					let j = reso_type_index_by_key[search_type][i];
					if (searchtext[j].indexOf(value) > -1) {
						$searchbase.eq(j).show();
					} else {
						$searchbase.eq(j).hide();
					}
				}
			} else {
				for (let j = 0; j < searchbase_length; j++) {
					$searchbase.eq(j).toggle(searchtext[j].indexOf(value) > -1);
				}
			}
			//debounce headline and counter update
			if (timeout_headline_update != null) {
				clearTimeout(timeout_headline_update);
				timeout_headline_update = null;
			}
			timeout_headline_update = setTimeout( function(){
				timeout_headline_update = null;
				let $visible = $searchbase.filter(':visible');
				//update protocol date + link headlines
				$newweek.hide();
				$visible.prevUntil('.newweek').prev('.newweek').show();
				$visible.prev('.newweek').show();
				//update counter
				$resocounter.text($visible.length);
			}, 500)
		};

		// search field key listener
		$search_field.on("keyup", function(ev) {
			let t = this;
			if (timeout_debounce_search !== null) {
				clearTimeout(timeout_debounce_search);
				timeout_debounce_search = null;
			}
			timeout_debounce_search = setTimeout(function (){
				timeout_debounce_search = null;
				update_search_result_event.call(t, ev);
			}, 600);
		});
	});
})();
