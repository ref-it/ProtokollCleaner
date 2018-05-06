<?php
/**
 * CONFIG FILE ProtocolHelper
 * Application config
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
 
/* ===============================================================
 *    RENAME THIS FILE TO >>> 'config.php'
 * ===============================================================
 */
 
// ===== DB SETTINGS =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbname_xxx');
define('DB_USERNAME', 'dbuser___xxx');
define('DB_CHARSET', 'utf8');
define('DB_PASSWORD', 'dbpassword_xxx');
define('TABLE_PREFIX', 'silmph__'); //_S_tura _ILM_enau _P_rotocol _H_elper

// ===== Base Settings =====
define('BASE_TITLE', 'ProtocolHelper');
define('BASE_URL', 'https://refit01.mollybee.de');
define('BASE_SUBDIRECTORY', '/'); // starts and ends with letter '/'

define('TIMEZONE', 'Europe/Berlin'); //Mögliche Werte: http://php.net/manual/de/timezones.php
define('TEMPLATE', 'stura');

// ===== SimpleSAML Settings & Konstants
define('SIMPLESAML_ACCESS_GROUP', 'stura');

// ===== Wiki Settings =====
define('WIKI_URL', 'https://wiki.stura.tu-ilmenau.de');
define('WIKI_XMLRPX_PATH', '/lib/exe/xmlrpc.php');
define('WIKI_USER', 'wikiuser_xxx');
define('WIKI_PASSWORD', 'wikipassword_xxx');

// ===== Security Settings =====
define('PW_PEPPER', 'XXXXX_PLEASECHANGE_TO_CRYPTIC_LETTERS_a-zA-Z0-9_MIN_LENGTH_32_XXXXX');
define('RENAME_FILES_ON_UPLOAD', 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm');
define('ENABLE_ADMIN_INSTALL', false);
define('DEBUG', false); //Level = false / 0 => disabled || 1 => Basic debug information || 2 => additional information || 3 => all

// ===== CRON SETTINGS =====
const CRON_USERMAP = [
	'cronuser' => [
		'password' => '1234', //
		'displayName' => 'Cron User',
		'mail' => 'ref-it@tu-ilmenau.de',
		'groups' => ['cron', 'croninfo', 'cronmail', 'cronwiki'],
		'eduPersonPrincipalName' => ['cronuser'],
	]
];

// ===== DO NOT CHANGE THIS =====
require_once (dirname(__FILE__)."/framework/init.php");
// end of file -------------

