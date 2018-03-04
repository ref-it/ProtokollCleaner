<?php
/**
 * CONTROLLER Resolution Controller
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
require_once (SYSBASE.'/framework/class.wikiClient.php');

class ResolutionController extends MotherController
{
	/**
	 * class constructor
	 * @param Database $db        	
	 * @param AuthHandler $auth        	
	 * @param Template $template        	
	 */
	public function __construct($db, $auth, $template)
	{
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * ACTION list resolutions
	 * draw resolution list
	 */
	public function rlist(){
		//calculate accessmap
		$validator_map = [
			'pid' => ['integer',
				'min' => 1,
			],
		];
		$vali = new Validator();
		$vali->validateMap($_GET, $validator_map, false);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->renderErrorPage(403, null);
			} else if($vali->getLastErrorCode() == 404){
				$this->renderErrorPage(404, null);
			} else {
				$this->renderErrorPage(404, null);
			}
			return;
		}
		//permission - edit this to add add other committee
		$perm = 'stura';
		if (isset($vali->getFiltered()['pid'])){
			$resos = $this->db->getResolutionByCommittee($perm, $vali->getFiltered()['pid']);
		} else {
			$resos = $this->db->getResolutionByCommittee($perm);
		}
		
		$resos2 = NULL;
		$resos2 = array();
		//parse resolutions: categorize, and split to array
		foreach ($resos as $pos => $rawres){
			if ($rawres['noraw'] == 0){
				$tmp = protocolHelper::parseResolution($rawres['text'], NULL, NULL, $perm);
				$resos[$pos] = array_merge( $tmp, $resos[$pos]);
			} else {
				$resos[$pos]['Titel'] = $rawres['text'];
				$resos[$pos]['Beschluss'] = 'angenommen';
			}
			$resos[$pos]['date_obj'] = date_create_from_format('Y-m-d His', $rawres['date'].' 000000' );
		}
		$this->t->setTitlePrefix('Beschlussliste - '.ucwords( $perm, " \t\r\n\f\v-"));
		$this->t->appendCssLink('reso.css', 'screen,projection');
		$this->t->appendJsLink('reso.js');
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'reso' => &$resos,
			'committee' => $perm,
			'pid' => (isset($vali->getFiltered()['pid']))? $vali->getFiltered()['pid'] : NULL,
		]);
		$this->t->printPageFooter();
	}
}

?>