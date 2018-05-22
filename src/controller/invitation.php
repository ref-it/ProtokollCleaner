<?php
use SILMPH\File;
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
		$mail_address = parent::$protomap[$proto['gname']][3];
		$tops_tmp = $this->db->getTopsOpen($proto['gname']);
		$tops = [];
		foreach ($tops_tmp as $id => $top){
			if (!$top['skip_next']){
				$tops[$id] = $top;
			}
		}
		
		$resorts = $this->db->getResorts($proto['gname']);
		
		if (is_string($mail_address)){
			$mailer->mail->addAddress($mail_address);
		} elseif (is_array($mail_address)) {
			foreach ($mail_address as $mail_addr){
				$mailer->mail->addAddress($mail_addr);
			}
		}
		
		$mailer->mail->Subject = 'Sitzungseinladung - '.ucfirst(strtolower($proto['gname'])).' - '.$pdate->format('d.m.Y H:i');
		
		$mailer->bindVariables([
			'sender' 	=> ($user != NULL)? $user['username'] : '',
			'message' 	=> $additional_message,
			'committee' => $proto['gname'],
			'tops' 		=> $tops,
			'proto'		=> $proto, 
			'resorts'	=> $resorts,
			'protoInternLink' => WIKI_URL.'/'.parent::$protomap[$proto['gname']][0].'/',
			'protoPublicLink' => WIKI_URL.'/'.parent::$protomap[$proto['gname']][1].'/',
			'unreconciled_protocols' => $openProtos,
			'topLink' 	=> BASE_URL.BASE_SUBDIRECTORY.'invite',
			'protoLink' => BASE_URL.BASE_SUBDIRECTORY.'protolist'
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
		$tops = $this->db->getTopsOpen($perm, true);
		$resorts = $this->db->getResorts($perm);
		$member = $this->db->getMembersCounting($perm);
		$committee = $this->db->getCommitteebyName($perm);
		$newproto = $this->db->getNewprotos($perm);
		$settings = $this->db->getSettings();
		$legis = $this->db->getCurrentLegislatur();
		$oldproto = $this->db->getProtocolsByLegislatur($perm, $legis['number']);
		$sett = [];
		$sett['auto_invite'] = intval($settings['AUTO_INVITE_N_HOURS']);
		$sett['disable_restore'] = intval($settings['DISABLE_RESTORE_OLDER_DAYS']);
		$sett['meeting_hour'] = intval($committee['default_meeting_hour']);
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'tops' => $tops,
			'resorts' => $resorts,
			'member' => $member,
			'committee' => $perm,
			'newproto' => $newproto,
			'settings' => $sett,
			'legislatur' => $legis,
			'protomap' => self::$protomap[$perm],
			'nth-proto' => (count($oldproto)+1)
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
				//delete files/attachements
				require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
				$fh = new FileHandler($this->db);
				$fh->deleteFilesByLinkId($top['id']);
				//remove top
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
				
				$deltops = $this->db->getDeleteTopsByMemberIdSoft($vali->getFiltered('mid'));
				if (is_array($deltops) || count($deltops) > 0 ){
					require (FRAMEWORK_PATH.'/class.fileHandler.php');
					$fh = new FileHandler($this->db);
					foreach ($deltops as $dtop){
						$fh->deleteFilesByLinkId($dtop['id']);
					}
				}
				
				//remove member of not generated newprotocols
				$npnc = $this->db->deleteMemberOfUncreatedNewprotoByMemberId($vali->getFiltered('mid'));
				//delete tops
				$tr = $this->db->deleteTopsByMemberIdSoft($vali->getFiltered('mid'));
				//delete newprotocol
				if ($tr){
					$np = $this->db->deleteNewprotoByMemberIdSoft($vali->getFiltered('mid'));
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
			'mjob' => ['regex',
				'empty',
				'pattern' => '/^[a-zA-Z0-9\-_ .,äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]*$/',
				'error' => 'Fehler bei der Tätigkeitsangabe. Kommaseparierte Liste.'
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
				$joblist_tmp = explode(',', $vali->getFiltered('mjob'));
				$joblist = [];
				foreach ($joblist_tmp as $job){
					$job = trim($job, "-.,_ \t\n\r\0\x0B");
					if ($job != '') $joblist[] = $job;
				}
				
				$newmem = [
					'name' => $vali->getFiltered('mname'),
					'gremium' => $grem['id'],
					'job' => implode(', ', $joblist)
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
				'empty',
				'multi' => ',',
				'multi_add_space'
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
				'noTrim',
				'trimLeft' => "\n\r\0\x0B",
				'trimRight' => " \t\n\r\0\x0B",
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

			//check if there is planned protocol and mail invitation is done
			$newprotos = $this->db->getNewprotos($filtered['committee']);
			$newprotoMailDone = false;
			foreach ($newprotos as $proto){
				if (!$proto['generated_url'] && $proto['invite_mail_done']){
					$newprotoMailDone = true;
					break;
				}
			}
			if ($newprotoMailDone){
				if (!isset($top['id']) && !is_array($resort)){
					//if is new top and there is no resort set automatically to 'skip_next' = true
					$top['skip_next'] = 1;
				} elseif(isset($top['id']) && $top['resort'] && !is_array($resort)){ 
					//if resort top is refactored to normal top set automatically to 'skip_next' = true
					$top['skip_next'] = 1;
				}
			}
			
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
			$newtid = 0;
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
			$top = $this->db->getTopById($top['id'], true);
			$top['addedOn'] = date_create($top['added_on'])->format('d.m.Y H:i');
			if ($resort) {
				$top['resort'] = $resort;
			}
			$top['isNew'] = ($newtid>0)? 1:0;
			$top['goal'] = ($top['goal'])?$top['goal']:'';
			$top['person'] = ($top['person'])?$top['person']:'---';
			$top['expected_duration'] = ($top['expected_duration'])?$top['expected_duration']:0;
			
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
			
			$stateLonMap = ['Geplant', 'Eingeladen', 'Erstellt'];
			$out = [
				'state' => 	$state,
				'stateLong' => $stateLonMap[$state],
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
				'error' => 'Sitzungskennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
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
			// open protocols // not aggreed
			$notAgreedProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, true, false, " AND P.ignore = 0 AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$draftStateProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, false, true, " AND P.ignore = 0 AND (P.public_url IS NULL) AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
		
			$ok = $this->send_mail_invitation(
				$nproto,
				[	'username' => $this->auth->getUsername(), 
					'userFullname' => $this->auth->getUserFullName(), 
					'mail' => $this->auth->getUserMail()	],
				['notAgreed' => $notAgreedProtocols, 'draftState' => $draftStateProtocols ],
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
	
	/**
	 * POST action
	 * create protocol in wiki
	 */
	public function nptowiki(){
		$memberStateOptions = ['Fixme', 'J', 'E', 'N'];
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
				'error' => 'Ungültige Sitzungsid'
			],
			'legislatur' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Legislatur.'
			],
			'nthproto' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Sitzungsnummer'
			],
			'reaskdone' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger Parameter: reaskdone'
			],
			'management' => ['name',
				'empty',
				'error' => 'Ungültiger Name: Sitzungsleitung'
			],
			'protocol' => ['name',
				'empty',
				'error' => 'Ungültige Name: Protokoll'
			],
			'member' => [ 'array',
				'key' => ['integer',
					'min' => '1',
					'error' => 'Invalid Member Id.'
				],
				'validator' => ['integer',
					'min' => '0',
					'max' => max(0, count($memberStateOptions)-1),
					'error' => 'Invalid Member State.'
				],
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
			$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
			if (!$nproto
				|| $nproto['gname'] != $vali->getFiltered('committee')
				|| $nproto['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Protokoll nicht gefunden');
				return;
			}
			$nproto['name'] = date_create($nproto['date'])->format('Y-m-d');
			//don't allow dates in the past
			$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
			$now = date_create();
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
				
			if ($diff > 3600) { //one hour
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Vergangene Sitzungen können nicht im Wiki erzeugt werden.'
				];
				$this->print_json_result();
				return;
			}
			// dont allow duplicate creation on same newprotoelement
			if ($nproto['generated_url']) {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Abeschlossene Protokolle können nicht noch einmal erzeugt werden.'
				];
				$this->print_json_result();
				return;
			}
			// check if page exists on wiki -> reask user if it should be overwritten
			require_once (SYSBASE.'/framework/class.wikiClient.php');
			$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
			if (!$vali->getFiltered('reaskdone')) {
				$a = $x->getPage(parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name']);
				if ($a != ''){
					$this->json_result = [
						'success' => true,
						'reask' => true,
						'msg' => '<div class="alert alert-danger" style="color: #770000;">Im Wiki existiert bereits ein Protokoll mit dem Namen "'.$nproto['name'].'".<br>Soll das Protokoll wirklich überschrieben werden?</div>'
					];
					$this->print_json_result();
					return;
				}
			}
			//management , protocol, member statemap
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			foreach ($members as $id => $member){
				//management , protocol
				if ($member['name'] === $vali->getFiltered('management')){
					$nproto['management'] = $member['id'];
				}
				if ($member['name'] === $vali->getFiltered('protocol')){
					$nproto['protocol'] = $member['id'];
				}
				// member statemap
				$members[$id]['state'] = (isset($vali->getFiltered('member')[$member['id']]))? $vali->getFiltered('member')[$member['id']] : 0;
				$members[$id]['stateName'] = $memberStateOptions[$members[$id]['state']];
			}
			// open protocols // not aggreed
			$notAgreedProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, true, false, " AND P.ignore = 0 AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$draftStateProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, false, true, " AND P.ignore = 0 AND (P.public_url IS NULL) AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$newprotoProtocols_tmp = $this->db->getNewprotos($vali->getFiltered('committee'));
			$newprotoProtocols = [];
			foreach ($newprotoProtocols_tmp as $np) {
				$newprotoProtocols[ date_create($np['date'])->format('Y-m-d') ] = $np;
			}
			//tops and gather file ids
			$files = [];
			require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			$tops_tmp = $this->db->getTopsOpen($nproto['gname'], true);
			$tops = [];
			$skipped = [];
			foreach ($tops_tmp as $id => $top){
				if (!$top['skip_next']){
					$tops[$id] = $top;
					if ($top['filecounter'] > 0) $files[$top['id']] = $fh->filelist($top['id']);
				} else {
					$skipped[$id] = $top;
				}
			}
			//resortalias
			$resorts = $this->db->getResorts($vali->getFiltered('committee'));
			
			//create protocoltext
			ob_start();
			$this->includeTemplate('protocol_template_'.strtolower($vali->getFiltered('committee')), [
				'legislatur' => $vali->getFiltered('legislatur'),
				'nthproto' => $vali->getFiltered('nthproto'),
				'proto' => $nproto,
				'members' => $members,
				'date' => date_create($nproto['date']),
				'protoInternLink' => WIKI_URL.'/'.parent::$protomap[$vali->getFiltered('committee')][0].'/',
				'protoPublicLink' => WIKI_URL.'/'.parent::$protomap[$vali->getFiltered('committee')][1].'/',
				'openProtocols' => ['notAgreed' => $notAgreedProtocols, 'draftState' => $draftStateProtocols, 'newproto' => $newprotoProtocols ],
				'protoAttachBasePath' => parent::$protomap[$vali->getFiltered('committee')][0],
				'files' => $files,
				'tops' => $tops,
				'resorts' => $resorts,
			]);
			$prot_text = ob_get_clean();
			
			if (DEBUG >= 2){
				echo '<pre>'; var_dump($prot_text); echo '</pre>';
				return;
			}
			
			$ok = false;
			//write protocol to wiki
			$ok = $x->putPage(
				parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'],
				$prot_text,
				['sum' => 'GENERIERT mit '.BASE_TITLE.' von ('. $this->auth->getUsername().')']
				);
			if ($ok == false){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim Schreiben. (Code: '.$x->getStatusCode().')'
				];
				error_log('NewProto -> WIKI: Could not write. Request: Put Page - '.parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'].' - Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():''));
				$this->print_json_result();
				return;
			}
			//upload files
			$attach_base = parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'];
			foreach ($files as $tid => $filelist){
				/* @var $file File */
				foreach ($filelist as $file){
					$ok = false;
					$wikipath = $attach_base.':'.str_replace(' ', '_', $file->filename).(($file->fileextension)?'.'.$file->fileextension:'');
					$ok = $x->putAttachement($wikipath,$fh->fileToBase64($file),['ow' => true]);
					if ($ok == false){
						$this->json_result = [
							'success' => false,
							'eMsg' => 'Fehler beim Dateiupload. (Code: '.$x->getStatusCode().')'
						];
						error_log('NewProto -> WIKI: Could not write. Request: Put Page - '.parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'].' - Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():''));
						$this->print_json_result();
						return;
					}
				}
			}
			// update tops
			foreach ($tops as $top){
				$top['used_on'] = $nproto['id'];
				$this->db->updateTop($top);
			}
			// unskip skipped for next week
			foreach ($skipped as $top){
				$top['skip_next'] = 0;
				$this->db->updateTop($top);
			}
			
			// update newproto 
			$nproto['generated_url'] = $nproto['name'];
			$this->db->updateNewproto($nproto);
			if (!$ok){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim DB Update.'
				];
				$this->print_json_result();
				return;
			}

			$this->json_result = [
				'success' => true,
				'msg' => 'Protokoll im Wiki erstellt.',
				'url' => WIKI_URL.'/'.str_replace(':', '/', parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'])
			];
			$this->print_json_result();
			
		}
	}
	
	/**
	 * POST action
	 * restore newproto + used tops
	 */
	public function nprestore(){
		$memberStateOptions = ['Fixme', 'J', 'E', 'N'];
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
				'error' => 'Ungültige Sitzungsid'
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
			$nproto['name'] = date_create($nproto['date'])->format('Y-m-d');
			//don't allow dates in the past
			$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
			$now = date_create();
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
			$settings = $this->db->getSettings();
			if ($diff > 3600 * 24 * intval($settings['DISABLE_RESTORE_OLDER_DAYS'])) { // default 3 weeks
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Vergangene Sitzungeneinladungen können nicht wiederhergestellt werden.'
				];
				$this->print_json_result();
				return;
			}
			// dont allow duplicate creation on same newprotoelement
			if (!$nproto['generated_url']) {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Nicht abeschlossene Einladungen können nicht wiederhergestellt werden.'
				];
				$this->print_json_result();
				return;
			}
			
			//tops
			$tops_tmp = $this->db->getTopsByNewproto($nproto['id']);
			// update tops
			foreach ($tops_tmp as $top){
				$top['used_on'] = NULL;
				$ok = $this->db->updateTop($top);
				if (!$ok){
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Fehler beim DB Update.'
					];
					$this->print_json_result();
					return;
				}
			}
			
			// update newproto
			$nproto['generated_url'] = NULL;
			$ok = $this->db->updateNewproto($nproto);
			if (!$ok){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim DB Update.'
				];
				$this->print_json_result();
				return;
			}
			
			$this->json_result = [
				'success' => true,
				'msg' => 'Protokoll wiederhergestellt.',
			];
			$this->print_json_result();
				
		}
	}
}
