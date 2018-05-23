<?php
/**
 * FRAMEWORK ProtocolHelper
 * AuthHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			lukas staab
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			18.02.2018
 * @copyright 		Copyright (C) Referat IT 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

require_once (dirname(__FILE__).'/Singleton.php');
require_once (dirname(__FILE__).'/class.AuthHandler.php');

/**
 * SimpleSAML Auth Handler
 * extends Singleton class
 * handles SimpleSAML Authentification
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			lukas staab
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			18.02.2018
 * @copyright 		Copyright (C) Referat IT 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
class AuthSamlHandler extends Singleton implements AuthHandler{
	private static $SIMPLESAMLDIR;
	private static $SIMPLESAMLAUTHSOURCE;
	private static $AUTHGROUP;
	private static $ADMINGROUP;
	private $saml;
	
	/**
	 * return instance of this class#
	 * singleton class
	 * return same instance on every call
	 * @param bool $noPermCheck
	 * @return AuthHandler
	 */
	public static function getInstance(...$pars):AuthSamlHandler{
		return parent::getInstance(...$pars);
	}

	/**
	 * class constructor
	 * protected cause of extended singleton class
	 * @param bool $noPermCheck
	 */
	protected function __construct(){
		require_once(self::$SIMPLESAMLDIR . '/lib/_autoload.php');
		$this->saml = new SimpleSAML_Auth_Simple(self::$SIMPLESAMLAUTHSOURCE);
		session_start();
		$this->requireAuth();
	}
	
	final static protected function static__set($name, $value){
		if (property_exists(get_class(), $name))
			self::$$name = $value;
		else
			throw new Exception("$name ist keine Variable in " . get_class());
	}
	
	/**
	 * return user displayname
	 * @return string
	 */
	function getUserFullName(){
		$this->requireAuth();
		return $this->getAttributes()["displayName"][0];
	}
	
	/**
	 * handle session and user login
	 */
	function requireAuth(){
		if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] && !$this->saml->isAuthenticated()){
			header('HTTP/1.0 401 UNATHORISED');
			die("Login nicht (mehr) gueltig");
		}
		$this->saml->requireAuth();
		if(!$this->hasGroup(self::$AUTHGROUP)){
			header('HTTP/1.0 403 FORBIDDEN');
			die("Du besitzt nicht die nötigen Rechte um diese Seite zu sehen.");
		}
		//session
		if (!isset($_SESSION['SILMPH']['AUTH_INSTANT'])
			|| $_SESSION['SILMPH']['AUTH_INSTANT'] != $this->saml->getAuthData('AuthnInstant')){
			session_destroy();
			session_start();
			$_SESSION['SILMPH']['AUTH_INSTANT'] = $this->saml->getAuthData('AuthnInstant');
		}
		//session client info
		if(!isset($_SESSION['SILMPH'])
			|| (isset($_SESSION['SILMPH']['CLIENT_IP']) && $_SESSION['SILMPH']['CLIENT_IP'] != $_SERVER['REMOTE_ADDR'] )
			|| (isset($_SESSION['SILMPH']['CLIENT_AGENT']) && $_SESSION['SILMPH']['CLIENT_AGENT'] != ((isset($_SERVER ['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR']) ) ){
			$_SESSION['SILMPH']['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['SILMPH']['CLIENT_AGENT'] = ((isset($_SERVER ['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR']);
		}
		//init messagehandler
		if (!isset($_SESSION['SILMPH']['MESSAGES'])) {
			$_SESSION['SILMPH']['MESSAGES'] = [];
		}
	}
	
	/**
	 * return current user attributes
	 * @return array
	 */
	function getAttributes(){
		global $DEV;
		$attributes = $this->saml->getAttributes();
		//var_dump($attributes['groups']);
		if (!$DEV){
			return $attributes;
		}else{
			$removeGroups = [];
			//$removeGroups = ["ref-finanzen","ref-finanzen-hv",];
			$attributes["groups"] = array_diff($attributes["groups"], $removeGroups);
			return $attributes;
		}
	}
	
	/**
	 * return user mail address
	 * @return string
	 */
	function getUserMail(){
		$this->requireAuth();
		return $this->getAttributes()["mail"][0];
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
	function hasGroup($groups, $delimiter = ","){
		$attributes = $this->getAttributes();
		if(!isset($attributes["groups"])){
			return false;
		}
		if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map("strtolower", $attributes["groups"]))) == 0){
			return false;
		}
		return true;
	}
	
	function hasGremium($gremien, $delimiter = ","){
		$attributes = $this->getAttributes();
		if(!isset($attributes["gremien"])){
			return false;
		}
		if (count(array_intersect(explode($delimiter, strtolower($gremien)), array_map("strtolower", $attributes["gremien"]))) == 0){
			return false;
		}
		return true;
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
		if (isset($attributes["mail"]) && isset($attributes["mail"][0]))
			return $attributes["mail"][0];
		return null;
	}
	
	/**
	 * return log out url
	 * @return string
	 */
	function getLogoutURL(){
		return $this->saml->getLogoutURL();
	}
	
	/**
	 * return boolean if admin is on group list
	 * @return bool
	 */
	function isAdmin(){
		return $this->hasGroup(self::$ADMINGROUP);
	}
	
	/**
	 * send html header to redirect to logout url
	 * @param string $param
	 */
	function logout(){
		header('Location: '. $this->getLogoutURL());
		die();
	}
}