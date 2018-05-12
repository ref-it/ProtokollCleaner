/**
 * JS SCRIPTS wiki2html parser
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
	if (typeof(String.prototype.wiki2html) != 'function'){
	    String.prototype.wiki2html = function() {
	    	var s = this;		// input string
	    	var newline = true; //add new line on lines without extra html
    	
	    	// headlines ====================================================
	    	var _headline = function(line){
	    		for (var i = 2; i<=6; i++){
	    			var re = new RegExp("^(={"+i+"})[\s]*([^=\n\r\s]+[^=\n\r]*)(={"+i+"})");
	    			if ((m = re.exec(line)) !== null && m.length == 4 && (m[2].trim()) != '') {
	    				line = line.replace(re, '<h'+(7-i)+'>'+(m[2].trim())+'</h'+(7-i)+'>');
	    				newline = false;
	    			}
	    		}
	    		return line;
	    	};
	    	// listings =====================================================
	    	var openedLists = [];
	    	var listEopen = false;
	    	// close list
	    	var _closeList = function (count){
	    		out = '';
	    		if (count == false || count == 0 || count == 'undefined' || typeof(count) == 'undefined' ){
	    			count = openedLists.length;
	    		} else {
	    			count = Math.min(count, openedLists.length);
	    		}
	    		while (count > 0){
	    			newline = false;
	    			out += '</li>'+openedLists.pop();
	    			listEopen = true;
	    			count--;
	    		}
	    		
	    		return out;
	    	}
	    	// open list
	    	var _openList = function (type, level){
	    		out = '';
	    		if (openedLists.length > 0){
	    			//out += '<li>';
	    		}
	    		if (type == 'ul') {
    				out += '<ul>';
    				openedLists.push('</ul>');
    			} else {
    				var oltype = ((level%3 == 1)?'1':((level%3 == 2)?'a':'i')); 
    				out += '<ol type="'+oltype+'">';
    				openedLists.push('</ol>');
    			}
	    		listEopen = false;
	    		return out;
	    	}
	    	// handle list element
	    	// create valid html list
	    	var __list = function (line, type, level){
	    		out = '';
	    		var diff = level - openedLists.length;
	    		if (diff > 0){ //open new level
	    			out += _openList(type, level);
	    			out += __list(line,type,level);
	    		} else if (diff < 0 || (
	    			openedLists.length > 0 &&
	    			(openedLists[openedLists.length-1] == '</ul>' && type == 'ol')
					|| (openedLists[openedLists.length-1] == '</ol>' && type == 'ul')
	    		)){
	    			out+= _closeList(-diff);
	    			if (openedLists.length > 0){
	    				//close list if listelement is a different type
	    				if ((openedLists[openedLists.length-1] == '</ul>' && type == 'ol')
	    					|| (openedLists[openedLists.length-1] == '</ol>' && type == 'ul')){
	    					out += _closeList(1);
	    					out += _openList($type);
	    				}
	    			}
	    			out += __list(line, type, level);
	    		} else { // only add list element
	    			out += ((listEopen)?'</li>':'')+'<li>'+line+'';
	    			listEopen = true;
	    		}
	    		return out;
	    	}
	    	// parse list line
	    	var levelRe = /(^[ ]*)/;
	    	var _list = function (line){
	    		var trimmed = line.trim().trim('');
	    		var level = (trimmed.length > 0 && (trimmed[0] == '-' || trimmed[0] == '*') && 
	    			(m = levelRe.exec(line)) !== null)?
	    			m[0].length	: 0;
	    		level = Math.floor(level/2);
	    		if (trimmed.length > 0 && level >= 1 && trimmed[0] == '-'){
	    			newline = false;
	    			return __list( line.replace(/^(\s|-)*/, ''), 'ol', level);
	    		} else if (trimmed.length > 0 && level >= 1 && trimmed[0] == '*'){
	    			newline = false;
	    			return __list( line.replace(/^(\s|\*)*/, ''), 'ul', level);
	    		} else {
	    			return _closeList(0)+line;
	    		}
	    	}
	    	// underline ====================================================
	    	var _underline = function (line){
	    		return line.replace(/(__)([^_]+)(__)/mg, '<u>$2</u>');
	    	}
	    	// italic ====================================================
	    	var _italic = function (line){
	    		return line.replace(/(\/\/)([^\/]+)(\/\/)/mg, '<i>$2</i>');
	    	}
	    	// bold ====================================================
	    	var _strong = function (line){
	    		return line.replace(/(\*\*)([^\*]+)(\*\*)/mg, '<strong>$2</strong>');
	    	}
	    	// code ====================================================
	    	var _code = function (line){
	    		return line.replace(/('')([^']+)('')/mg, '<code>$2</code>');
	    	}
	    	// boxed ====================================================
	    	var _boxed = function (line){
	    		return line.replace(/^(  )(.*)/mg, '<span class="boxed">$2</span>');
	    	}
	    	// external link ====================================================
	    	var _extLink = function (line){
	    		var tmp = line.replace(/(\[\[)((http(s)?:\/\/)?(([a-zA-Z0-9\.\-\/_])+))((\|)([^\]\|]*)\]\])/mg, '<a href="$2">$9</a>');
	    		return tmp.replace(/(\[\[)((http(s)?:\/\/)?(([a-zA-Z0-9\.\-\/_])+))(([^\]\|]*)\]\])/mg, '<a href="$2">$2</a>');
	    	}
	    	// line ====================================================
	    	var _hrLine = function (line){
	    		var re = /^(\s)*[\-]{4,}(\s)*$/;
	    		if ((m = re.exec(line)) !== null){
	    			newline = false;
	    			return line.replace(re, '<hr>');
	    		}
	    		return line;
	    	}
	    	
	    	//loop lines =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
			var lines = s.split(/\r?\n/);
			var trimmed = '';
			var closelist = [];
			var lastNewline = false;
			for (var i = 0; i < lines.length; i++){
				newline = true;
				lines[i] = _headline(lines[i]);
				lines[i] = _list(lines[i]);
				lines[i] = _underline(lines[i]);
				lines[i] = _italic(lines[i]);
				lines[i] = _strong(lines[i]);
				lines[i] = _code(lines[i]);
				lines[i] = _boxed(lines[i]);
				lines[i] = _extLink(lines[i]);
				lines[i] = _hrLine(lines[i]);
				if (lastNewline) lines[i] = '<br>' + lines[i];
				lastNewline = newline;
			}
			return lines.join("");
	    };
	}
})();