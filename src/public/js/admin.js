/**
 * JS SCRIPTS admin
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        script
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

(function(){
	function editable_mail_settings(object, function_name){
		$(object).editable({
			submit: function($elem, text){
				var validator = $elem[0].dataset.validator;
				var old_data = $elem.html();
				var dataset = { value: text,
								data: $elem.data('value'),
								mfunction: function_name,
								};
				fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				console.log(dataset);
				$.ajax({
					type: "POST",
					url: 'admin/savemail',
					data: dataset,
					success: function(data){
						console.log(data);
						pdata = {};
						try {
							pdata = JSON.parse(data);
						} catch(e) {
							console.log(data);
							pdata.success=false;
							pdata.eMsg = ('Unerwarteter Fehler. Seite wird neu geladen...');
							auto_page_reload(5000);
						}
						if(pdata.success == true){
							//add element
							if (validator != "password"){
								$elem.html(pdata.val);
							}
							$elem.css('background-color', '');
							silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
						} else {
							if (validator != "password"){
								$elem.html(old_data);
							}
							$elem.css('background-color', '#ff4c4c');
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: function(data){
						console.log(data);
						if (validator != "password"){
							$elem.html(old_data);
						}
						try {
							pdata = JSON.parse(data.responseText);
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						} catch(e) {
							console.log(data);
							$elem.css('background-color', '#ff4c4c');
							silmph__add_message('Unerwarteter Fehler. Seite wird neu geladen...', MESSAGE_TYPE_WARNING, 5000);
							auto_page_reload(5000);
						}
					}
				});
			},
			parse: function(str){
				return ('' + strip(str).trim());
			},
			validate: function(str, $elem){
				var text =  '' + strip(str).trim();
				var validator = $elem[0].dataset.validator;
				switch (validator){
					case 'host':
						if (checkIsValidDomain(text) || checkIsValidIp(text) || text == ''){
							return true;
						} else silmph__add_message('Ungültiger Hostname.', MESSAGE_TYPE_WARNING, 5000);
						break;
					case 'username':
						if(checkIsValidUsername(text) || text == ''){
							return true;
						} else silmph__add_message('Ungültiger Nutzername.', MESSAGE_TYPE_WARNING, 5000);
						break;
					case 'password':
						if (text.length >= 4  || text == ''){
							return true;
						} else {
							silmph__add_message('Das Passwort muss aus mindestens 4 Zeichen bestehen.', MESSAGE_TYPE_WARNING, 5000);
							return false;
						}
						break;
					case 'ssltls':
						if(text == "SSL" || text == "TLS"){
							return true;
						} else silmph__add_message('Üngültiger Sicherheitstyp.', MESSAGE_TYPE_WARNING, 5000);
						break;
					case 'integer':
						if(isInt(text) && Number(text) > 0 && Number(text) < 100000){
							return true;
						} else silmph__add_message('Der gegebene Wert ist keine ganze Zahl oder größer als 99999.', MESSAGE_TYPE_WARNING, 5000);
						break;
					case 'mail':
						if(checkIsValidEmail(text) || text == ''){
							return true;
						} else silmph__add_message('Ungültiger E-Mailadresse.', MESSAGE_TYPE_WARNING, 5000);
						break;
					case 'user':
						if(checkIsValidName(text) || text == ''){
							return true;
						} else silmph__add_message('Ungültiger Name.', MESSAGE_TYPE_WARNING, 5000);
						break;
					default:
						break;
				}
				return false;
			},
			onerror: function(obj, text){
				obj.css('background-color', '#ff4c4c');
			},
			onnoerror: function($editbox, text, $elem){
				$elem.css('background-color', '');
			},
			filterBeforeShow: function (oldtext, $elem, $inputs){
				var validator = $elem[0].dataset.validator;
				if (validator == "password"){
					return '';
				} else if (validator == "ssltls"){
					$inputs.bind('keydown', function(e){
						var $radiogroup = $inputs;
						var code = (e.keyCode ? e.keyCode : e.which);
						if (code == 37 || code == 39) { //arrow left || arrow right
							var $radios = $radiogroup.find('input');
							var currentIndex = 0;
							var labels_length = $radios.length
							for(var i = 0; i < labels_length; i++){
								if($radios[i].checked){
									currentIndex = i;
									break;
								}
							}
							//calculate new index
							currentIndex += (code - 38);
							currentIndex = (currentIndex + labels_length)%labels_length;
							$($radios[currentIndex]).click();
							e.stopPropagation();
						}
					});
					var $radios = $inputs.find('input');
					var labels_length = $radios.length;
					$radios.click(function(){
						this.checked = true;
						$inputs.attr('value',this.value.toUpperCase());
						$inputs.val(this.value.toUpperCase());
					});
					for(var i = 0; i < labels_length; i++){
						if($radios[i].value == oldtext.toLowerCase()){
							$radios[i].checked = true;
						} else {
							$radios[i].checked = false;
						}
					}
					return oldtext;
				} else {
					return oldtext;
				}
			},
			editBox: function ($elem){
				var validator = $elem[0].dataset.validator;
				switch (validator){
					case 'password':
						return $('<input type="password" minlength="5"></input>');
						break;
					case 'ssltls':
						{
							var $radiogroup = $('<div class="radiogroup radiotoggle2" tabindex="0"><input type="radio" id="toggle-ssl" name="togglesecure" value="ssl">'+
										'<label class="noselect" for="toggle-ssl">SSL</label>' +
									'<input type="radio" id="toggle-tls" name="togglesecure" value="tls">' + 
										'<label class="noselect" for="toggle-tls">TLS</label>' +
									'<div class="radiomover"></div></div>');
							var $radios = $radiogroup.find('input');
							$radiogroup.bind('keydown', function (e) {
								
							});
							return $radiogroup;
						}break;
					case 'integer':
						return $('<input type="number" min="0" max="99999" step="1.0"></input>');
						break;
					case 'mail':
						return $('<input type="email"></input>');
						break;
					default:
						return $('<input type="text"></input>');
						break;
				}
			},
			textToElem: function($elem, text){
				var validator = $elem[0].dataset.validator;
				if (!(validator == "password")){
					$elem.html(text);
				} else {
					var passText = '';
					for (var i = 0; i < text.length; i++) passText += '*';
					$elem.html(passText);
					$elem[0].dataset.newval = text;
				}
			}
		})
	}
	
	function sendTestMail(){
		var r = confirm("Es wird eine E-Mail an das angegebene Postfach gesendet. Prüfen Sie anschließend Ihren Posteingang.\n\nMöchten Sie Fortfahren?");
		if (r == true) {
			var dataset = { mfunction: "test_mailer" };
			fchal = document.getElementById('fchal');
			dataset[fchal.getAttribute("name")] = fchal.value;
			$.ajax({
				type: "POST",
				url: 'admin/testmail',
				data: dataset,
				success: function(data){
					pdata = {};
					try {
						pdata = JSON.parse(data);
					} catch(e) {
						console.log(data);
						pdata.success=false;
						pdata.eMsg = ('Unerwarteter Fehler. Seite sollte neu geladen werden...');
					}
					if(pdata.success == true){
						//add element
						silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
					} else {
						silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
					}
				},
				error: function(data){
					try {
						pdata = JSON.parse(data.responseText);
						silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
					} catch(e) {
						console.log(data);
						silmph__add_message('Unerwarteter Fehler.', MESSAGE_TYPE_WARNING, 5000);
					}
				}
			});
		}
	}
	$(document).ready(function(){
		editable_mail_settings( $('.admin .editable.mail') , 'edit_mailsetting');
		$('.admin.mail_settings .footerline button.submit').click(sendTestMail);
	});

})();