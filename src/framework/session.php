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
 
require_once (dirname(__FILE__)."/AuthHandler.php");

$auth = AuthHandler::getInstance();

$hasAuth = $auth->requireGroup(SIMPLESAML_ACCESS_GROUP);
$db = null;

if (!$hasAuth){
	$t = new template();
	http_response_code (404);
	$t->setTitlePrefix('404 - Seite nicht gefunden');
	$t->printPageHeader();
	include (SYSBASE."/templates/".TEMPLATE."/404.phtml");
	$t->printPageFooter();
	die();
}

if (!isset($_SESSION['SILMPH']['FORM_CHALLANGE_NAME'])){
	$_SESSION['SILMPH']['FORM_CHALLANGE_NAME'] = generateRandomString(10);
	$_SESSION['SILMPH']['FORM_CHALLANGE_VALUE'] = generateRandomString(22);
}

if($db === NULL) $db = new database();

$router = Router::getInstance();
$router->route();

if ($db!=NULL){
	$db->close();
	$db = NULL;
}
