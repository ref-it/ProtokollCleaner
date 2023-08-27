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
			$protocols_newproto = $this->db->getNewprotos($gremium, 'generated_url', true);
			//3 get current members
			$members = $this->db->getMembers($gremium);
			//remember protos
			$handleProtos = ['agreed_but_draft' => [], 'not_handled' => []];
			//4=check 2 not in 1 and protocol member set && member exists in 3
			foreach ($protocols_newproto as $nk => $np){
				if (!$nk) continue;
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
						$handleProtos['agreed_but_draft'][$nk]=$np;
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
			// room
			if (!$nproto['room']){
				$committee = $this->db->getCommitteebyName($nproto['gname']);
				$nproto['room'] = $committee['default_room'];
			}
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

	/**
	 * implode and concat resort for member job name
	 * @param array $member
	 * @üaram integer $gid
	 * @return array
	 */
	private function sgis2DbMember($member, $gid){
		$job = '';
		$out = [
			'name' => $member['name'],
			'gremium' => $gid,
			'job' => '',
			'overwrite' => NULL,
			'flag_elected' => 0,
			'flag_active' => 0,
			'flag_ref' => 0,
			'flag_stuff' => 0,
		];
		if (isset($member['dbid'])){
			$out['id'] = $member['dbid'];
		}
		foreach ($member['gremien'] as $k => $v){
			$handled = false;
			if ($k == 'Studierendenrat (StuRa)'){
				$ak = array_keys($v);
				$aks = implode(',', $ak);
				if (isset($v['Mitglied']) || false!==strpos( $aks,'Entsandt')){
					$out['flag_elected'] = 1;
					$handled = true;
				}
				if (isset($v['Aktiv'])){
					$out['flag_active'] = 1;
					$job.=((!empty($job))?', ':'').'Aktiv';
					$handled = true;
				}
				if (isset($v['Schlüsselmeister'])){
					$out['flag_active'] = 1;
					$job.=((!empty($job))?', ':'').'Schlüsselmeister';
					$handled = true;
				}
				if (isset($v['Angestellt'])){
					$out['flag_stuff'] = 1;
					$job.=((!empty($job))?', ':'').'Angestellt';
					$handled = true;
				}
				if (isset($v['Interclubverantwortlicher'])){
					$out['flag_active'] = 1;
					$job.=((!empty($job))?', ':'').'Interclubverantwortlich';
					$handled = true;
				}
				if (false !== strpos( $aks,'Entsandt')){
					foreach ($v as $role => $ign){
						if (false !== strpos( $role,'Entsandt')){
							$split = explode(' ', $role);
							$job.=((!empty($job))?', ':'')."{$split[0]} FSR {$split[1]}";
							$handled = true;
						}
					}
				}
			}
			if ($k == 'KTS'){
				$job.=((!empty($job))?', ':'').'KTS'.((isset($v['Hauptdelegiert']))?' (HD)':'').((isset($v['Nebendelegiert']))?' (ND)':'');
				$handled = true;
			}
			if ($k == 'Konsul' && isset($v['Mitglied'])){
				$job.=((!empty($job))?', ':'').'Konsul';
				$out['flag_stuff'] = 1;
				$handled = true;
			}
			if (0 === strpos( $k,'Referat')){
				$rname = substr($k,8);
				$pre = '';
				$ak = array_keys($v);
				$aks = implode(',', $ak);
				if (isset($this->resort_akuefi[$rname]) && $rname != 'Finanzen'){
					$rname = $this->resort_akuefi[$rname];
				}
				if (isset($v['stellv. Leiter']) || isset($v['stellv. Leiter:in'])){
					$out['flag_ref'] = 1;
					$pre = 'stellv. Ref ';
				}
				if (isset($v['Leiter']) || isset($v['Leiter:in'])){
					$out['flag_ref'] = 1;
					$pre = 'Leitung Ref ';
				}
				if ($rname == 'Finanzen'){
					$pre = '';
					if (false!==strpos($aks,'stellv. Haushaltsverantwortlich')){
						$rname = 'stellv. HV';
					} elseif (false!==strpos($aks,'Haushaltsverantwortliche')){
						$rname = 'HV';
					}
					if (false!==strpos($aks,'stellv. Kassenverantwortliche')){
						$rname = 'stellv. KV';
					} elseif (false!==strpos($aks,'Kassenverantwortliche')){
						$rname = 'KV';
					}
				}
				$job.=((!empty($job))?', ':'').$pre . $rname;
				$handled = true;
			}
			if (0 === strpos( $k,'AG ')){
				//ignore AG (disabled in request)
				$handled = true;
			}
			if ($handled){
				unset($member['gremien'][$k]);
			}
		}

		$out['job'] = $job;
		return $out;
	}

	private $resort_akuefi = [
		'Hochschulpolitik' => 'HoPo',
		'Hochschulpolitik (HoPo)' => 'HoPo',
		'Soziales' => 'Soz',
		'Internationales' => 'INT',
		'Öffentlichkeitsarbeit' => 'Öff',
		'Sport, Umwelt und Gesundheit' => 'SUG',
		'Politische Bildung' => 'PoliBi',
		'Finanzen' => 'HV',
		'Konferenz Thüringer Studierendenschaften (KTS)' => 'KTS',
		'Verbesserung der Studienbedingungen' => 'VerS',
		'Verbesserung der Studienbedingungen 2020' => 'VerS2020'
	];

	/**
	 * ACTION cronsgis
	 */
	public function sgis(){
		header('Content-type:text/plain;charset=utf-8');
		$perm = 'stura';
		//call sgis api -------------------
		require_once(FRAMEWORK_PATH.'/class.hHttpClient.php');
		$http = new hHttpClient();

		$sgis_error = 0;
		$sgis_member = [];
		$sgis_gremien = [];
		$sgis_start = microtime(true);
		$sgis_resorts = [
			'Interclubverantwortlicher' => 'AG Interclub',
			'Schlüsselmeister' => 'Tokenverantwortlicher',
			'Entsandt MB' => 'FSR MB',
			'Entsandt MN' => 'FSR MN',
			'Entsandt WM' => 'FSR WM',
			'Entsandt EI' => 'FSR EI',
			'Entsandt IA' => 'FSR IA',
			'Angestellt Angestellte',
		];
		$resort_akuefi = $this->resort_akuefi;
		$sgis_request_count = 1;

		//get Gremien
		$http->__doRequest('POST', SGISAPI_URL, NULL, NULL, [SGISAPI_HEADER => SGISAPI_KEY], [], [], ['action' => 'getGremien']);
		if ($http->getStatusCode() == 200) {
			$sgis = $http->getResponseBodyContent();
			$_sgis_gremien = json_decode($sgis, true)['result'];
		} else {
			$sgis_error = $sgis_error | 1;
		}

		if (!$sgis_error) {
			foreach ($_sgis_gremien as $g) {
				if (false === strpos($g['name'], 'Fachschaftsrat')) {
					$sgis_gremien[$g['id']] = $g;
					if (!$sgis_error) {
						//get Rollen
						$http->__doRequest('POST', SGISAPI_URL, NULL, NULL, [SGISAPI_HEADER => SGISAPI_KEY], [], [], ['action' => 'getRollen', 'gid' => $g['id']]);
						$sgis_request_count++;
						if ($http->getStatusCode() == 200) {
							$sgis2 = $http->getResponseBodyContent();
							$_sgis_rollen = json_decode($sgis2, true);
							if (!isset($_sgis_rollen['result']) || !isset($_sgis_rollen['result']['rollen'])) {
								/*echo "\n================\nNotice: Empty Gremium:\n";
								var_export($g);
								echo "\n";*/
								unset($sgis_gremien[$g['id']]);
							} else {
								if (!in_array($g['name'], $sgis_resorts, true) && (strpos($g['name'], 'Studierendenrat')===false)){
									$sgis_resorts[$g['id']] = str_replace([
										'Konsul',
										'KTS',
										'Referat Hochschulpolitik (HoPo)'
									], [
										'Angestellt Konsul',
										'Konferenz Thüringer Studierendenschaften (KTS)',
										'Referat Hochschulpolitik'
									], $g['name']);
								}
								$sgis_gremien[$g['id']]['rollen'] = $_sgis_rollen['result']['rollen'];
								if (!$sgis_error) {
									foreach ($sgis_gremien[$g['id']]['rollen'] as $r_key => $r) {
										//skip aktive in non stura
										if ($r['name'] == 'Aktiv' && false===strpos($g['name'], 'Studierendenrat')){
											if (DEBUG || DEBUG>0) echo "\n----------\nSkip - G:".$g['name'].' R:'.$r['name'];
											continue;
										}
										//get Member----------
										$http->__doRequest('POST', SGISAPI_URL, NULL, NULL, [SGISAPI_HEADER => SGISAPI_KEY], [], [], ['action' => 'getPersonen', 'rid' => $r['id']]);
										$sgis_request_count++;
										if ($http->getStatusCode() == 200) {
											$sgis3 = $http->getResponseBodyContent();
											$_sgis_member = json_decode($sgis3, true);
											if (!isset($_sgis_member['result'])) {
												/*echo "\n-----------\nNotice: Empty AG:\n";
												var_export($r);
												echo "\n";*/
												unset($sgis_gremien[$g['id']]['rollen'][$r_key]);
											} else {
												$sgis_gremien[$g['id']]['rollen'][$r_key]['member'] = $_sgis_member['result'];
												foreach ($_sgis_member['result']['currentMembers'] as $m){
													if (!isset($sgis_member[$m['id']])){
														$sgis_member[$m['id']]['id'] = $m['id'];
														$sgis_member[$m['id']]['name'] = $m['name'];
														$sgis_member[$m['id']]['gremien'] = [];
													}
													$sgis_member[$m['id']]['gremien'][$g['name']][$r['name']] = 1;
												}
											}
										} else {
											$sgis_error = $sgis_error | 4;
											echo 'Error SGIS-API: getPersonen:' . json_encode($r);
										}
										//--------------------
									}
								}
							}
						} else {
							$sgis_error = $sgis_error | 2;
							echo 'Error SGIS-API: getRollen:' . json_encode($g);
						}
					}
				}
			}
		}

		if (!$sgis_error) {
			//db resorts ==========================
			//diff resorts
			$db_resorts = $this->db->getResorts($perm);
			$diff_resorts = [
				'add' => $sgis_resorts,
				'delete' => []
			];
			if (false !== ($tpos = array_search('Tokenverantwortlicher', $diff_resorts['add']))){
				unset($diff_resorts['add'][$tpos]);
			}
			foreach ($db_resorts as $dbr) {
				if (false!== ($apos = array_search($dbr['type'] .' '. $dbr['name'],$diff_resorts['add'], true))){
					unset($diff_resorts['add'][$apos]);
				} else {
					$diff_resorts['delete'][] = $dbr;
				}
			}


			//delete resorts
			foreach ($diff_resorts['delete'] as $del_r){
				//delete top->resort link (update top)
				if (!$this->db->updateTopUnsetResortConstraintResortById($del_r['id'])){
					echo 'ERROR: DB: updateTopUnsetResortConstraintResortById: '- $this->db->getError();
				}
				//delete resort
				if (!$this->db->deleteResortById($del_r['id'])){
					echo 'ERROR: DB: DeleteResort: '- $this->db->getError();
				}
			}
			//add resorts
			$grem = $this->db->getCommitteebyName($perm);
			foreach ($diff_resorts['add'] as $k => $add_r){
				$type = NULL;
				$name = $add_r;
				$short = NULL;

				$ex = explode(' ', $add_r, 2);
				if (count($ex)==2){
					$type = $ex[0];
					$name = $ex[1];
				} else if ($add_r === 'Promovierendenvertretung') {
+					continue;
				} else {
					echo "\nError: Unknown Resort Type of: $add_r\n";
				}

				if (isset( $resort_akuefi[$name]) ){
					$short = $resort_akuefi[$name];
				}
				$this->db->createResort([
					'name' => $name,
					'type' => $type,
					'name_short' => $short,
					'gremium' => $grem['id'],
				]);
			}

			//db member list ==========================
			//diff member
			$db_member = $this->db->getMembers($perm);
			$db_member_name2id = [];
			foreach ($db_member as $mem){
				$db_member_name2id[$mem['name']] = $mem['id'];
			}
			$diff_member = [
				'add' => [],
				'delete' => $db_member_name2id,
				'update' => [],
			];

			foreach($sgis_member as $smem){
				if (isset($diff_member['delete'][$smem['name']])){
					$smem['dbid'] = $diff_member['delete'][$smem['name']];
					unset($diff_member['delete'][$smem['name']]);
					$diff_member['update'][] = $this->sgis2DbMember($smem, $grem['id']);
				} else {
					$diff_member['add'][] = $this->sgis2DbMember($smem, $grem['id']);
				}
			}

			//update members - delete -------------------------------------
			require_once(FRAMEWORK_PATH . '/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			foreach($diff_member['delete'] as $del_m) {
				$deltops = $this->db->getDeleteTopsByMemberIdSoft($del_m);
				if (is_array($deltops) || count($deltops) > 0) {
					foreach ($deltops as $dtop) {
						$fh->deleteFilesByLinkId($dtop['id']);
					}
				}

				//remove member of not generated newprotocols
				$npnc = $this->db->deleteMemberOfUncreatedNewprotoByMemberId($del_m);
				//delete tops
				$tr = $this->db->deleteTopsByMemberIdSoft($del_m);
				//delete $tr
				if ($tr) {
					$np = $this->db->deleteNewprotoByMemberIdSoft($del_m);
				}
				//delete member
				if ($np) {
					$me = $this->db->deleteMemberById($del_m);
				}
				//return result
				if (!$me) {
					echo "Error: Member Delete - mem['id']";
				}
			}

			//update members - add -------------------------------------
			foreach($diff_member['add'] as $add_m) {
				$res = $this->db->createMember($add_m);
				if (!$res){
					echo "\nError Add Member: ". json_encode($add_m);
					echo $this->db->getError();
					echo PHP_EOL;
				}
			}
			//update members - update -------------------------------------
			foreach($diff_member['update'] as $update_m) {
				if (array_key_exists( 'overwrite', $update_m)) unset($update_m['overwrite']) ;
				if (!$this->db->updateMemberById($update_m)){
					echo "\nError Update Member: ". json_encode($update_m);
					echo $this->db->getError();
					echo PHP_EOL;
				}
			}

			if (DEBUG || DEBUG>0){
				$sgis_end = microtime(true);
				$sgis_time = $sgis_end - $sgis_start;

				echo "\n\n=======================\nSGIS API Request Count: $sgis_request_count - in $sgis_time Second(s)";
			}
			die();
		} else {
			// error, can't reach api
			echo BASE_TITLE. "\nError: Couldn't reach SGIS API. Code: ".$sgis_error;
			die(BASE_TITLE. "\nError: Couldn't reach SGIS API. Code: ".$sgis_error);
		}
	}
}
