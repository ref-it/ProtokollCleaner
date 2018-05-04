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
 
require_once (dirname(__FILE__)."/AuthHandler.php");

$auth = AuthHandler::getInstance();

$hasAuth = $auth->requireGroup(SIMPLESAML_ACCESS_GROUP);
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
