<?php
/**
 * CONTROLLER Cron Controller
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
 
require_once (SYSBASE . '/framework/class._MotherController.php');

class CronController extends MotherController {
	
	/**
	 * 
	 * @param Database $db
	 * @param AuthHandler $auth
	 * @param Template $template
	 */
	function __construct($db, $auth, $template){
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * ACTION home
	 */
	public function base(){
		$this->t->printPageHeader();
		// cron users
		$users = CRON_USERMAP;
		$u = [];
		foreach ($users as $userName => $d){
			$u[] = $userName;
		}
		// cron routes
		include (FRAMEWORK_PATH . '/config/config.router.php');
		$r = [];
		foreach ($cronRoutes as $request => $d){
			foreach ($d as $routeName => $d2){
				$r[] = $request.': '.$routeName;
			}
		}
		$this->includeTemplate(__FUNCTION__, [
			'user' => $u,
			'routes' => $r,
		]);
		$this->t->printPageFooter();
	}
}