<?php
/**
 * CONTROLLER Mother Controller
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        controller
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
class MotherController {
	/**
	 * contains the database connection
	 * @var database::inctance
	 */
	protected $db;
	
	/**
	 * contains the AuthHandler
	 * @var AuthHandler
	 */
	protected $auth;
	
	/**
	 * contains the template instance
	 * @var template
	 */
	protected $t;
	
	/**
	 * 
	 * @param unknown $db
	 * @param unknown $auth
	 * @param unknown $template
	 */
	function __construct($db, $auth, $template){
		$this->db = $db;
		$this->auth = $auth;
		$this->t = $template;
	}
	
	private function getControllerName(){
		return str_replace('controller', '', strtolower(get_class($this)));
	}
	
	function includeTemplate($action){
		include (SYSBASE.'/templates/'.TEMPLATE.'/'.$this->getControllerName().'/'.$action.'.phtml');
	}
}