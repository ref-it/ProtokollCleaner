(function(){
	$(document).ready(function(){
		$('.protolist .proto button').click(function(e){
			$e = $(this);
			console.log(this);
			console.log($e);
			console.log($e.prev());
			console.log($e.parent().prev());
			console.log($e.parent().prev().text());
			proto = $e.parent().prev().text();
			perm = 'stura';
			window.location.replace('/protoedit?committee='+perm+'&proto='+proto);
		});
	});
})();