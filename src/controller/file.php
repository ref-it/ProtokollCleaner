<?php
use SILMPH\File;
/**
 * CONTROLLER File Controller
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

class FileController extends MotherController {
	
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
	 * ACTION return uploader gui
	 * render upload forms and gui
	 */
	public function npuploader(){
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
			'gui' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
		];
		$vali = new Validator();
		if (!isset($_GET['gui'])) $_GET['gui'] = '0';
		$vali->validateMap($_GET, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->renderErrorPage(403, NULL);
				return;
			} else if($vali->getLastErrorCode() == 404){
				$this->renderErrorPage(404, NULL);
				return;
			} else {
				$this->renderErrorPage($vali->getLastErrorCode(), NULL);
				return;
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->renderErrorPage(403, NULL);
			return;
		} else {
			//get top
			$filtered = $vali->getFiltered();
			$top = $this->db->getTopById($filtered['tid']);
			if (!$top
				|| $top['gname'] != $filtered['committee']
				|| $top['hash'] != $filtered['hash']
				|| $top['used_on'] != NULL ){ //top editable -> not linked to newproto
				$this->renderErrorPage(404, NULL);
				return;
			}
			//init fileHAndler
			require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			//get filelist
			$files = $fh->filelist($top['id']);
			//get resort list
			$resorts = $this->db->getResorts($filtered['committee']);
			//show result
			if ($filtered['gui']){
				$this->t->appendCSSLink('dropzone.css');
				$this->t->appendCSSLink('file.css');
				$this->t->appendJsLink('libs/dropzone.js');
				$this->t->appendJsLink('file.js');
				$this->t->setTitlePrefix('Anhänge - '.$top['headline']);
				$this->t->printPageHeader();
			}
			$this->includeTemplate(__FUNCTION__, [
				'files' => $files,
				'top' => $top,
				'resorts' => $resorts,
				'committee' => $filtered['committee'],
				'gui' => ($filtered['gui'])? true : false
			]);
			if ($filtered['gui']){
				$this->t->printPageFooter();
			}
		}
	}
	
}