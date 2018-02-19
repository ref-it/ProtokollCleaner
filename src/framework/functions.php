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
