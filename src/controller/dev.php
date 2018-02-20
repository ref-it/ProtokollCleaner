<?php
/**
 * CONTROLLER Base Controller
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

class DevController extends MotherController
{
	
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
	public function link(){
		$this->t->printPageHeader();
		include (SYSBASE.'/framework/lib/o/PCUI.php');
		$this->t->printPageFooter();
	}
	
	/**
	 * ACTION home
	 */
	public function wiki(){
		$this->t->printPageHeader();
		require_once (SYSBASE.'/framework/class.xrpcClient.php');
		
		$x = new xrpcClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		$x->setMethod('');
		$x->setParams([]);
		$x->send();
		
		$this->t->printPageFooter();
	}
}