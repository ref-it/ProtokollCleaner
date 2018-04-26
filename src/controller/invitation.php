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
	
	// HELPER ==========================================================================
	/**
	 * 
	 * @param array $proto Protocoll array, with additional fields: gname, membernames
	 * @param array $user user data ['username' => xxx]
	 * @param false|array $openProtos array of unreconciled protocols
	 * @param string $additional_message additional mail message
	 */
	public function send_mail_invitation($proto, $user = NULL, $openProtos = false, $additional_message = ''){
		$settings=$this->db->getSettings();
		$mailer = new MailHandler();
		$mailer->setLogoImagePath('/../public/images/logo_f.png');
		$initOk = $mailer->init($settings);
		// mail initialisation failed
		if (!$initOk) return false;
		
		$pdate = date_create($proto['date']);
		$mail_address = PROTOMAP[$proto['gname']][3];
		$tops_tmp = $this->db->getTopsOpen($proto['gname']);
		$tops = [];
		foreach ($tops_tmp as $id => $top){
			if (!$top['skip_next']){
				$tops[$id] = $top;
			}
		}
		
		$mailer->mail->addAddress($mail_address);
		$mailer->mail->Subject = 'Sitzungseinladung - '.ucfirst(strtolower($proto['gname'])).' - '.$pdate->format('d.m.Y H:i');
		
		$mailer->bindVariables([
			'sender' 	=> ($user != NULL)? $user['username'] : '',
			'message' 	=> $additional_message,
			'committee' => $proto['gname'],
			'tops' 		=> $tops,
			'proto'		=> $proto, 
			'protoInternLink' => WIKI_URL.'/',
			'unreconciled_protocols' => $openProtos,
			'topLink' 	=> BASE_URL.'/invite' 
		]);
		
		$mailer->setTemplate('proto_invite');
		if($mailer->send(false, false, true, true)){
			return true;
		} else {
			error_log('Es konnte keine Mail versendet werden. Prüfen Sie bitte die Konfiguration. '.((isset($mailer->mail) && isset($mailer->mail->ErrorInfo))? $mailer->mail->ErrorInfo: '' ));
			return false;
		}
	}
	
	// ACTIONS =========================================================================
	
	/**
	 * ACTION home
	 */
	public function ilist(){
		$perm = 'stura';
		$this->t->appendCSSLink('invite.css');
		$this->t->appendJsLink('libs/jquery-ui.min.js');
		$s = $this->t->getJsLinks();
		$this->t->setJsLinks([$s[0], $s[3], $s[1], $s[2]]);
		$this->t->appendJsLink('libs/jquery-dateFormat.min.js');
		$this->t->appendJsLink('libs/jquery_ui_widget_combobox.js');
		$this->t->appendJsLink('wiki2html.js');
		$this->t->appendJsLink('invite.js');
		$tops = $this->db->getTopsOpen($perm);
		$resorts = $this->db->getResorts($perm);
		$member = $this->db->getMembersCounting($perm);
		$committee = $this->db->getCommitteebyName($perm);
		$newproto = $this->db->getNewprotos($perm);
		$settings = $this->db->getSettings();
		$legis = $this->db->getCurrentLegislatur();
		$sett = [];
		$sett['auto_invite'] = intval($settings['AUTO_INVITE_N_HOURS']);
		$sett['disable_restore'] = intval($settings['DISABLE_RESTORE_OLDER_DAYS']);
		$sett['meeting_hour'] = intval($committee['default_meeting_hour']);
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'tops' => $tops,
			'resorts' => $resorts,
			'member' => $member,
			'newproto' => $newproto,
			'settings' => $sett,
			'legislatur' => $legis
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
			$tops = $this->db->getTopsOpen($vali->getFiltered('committee'));
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
	
	/**
	 * ACTION top edit|create - only create formular
	 */
	public function itopedit(){
		$perm = 'stura';
		if (!isset($_GET['committee'])){
			$_GET['committee'] = $perm;
		}
		//create accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_GET, $validator_map, false);
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
			$top = NULL;
			if (isset($vali->getFiltered()['tid'])){
				$t = $this->db->getTopById($vali->getFiltered('tid'));
				if ($t && $t['used_on'] == NULL && $t['gname'] == $vali->getFiltered('committee')){
					$top = $t;
				}
			}
			$resorts = $this->db->getResorts($vali->getFiltered('committee'));
			$member = $this->db->getMembers($vali->getFiltered('committee'));
			$this->includeTemplate(__FUNCTION__, [
				'top' => $top,
				'resorts' => $resorts,
				'member' => $member
			]);
		}
	}
	
	/**
	 * POST action
	 * itop update or create top in database 
	 */
	public function itopupdate(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'headline' => [ 'regex',
				'pattern' => '/^[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]{1}[a-zA-Z0-9\-_;,.:!?+\*%()#\/\\ äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9\-_;,.:!?+\*%()#\/\\äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]{1}$/',
				'error' => 'Ungültige Überschrift'
			],
			'resort' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Resort Id.'
			],
			'person' => ['name',
				'minlength' => '3',
				'error' => 'Ungültige Zeichen im Namen.',
				'empty'
			],
			'duration' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Dauer'
			],
			'goal' => ['regex',
				'pattern' => '/^[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9, äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+$/',
				'empty',
				'error' => 'Ungültige Zielsetzung'
			],
			'guest' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger "Gast" Status'
			],
			'intern' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger "Intern" Status'
			],
			'text' => ['regex',
				'pattern' => '/^(.|\r|\n)*$/',
				'empty',
				'noTagStrip',
				'error' => 'Ungültiger Text',
				'replace' => [['<del>','</del>'], ['%[[del]]%','%[[/del]]%']],
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Topkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Top Id.'
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
			$filtered = $vali->getFiltered();
			$filtered['text'] = str_replace(['%[[del]]%','%[[/del]]%'], ['<del>','</del>'], strip_tags($filtered['text']));
			
			$top = [];
			if ($filtered['tid'] > 0){
				$top = $this->db->getTopById($filtered['tid']);
				if (!$top
					|| $top['gname'] != $filtered['committee']
					|| $top['hash'] != $filtered['hash']){
					$this->json_not_found('Top nicht gefunden');
					return;
				}
			}
			$resort = false;
			if ($filtered['resort'] > 0){
				$resorts = $this->db->getResorts($filtered['committee']);
				if (isset($resorts[$filtered['resort']])){
					$resort = $resorts[$filtered['resort']];
				}
			}
			$gremium = $this->db->getCommitteebyName($filtered['committee']);

			$top['headline'] = $filtered['headline'];
			$top['resort'] = (is_array($resort))? $resort['id']: NULL;
			$top['person'] = $filtered['person']? $filtered['person'] : NULL ;
			$top['expected_duration'] = $filtered['duration'];
			$top['goal'] = $filtered['goal']? $filtered['goal']: NULL;
			$top['text'] = $filtered['text'];
			$top['guest'] = $filtered['guest'];
			$top['intern'] = $filtered['intern'];
			$top['gremium'] = $gremium['id'];
			$top['hash'] = (isset($top['hash']) && $top['hash'])? $top['hash'] : md5($top['headline'].date_create()->getTimestamp().$filtered['committee'].$vali->getFiltered('committee').mt_rand(0, 640000));
			
			//create 
			if (!isset($top['id'])){
				$newtid = $this->db->createTop($top);
				if (!$newtid) {
					$this->json_not_found('Top konnte nicht erstellt werden.');
					return;
				} else {
					$top['id'] = $newtid;
				}
			} else { //or update top 
				if (!$this->db->updateTop($top)) {
					$this->json_not_found('Top konnte nicht aktualisiert werden.');
					return;
				}
			}
			$top = $this->db->getTopById($top['id']);
			$top['addedOn'] = date_create($top['added_on'])->format('d.m.Y H:i');
			if ($resort) {
				$top['resort'] = $resort;
			}
			
			//return result
			$this->json_result = [
				'top' => $top,
				'success' => true,
				'msg' => ($filtered['tid'])?'Top aktualisiert':'Top erstellt.'
			];
			
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * new protocol create / update
	 */
	public function npupdate(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'date' => ['date',
				'parse' => 'Y-m-d',
				'error' => 'Ungültiges Datum'
			],
			'time' => ['time',
				'format' => 'H:i',
				'error' => 'Ungültige Uhrzeit'
			],
			'management' => ['name',
				'empty',
				'error' => 'Ungültiger Name: Sitzungsleitung'
			],
			'protocol' => ['name',
				'empty',
				'error' => 'Ungültige Name: Protokoll'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Top Id.'
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
			$nproto = [];
			if ($vali->getFiltered('npid') > 0){
				$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
				if (!$nproto
					|| $nproto['gname'] != $vali->getFiltered('committee')
					|| $nproto['hash'] != $vali->getFiltered('hash')){
					$this->json_not_found('Protokoll nicht gefunden');
					return;
				}
				$state = ($nproto['generated_url'] != null)? 2 : (($nproto['invite_mail_done'])? 1 : 0);
				if ($state == 2){
					$this->json_access_denied('Protokoll kann nicht geändert werden');
					return;
				}
			}
			//don't allow old date
			$validateDate = date_create_from_format('Y-m-d H:i:s', $vali->getFiltered('date').' '.$vali->getFiltered('time').':00');
			$now = date_create();
			
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
			if ($diff > 3600 * 24) { //one day
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Das Datum kann nicht in der Vergangenheit liegen.'
				];
				$this->print_json_result();
				return;
			}
			
			$gremium = $this->db->getCreateCommitteebyName($vali->getFiltered('committee'));
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			$memberLink = ['proto'=> NULL, 'manag'=> NULL];
			foreach ($members as $member){
				if ($member['name'] === $vali->getFiltered('management')){
					$memberLink['manag'] = $member['id'];
				}
				if ($member['name'] === $vali->getFiltered('protocol')){
					$memberLink['proto'] = $member['id'];
				}
			}
			
			$nproto['gremium'] = $gremium['id'];
			$nproto['date'] = $vali->getFiltered('date').' '.$vali->getFiltered('time').':00';
			$nproto['management'] = $memberLink['manag'];
			$nproto['protocol'] = $memberLink['proto'];
			$nproto['hash'] = (isset($nproto['hash']) && $nproto['hash'])? $nproto['hash'] : md5($nproto['date'].date_create()->getTimestamp().$vali->getFiltered('committee').mt_rand(0, 640000));
			$nproto['created_by'] = $this->auth->getUsername();
			$nproto['created_on'] = $now->format('Y-m-d H:i:00');
			
			//create
			$newnpid = 0;
			if (!isset($nproto['id'])){
				$newnpid = $this->db->createNewproto($nproto);
				if (!$newnpid) {
					$this->json_not_found('Sitzung konnte nicht geplant werden.');
					return;
				} else {
					$nproto['id'] = $newnpid;
				}
			} else { //or update newproto
				if (!$this->db->updateNewproto($nproto)) {
					$this->json_not_found('Geplant Sitzung konnte nicht aktualisiert werden.');
					return;
				}
			}
			$nproto = $this->db->getNewprotoById($nproto['id']);
			$settings = $this->db->getSettings();
	
			$state = ($nproto['generated_url'] != null)? 2 : (($nproto['invite_mail_done'])? 1 : 0);
			$disable_restore = false;
			if ($state == 2){
				$today = date_create();
				$npdate = date_create($nproto['date']);
				$npdate->setTime(0, 0);
				$diff1 = $today->getTimestamp() - $npdate->getTimestamp();
				if ( $diff1 > 3600 * 24 * intval($settings['DISABLE_RESTORE_OLDER_DAYS']) ){
					$disable_restore = true;
				}
			}
			
			$out = [
				'state' => 	$state,
				'stateLong' => (['Geplant', 'Eingeladen', 'Erstellt'])[$state],
				'disableRestore' => 	$disable_restore,
				'generatedUrl' => 		$nproto['generated_url'],
				'inviteMailDone' =>	$nproto['invite_mail_done'],
				'id' 	=> 	$nproto['id'],
				'hash' 	=> 	$nproto['hash'],
				'date'  =>	date_create($nproto['date'])->format('d.m.Y H:i'),
				'isNew' =>	($newnpid==0)? 1 : 0,
				'm' 	=>  $nproto['management']?$nproto['management']:'' ,
				'p' 	=>  $nproto['protocol']?$nproto['protocol']:''
			];
			//return result
			$this->json_result = [
				'np' => $out,
				'success' => true,
				'msg' => ($vali->getFiltered('npid'))?'Neue Sitzung aktualisiert':'Neue Sitzung geplant.'
			];
				
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * delete new protocol
	 */
	public function npdelete(){
		//calculate accessmap
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
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Top Id.'
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
			$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
			if (!$nproto
				|| $nproto['gname'] != $vali->getFiltered('committee')
				|| $nproto['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Protokoll nicht gefunden');
				return;
			}
			$state = ($nproto['generated_url'] != null)? 2 : (($nproto['invite_mail_done'])? 1 : 0);
			if ($state == 2){
				$this->json_access_denied('Protokoll kann nicht geändert werden');
				return;
			}
			$ok = $this->db->deleteNewprotoById($nproto['id']);
			if ($ok){
				$this->json_result = [
					'success' => true,
					'msg' => 'Sitzungseinladung wurde gelöscht.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Sitzungseinladung konnte nicht gelöscht werden.'
				];
			}
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * send invitation for new protocol
	 */
	public function npinvite(){
		//calculate accessmap
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
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Top Id.'
			],
			'text' => ['regex',
				'pattern' => '/^(.|\r|\n)*$/',
				'empty',
				'error' => 'Ungültiger Text'
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
			$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
			if (!$nproto
				|| $nproto['gname'] != $vali->getFiltered('committee')
				|| $nproto['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Protokoll nicht gefunden');
				return;
			}
			//don't allow dates in the past
			$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
			$now = date_create();
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
			
			if ($diff > 3600) { //one hour
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Einladungen für vergangene Sitzungen können nicht gesendet werden'
				];
				$this->print_json_result();
				return;
			}
			
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			$membernames = [
				'p'=> ($nproto['protocol'] && isset($members[$nproto['protocol']]))? $members[$nproto['protocol']] : NULL, 
				'm'=> ($nproto['management'] && isset($members[$nproto['management']]))? $members[$nproto['management']] : NULL 
			];
			$nproto['membernames'] = $membernames;
			$openProtos = $this->db->getProtocols($vali->getFiltered('committee'), true, false);
			
			$ok = $this->send_mail_invitation(
				$nproto,
				[	'username' => $this->auth->getUsername(), 
					'userFullname' => $this->auth->getUserFullName(), 
					'mail' => $this->auth->getUserMail()	],
				$openProtos,
				$vali->getFiltered('text')
			);
			if ($ok){
				// update proto
				$nproto['invite_mail_done'] = true;
				$this->db->updateNewproto($nproto);
				
				$this->json_result = [
					'success' => true,
					'msg' => 'Einladung erfolgreich versendet.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim Senden der Einladung.'
				];
			}
			$this->print_json_result();
		}
	}
}