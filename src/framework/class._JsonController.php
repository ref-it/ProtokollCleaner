<?php
/**
 * FRAMEWORK JsonHandler
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
class JsonController {
	/**
	 * json result of the function
	 * @var array
	 */
	protected $json_result;
	
	// ================================================================================================
	
	/**
	 * private class constructor
	 * implements singleton pattern
	 */
	function __construct(){
	}
	
	/**
	 * dummy function for inheritance
	 * so mother controller may implements a translator pattern
	 */
	function translate ($in){
		return $in;
	}
	
	// ================================================================================================
	
	/**
	 * returns 403 access denied in json format
	 */
	function json_access_denied(){
		http_response_code (403);
		$this->json_result = array('success' => false, 'eMsg' => $this->translate('Access Denied.'));
		$this->print_json_result();
	}
	
	/**
	 * returns 404 not found in html format
	 * @param false|string $message (optional) error message
	 */
	function json_not_found($message = false){
		http_response_code (404);
		$this->json_result = array('success' => false, 'eMsg' => $this->translate( ($message)? $message : 'Page not Found.'));
		$this->print_json_result();
	}
	
	/**
	 * echo json result  stored in $this->json_result
	 */
	protected function print_json_result(){
		echo json_encode($this->json_result, JSON_HEX_QUOT | JSON_HEX_TAG);
	}
}