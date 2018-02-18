<?php
/**
 * AJAX HANFLER admin
 * Application starting point
 *
 * @package         TODO
 * @category        script
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
// ===== load framework =====
if (!file_exists ( dirname(__FILE__, 4).'/config.php' )){
	echo 'No configuration file found!. Please create and edit "config.php".';
	die();
}
require_once (dirname(__FILE__, 4).'/config.php');

class currentJsonHandler extends jsonHandler {
	function __construct(){
		parent::__construct();
		$this->functionAccess['function_name'] = 'permission1';
		$this->functionAccess['update_mail_setting'] = 'admin';
		$this->functionAccess['send_testmail'] = 'admin';
	}
	/**
	 * handles json request: dummy ajax handler
	 */
	public function silmph_function_name(){
		if (isset($_POST['data'])){
			//do something
			//maybe access database
			//$this->db->doSomething();
			$this->json_result = array('success' => true, 'msg' => 'ok', 'id'=> 4, 'name' => 'return text');
			$this->json_result = array('success' => false, 'eMsg' => 'Out Error Message');
				
			$this->print_json_result();
		
		} else {
			$this->access_denied_json();
		}
	}
	/**
	 * handles json request: admin update email settings
	 */
	public function silmph_update_mail_setting(){
		if (isset($_POST['value']) && isset($_POST['data'])){
			$data_value = trim(strip_tags($_POST['value']));
			$data_key = $typed_username = trim(preg_replace("/^[^a-z]+|[^a-z_]*|[^a-z]+$/", "", $_POST['data']));
	
			if ($data_key != '' && $data_key == $_POST['data']){
				$data_key = '' . strtoupper($data_key);
				//lookup customers
				$settings = $this->db->getSettings();
				if (!array_key_exists($data_key, $settings)){
					$this->access_not_found();
				} else {
					$valid = false;
					$value_to_store = '';
					switch ($data_key){
						case 'SMTP_HOST': {
							$res = isValidDomain($data_value);
							if ($res > 0 && $res < 3){
								$value_to_store = $data_value;
								$valid = true;
							} else if ($res == 3) {
								$value_to_store = idn_to_ascii($data_value);
								$valid = true;
							} else if ($data_value === ''){
								$value_to_store = '';
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Kein gültiger Hostname angegeben.');
								$this->print_json_result();
							}
						} 	break;
						case 'SMTP_USER':
							if(isValidMailUsername($data_value) || $data_value === ''){
								$value_to_store = $data_value;
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Kein gültiger Nutzername für den SMTP Server.');
								$this->print_json_result();
							} break;
						case 'MAIL_PASSWORD':
							if (strlen($data_value) >= 4) {
								$value_to_store = silmph_encrypt_key ($data_value, SILMPH_KEY_SECRET);
								$valid = true;
							} else if ( $data_value === ''){
								$value_to_store = '';
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Das Passwort muss aus mindestens 4 Zeichen bestehen.');
								$this->print_json_result();
							}
							break;
						case 'SMTP_SECURE':
							if($data_value === "SSL" || $data_value === "TLS"){
								$value_to_store = $data_value;
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Der Sicherheitstyp muss TLS oder SSL sein.');
								$this->print_json_result();
							} break;
						case 'SMTP_PORT': {
							$data_port = filter_var($data_value, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "max_range"=>99999, "default"=>0)));
							if($data_port > 0){
								$value_to_store = $data_port;
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Der SMTP Port muss eine ganze Zahl zwischen 1 und 99999 sein.');
								$this->print_json_result();
							}
						}	break;
						case 'MAIL_FROM':
							if(isValidEmail($data_value) || $data_value === ''){
								$value_to_store = $data_value;
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Der Wert für den Mailabsender ist keine gültige E-Mailadresse.');
								$this->print_json_result();
							}
							break;
						case 'MAIL_FROM_ALIAS':
							if(isValidMailName($data_value) || $data_value === ''){
								$value_to_store = $data_value;
								$valid = true;
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Der Aliasname für den Mailabsender enthält ungültige Zeichen.');
								$this->print_json_result();
							}
							break;
						default:
							$this->access_not_found();
							break;
					}
					if ($valid) {
						if ($settings[$data_key] == $value_to_store) {
							$this->json_result = array('success' => false, 'eMsg' => 'Es wurde kein Wert geändert.');
						} else {
							if($this->db->setSettings($data_key, $value_to_store)){
								$this->db->setSettings('LAST_TESTMAIL', 0);
								$this->json_result = array('success' => true, 'msg' => 'E-Maileinstellungen erfolgreich aktualisiert.', 'val' => (($data_key != 'SMTP_PASSWORD')? $value_to_store : ''));
							} else {
								$this->json_result = array('success' => false, 'eMsg' => 'Unbekannter DB Fehler aufgetreten.');
								error_log('DB Error on Mail Settings update. Key: "' . $data_key . '" Value: "' . $value_to_store);
							}
						}
						$this->print_json_result();
					}
				}
	
			} else {
				$this->access_denied_json();
			}
		} else {
			$this->access_denied_json();
		}
	}
	
	/**
	 * handles json request: admin update email settings
	 */
	public function silmph_send_testmail(){
		$settings=$this->db->getSettings();
		if(( MAIL_TEST_TIMEOUT * 60 ) - (time() - $settings['LAST_TESTMAIL']) > 0){
			$this->json_result = array('success' => false, 'eMsg' => 'In den letzten '.MAIL_TEST_TIMEOUT.' Minuten wurde bereits eine Test-EMail versendet. Prüfen Sie bitte Ihren Posteingang.');
		} else {
			$mailer = new MailHandler();
			$initOk = $mailer->init($settings);
			$mail_address = '';
			if($initOk){
				if ($_SESSION['USER_SET']['email'] != '' ) $mail_address = $_SESSION['USER_SET']['email'];
				else if ($settings['MAIL_FROM'] != '' ) $mail_address = $settings['MAIL_FROM'];
				$mailer->mail->addAddress($mail_address);
				$mailer->mail->Subject = "Testmail - ".BASE_TITLE;
					
				$mailer->bindVariables(array(
					'name' 			=> $_SESSION['USER_SET']['username'],
					'time' 			=> date_create()->format('H:i d.m.Y'),
					'base_url' 		=> BASE_URL
				));
			}
			$mailer->setTemplate('test_mail');
			if($mailer->send(false, false, true, true)){
				if (!$this->db->setSettings('LAST_TESTMAIL', time())){
					ob_start();
					debug_print_backtrace(0, 5);
					$error_trace = ob_get_clean();
					error_log("Es trat ein Datenbankfehler auf. FEHLER: ".$this->db->getError()." \nStacktrace:\n" . sprintf($error_trace));
				}
				$this->json_result = array('success' => true, 'msg' => "Eine Test-Mail wurde an '$mail_address' versendet.");
			} else {
				$this->json_result = array('success' => false, 'eMsg' => 'Es konnte keine Mail versendet werden. Prüfen Sie bitte die Konfiguration. '.((isset($mailer->mail) && isset($mailer->mail->ErrorInfo))? $mailer->mail->ErrorInfo: '' ));
			}
		}
		$this->print_json_result();
	}
}

$jh = currentJsonHandler::getInstance();

if (isset($_POST['mfunction'])&& $_POST['mfunction']==='function_name'){
	//TODO maybe create and call input validator
	$jh->call('function_name');
} else {
	$jh->call('not_found');
}
?>
