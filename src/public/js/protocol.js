(function(){
	$(document).ready(function(){
		$('.protolist .proto button').click(function(e){
			$e = $(this);
			var proto = $e.parent().prev().text();
			var perm = 'stura';
			window.location.href = '/protoedit?committee='+perm+'&proto='+proto;
		});
		
		$('.protostatus .legislatur button.add').click(function(e){
			$e = $(this);
			var proto_nr = parseInt($e.prev().text());
			proto_nr++;
			$e.prev().text(proto_nr);
		});
		
		$('.protostatus .legislatur button.sub').click(function(e){
			$e = $(this);
			var proto_nr = parseInt($e.next().text());
			proto_nr = Math.max(proto_nr-1, 1);
			$e.next().text(proto_nr);
		});
		
		$('.protolinks button.commit').click(function(e){
			$e = $(this);
			//show warning if there are errors
			//test if is no draft -> show warning if there are open fixme, todo, and deleteme
			
			//get variables
			//attachements
			// do ajax post
		});
		
	});
	
})();