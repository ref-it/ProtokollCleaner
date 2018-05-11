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
 
require_once (SYSBASE . '/framework/class._JsonController.php');
require_once (SYSBASE . '/framework/class.validator.php');

class MotherController extends JsonController {
	/**
	 * contains constant PROTOMAP
	 * @var array
	 */
	protected static $protomap = PROTOMAP;
	
	/**
	 * contains the database connection
	 * @var DatabaseModel|Database
	 */
	protected $db;
	
	/**
	 * contains the AuthHandler
	 * @var AuthHandler|BasicAuthHandler
	 */
	protected $auth;
	
	/**
	 * contains the Template instance
	 * Template
	 */
	protected $t;
	
	/**
	 * 
	 * @param Database $db
	 * @param AuthHandler $auth
	 * @param Template $template
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
	function includeTemplate($action, $param = NULL){
		include (SYSBASE.'/templates/'.TEMPLATE.'/'.$this->getControllerName().'/'.$action.'.phtml');
	}
	
	/**
	 * handles and show html error codes
	 * @param array $nav navigation array
	 * @param integer $code HTML error code
	 */
	function renderErrorPage($code, $nav){
		if ($this->t == NULL) $this->t = new Template($this->auth, $nav);
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
	
	/**
	 * generate Post challenge
	 * @param boolean $nohtml dont return html, return array
	 * @return string|array
	 */
	public function getChallenge($nohtml = false){
		if (!$nohtml){			
			return ((isset($_SESSION['SILMPH']) && isset($_SESSION['SILMPH']['FORM_CHALLENGE_NAME']))? '<input type="hidden" id="fchal" name="'.$_SESSION['SILMPH']['FORM_CHALLENGE_NAME'].'" value="'.$_SESSION['SILMPH']['FORM_CHALLENGE_VALUE'].'">': '').
			'<input type="hidden" id="fchal2" name="antichallenge" value="'.mt_rand().'">'; // dont commit this value to server
		} else{
			$out = [];
			if (isset($_SESSION['SILMPH']) && isset($_SESSION['SILMPH']['FORM_CHALLENGE_NAME'])) $out[$_SESSION['SILMPH']['FORM_CHALLENGE_NAME']] = $_SESSION['SILMPH']['FORM_CHALLENGE_VALUE'];
			$out['antichallenge'] = mt_rand();
			return $out;
		}
	
	}
	
}