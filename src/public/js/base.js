/* ============================= JQuery Editable Plugin ========================================================== */
/**
 * BASE JS Scripts
 * 
 * @author michael g
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 17.02.2018
 * @copyright 	Copyright (C) 2018 - All rights reserved
 */
//plugin to make any element text editable
$.fn.extend({
	editable: function (param) {
		$(this).each(function (index, elem) {
			var $el = $(elem),
			$edittextbox = ( (!param.hasOwnProperty('editBox')) ? 
					($('<input type="text"></input>')) :
					((jQuery.isFunction(param.editBox))? 
							param.editBox($el) : 
							(param.editBox))
			),
			$edittextbox = $edittextbox.css('max-width', $el.width()),
			submitChanges = function () {
				//set properties
				var valide = true;
				var text = $edittextbox.val();
				if (param.hasOwnProperty('validate')){
	          		valide = param.validate(text, $el);
	          	}
	          	alwayssubmit = false;
	          	if (param.hasOwnProperty('alwaysSubmit')){
	          		alwayssubmit = param.alwaysSubmit;
	          	}
	          	var checkSubmitResult = false;
	          	if (param.hasOwnProperty('checkSubmitResult')){
	          		checkSubmitResult = param.checkSubmitResult;
	          	}
				if (valide) {
	        		if (param.hasOwnProperty('parse')){
	          			text = param.parse(text, $el, $edittextbox);
	          		}
					if (param.hasOwnProperty('submit')){
						if ($el.html() != text || alwayssubmit){
							var submText = text;
							if (param.hasOwnProperty('parseForSubmit')){
								submText = param.parseForSubmit(text, $el);
			          		}
		          			if (checkSubmitResult) {
		          				valide = param.submit($el, submText);
		          			} else {
		          				param.submit($el, submText);
		          			}
						}
	          		}
					if (!checkSubmitResult || valide){
		          		if (param.hasOwnProperty('onnoerror')){
		          			param.onnoerror($edittextbox, text, $el);
		          		}
		          		if (param.hasOwnProperty('textToElem')){
		          			param.textToElem($el, text);
		          		} else {
		          			$el.html(text);
		          		}
						$el.show();
						$(document).unbind('click', submitChanges);
						$edittextbox.remove();
						$el[0].focus();
						if (param.hasOwnProperty('callback')){
							param.callback($el, text);
						}
					}
				}
				if (!valide){
					if (param.hasOwnProperty('onerror')){
	          			param.onerror($edittextbox, text);
	          		}
				}
			};
			var showEditable = function(){
				var tempVal = $el.html();
				if (param.hasOwnProperty('filterBeforeShow')){
					tempVal = param.filterBeforeShow(tempVal, $el, $edittextbox);
	          	}
				$edittextbox.val(tempVal).insertBefore(elem);
				var shift_pressed = false;
				$edittextbox.bind('keyup', function (e) {
					var code = (e.keyCode ? e.keyCode : e.which);
					if (code == 16) { //shift
						shift_pressed = false;
					}
				});
				$edittextbox.bind('keydown', function (e) {
					var code = (e.keyCode ? e.keyCode : e.which);
					if (code == 13 || code == 9) { //enter || tab --> submit
						if (!shift_pressed){
							submitChanges();
							e.stopPropagation();
						}
					}
					else if (code == 27) { //escape --> close/abort
						$el.show();
						$(document).unbind('click', submitChanges);
						$edittextbox.remove();
						e.stopPropagation();
					} else if (code == 16) {
						shift_pressed = true;
					}
				}).select().focus();
				$edittextbox.click(function (event) {
					event.stopPropagation();
				});
				$el.hide();
				$(document).click(submitChanges);
			}
			$el.dblclick(function (e) {
				showEditable();
			});
			$el.bind('keydown', function(e){
				if ($el.is(":focus")){
					var code = (e.keyCode ? e.keyCode : e.which);
					if (code == 13) { //enter
						e.stopPropagation();
						showEditable();
					}
				}
			});
		});
		return this;
	}
});
/* ============================= Helper functions ========================================================== */

function escapeHtml(text) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function strip(html)
{
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return escapeHtml(tmp.textContent || tmp.innerText || "");
}

