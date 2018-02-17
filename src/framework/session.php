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
 
//create session
session_start();
$db = null;

//check IP and user agent
if(isset($_SESSION['CLIENT_IP']) && isset($_SESSION['CLIENT_AGENT'])){
	if ($_SESSION['CLIENT_IP'] != $_SERVER['REMOTE_ADDR'] || $_SESSION['CLIENT_AGENT'] != $_SERVER ['HTTP_USER_AGENT']){
		//die or reload page is IP isn't the same when session was created -> need new login
		session_destroy();
		session_start();
		header("Refresh: 0");
		die();
	}
} else {
	$_SESSION['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
	$_SESSION['CLIENT_AGENT'] = $_SERVER ['HTTP_USER_AGENT'];
}

if(!isset($_SESSION['USER_ID'])){
	$_SESSION['USER_ID'] = 0;
}

if(!isset($_SESSION['LAST_ACTION'])){
	$_SESSION['LAST_ACTION'] = time();
}

if(!isset($_SESSION['MESSAGES'])){
	$_SESSION['MESSAGES'] = array();
}

//check logout request
if ($_SESSION['USER_ID'] !== 0 && ( isset($_GET['logout']) || strpos($_SERVER['REQUEST_URI'], '&logout=1') !== false || strpos($_SERVER['REQUEST_URI'], '?logout=1') !== false )){
	session_destroy();
	session_start();
	$_SESSION['LOGOUT'] = array("Sie haben sich erfolgreich abgemeldet.", "SUCCESS");
	header('Location: '.BASE_URL . $_SERVER['PHP_SELF']);
	if($db !== NULL) $db->close();
	die();
}
//error handling
if (isset($_GET['page_error']) && $_GET['page_error'] === '404'){
	$t = new template();
	http_response_code (404);
	$t->setTitlePrefix('404 - Seite nicht gefunden');
	$t->printPageHeader();
	include (dirname(__FILE__)."/../templates/".TEMPLATE."/404.phtml");
	$t->printPageFooter();
	die();
} else if (isset($_GET['page_error']) && $_GET['page_error'] === '403'){
	$t = new template();
	http_response_code (403);
	$t->setTitlePrefix('403 - Zugriff verweigert');
	$t->printPageHeader();
	include (dirname(__FILE__)."/../templates/".TEMPLATE."/403.phtml");
	$t->printPageFooter();
	die();
}

//load login if userid is null
if ($_SESSION['USER_ID'] === 0){
	if($db === NULL) $db = new database();
	$_SESSION['LAST_ACTION'] = time();
	require_once (dirname(__FILE__)."/login.php");
}
if ($_SESSION['USER_ID'] !== 0){	
	//check permission set every PERMISSION_CHECKUP
	$_SESSION['USER_PERMISSION_COUNTER'] = ($_SESSION['USER_PERMISSION_COUNTER'] + 1)%PERMISSION_CHECKUP;
	if ($_SESSION['USER_PERMISSION_COUNTER'] === 0){
		if(!password_verify(count($_SESSION['USER_PERMISSIONS']) . implode('|', $_SESSION['USER_PERMISSIONS']) , $_SESSION['USER_PERMISSION_CHECK'])) {
			session_destroy();
			session_start();
			header("Location: " . BASE_URL);
			if($db !== NULL) $db->close();
			die('SECURITY BREACH');
		}
	}
	//sessiontimeout
	if(( SESSION_EXPIRES_AFTER * 60 ) - (time() - $_SESSION['LAST_ACTION']) < 0){
		session_destroy();
		session_start();
		$_SESSION['LOGOUT'] = array("Sie wurden aufgrund zu langer Inaktivität abgemeldet.", "INFO");
		header('Location: '.BASE_URL . $_SERVER['PHP_SELF']);
		if($db !== NULL) $db->close();
		die();
	} else {
		$_SESSION['LAST_ACTION'] = time();
	}
	//regenerate session id periodically
	if(!isset($_SESSION['REGENERATE_TIME'])){
		$_SESSION['REGENERATE_TIME'] = time();
	} else if(time() - $_SESSION['REGENERATE_TIME'] > 1800) {
		//session started more than 30 minutes ago (and still used)
		session_regenerate_id(true);
		$_SESSION['REGENERATE_TIME'] = time();
	}
	if($db === NULL) $db = new database();
} else {
	if($db !== NULL) $db->close();
	die();
}
