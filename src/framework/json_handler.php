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
class jsonHandler {
	/**
	 * contains the own object
	 * class implements singleton design pattern
	 * @var jsonHandler::instance
	 */
	protected static $_instance = null;
	protected $_instance2 = null;
	
	/**
	 * contains the database connection
	 * @var database::inctance
	 */
	protected $db;
	
	/**
	 * array maps function names to permissions
	 * 'functionname' => permission
	 * @var array 
	 */
	protected $functionAccess;
	
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
	protected function __construct(){
		$this->functionAccess = array(
			'comment_order' => 'comment_order',
			'get_order_comments' => 'comment_order'
		);
		global $db;
		$this->db = $db;
		$this->_instance2 = $this;
	}
	
	/**
	 * returns instance of this class
	 * implements singleton pattern
	 */
	public static function getInstance()
	{
		if (!isset(static::$_instance)) {
			static::$_instance = new static;
		}
		return static::$_instance;
	}
	
	/**
	 * prevent cloning of an instance via the clone operator
	 */
	protected function __clone() {}
	
	/**
	 * prevent unserializing via the global function unserialize()
	 *
	 * @throws Exception
	 */
	public function __wakeup()
	{
		throw new Exception("Cannot unserialize singleton");
	}
	
	// ================================================================================================
	
	/**
	 * check function permission and calls function if the permission is ok
	 * @param string $functionname function which should be called
	 */
	public function call($functionname){
		if ($functionname === "not_found") {
			$return = $this->access_not_found();
			$this->db->close();
			return $return;
		} else if (!isset($this->functionAccess[$functionname])){
			$return = $this->access_denied_json();
			$this->db->close();
			return $return;
		} else if (!isset($_SESSION['FORM_CHALLANGE_NAME']) || !isset($_POST[$_SESSION['FORM_CHALLANGE_NAME']]) || $_POST[$_SESSION['FORM_CHALLANGE_NAME']] != $_SESSION['FORM_CHALLANGE_VALUE']) {
			$return = $this->access_denied_json();
			$this->db->close();
			return $return;
		}
		$needPermission = $this->functionAccess[$functionname];		
		if (checkUserPermission($needPermission)){
			$return = $this->__call('silmph_'.$functionname);
		} else {
			$return = $this->access_denied_json();
		}
		$this->db->close();
		return $return;
	}
	
	/**
	 * calls an function of this instance
	 * @param string $method functionname
	 * @param mixed $args function parameters
	 */
	public function __call($method, $args = array()) {		
		return call_user_func_array(array($this->_instance2, $method), $args);
	}
	
	// ================================================================================================
	
	/**
	 * returns 403 access denied in json format
	 */
	function access_denied_json(){
		http_response_code (403);
		$json_result = array('success' => false, 'eMsg' => 'Zugriff verweigert.');
		echo json_encode($json_result, JSON_HEX_QUOT | JSON_HEX_TAG);
	}
	
	/**
	 * returns 404 not found in html format
	 */
	function access_not_found(){
		http_response_code (404);
		$t = new template();
		$t->setTitlePrefix('404 - Seite nicht gefunden');
		$t->printPageHeader();
		include (dirname(__FILE__)."/../templates/".TEMPLATE."/404.phtml");
		$t->printPageFooter();
	}
	
	/**
	 * echo json result  stored in $this->json_result
	 */
	protected function print_json_result(){
		echo json_encode($this->json_result, JSON_HEX_QUOT | JSON_HEX_TAG);
	}
	
	// ================================================================================================
	
	/**
	 * handles json request: add a comment to an order
	 */
	public function silmph_comment_order() {
		if (isset($_POST['data_id']) && isset($_POST['data_text'])){
			$json = true;
			$data_id = filter_var($_POST['data_id'], FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "default"=>0)));
			$data_comment = trim(strip_tags($_POST['data_text']));
		
			if ($data_id > 0 && $data_comment != ''){
				//create Comment
				$res = $this->db->createComment($data_id, $_SESSION['USER_ID'], $data_comment);
		
				if ($this->db->isError()){
					$this->json_result = array('success' => false, 'eMsg' => 'Some error occured: '.$this->db->getError());
				} else {
					if ($res > 0){
						$this->json_result = array('success' => true, 'msg' => 'ok', 'comment' => '<h2>'.date_create()->format('d.m.Y H:i:s')." (".$_SESSION['USER_SET']['username'].')</h2>'.$data_comment);
					} else {
						$this->json_result = array('success' => false, 'eMsg' => 'Kein Kommentar erstellt.');
					}
				}
				$this->print_json_result();
			} else {
				$this->access_denied_json();
			}
		} else {
			$this->access_denied_json();
		}
	}
	
	/**
	 * handles json request: list all comments for order
	 */
	public function silmph_get_order_comments(){
		if (isset($_POST['data'])){
			$data_id = filter_var($_POST['data'], FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "default"=>0)));
							
			if ($data_id > 0){
				//get usernames
				$usernames = $this->db->getUserMap();
				
				//get comments from order
				$res = $this->db->getCommentsByOrderId($data_id);
				
				$comment_return = '';
				foreach ($res as $key => $line) {
					if ($key > 0) $comment_return .= "\n<hr>";
					$comment_return .= '<h2>'.date_create($line['created_on'])->format('d.m.Y H:i:s')." (".$usernames[$line['created_by']]['username'].')</h2>'.$line['comment'];
				}
				if ($this->db->isError()){
					$this->json_result = array('success' => false, 'eMsg' => 'Some error occured: '.$this->db->getError());
				} else {
					$this->json_result = array('success' => true, 'msg' => 'ok', 'comments' => $comment_return);
				}
				$this->print_json_result();
			} else {
				$this->access_denied_json();
			}
		} else {
			$this->access_denied_json();
		}
	}
	
}