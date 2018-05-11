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
	
	/**
	 * POST ACTION upload
	 * handle file upload
	 */
	public function npupload(){
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
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
				return;
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
				return;
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
				return;
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
			return;
		} else {
			//file set, maxcount
			if( !isset($_FILES['file'])
				|| ! isset($_FILES['file']['error'])
				|| count($_FILES['file']['error']) == 0
				|| count($_FILES['file']['error']) > UPLOAD_MAX_MULTIPLE_FILES){
				
			}
			//get top
			$filtered = $vali->getFiltered();
			$top = $this->db->getTopById($filtered['tid']);
			if (!$top
				|| $top['gname'] != $filtered['committee']
				|| $top['hash'] != $filtered['hash']
				|| $top['used_on'] != NULL ){ //top editable -> not linked to newproto
				$this->json_not_found();
				return;
			}
			require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			$upload_result = $fh->upload($top['id']);
			
			if ($upload_result && $upload_result['success'] && count($upload_result['error']) == 0 && count($upload_result['fileinfo']) > 0){
				$files = array_values($upload_result['fileinfo']);
				$this->json_result = [
					'success' => true,
					'task' => 'add',
					'msg' => 'File upload was successful',
					'mime' => ($files[0]->mime)? $files[0]->mime : ' - ',
					'hash' => $files[0]->hashname,
					'added' => ($files[0]->added_on)? date_create($files[0]->added_on)->format('Y-m-d H:i') : date_create()->format('Y-m-d H:i'),
					'size' => FileHandler::formatFilesize($files[0]->size),
					'name' => $files[0]->filename.(($files[0]->fileextension)?'.'.$files[0]->fileextension:''),
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => implode('<br>', $upload_result['error']),
				];
			}
		}
		$this->print_json_result();
		return;
	}
	
	/**
	 * ACTION get
	 * handle file delivery
	 */
	public function get(){
		$validator_map = [
			'key' => ['regex',
				'pattern' => '/^([0-9a-f]{64})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'fdl' => ['integer', //force download
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
		];
		$vali = new Validator();
		if (!isset($_GET['fdl'])) $_GET['fdl'] = 0;
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
		} else {
			require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			//get file
			$file = $fh->checkFileHash($vali->getFiltered('key'));
			if (!$file){
				$this->renderErrorPage(404, NULL);
				return;
			}
			//check matching top
			$top = $this->db->getTopById($file->link);
			if (!$top
				|| $top['used_on'] != NULL ){ //top editable -> not linked to newproto
				$this->renderErrorPage(404, NULL);
				return;
			} else if (!checkUserPermission($top['gname'])) {
				$this->renderErrorPage(403, NULL);
				return;
			}
			$fh->deliverFileData($file, $vali->getFiltered('fdl'));
			return;
		}
	}
	
	/**
	 * POST ACTION remove
	 * remove file from top
	 */
	public function npremove(){
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
			'key' => ['regex',
				'pattern' => '/^([0-9a-f]{64})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
				return;
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
				return;
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
				return;
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
			return;
		} else {
			//get top
			$filtered = $vali->getFiltered();
			$top = $this->db->getTopById($filtered['tid']);
			if (!$top
				|| $top['gname'] != $filtered['committee']
				|| $top['hash'] != $filtered['hash']
				|| $top['used_on'] != NULL ){ //top editable -> not linked to newproto
				$this->json_not_found();
				return;
			}
			
			require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			//get file
			$file = $fh->checkFileHash($vali->getFiltered('key'));
			if (!$file){
				$this->json_not_found();
				return;
			}
			$fh->defleteFileByHash($file->hashname);
			$this->json_result = [
				'success' => true,
				'msg' => 'Anhang entfernt.',
			];
			$this->print_json_result();
			return;
		}
	}
	
}