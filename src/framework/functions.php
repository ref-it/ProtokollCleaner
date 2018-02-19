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
		$map = Router::getPermissionMap();
		if (isset($map[$requested_permission])){
			$auth = AuthHandler::getInstance();
			return $auth->requireGroup($map[$requested_permission]);
		} else {
			return false;
		}
	}
}

/* timing functions ----------------------------- */
/**
 * @param $str Name des Profiling Flags
 */
function prof_flag($str){
	global $prof_timing, $prof_names;
	$prof_timing[] = microtime(true);
	$prof_names[] = $str;
}
/**
 * Print all Profiling Flags from prof_flag()
 */
function prof_print(){
	global $prof_timing, $prof_names;
	$sum = 0;
	$size = count($prof_timing);
	$out = "";
	for ($i = 0; $i < $size - 1; $i++){
		$out .= "<b>{$prof_names[$i]}</b><br>";
		$sum += $prof_timing[$i + 1] - $prof_timing[$i];
		$out .= sprintf("&nbsp;&nbsp;&nbsp;%f<br>", $prof_timing[$i + 1] - $prof_timing[$i]);
	}
	$out .= "<b>{$prof_names[$size-1]}</b><br>";
	$out = '<div class="profiling-output noprint"><h3><i class="fa fw fa-angle-toggle"></i> Ladezeit: ' . sprintf("%f", $sum) . '</h3>' . $out;
	$out .= "</div>";
	echo $out;
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
