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
	 * ACTION croninfo
	 */
	public function info(){
		$this->t->setTitlePrefix('Croninfo');
		$this->t->printPageHeader();
		// cron users
		$users = CRON_USERMAP;
		$u = [];
		foreach ($users as $userName => $d){
			$found = false;
			foreach ($d['groups'] as $perm){
				if (strpos($perm, 'cron') !== false){
					$found = true;
					break;
				}
			}
			if (!$found) continue;
			$u[] = [$userName, $d['groups']];
		}
		// cron routes
		include (FRAMEWORK_PATH . '/config/config.router.php');
		$r = [];
		foreach ($cronRoutes as $request => $d){
			foreach ($d as $routeName => $d2){
				if (substr($routeName, strlen(BASE_SUBDIRECTORY) -1, 4) != 'cron') continue;
				$r[] = [$request, $routeName, $d2[3]] ;
			}
		}
		$this->includeTemplate(__FUNCTION__, [
			'user' => $u,
			'routes' => $r,
		]);
		$this->t->printPageFooter();
	}
	
	/**
	 * ACTION cronmail
	 */
	public function mail(){
		$this->invite_mail();
		$this->remember_proto_mail();
	}
	
	/**
	 * send protocol remember mails
	 */
	public function remember_proto_mail(){
		//get gremien
		$now = date_create();
		$committees = $this->db->getCommitteeList();
		foreach ($committees as $committee){
			$gremium = $committee['name'];
			
			//1 get db protocols ----------------------------
			$protocols_db_tmp = $this->db->getProtocols($gremium, false , false, false, false, 'AND (P.public_url IS NOT NULL OR P.ignore=1 OR P.draft_url IS NOT NULL)');
			$protocols_db = ['draft_state' => [], 'ignore' => [], 'all' => []];
			//filter db protocols
			//date + state
			foreach ($protocols_db_tmp as $p){
				if ($p['ignore']){
					$protocols_db['ignore'][$p['date']] = true;
				} elseif($p['draft_url']){
					$protocols_db['draft_state'][$p['date']] = true;
				}
				$protocols_db['all'][$p['date']] = $p;
			}
			//2 get ready newproto protocols -----------------
			$protocols_newproto = $this->db->getNewprotos($gremium, 'generated_url');
			//3 get current members
			$members = $this->db->getMembers($gremium);
			//remember protos
			$handleProtos = ['agreed_but_draft' => [], 'not_handled' => []];
			//4=check 2 not in 1 and protocol member set && member exists in 3
			foreach ($protocols_newproto as $nk => $np){
				$pdate = date_create($np['date']);
				if (!isset($protocols_db['all'][$nk]) ){
					if ($now->getTimestamp() > $pdate->getTimestamp() + 86400 * 3) {					
						$handleProtos['not_handled'][$nk]=$np;
					} else {
						continue;
					}
				} elseif(isset($protocols_db['ignore'][$nk])) {
					continue;
				} elseif(isset($protocols_db['draft_state'][$nk])){
					if ($protocols_db['draft_state'][$nk]['agreed'] &&
						$now->getTimestamp() > $pdate->getTimestamp() + 86400 * 7) {
						$handleProtos['agreed_but_draft'][]=$np;
					} else {
						continue;
					}
				}
			}

			//create mail for every 4
			$settings=$this->db->getSettings();
			foreach ($handleProtos as $group => $set){
				foreach ($set as $date => $np){
					//test to prevent spamming
					if ($np['mail_proto_remember']){
						$breakdate = date_create($np['mail_proto_remember']);
						$breakdate->modify('+1 day');
						if ($now->getTimestamp() <= $breakdate->getTimestamp()){
							continue;
						}
					}
					//setup mailer
					$mailer = new MailHandler();
					$mailer->setLogoImagePath('/../public/images/logo_f.png');
					$initOk = $mailer->init($settings);
					// mail initialisation failed
					if (!$initOk) return false;
					//send email
					if (isset($members[$np['protocol']]['email']) && $members[$np['protocol']]['email']){
						//mail to person
						$mail_address = $members[$np['protocol']]['email'];
					} else {
						//mail to group
						$mail_address = parent::$protomap[$gremium][3];
					}
					
					if (is_string($mail_address)){
						$mailer->mail->addAddress($mail_address);
					} elseif (is_array($mail_address)) {
						foreach ($mail_address as $mail_addr){
							$mailer->mail->addAddress($mail_addr);
						}
					}
					
					$mailer->mail->Subject = 'Protokollerinnerung - '.$date.' - '.(($group=='not_handled')?'Entwurf nicht veröffentlicht': 'Abgestimmt, aber nicht veröffentlicht');
					
					$mailer->bindVariables([
						'newproto' => $np,
						'date' => $date,
						'member' => (isset($members[$np['protocol']])) ? $members[$np['protocol']]: NULL,
						'group' => $group,
						'gremium' => $gremium,
						'protoLink' 	=> BASE_URL.BASE_SUBDIRECTORY.'protoedit?committee=stura&proto='.$date,
						'toolLink' 	=> BASE_URL.BASE_SUBDIRECTORY
					]);
					$mailer->setTemplate('proto_remember');
					
					if($mailer->send(false, false, true, true)){
						//update last mail send on newproto -> test to prevent spamming
						$np['mail_proto_remember'] = $now->format('Y-m-d H:i:s');
						$this->db->updateNewproto($np);
					} else {
						error_log('Es konnte keine Mail versendet werden. Prüfen Sie bitte die Konfiguration. '.((isset($mailer->mail) && isset($mailer->mail->ErrorInfo))? $mailer->mail->ErrorInfo: '' ));
						return false;
					}
				}
			}
		}
	}
	
	/**
	 * send sizungs einladungs mails
	 */
	private function invite_mail(){
		// calculate pending date
		$settings = $this->db->getSettings();
		$nowm = date_create();
		$nowm->modify("-1 hour");
		$date = date_create();
		if ($date->format('i') != '0'){ // current time + 1-59 minutes -> round to next hour
			$date->modify("+1 hour");
			$date->setTime($date->format('H'), 0, 0 );
		}
		$date->modify("+{$settings['AUTO_INVITE_N_HOURS']} hour"); // add auto invite time
		// get pending newprotos
		//calculate pending protos
		$nprotos = $this->db->getNewprotoPending($date->format('Y-m-d H:i:s'), $nowm->format('Y-m-d H:i:s'));
		// send mail for each pending protocol
		$ok = true;
		require_once(SYSBASE.'/controller/invitation.php');
		$ic = new InvitationController($this->db, $this->auth, null);
		
		foreach ($nprotos as $nproto){
			if (!$ok) break;
			$members = $this->db->getMembers($nproto['gname']);
			$membernames = [
				'p'=> ($nproto['protocol'] && isset($members[$nproto['protocol']]))? $members[$nproto['protocol']] : NULL,
				'm'=> ($nproto['management'] && isset($members[$nproto['management']]))? $members[$nproto['management']] : NULL
			];
			$nproto['membernames'] = $membernames;
			// open protocols // not aggreed
			$notAgreedProtocols = $this->db->getProtocols($nproto['gname'], false, false, true, false, " AND P.ignore = 0 AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$draftStateProtocols = $this->db->getProtocols($nproto['gname'], false, false, false, true, " AND P.ignore = 0 AND (P.public_url IS NULL) AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
		
			//send mail invitation
			$ok = $ic->send_mail_invitation(
				$nproto,
				NULL,
				['notAgreed' => $notAgreedProtocols, 'draftState' => $draftStateProtocols ],
				''
			);
			if ($ok){
				// update proto
				$nproto['invite_mail_done'] = true;
				$this->db->updateNewproto($nproto);
			} else {
				echo  date_create()->format('Y-m-d H:i:s').': Fehler beim Senden der Mail-Einladung. Gremium: '.$nproto['gname'];
			}
		}
		//return nothing if ok -> so cron only creates mail if something gone wrong
		return;
	}
	
	/**
	 * ACTION cronwiki
	 */
	public function wiki(){
		// trigger resolution to wiki
		// load resolutions
		require_once(SYSBASE.'/controller/resolution.php');
		$rc = new ResolutionController($this->db, $this->auth, null);
		$result = $resos = $rc->resoToWiki(true);
		//load protocols
		if (!$result['success']){
			echo $result['eMsg'];
		}
		//return nothing if ok -> so cron only creates mail if something gone wrong
		return;
	}
}