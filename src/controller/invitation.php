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
	public function ilist(){
		$perm = 'stura';
		$this->t->appendJsLink('libs/jquery-ui.min.js');
		$s = $this->t->getJsLinks();
		$this->t->setJsLinks([$s[0], $s[3], $s[1], $s[2]]);
		$this->t->appendCSSLink('invite.css');
		$this->t->appendJsLink('wiki2html.js');
		$this->t->appendJsLink('invite.js');
		$tops = $this->db->getTops($perm);
		$resorts = $this->db->getResorts($perm);
		$member = $this->db->getMembersCounting($perm);
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'tops' => $tops,
			'resorts' => $resorts,
			'member' => $member
		]);
		$this->t->printPageFooter();
	}
	
	/**
	 * POST action
	 * delete top by id hash and committee
	 */
	public function tdelete(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'error' => 'Topkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
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
			
			$top = $this->db->getTopById($vali->getFiltered('tid'));
			if (!$top 
				|| $top['gname'] != $vali->getFiltered('committee') 
				|| $top['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Top nicht gefunden');
			} else {
				$ok = $this->db->deleteTopById($top['id']);
				if ($ok){
					$this->json_result = [
						'success' => true,
						'msg' => 'Top wurde gelöscht.'
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Top konnte nicht gelöscht werden.'
					];
				}
				$this->print_json_result();
			}
		}
	}
	
	/**
	 * POST action
	 * delete top by id hash and committee
	 */
	public function tpause(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'error' => 'Topkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
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
			$top = $this->db->getTopById($vali->getFiltered('tid'));
			if (!$top
				|| $top['gname'] != $vali->getFiltered('committee')
				|| $top['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Top nicht gefunden');
			} else {
				$top['skip_next'] = ($top['skip_next']==1)?0:1;
				$ok = $this->db->updateTop($top);
				if ($ok){
					$this->json_result = [
						'success' => true,
						'msg' => 'Top wurde geändert.',
						'skipnext' => ($top['skip_next']==1)
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Top nicht geändert.',
						'skipnext' => ($top['skip_next']==1)
					];
				}
				$this->print_json_result();
			}
		}
	}
	
	/**
	 * POST action
	 * sort tops
	 */
	public function tsort(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'list' => ['array',
				'minlength' => 2,
				'validator' => ['integer',
					'min' => '1',
					'error' => 'Ungültige Id.'
				],
				'error' => 'Kein Array.'
			],
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
			$tops = $this->db->getTops($vali->getFiltered('committee'));
			$sortpos = 1;
			$ok = true;
			foreach($vali->getFiltered('list') as $sortid){
				if (!isset($tops[$sortid])) continue;
				if (isset($tops[$sortid]['used_on'])) continue;
				if (isset($tops[$sortid]['resort'])) continue;
				if ($tops[$sortid]['order'] != $sortpos){
					$tops[$sortid]['order'] = $sortpos;
					$ok = $this->db->updateTop($tops[$sortid]);
				}
				if (!$ok) break;
				$sortpos++;
			}
			if ($ok){
				$this->json_result = [
					'success' => true,
					'msg' => 'Tops sortiert.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Top nicht geändert.',
				];
			}
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * delete committee member
	 */
	public function mdelete(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'mid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
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
			$member = $this->db->getMemberById($vali->getFiltered('mid'));
			if (!$member || $member['gname'] != $vali->getFiltered('committee')){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Mitglied nicht gefunden nicht gefunden.'
				];
			} else {
				$np = false;
				$tr = false;
				$me = false;
				//delete tops
				$tr = $this->db->deleteTopsByMemberId($vali->getFiltered('mid'));
				//delete newprotocol
				if ($tr){
					$np = $this->db->deleteNewprotoByMemberId($vali->getFiltered('mid'));
				}
				//delete member
				if ($np){
					$me = $this->db->deleteMemberById($vali->getFiltered('mid'));
				}
				//return result
				if ($me){
					$this->json_result = [
						'success' => true,
						'msg' => 'Mitglied erfolgreich gelöscht.'
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Fehler beim Löschen.'
					];
				}
			}
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * add committee member
	 */
	public function madd(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'mname' => ['name',
				'minlength' => '3',
				'error' => 'Ungültige Zeichen im Namen.'
			],
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
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			$found = false;
			foreach ($members as $mem){
				if ($mem['name']==$vali->getFiltered('mname')){
					$found = true;
					break;
				}
			}
			if ($found){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Mitgliedsname bereits vorhanden.'
				];
			} else {
				$grem = $this->db->getCreateCommitteebyName($vali->getFiltered('committee'));
				$newmem = [
					'name' => $vali->getFiltered('mname'),
					'gremium' => $grem['id'],
				];
				$res = $this->db->createMember($newmem);
				if ($res){
					$newmem['id'] = $res;
					$this->json_result = [
						'newmember' => $newmem,
						'success' => true,
						'msg' => 'Mitglied erfolgreich hinzugefügt.'
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Fehler beim Erstellen.'
					];
				}
			}
			$this->print_json_result();
		}
	}
	
}