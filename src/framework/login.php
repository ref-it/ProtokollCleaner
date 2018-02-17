<?php
/**
 * CONFIG FILE ProtocolHelper
 * Application initialisation
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        configuration
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

//return access denied if no user was set
$showLoginArea = true;
$showForgetPassword = false;
$showResetPassword = false;
$typed_username = '';
$typed_password = '';
$typed_password2 = '';
$typed_email = '';
$typed_challenge = '';
$typed_uid = '';
$break_login = false;
$_SESSION['LOGIN_USER_NAME'] = 'username';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
	$showLoginArea = false;
	//set initial session login form values
	if (!isset($_SESSION['LOGIN_USER_NAME']) || !isset($_SESSION['LOGIN_PASS_NAME'])){
		$_SESSION['LOGIN_PASS_NAME'] = generateRandomString(16);
	}
	if(!isset($_GET['login']) && !isset($_GET['register'])&& !isset($_GET['reset_password']) && isset($_GET['forgot_password'])){
		$showForgetPassword = true;
		if( !isset($_POST[$_SESSION['LOGIN_PASS_NAME']])){
			$_SESSION['MESSAGES'][] = array('Die Übermittelten Daten sind leider abgelaufen.', 'INFO');
		} else {
			$typed_email = trim(strip_tags($_POST[$_SESSION['LOGIN_PASS_NAME']]));
			$typed_email = (strlen($typed_email) > 250) ? substr($typed_email, 0, 250) : $typed_email;
			
			if($typed_email != $_POST[$_SESSION['LOGIN_PASS_NAME']]){
				$_SESSION['MESSAGES'][] = array('Die E-Mailadresse enthält entweder unerlaubte Zeichen oder ist zu lang.', 'INFO');
			} else if(strlen($typed_email) < 5){
				$_SESSION['MESSAGES'][] = array('Die E-Mailadresse muss aus mindestens 5 Zeichen bestehen.', 'INFO');
			} else if (!isValidEmail($typed_email)){
				$_SESSION['MESSAGES'][] = array('Die E-Mailadresse entspricht keinem gültigen Format.', 'INFO');
			} else {
				$user = $db->getUserByEmail($typed_email);
				if (!$user){
					$_SESSION['MESSAGES'][] = array('Die angegebene E-Mailadresse konnte nicht gefunden werden.', 'INFO');
				} else {
					// check last timestamp
					if($user['password_reset_challenge'] != '' && ( FORGOT_PASSWORD_TIMEOUT * 60 ) - (time() - $user['last_password_reset_request']) > 0){
						$_SESSION['MESSAGES'][] = array('Für diese E-Mailadresse wurde innerhalb der letzten '.FORGOT_PASSWORD_TIMEOUT.' Minuten bereits ein neues Passwort angefordert.', 'INFO');
					} else {
						// generate challenge and store into db
						$challenge = generateRandomString(256);
						
						$settings = $db->getSettings();
						// generate Mail
						$mailer = new SilmphMailer();
						$initOk = $mailer->init($settings);
						if($initOk){ 
							if ($user['alias'] != ''){
								$mailer->mail->addAddress($user['email'], $user['alias']);
							} else {
								$mailer->mail->addAddress($user['email']);
							}
							$mailer->mail->Subject = "Passwort zurücksetzten angefordert - ".BASE_TITLE;
							
							$mailer->bindVariables(array(
								'name' 			=> $user['alias'],
								'challenge' 	=> $challenge,
								'time' 			=> date_create()->format('H:i d.m.Y'),
								'valid_until' 	=> date_create()->add(new DateInterval('PT'.FORGOT_PASSWORD_TIMEOUT.'M'))->format('H:i d.m.Y'),
								'user_id' 		=> $user['id'],
								'base_url' 		=> BASE_URL
							));
						}
						$mailer->setTemplate('password_request');
						if($mailer->send(false, true, true)){
							if (!$db->setUserMailChallange( $user['id'], $challenge)){
								ob_start();
								debug_print_backtrace(0, 5);
								$error_trace = ob_get_clean();
								error_log("Es trat ein Datenbankfehler auf. FEHLER: ".$db->getError()." \nStacktrace:\n" . sprintf($error_trace));
							}
							$_SESSION['MESSAGES'][] = array('Eine E-Mail zum zurücksetzten des Passwortes wurde versendet.', 'SUCCESS');
						}
					}
				}
			}
		}
	} else if(!isset($_GET['login']) && !isset($_GET['register'])&& !isset($_GET['forgot_password']) && isset($_GET['reset_password']) &&
	   isset($_POST['uid'])  && isset($_POST['challenge']) && isset($_POST[$_SESSION['LOGIN_PASS_NAME']]) && isset($_POST[$_SESSION['LOGIN_PASS_NAME'].'2']) ){
		//test get parameter
		$data_id = filter_var($_POST['uid'], FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "default"=>0)));
		$reqChallenge = preg_replace("/[^a-f0-9|]*/", "", $_POST['challenge']);
		$typed_password = preg_replace("/[^a-zA-Z0-9\-_*+!§.,:;+*%\/\\\[\]?|]*/", "", $_POST[$_SESSION['LOGIN_PASS_NAME']]);
		$typed_password = (strlen($typed_password) > 250) ? substr($typed_password, 0, 250) : $typed_password;
		$typed_password2 = preg_replace("/[^a-zA-Z0-9\-_*+!§.,:;+*%\/\\\[\]?|]*/", "", $_POST[$_SESSION['LOGIN_PASS_NAME'].'2']);
	
		$reset_valid = false;
		$tmpUser = null;
		if ($data_id > 0 && strlen($reqChallenge) === 512){
			$tmpUser = $db->getUserById($data_id);
			//user found
			if($tmpUser){
				//request was set and parameter matches
				if ($tmpUser['password_reset_challenge'] != '' &&
					$tmpUser['last_password_reset_request'] != 0 &&
					$tmpUser['email'] != ''&&
					$tmpUser['password_reset_challenge'] === $reqChallenge){
					//request not timed out jet
					if(( FORGOT_PASSWORD_TIMEOUT * 60 ) - (time() - $tmpUser['last_password_reset_request']) >= 0){
						$reset_valid = true;
						$typed_challenge = $reqChallenge;
						$typed_uid = $tmpUser['id'];
						$showLoginArea = false;
						$showForgetPassword = false;
						$showResetPassword = true;
					}
				}
			}
			
		}
		if ($reset_valid){
			if(strlen($typed_password) < MIN_PASSWORD_LENGTH || strlen($typed_password) < 3){
				$_SESSION['MESSAGES'][] = array('Das Passwort muss mindestens '.MIN_PASSWORD_LENGTH.' Zeichen enthalten.', 'INFO');
				$reset_valid = false;
			}
			if($typed_password != $_POST[$_SESSION['LOGIN_PASS_NAME']]){
				$_SESSION['MESSAGES'][] = array('Das Passwort darf nur alphanumerischen Zeichen und folgende Sonderzeichen enthalten: +\-*%!?§_.,:;/\[]?|', 'INFO');
				$reset_valid = false;
			}
			if ($typed_password != $typed_password2){
				$_SESSION['MESSAGES'][] = array('Die beiden Passwörter müssen übereinstimmen.', 'INFO');
				$reset_valid = false;
			}
		} else { //reset typed vars
			$typed_password = '';
			$typed_password2 = '';
		}
		if ($reset_valid){
			$password_hash = password_hash($typed_password . PW_PEPPER, PASSWORD_DEFAULT);
			$ret = $db->updateUserPw( $tmpUser['id'], $password_hash);
			if ($ret) {
				$_SESSION['MESSAGES'][] = array('Das Passwort wurde erfolgreich zurückgesetzt.', 'SUCCESS');
				
			} else {
				$_SESSION['MESSAGES'][] = array('Das Passwort konnte nicht zurückgesetzt werden. Möglicherweise ist der Link abgelaufen.', 'INFO');
			}
			//reset typed vars + show login
			$showLoginArea = true;
			$showForgetPassword = false;
			$showResetPassword = false;
			$typed_password = '';
			$typed_password2 = '';
		}
	} else {
		//check if login is requested and parameter are set
		if(!isset($_POST[$_SESSION['LOGIN_USER_NAME']]) || !isset($_POST[$_SESSION['LOGIN_PASS_NAME']])){
			echo json_encode(array('success' => false, 'eMsg' => 'Access denied. Login First.'), JSON_HEX_QUOT | JSON_HEX_TAG);
			$db->close();
			die();
		} else {
			$typed_username = trim(preg_replace("/^[^a-zA-Z0-9]+|[^a-zA-Z0-9\-_]*|[^a-zA-Z0-9]+$/", "", $_POST[$_SESSION['LOGIN_USER_NAME']]));
			$typed_username = (strlen($typed_username) > 250) ? substr($typed_username, 0, 250) : $typed_username;
			$typed_password = preg_replace("/[^a-zA-Z0-9\-_*+!§.,:;+*%\/\\\[\]?|]*/", "", $_POST[$_SESSION['LOGIN_PASS_NAME']]);
			$typed_password = (strlen($typed_password) > 250) ? substr($typed_password, 0, 250) : $typed_password;
			if (defined('ENABLE_ADMIN_INSTALL') && ENABLE_ADMIN_INSTALL && isset($_GET['register']) && isset($_POST[$_SESSION['LOGIN_PASS_NAME'].'2'])){
				$typed_password2 = $_POST[$_SESSION['LOGIN_PASS_NAME'].'2'];
				if(strlen($typed_username) <= 2){
					$_SESSION['MESSAGES'][] = array('Der Nutzername muss mindestens 3 Zeichen enthalten.', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if($typed_username != $_POST[$_SESSION['LOGIN_USER_NAME']]){
					$_SESSION['MESSAGES'][] = array('Der Nutzername muss mit einem alphanumerischen Zeichen beginnen und enden. Folgende Sonderzeichen sind innenstehend erlaubt: -_*+', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if(strlen($typed_password) < MIN_PASSWORD_LENGTH || strlen($typed_password) < 3){
					$_SESSION['MESSAGES'][] = array('Das Passwort muss mindestens '.MIN_PASSWORD_LENGTH.' Zeichen enthalten.', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if ($typed_password != $typed_password2){
					$_SESSION['MESSAGES'][] = array('Die beiden Passwörter müssen übereinstimmen.', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if($typed_password != $_POST[$_SESSION['LOGIN_PASS_NAME']]){
					$_SESSION['MESSAGES'][] = array('Das Passwort darf nur alphanumerischen Zeichen und folgende Sonderzeichen enthalten: +\-*%!?§_.,:;/\[]?|', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if(!break_login){
					$userset = $db->getUserByName($typed_username);
					if ($userset){
						$_SESSION['MESSAGES'][] = array('Der gewählte Nutzername ist bereits vergeben.', 'INFO');
						$break_login = true;
						$showLoginArea = true;
					}
				}
				if(!$break_login){ //register user
					$password_hash = password_hash($typed_password . PW_PEPPER, PASSWORD_DEFAULT);
					$ret = $db->createUserFullPermission($typed_username, $password_hash);
					if ($ret) {
						$break_login = false; //login after register
						$_GET['login'] = '1';
					} else {
						$_SESSION['MESSAGES'][] = array('DB Error: '. $db->getError(), 'INFO');
					}
				}
			}
			if (!$break_login && isset($_GET['login'])) {
				if(strlen($typed_username) <= 2){
					$_SESSION['MESSAGES'][] = array('Der Nutzername muss mindestens 3 Zeichen enthalten.', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if(strlen($typed_password) <= 2){
					$_SESSION['MESSAGES'][] = array('Das Passwort darf leer sein.', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if(!$break_login && $typed_password != $_POST[$_SESSION['LOGIN_PASS_NAME']]){
					$_SESSION['MESSAGES'][] = array('Ungültiger Login. Nutzername oder Passwort sind nicht korrekt.', 'INFO');
					$break_login = true;
					$showLoginArea = true;
				}
				if (!$break_login){ //do login
					$userset = $db->getUserByName($typed_username);
					if (count($userset) != 0 && password_verify($typed_password . PW_PEPPER, $userset['password'])){
						$db->setUserLastLogin($userset['id']);
						$_SESSION['LAST_ACTION'] = time();
						$_SESSION['REGENERATE_TIME'] = time();
						$_SESSION['USER_ID'] = $userset['id'];
						$_SESSION['USER_SET'] = $userset;
						//prevent CSRF attacks
						$_SESSION['FORM_CHALLANGE_NAME'] = generateRandomString(10);
						$_SESSION['FORM_CHALLANGE_VALUE'] = generateRandomString(22);
						$_SESSION['USER_PERMISSIONS'] = $db->getUserPermissions($userset['id']);
						$_SESSION['USER_PERMISSIONS'][]="user";
						$_SESSION['USER_PERMISSION_CHECK'] = password_hash(count($_SESSION['USER_PERMISSIONS']) . implode('|', $_SESSION['USER_PERMISSIONS']), PASSWORD_DEFAULT);
						$_SESSION['USER_PERMISSION_COUNTER'] = 1;
						session_regenerate_id(true);
						header('Location: '.BASE_URL . $_SERVER['PHP_SELF']);
						$db->close();
						die();
					} else {
						$_SESSION['MESSAGES'][] = array('Ungültiger Login. Nutzername oder Passwort sind nicht korrekt.', 'INFO');
						$showLoginArea = true;
					}
				}
			} else if (!$break_login) {
				echo json_encode(array('success' => false, 'eMsg' => 'Access denied. Login First.'), JSON_HEX_QUOT | JSON_HEX_TAG);
				$db->close();
				die();
			}
		}
	}
} else {
	if(!isset($_GET['login']) && !isset($_GET['register'])&& !isset($_GET['reset_password']) && isset($_GET['forgot_password'])){
		$showLoginArea = false;
		$showForgetPassword = true;
		$showResetPassword = false;
	} else if(!isset($_GET['login']) && !isset($_GET['register'])&& !isset($_GET['forgot_password']) && 
		       isset($_GET['reset_password'])  && isset($_GET['id'])  && isset($_GET['challenge'])){
		//test get parameter
		$data_id = filter_var($_GET['id'], FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "default"=>0)));
		$reqChallenge = preg_replace("/[^a-f0-9|]*/", "", $_GET['challenge']);

		$reset_valid = false;
		if ($data_id > 0 && strlen($reqChallenge) === 512){
			$tmpUser = $db->getUserById($data_id);
			//user found
			if($tmpUser){
				//request was set and parameter matches
				if ($tmpUser['password_reset_challenge'] != '' && 
				  $tmpUser['last_password_reset_request'] != 0 && 
				  $tmpUser['email'] != ''&&
				  $tmpUser['password_reset_challenge'] === $reqChallenge){
					//request not timed out jet
					if(( FORGOT_PASSWORD_TIMEOUT * 60 ) - (time() - $tmpUser['last_password_reset_request']) >= 0){
						$reset_valid = true;
						$typed_challenge = $reqChallenge;
						$typed_uid = $tmpUser['id'];
					}
				}
			}
		}
		if ($reset_valid){
			$showLoginArea = false;
			$showForgetPassword = false;
			$showResetPassword = true;
		} else {
			$_SESSION['MESSAGES'][] = array('Das Passwort konnte nicht zurückgesetzt werden. Möglicherweise ist der Link abgelaufen.', 'INFO');
		}
	}
}
if($showLoginArea){
	$t = new template();
	$t->setTitlePrefix('Login');
	$t->printPageHeader();
	
	$_SESSION['LOGIN_PASS_NAME'] = generateRandomString(16);
	
	//show loginformular 
	include (dirname(__FILE__)."/../templates/".TEMPLATE."/login.phtml");
	
	$t->printPageFooter();
	$db->close();
	die();
}
if($showForgetPassword){
	$t = new template();
	$t->setTitlePrefix('Passwort Vergessen');
	$t->printPageHeader();
	
	$_SESSION['LOGIN_PASS_NAME'] = generateRandomString(16);
	
	//show loginformular
	include (dirname(__FILE__)."/../templates/".TEMPLATE."/forgot_password.phtml");

	$t->printPageFooter();
	$db->close();
	die();
}
if($showResetPassword){
	$t = new template();
	$t->setTitlePrefix('Passwort zurücksetzten');
	$t->printPageHeader();

	$_SESSION['LOGIN_PASS_NAME'] = generateRandomString(16);

	//show loginformular
	include (dirname(__FILE__)."/../templates/".TEMPLATE."/reset_password.phtml");

	$t->printPageFooter();
	$db->close();
	die();
}
