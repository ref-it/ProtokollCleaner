<?php
/**
 * CONTROLLER Invitation Controller
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

class InvitationController extends MotherController {
	
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
		$this->t->appendCSSLink('invite.css');
		$this->t->appendJsLink('wiki2html.js');
		$this->t->appendJsLink('invite.js');
		
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__);
		$this->t->printPageFooter();
	}
}