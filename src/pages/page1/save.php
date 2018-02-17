<?php
/**
 * AJAX HANFLER admin
 * Application starting point
 *
 * @package         TODO
 * @category        script
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
// ===== load framework =====
if (!file_exists ( dirname(__FILE__, 4).'/config.php' )){
	echo 'No configuration file found!. Please create and edit "config.php".';
	die();
}
require_once (dirname(__FILE__, 4).'/config.php');

class currentJsonHandler extends jsonHandler {
	function __construct(){
		parent::__construct();
		$this->functionAccess['function_name2'] = 'permission1';
	}
	/**
	 * handles json request: dummy ajax handler
	 */
	public function silmph_function_name2(){
		if (isset($_POST['data'])){
			//do something
			//maybe access database
			//$this->db->doSomething();
			$this->json_result = array('success' => true, 'msg' => 'ok', 'id'=> 4, 'name' => 'return text');
			$this->json_result = array('success' => false, 'eMsg' => 'Out Error Message');
				
			$this->print_json_result();
		
		} else {
			$this->access_denied_json();
		}
	}
}

$jh = currentJsonHandler::getInstance();

if (isset($_POST['mfunction'])&& $_POST['mfunction']==='function_name2'){
	//TODO maybe create and call input validator
	$jh->call('function_name2');
} else {
	$jh->call('not_found');
}
?>
