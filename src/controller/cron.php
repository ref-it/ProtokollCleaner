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