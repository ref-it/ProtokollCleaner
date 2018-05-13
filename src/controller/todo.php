<?php
/**
 * CONTROLLER Todo Controller
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        controller
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			22.04.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (SYSBASE . '/framework/class._MotherController.php');

class TodoController extends MotherController {
	
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
	 * ACTION tlist
	 */
	public function tlist(){
		$perm = 'stura';
		
		$this->t->setTitlePrefix('Todos - '.ucfirst(strtolower($perm)));
		$this->t->clearMetaOther();
		$this->t->appendOtherHeadTag('<link rel="manifest" href="'.BASE_SUBDIRECTORY.'todo/manifest?committee='.$perm.'">');
		$this->t->appendMeta(['name' => 'mobile-web-app-capable', 'content' => 'yes']);
		$this->t->appendMeta(['name' => 'apple-mobile-web-app-capable', 'content' => 'yes']);
		$this->t->appendMeta(['name' => 'application-name', 'content' => 'Todoliste - '. ucfirst(strtolower($perm))]);
		$this->t->appendMeta(['name' => 'apple-mobile-web-app-title', 'content' => 'Todoliste - '. ucfirst(strtolower($perm))]);
		$this->t->appendMeta(['name' => 'msapplication-navbutton-color', 'content' => '#173d92']);
		$this->t->appendMeta(['name' => 'apple-mobile-web-app-status-bar-style', 'content' => 'black-translucent']);
		$this->t->appendMeta(['name' => 'msapplication-starturl', 'content' => BASE_URL.BASE_SUBDIRECTORY.'todo/list?gremium='.$perm]);
		$this->t->appendOtherHeadTag('<link rel="shortcut icon" href="'.BASE_SUBDIRECTORY.'images/todo.ico" type="image/x-icon">');
		$this->t->appendOtherHeadTag('<link rel="apple-touch-icon" href="'.BASE_SUBDIRECTORY.'images/todo.ico">');
		
		$this->t->appendCSSLink('todo.css');
		$this->t->appendJsLink('todo.js');
		$this->t->printPageHeader();
		
		$limit_todo = !(isset($_GET['alltype']) && $_GET['alltype']==='1');
		$limit_date = (isset($_GET['nolimit']) && $_GET['nolimit']==='1')? false : (date_create()) ;
		if ($limit_date!=false){
			$limit_date->setTime(0, 0, 0);
			if ($limit_date->format('N') != 1) { //not monday
				$limit_date->modify('last monday');
			}
			$limit_date->sub(new DateInterval('P28D'));
			$limit_date = $limit_date->format('Y-m-d');
		}
		$todos = $this->db->getTodosByGremium($perm, $limit_todo, $limit_date);
		$this->includeTemplate(__FUNCTION__, [ 
			'todos' => $todos, 
			'perm' => $perm,
			'limit_todo' => $limit_todo,
			'limit_date' => $limit_date]);
		$this->t->printPageFooter();
	}
	
	public function tupdate(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'error' => 'Todokennung hat das falsche Format.'
			],
			'pid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
			'value' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger Wert.'
			]
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$todo = $this->db->getTodosByHashPidGrem($vali->getFiltered('pid'), $vali->getFiltered('hash'), $vali->getFiltered('committee'));
			if ($todo == NULL){
				$this->json_not_found('Todoeintrag nicht gefunden');
			} else {
				$todo['done'] = $vali->getFiltered('value');
				$up = $this->db->updateTodo($todo);
				if ($up){
					$this->json_result = [
						'success' => true,
						'msg' => 'Todo update'
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Todo konnte nicht geupdatet werden.'
					];
				}
				$this->print_json_result();
			}
		}
	}
	
	public function manifest(){
		if (!isset($_GET['committee'])) $_GET['committee'] = 'stura';
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(self::$protomap)).'/',
				'maxlength' => 10,
			],
		];
		$vali = new Validator();
		$vali->validateMap($_GET, $validator_map, true);
		if ($vali->getIsError()){
			$this->renderErrorPage(404);
		}
		$this->includeTemplate(__FUNCTION__, ['committee' => $vali->getFiltered('committee')] );
	}
}