function auto_page_reload(delay){
	setTimeout(function() { window.location.replace(window.location); }, delay);
}
//removes html elements from string
//usage: $.strRemove("h2", messagetext);
(function($) {
  $.strRemove = function(element, string) {
  	var $div = $('<div>').html(string);
  	$div.find(element).remove();
      return $div.html();
  };
})(jQuery);
if(typeof stringToDate != 'function'){
   	window.stringToDate = function(datestr2){
   		var datestr = datestr2;
   		var pattern = /(\d{2})\.(\d{2})\.(\d{4})/;
   		return date = new Date(datestr.replace(pattern,'$3-$2-$1'));
   	}
}
/* ============================= Validators ========================================================== */
function isInt(n){
    return Number(n) == n && n % 1 === 0;
}
function checkIsValidEmail(email) {
	var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/;
	return regex.test(email);
}
function checkIsValidDomain(domain){
	if (/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(domain)) {
		if (domain.length < 64){
			return true;
		}
		return false;
	}
	return false;
} 
function checkIsValidIp(ipadr, recursive) {
	if (typeof(recursive) == 'undefined'){
		recursive = true;
	}
	if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$|^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/.test(ipadr)) {
	   return true;
	} else {
		if (recursive && ipadr.length > 2 && ipadr.charAt(0) == '[' && ipadr.charAt(ipadr.length-1) == ']'){
			return checkIsValidIp(ipadr.substring(1, ipadr.length-1), false);
		} else {
			return false;
		}
	}
}
function checkIsValidUsername(name){
	if (/^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*[a-zA-Z0-9]+$/.test(name)) {
		if (name.length < 64){
			return true;
		}
		return false;
	}
	return false;
}

function checkIsValidName(name){
	name = name.replace(/&amp;/g, '&');
	if (/^[a-zA-Z0-9äöüÄÖÜß]+[a-zA-Z0-9\-_#&\/ .äöüÄÖÜß]*[a-zA-Z0-9äöüÄÖÜß]+$/.test(name)) {
		if (name.length < 64){
			return true;
		}
		return false;
	}
	return false;
} 
/* ============================= MESSAGE SYSTEM ========================================================== */
const MESSAGE_TYPE_NORMAL = 0; //blue
const MESSAGE_TYPE_INFO = 1; //blue
const MESSAGE_TYPE_WARNING = 2; //red
const MESSAGE_TYPE_SUCCESS = 3; //green

//hide parent object -> fade opacity

var silmph__hide_remove_parent = function (object){
	$(object).parent().stop(true, false).animate({ height: 0, opacity: 0 }, 300,function(){ $(this).remove(); });
}

//show messages on screen
var silmph__add_message = function (msg, type, hide_delay){
	var msg_div = document.createElement('div');
	switch(type){
		case MESSAGE_TYPE_INFO: 
			msg_div.className = "silmph__message_info";
		break; 
		case MESSAGE_TYPE_WARNING: 
			msg_div.className = "silmph__message_warning";
		break; 
		case MESSAGE_TYPE_SUCCESS: 
			msg_div.className = "silmph__message_success";
		break;
		case MESSAGE_TYPE_NORMAL:
		default:break;
	}
	msg_div.innerHTML = msg;
		var close_i = document.createElement('i');
		close_i.innerHTML = 'X';
		$(close_i).click(function(e){
			silmph__hide_remove_parent(this);
			e.stopPropagation();
		});
		msg_div.appendChild(close_i);
	msg_div.style.display = 'none';

	$(msg_div).appendTo('#silmph__message_container').slideDown( 300 );

	if (hide_delay === parseInt(hide_delay, 10)){
		if (hide_delay > 0){
			$(msg_div).delay(hide_delay).animate({ height: 0, opacity: 0 }, 300,function(){ $(this).remove(); });
		}
	}
};

/* =============================   ========================================================== */
(function (){
	var $modalwrapper;
	var modal_close = function(){
		if($modalwrapper.hasClass('open')) $modalwrapper.removeClass('open');
		if ($modalwrapper.children().each(function(i, e){
			$e = $(e);
			if ($e.hasClass('open')) $e.removeClass('open');
		}));
	}

	$(document).ready(function(){
		$modalwrapper = $('.modalwrapper');
		$('.modalwrapper .modal_close').click(modal_close);
		$('.modalwrapper .close').click(modal_close);
		
		var $elem1 = $('#fullscreen_toggle');
		$elem1.click(function(){
			el = document.getElementById('body');
			if (screenfull.enabled) {
		        screenfull.toggle(el);
		    }
			var toggleText = '';
			var $this = $(this);
			if ($this.attr('data-toggletext')){
				toggleText = $this.data('toggletext');
				$this.data('toggletext', $this.html());
				$this.html(toggleText)
			}
		});
		
		var $elem2 = $("#menu_toggle_check")
		$('label.menu_toggle').bind('keydown', function(e) {
		    if ( e.keyCode === 32 || e.keyCode === 13 ) { // spacebar key maps to keycode 32 // enter key maps to keycode 13
		        // Execute code here.
		    	$elem2.click();
		    }
		});
	});
	
	$(document).keydown(function(e) {
     if (e.keyCode == 27) { // escape key maps to keycode 27
        if($modalwrapper.hasClass('open')){
        	modal_close();
        	e.stopPropagation();
        }
    }
});
	
})();