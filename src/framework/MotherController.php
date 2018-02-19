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
 
require_once (SYSBASE . '/framework/JsonController.php');
require_once (SYSBASE . '/framework/Validator.php');

class MotherController extends JsonController {
	/**
	 * contains the database connection
	 * database
	 */
	protected $db;
	
	/**
	 * contains the AuthHandler
	 * AuthHandler
	 */
	protected $auth;
	
	/**
	 * contains the template instance
	 * template
	 */
	protected $t;
	
	/**
	 * 
	 * @param database $db
	 * @param AuthHandler $auth
	 * @param template $template
	 */
	function __construct($db, $auth, $template){
		$this->db = $db;
		$this->auth = $auth;
		$this->t = $template;
	}
	
	/**
	 * return instance controller name
	 * @return string
	 */
	private function getControllerName(){
		return str_replace('controller', '', strtolower(get_class($this)));
	}
	
	/**
	 * includes Template File
	 * @param string $action
	 */
	function includeTemplate($action){
		include (SYSBASE.'/templates/'.TEMPLATE.'/'.$this->getControllerName().'/'.$action.'.phtml');
	}
	
	/**
	 * handles and show html error codes
	 *
	 * @param integer $code HTML error code
	 */
	function renderErrorPage($code, $nav){
		if ($this->t == NULL) $this->t = new template($this->auth, $nav);
		if ($code === 404){
			http_response_code (404);
			$this->t->setTitlePrefix('404 - Seite nicht gefunden');
			$this->t->printPageHeader();
			include (SYSBASE."/templates/".TEMPLATE."/404.phtml");
			$this->t->printPageFooter();
		} else if ($code === 403){
			http_response_code (403);
			$this->t->setTitlePrefix('403 - Zugriff verweigert');
			$this->t->printPageHeader();
			include (SYSBASE."/templates/".TEMPLATE."/403.phtml");
			$this->t->printPageFooter();
		} else {
			error_log("Router: Unhandled error code: $code");
		}
		if ($this->db != NULL) $this->db->close();
		die();
	}
	
}