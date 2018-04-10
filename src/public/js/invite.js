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
			var $e = $(this);
			console.log($e.closest('.silmph_top')[0].dataset.tid);
		});
	});
})();