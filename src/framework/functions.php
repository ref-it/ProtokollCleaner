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
if (!function_exists('checkUserPermission')){
	/**
	 * check if user has requested permission
	 * @param string $requested_permission
	 * @return boolean
	 */
	function checkUserPermission( $requested_permission ){
		if (!isset($_SESSION['USER_PERMISSIONS']) || !is_array($_SESSION['USER_PERMISSIONS'])){
			return false;
		}
		return in_array($requested_permission, $_SESSION['USER_PERMISSIONS']);
	}
}

/* VALIDATORS ---------------------------------------------------------------------- */
if (!function_exists('isValidEmail')){
	/**
	 * check if string is a valid email address
	 * @param string $email
	 * @return boolean
	 */
	function isValidEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL)
		&& preg_match('/@.+\./', $email);
	}
}

if (!function_exists('endsWith')){
	/**
	 * check if string is ends with other string
	 * @param string $haystack
	 * @param array|string $needle
	 * @param null|string $needleprefix
	 * @return boolean
	 */
	function endsWith($haystack, $needle, $needleprefix = null)
	{
		if (is_array($needle)){
			foreach ($needle as $sub){
				$n=(($needleprefix)?$needleprefix:'').$sub;
				if (substr($haystack, -strlen($n))===$n) {
					return true;
				}
			}
			return false;
		} else if (strlen($needle) == 0){
			return true;
		} else {
			return substr($haystack, -strlen($needle))===$needle;
		}
	}
}

// 0/false no match, 1 -> ip address, 2 -> hostname, 3 -> hostname idn format
if (!function_exists('isValidDomain')){
	/**
	 * check if string is a valid hostname or ip address (supports ipv4 and ipv6)
	 * 		0 -> no match
	 * 		1 -> ip address 
	 * 		2 -> hostname 
	 * 		3 -> hostname idn format
	 * @param string $hostname
	 * @return integer ==>  0/false no match, 1 -> ip address, 2 -> hostname, 3 -> hostname idn format
	 */
	function isValidDomain($hostname){
		if (isValidIP($hostname)){
			return 1;
		} else if ( preg_match("/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/", $hostname) &&
			( (version_compare(PHP_VERSION, '7.0.0') >= 0) && filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ||
			  (version_compare(PHP_VERSION, '7.0.0') < 0) ) ) {
			return 2;
		} else {
			$value_idn = idn_to_ascii($hostname);
			if ( preg_match("/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/", $value_idn) &&
				( (version_compare(PHP_VERSION, '7.0.0') >= 0) && filter_var($value_idn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)  ||
			    (version_compare(PHP_VERSION, '7.0.0') < 0) ) ) {
				return 3;
			} else {
				return 0;
			}
		}
	}
}

if (!function_exists('isValidIP')){
	/**
	 * check if string is a valid ip address (supports ipv4 and ipv6)
	 * @param string $ipadr
	 * @param $recursive if true also allowes IP address with surrounding brackets []
	 * @return boolean
	 */
	function isValidIP( $ipadr, $recursive = true) {
		if ( preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$|^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/', $ipadr)) {
			return true;
		} else {
			if ($recursive && strlen($ipadr) > 2 && $ipadr[0] == '[' && $ipadr[strlen($ipadr)] == ']'){
				return isValidIP(substr($ipadr, 1, -1), false);
			} else {
				return false;
			}
		}
	}
}

if (!function_exists('isValidMailUsername')){
	/**
	 * check if string is a valid username for mail login
	 * @param string $name
	 * @return boolean
	 */
	function isValidMailUsername($name){
		if (preg_match( '/^[a-zA-Z0-9]+[a-zA-Z0-9\-_.]*[a-zA-Z0-9]+$/', $name)) {
			if (strlen($name) < 64){
				return true;
			}
			return false;
		}
		return false;
	}
}
if (!function_exists('isValidMailName')){
	/**
	 * check if string is a valid mail alias name
	 * localized for Germany
	 * @param string $name
	 * @return boolean
	 */
	function isValidMailName($name){
		$name = str_replace("&amp;", "&", $name);
		if (preg_match("/^[a-zA-Z0-9äöüÄÖÜß]+[a-zA-Z0-9\-_&#\/ .äöüÄÖÜß]*[a-zA-Z0-9äöüÄÖÜß]+$/", $name )) {
			if (strlen($name) < 64){
				return true;
			}
			return false;
		}
		return false;
	}
}
/* SECURE KEY FUNCTIONS ---------------------------------------------------------------------- */
if (!function_exists('generateRandomString')){
	/**
	 * generates secure random hex string of length: 2*$length
	 * @param integer $length 0.5 string length
	 * @return NULL|string
	 */
	function generateRandomString($length) {
		if (!is_int($length)){
			throwException('Invalid argument type. Integer expected.');
			return null;
		}
		if (version_compare(PHP_VERSION, '7.0.0') >= 0){
			return bin2hex(random_bytes($length));
		} else {
			return bin2hex(openssl_random_pseudo_bytes($length));
		}
	}
}


//encrypt data with secret key
//https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
if (!function_exists('silmph_encrypt_key')) {
	/**
	 * encrypt string with key
	 * @param string $data
	 * @param string $keyAscii
	 * @return string encrypted string
	 */
	function silmph_encrypt_key ($data, $keyAscii){
		require_once(dirname(__FILE__).'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\Key::loadFromAsciiSafeString($keyAscii);
		$ciphertext = Defuse\Crypto\Crypto::encrypt($data, $key);
		return $ciphertext;
	}
}

//decrypt data with secret key
//https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
if (!function_exists('silmph_decrypt_key')) {
	/**
	 * decrypt string with key
	 * @param string $ciphertext
	 * @param string $keyAscii
	 * @return string|false decrypted string | false if cipher was manipulated
	 */
	function silmph_decrypt_key ($ciphertext, $keyAscii){
		require_once(dirname(__FILE__).'/external_libraries/crypto/defuse-crypto.phar');
		$key = Defuse\Crypto\Key::loadFromAsciiSafeString($keyAscii);
		try {
			$data = Defuse\Crypto\Crypto::decrypt($ciphertext, $key);
			return $data;
		} catch (Defuse\Crypto\WrongKeyOrModifiedCiphertextException $ex) {
			// An attack! Either the wrong key was loaded, or the ciphertext has
			// changed since it was created -- either corrupted in the database or
			// intentionally modified by Eve trying to carry out an attack.
			return false;
		}
	}
}
