<?php
/**
 * FRAMEWORK ProtocolHelper
 * AuthHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			18.02.2018
 * @copyright 		Copyright (C) Michael Gnehr 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (dirname(__FILE__).'/class.AuthHandler.php');

/**
 * DummyAuth Handler
 * used for debugging login
 * replaces SAML login and provide simple login
 * implements the SAML Interface of AuthHandler/AuthSamlHandler
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			18.02.2018
 * @copyright 		Copyright (C) Michael Gnehr 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
class AuthDummyHandler implements AuthHandler{
	
	/**
	 * reference to own instance
	 * singelton instance of this class
	 * @var BasicAuthHandler
	 */
	private static $instance; //singelton instance of this class
	
	/**
	 * current user data
	 *  keys
	 *    eduPersonPrincipalName
	 *    mail
	 *    displayName
	 *    groups
	 * @var array
	 */
	private $attributes;
	
	/**
	 * class constructor
	 * protected cause of singleton class
	 * @param bool $noPermCheck
	 */
	protected function __construct(){
		//create session
		session_start();
		$this->attributes = [
			'displayName' => 'Michael G',
			'mail' => 'michael@gnehr.de',
			'groups' => ['stura', 'ref-it', 'sgis', 'admin'],
			'eduPersonPrincipalName' => ['michaguser'],
		];
	}
	
	/**
	 * return instance of this class
	 * singleton class
	 * return same instance on every call
	 * @param bool $noPermCheck
	 * @return AuthHandler
	 */
	public static function getInstance(){
		if (!isset(self::$instance)){
			global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE;
			self::$instance = new AuthDummyHandler($SIMPLESAML, $SIMPLESAMLAUTHSOURCE);
		}
		return self::$instance;
	}
	
	/**
	 * handle session and user login
	 */
	function requireAuth(){
		//check IP and user agent
		if(isset($_SESSION['SILMPH']) && isset($_SESSION['SILMPH']['CLIENT_IP']) && isset($_SESSION['SILMPH']['CLIENT_AGENT'])){
			if ($_SESSION['SILMPH']['CLIENT_IP'] != $_SERVER['REMOTE_ADDR'] || $_SESSION['SILMPH']['CLIENT_AGENT'] != $_SERVER ['HTTP_USER_AGENT']){
				//die or reload page is IP isn't the same when session was created -> need new login
				session_destroy();
				session_start();
				$_SESSION['SILMPH']['MESSAGE'] = ['New Session Started!'];
				header("Refresh: 0");
				die();
			}
		} else {
			$_SESSION['SILMPH']['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['SILMPH']['CLIENT_AGENT'] = $_SERVER ['HTTP_USER_AGENT'];
		}
		
		if(!isset($_SESSION['SILMPH']['USER_ID'])){
			$_SESSION['SILMPH']['USER_ID'] = 0;
		}
		
		//session expire after 2 hours no action
		if(!isset($_SESSION['SILMPH']['LAST_ACTION'])){
			$_SESSION['SILMPH']['LAST_ACTION'] = time();
		}
		if (time() - $_SESSION['SILMPH']['LAST_ACTION'] > 3600*2){
			session_destroy();
			session_start();
			$_SESSION['SILMPH']['MESSAGE'] = ['New Session Started!'];
			header("Refresh: 0");
			die();
		} else {
			$_SESSION['SILMPH']['LAST_ACTION'] = time();
		}
		
		if(!isset($_SESSION['SILMPH']['MESSAGES'])){
			$_SESSION['SILMPH']['MESSAGES'] = array();
		}
		
		//check logout request
		if ($_SESSION['SILMPH']['USER_ID'] !== 0 && ( isset($_GET['logout']) || strpos($_SERVER['REQUEST_URI'], '&logout=1') !== false || strpos($_SERVER['REQUEST_URI'], '?logout=1') !== false )){
			session_destroy();
			session_start();
			$_SESSION['SILMPH']['MESSAGES'] = [["Sie haben sich erfolgreich abgemeldet.", 'INFO']];
			$_SESSION['SILMPH']['MESSAGES'][] = ['New Session Started!', 'INFO'];
			header('Location: '.BASE_URL . $_SERVER['PHP_SELF']);
			die();
		}
		
		if ($_SESSION['SILMPH']['USER_ID'] === 0 && ( isset($_GET['login']) || strpos($_SERVER['REQUEST_URI'], '&login=1') !== false || strpos($_SERVER['REQUEST_URI'], '?login=1') !== false )){
			$_SESSION['SILMPH']['MESSAGES'][] = ["Sie haben sich erfolgreich angemeldet.", 'INFO'];
			$_SESSION['SILMPH']['USER_ID'] = 1;
			if (isset($_GET['request'])){
				$requested = trim(strip_tags($_GET['request']));
				$requested = str_replace($_SERVER['PHP_SELF'], '', $requested);
				if (mb_strpos($requested, 'login')===false){
					header('Location: '.BASE_URL . $requested);
					die();
				}
			}
		}
		
		if ($_SESSION['SILMPH']['USER_ID'] === 0){
			$request = $_SERVER['REQUEST_URI'];
			if ($request === '/') $request = '';
			if ($request === $_SERVER['PHP_SELF']) $request = '';
			if ($request) $request = '&request='.urlencode($request);
			echo 'Please Login: <a href="'.BASE_URL.BASE_SUBDIRECTORY.'?login=1'.$request.'">Use THIS LINK</a>';
			die();
		}
	}
	
	/**
	 * check group permission - die on error
	 * return true if successfull
	 * @param string $groups    String of groups
	 * @return bool  true if the user has one or more groups from $group
	 */
	function requireGroup($group){
		$this->requireAuth();
		if (!$this->hasGroup($group)){
			header('HTTP/1.0 403 Unauthorized');
			echo 'You have no permission to access this page.';
			die();
		}
		return true;
	}
	
	/**
	 * check group permission - return result of check as boolean
	 * @param string $groups    String of groups
	 * @param string $delimiter Delimiter of the groups in $group
	 * @return bool  true if the user has one or more groups from $group
	 */
	function hasGroup($group, $delimiter = ","){
		$this->requireAuth();
		$attributes = $this->getAttributes();
		if (count(array_intersect(explode($delimiter, strtolower($group)), array_map("strtolower", $attributes["groups"]))) == 0){
			return false;
		}
		return true;
	}
	
	/**
	 * return log out url
	 * @return string
	 */
	function getLogoutURL(){
		return BASE_URL.BASE_SUBDIRECTORY . '?logout=1';
	}
	
	/**
	 * send html header to redirect to logout url
	 * @param string $param
	 */
	function logout(){
		header('Location: '. $this->getLogoutURL());
		die();
	}
	
	/**
	 * return current user attributes
	 * @return array
	 */
	function getAttributes(){
		return $this->attributes;
	}
	
	/**
	 * return username or user mail address
	 * if not set return null
	 * @return string|NULL
	 */
	function getUsername(){
		$attributes = $this->getAttributes();
		if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0]))
			return $attributes["eduPersonPrincipalName"][0];
		if (isset($attributes["mail"]) && isset($attributes["mail"]))
			return $attributes["mail"];
		return null;
	}
	
	/**
	 * return user displayname
	 * @return string
	 */
	function getUserFullName(){
		$this->requireAuth();
		return $this->getAttributes()["displayName"];
	}
	
	/**
	 * return user mail address
	 * @return string
	 */
	function getUserMail(){
		$this->requireAuth();
		return $this->getAttributes()["mail"];
	}
}
