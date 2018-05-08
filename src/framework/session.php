<?php
/**
 * FRAMEWORK session
 * handles user session
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

$auth = NULL;
$hasAuth = false;

function setAuthHandler(){
	$method = strtoupper($_SERVER['REQUEST_METHOD']);
	$parsed_url = parse_url($_SERVER['REQUEST_URI']);//URI zerlegen
	
	$path = (isset($parsed_url['path']))? trim($parsed_url['path'],'/'):'';
	if ($path == '') $path = '/';
	include (dirname(__FILE__).'/config/config.router.php');
	
	global $auth;
	global $hasAuth;
	
	if (isset($cronRoutes[$method])
		&& isset($cronRoutes[$method][$path])){
		require_once (dirname(__FILE__)."/class.AuthBasicHandler.php");
		$auth = BasicAuthHandler::getInstance(empty($cronRoutes[$method][$path][0]));
		$hasAuth = (empty($cronRoutes[$method][$path][0]))? true : $auth->hasGroup('cron');
	} else {
		if (DEBUG >= 1 && DEBUG_USE_DUMMY_LOGIN){
			require_once (dirname(__FILE__)."/class.AuthDummyHandler.php");
		} else {
			require_once (dirname(__FILE__)."/class.AuthSamlHandler.php");
			$conf = [
				"AuthHandler" => [
					"SIMPLESAMLDIR" => SAML_SIMPLESAMLDIR,
					"SIMPLESAMLAUTHSOURCE" => SAML_SIMPLESAMLAUTHSOURCE,
					"AUTHGROUP" => SAML_AUTHGROUP,
					"ADMINGROUP" => SAML_ADMINGROUP,
				],
			];
			Singleton::configureAll($conf);
		}
		$auth = AuthHandler::getInstance();
		$hasAuth = $auth->hasGroup(SIMPLESAML_ACCESS_GROUP);
	}
}
setAuthHandler();

$db = null;

if (!$hasAuth){
	$t = new Template();
	http_response_code (404);
	$t->setTitlePrefix('404 - Seite nicht gefunden');
	$t->printPageHeader();
	include (SYSBASE."/templates/".TEMPLATE."/404.phtml");
	$t->printPageFooter();
	die();
}

if (!isset($_SESSION['SILMPH']['FORM_CHALLENGE_NAME'])){
	$_SESSION['SILMPH']['FORM_CHALLENGE_NAME'] = generateRandomString(10);
	$_SESSION['SILMPH']['FORM_CHALLENGE_VALUE'] = generateRandomString(22);
}

if($db === NULL) $db = new DatabaseModel();

$router = Router::getInstance();
$router->route();

if ($db!=NULL){
	$db->close();
	$db = NULL;
}
