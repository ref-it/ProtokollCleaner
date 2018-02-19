<?php
/**
 * FRAMEWORK Router
 * Route requested URL Paths
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

class Router {
	/**
	 * contains the own object
	 * class implements singleton design pattern
	 * @var Router::instance
	 */
	protected static $_instance = null;
	
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
	 * contains route map
	 * GET routes are handled with template
	 * POST routes are handled with json_handler
	 * @var $routes
	 */
	protected $routes;
	
	/**
	 * contains route map
	 * controller are called directly
	 * @var $rawRoutes
	 */
	protected $rawRoutes;
	
	/**
	 * contains navigation map
	 * controller are called directly
	 * @var $rawRoutes
	 */
	protected $navigation;
	
	// ================================================================================================
	
	/**
	 * private class constructor
	 * implements singleton pattern
	 */
	protected function __construct(){
		global $db;
		$this->db = $db;
		
		global $auth;
		$this->auth = $auth;
		
		include (dirname(__FILE__).'/config/config.router.php');
		$this->routes = $routes;
		$this->rawRoutes = $rawRoutes;
		$this->navigation = $navigation;
	}
	
	
	/**
	 * returns instance of this class
	 * implements singleton pattern
	 */
	public static function getInstance()
	{
		if (!isset(static::$_instance)) {
			self::$_instance = new Router();
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
	 * route requested page
	 * handles requested url paths, and page errors
	 * routes can be configured in 'config.router.php'
	 */
	public function route(){
		// handle 404, 403
		// error handler
		if (isset($_GET['page_error'])){
			if (is_numeric($_GET['page_error'])){
				$val = intval($_GET['page_error']);
				require_once (SYSBASE.'/framework/MotherController.php');
				$c = new MotherController($this->db, $this->auth, NULL);
				if ($val > 0){
					$c->renderErrorPage($val, $this->navigation);
				} else {
					$c->renderErrorPage(-1, $this->navigation);
				}
			}
		}
		
		//handle routes
		$route_access = false;
		$parsed_url = parse_url($_SERVER['REQUEST_URI']);//URI zerlegen
		
		$method = strtoupper($_SERVER['REQUEST_METHOD']);
		$path = (isset($parsed_url['path']))? trim($parsed_url['path'],'/'):'';
		if ($path == '') $path = '/';

		//handle templated routes
		if (isset($this->routes[$method]) 
			&& isset($this->routes[$method][$path])){
			// check permission
			if ($this->auth->hasGroup($this->routes[$method][$path][0], ',')){
				$route_access = true;
				if ($method == 'GET') {
					$this->callController(
						array_slice($this->routes[$method][$path], 1 ), $method, $path, true
					);
				} else if ($method == 'POST') {
					// validate POST CHALLENGE
					if(!isset($_SESSION['SILMPH']['FORM_CHALLENGE_NAME'])
						|| $_SESSION['SILMPH']['FORM_CHALLENGE_NAME'] == ''
						|| !isset($_SESSION['SILMPH']['FORM_CHALLENGE_VALUE'])
						|| $_SESSION['SILMPH']['FORM_CHALLENGE_VALUE'] == ''
						|| !isset($_POST[$_SESSION['SILMPH']['FORM_CHALLENGE_NAME']])
						|| $_POST[$_SESSION['SILMPH']['FORM_CHALLENGE_NAME']] 
							!= $_SESSION['SILMPH']['FORM_CHALLENGE_VALUE']){
						require_once (SYSBASE.'/framework/MotherController.php');
						$c = new MotherController($this->db, $this->auth, NULL);
						$c->renderErrorPage(403, $this->navigation);
					} else {
						$this->callController(
							array_slice($this->routes[$method][$path], 1 ), $method, $path
						);
					} 
				} else {
					$this->callController(
						array_slice($this->routes[$method][$path], 1 ), $method, $path
					);
				}
			}
		} else if (isset($this->rawRoutes[$method]) 
			&& isset($this->rawRoutes[$method][$path])){
			if ($this->auth->hasGroup($this->rawRoutes[$method][$path][0], ',')){
				$route_access = true;
				$this->callController(
					array_slice($this->rawRoutes[$method][$path], 1 ), $method, $path
				);
			}
		} else {
			//route not found --> 404 Not Found
			require_once (SYSBASE.'/framework/MotherController.php');
			$c = new MotherController($this->db, $this->auth, NULL);
			$c->renderErrorPage(404, $this->navigation);
		}
		
		if (!$route_access){
			//route no access --> 403 Access Denied
			require_once (SYSBASE.'/framework/MotherController.php');
			$c = new MotherController($this->db, $this->auth, NULL);
			$c->renderErrorPage(403, $this->navigation);
		}
	}
	
	/**
	 * call controller method
	 * @param array $routedata, passed from routing config
	 * @param String $method, server request method
	 * @param String $path, requested server path
	 * @param boolean $template, create template instance
	 */
	private function callController($routedata, $method, $path, $template = false){
		if (file_exists(SYSBASE.'/controller/'.$routedata[0].'.php')){
			require_once(SYSBASE.'/controller/'.$routedata[0].'.php');
			$controllername = ucfirst($routedata[0]).'Controller';
			
			$t = ($template)? new template($this->auth, $this->navigation, $path): NULL;
			$c = new $controllername($this->db, $this->auth, $t);
			if (method_exists($c, $routedata[1]) && is_callable([$c, $routedata[1]]) ){
				$c->{$routedata[1]}($routedata);
			} else {
				error_log("Router: Controller Action '{$routedata[1]}' could not be found.");
				$c->renderErrorPage(404, $this->navigation);
			}
		} else {
			error_log("Router: Controller '{$routedata[0]}' could not be found.");
			require_once (SYSBASE.'/framework/MotherController.php');
			$c = new MotherController($this->db, $this->auth, NULL);
			$c->renderErrorPage(404, $this->navigation);
		}
	}
}