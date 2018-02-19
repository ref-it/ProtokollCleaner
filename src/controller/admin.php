<?php
/**
 * CONTROLLER Admin Controller
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        controller
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (SYSBASE . '/framework/MotherController.php');

class AdminController extends MotherController {
	
	/**
	 * 
	 * @param Database $db
	 * @param AuthHandler $auth
	 * @param Template $template
	 */
	function __construct($db, $auth, $template){
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * ACTION admin
	 */
	public function admin(){
		$this->t->setTitlePrefix('Admin');
		$this->t->appendJsLink('admin.js');
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__);
		$this->t->printPageFooter();
	}
	
	private static $mail_validators = [
		'SMTP_HOST' 	=> ['value' => ['domain', 'empty']],
		'SMTP_USER' 	=> ['value' => ['regex', 
			'pattern' => '/^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*[a-zA-Z0-9]+$/', 
			'maxlength' => 64,
			'error' => 'Kein gültiger Nutzername für den SMTP Server.']],
		'MAIL_PASSWORD' => ['value' => ['password', 
			'encrypt', 
			'minlength' => 4, 
			'empty']],
		'SMTP_SECURE' 	=> ['value' => ['regex', 
			'pattern' => '/SSL|TLS/',
			'upper',
			'error' => 'Der Sicherheitstyp muss TLS oder SSL sein.' ]],
		'SMTP_PORT' 	=> ['value' => ['integer', 
			'min' => 0, 
			'max' => 65535,
			'error' => 'Der SMTP Port muss eine ganze Zahl zwischen 1 und 65535 sein.']],
		'MAIL_FROM' 	=> ['value' => ['mail', 'empty']],
		'MAIL_FROM_ALIAS' => ['value' => ['regex', 
			'pattern' => "/^[a-zA-Z0-9äöüÄÖÜß]+[a-zA-Z0-9\-_&#\/ .äöüÄÖÜß]*[a-zA-Z0-9äöüÄÖÜß]+$/",
			'maxlength' => 64,
			'error' => 'Der Aliasname für den Mailabsender enthält ungültige Zeichen.']],
	];
	
	/**
	 * ACTION JSON save mail settings
	 */
	public function mail_update_setting(){
		$vali = new Validator();
		$vali->validatePostGroup(self::$mail_validators, 'data', true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = array('success' => false, 'eMsg' => $vali->getLastErrorMsg());
				$this->print_json_result();
			}
		} else {
			$filtered = $vali->getFiltered();
			$data_key = trim(array_keys($filtered)[0]);
			$data_value = trim(array_values($filtered)[0]['value']);
			
			//lookup customers
			$settings = $this->db->getSettings();
			if (!array_key_exists($data_key, $settings)){
				$this->json_not_found();
			} else {
				if ($settings[$data_key] == $data_value) {
					$this->json_result = array('success' => false, 'eMsg' => 'Es wurde kein Wert geändert.');
				} else {
					if($this->db->setSettings($data_key, $data_value)){
						$this->db->setSettings('LAST_TESTMAIL', 0);
						$this->json_result = array('success' => true, 'msg' => 'E-Maileinstellungen erfolgreich aktualisiert.', 'val' => (($data_key != 'SMTP_PASSWORD')? $data_value : ''));
					} else {
						$this->json_result = array('success' => false, 'eMsg' => 'Unbekannter DB Fehler aufgetreten.');
						error_log('DB Error on Mail Settings update. Key: "' . addslashes($data_key) . '" Value: "' . $data_value);
					}
				}
				$this->print_json_result();
			}
		}
	}
	
	public function mail_testmessage(){
		$settings=$this->db->getSettings();
		if(( MAIL_TEST_TIMEOUT * 60 ) - (time() - $settings['LAST_TESTMAIL']) > 0){
			$this->json_result = array('success' => false, 'eMsg' => 'In den letzten '.MAIL_TEST_TIMEOUT.' Minuten wurde bereits eine Test-EMail versendet. Prüfen Sie bitte Ihren Posteingang.');
		} else {
			$mailer = new MailHandler();
			$initOk = $mailer->init($settings);
			$mail_address = '';
			if($initOk){
				if ($this->auth->getUserMail() != '' ) $mail_address = $this->auth->getUserMail();
				else if ($settings['MAIL_FROM'] != '' ) $mail_address = $settings['MAIL_FROM'];
				$mailer->mail->addAddress($mail_address);
				$mailer->mail->Subject = "Testmail - ".BASE_TITLE;
					
				$mailer->bindVariables(array(
					'name' 			=> $this->auth->getUserFullName().' ('.$this->auth->getUsername().')',
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