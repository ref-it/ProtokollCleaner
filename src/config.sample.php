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
define("SAML_SIMPLESAMLDIR" , dirname(__FILE__,4) . "/simplesamlphp");
define("SAML_SIMPLESAMLAUTHSOURCE" , "");
define("SAML_AUTHGROUP" , "");
define("SAML_ADMINGROUP" , "");

// ===== Wiki Settings =====
define('WIKI_URL', 'https://wiki.stura.tu-ilmenau.de');
define('WIKI_XMLRPX_PATH', '/lib/exe/xmlrpc.php');
define('WIKI_USER', 'wikiuser_xxx');
define('WIKI_PASSWORD', 'wikipassword_xxx');

// ===== Security Settings =====
define('PW_PEPPER', 'XXXXX_PLEASECHANGE_TO_CRYPTIC_LETTERS_a-zA-Z0-9_MIN_LENGTH_32_XXXXX');
define('ENABLE_ADMIN_INSTALL', false);
define('DEBUG', false); //Level = false / 0 => disabled || 1 => Basic debug information || 2 => additional information || 3 => all
define('DEBUG_USE_DUMMY_LOGIN', false);

// ===== CRON SETTINGS =====
define('CRON_USERMAP', [
	'cronuser' => [
		'password' => '1234', //
		'displayName' => 'Cron User',
		'mail' => 'ref-it@tu-ilmenau.de',
		'groups' => ['cron', 'croninfo', 'cronmail', 'cronwiki'],
		'eduPersonPrincipalName' => ['cronuser'],
	]
]);

// ===== UPLOAD SETTINGS =====
// DATABASE or FILESYSTEM storage
// Database Pros
// - good if recoverability is critical | gut wenn Wiederherstellbarkeit kritisch
// - backups with database, only new only need
// Fileysystem Pros
// - on defect systems restoring the online system is way faster if no files need to pushed back in database
// - easily run separate processes that catalog document metadata, perform virus scanning, perform keyword indexing
// - use storages wich uses compression, encryption, etc
// - no need for interpreter (PHP) to load file into ram
define('UPLOAD_TARGET_DATABASE', true); // true|false store into
define('UPLOAD_USE_DISK_CACHE', true);  // if DATABASE storage enabled , use filesystem as cache
define('UPLOAD_MULTIFILE_BREAOK_ON_ERROR', true); //if there are multiple files on Upload and an error occures: FALSE -> upload files with no errors, TRUE upload no file
define('UPLOAD_MAX_MULTIPLE_FILES', 1); // how many files can be uploaded at once
define('UPLOAD_DISK_PATH', dirname(__FILE__).'/filestorage'); // path to DATABASE filecache or FILESYSTEM storage - no '/' at the ends
define('UPLOAD_MAX_SIZE', 41943215); //in bytes - also check DB BLOB max size and php upload size limit in php.ini
define('UPLOAD_PROHIBITED_EXTENSIONS', 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm,forbidden');
define('UPLOAD_HAS_MOD_XSENDFILE', false); // if xmodsendfile detection fails, but it is installed on server, enable here


// ===== DO NOT CHANGE THIS =====
require_once (dirname(__FILE__)."/framework/init.php");
// end of file -------------

