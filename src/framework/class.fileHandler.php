<?php
use SILMPH\File;
/**
 * CONTROLLER FileHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			08.05.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

require_once (SYSBASE . '/framework/class._MotherController.php');
require_once (SYSBASE . '/framework/lib/class.file.php');

class FileHandler extends MotherController {

	private static $mimeMap = [

	];

	/**
	 * constructor
	 * @param Database $db
	 */
	function __construct($db){
		parent::__construct($db, NULL, NULL);
	}

	//functions
	
	public static function hasModXSendfile() {
		if (UPLOAD_HAS_MOD_XSENDFILE){
			return true;
		}
		if (function_exists ( 'apache_get_modules' )){
			$modlist = apache_get_modules();
			if (in_array('', $modlist, true)){
				return true;
			}
		}
		return false;
	}

	/**
	 * recursively delete directories and containing files
	 * may echo error messages
	 * @param string $dir directory path
	 * @return boolean success
	 */
	public static function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			if (is_dir("$dir/$file")){
				self::delTree("$dir/$file");
			} else {
				$res = unlink("$dir/$file");
				if (!$res) echo '<strong>ERROR on unlinking file</strong>: ' . "$dir/$file<br>";
			}
		}
		$res = rmdir($dir);
		if (!$res) echo '<strong>ERROR on removing directory</strong>: ' . "$dir<br>";
		return $res;
	}

	/**
	 * create directory if it does not exists
	 * allows recursive directory creation
	 * @param string $dir directory path
	 * @return boolean success
	 */
	public static function checkCreateDirectory($dir) {
		if (!is_dir($dir)) {
			if (!mkdir($dir, 0755, true)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * prettify filesize
	 * calculate short filesize from byte file size
	 * @param numeric $filesize in bytes
	 * @return string NaN| prettyfied filesize
	 */
	public static function formatFilesize($filesize){
		$unit = array('Byte','KB','MB','GB','TB','PB');
		$standard = 1024;
		if(is_numeric($filesize)){
			$count = 0;
			while(($filesize / $standard) >= 0.9){
				$filesize = $filesize / $standard;
				$count++;
			}
			return round($filesize,2) .' '. $unit[$count];
		} else {
			return 'NaN';
		}
	}

	/**
	 * return fileextension from mime type
	 * @param string $mime
	 */
	public static function extensionFromMime($mime){
		if (isset(self::$mimeMap[$mime])){
			return self::$mimeMap[$mime];
		}
		return false;
	}
}