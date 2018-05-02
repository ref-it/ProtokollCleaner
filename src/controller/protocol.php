<?php
/**
 * CONTROLLER Protocol Controller
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

class ProtocolController extends MotherController {
	/**
	 * request protocol from intern wiki and load basic information from database
	 * 
	 * basic information:
	 * 		name
	 * 		url
	 * 		committee
	 * 		(committe_id) if protocol is known in database
	 * 		date
	 * 		(id) if protocol is known in database
	 * 		(draft_url) if protocol is known in database
	 * 		(public_url) if protocol is known in database
	 * 
	 * @param string $committee
	 * @param string $protocol_name
	 * @param boolean $load_attachements
	 * @return Protocol|NULL
	 */
	private function loadWikiProtoBase ($committee, $protocol_name, $load_attachements = false){
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		prof_flag('get wiki page');
		$a = $x->getPage(parent::$protomap[$committee][0].':'.$protocol_name);
		prof_flag('got wiki page');
		if ($a == false //dont accept non existing wiki pages
			|| $a == ''
			|| strlen($protocol_name) < 10 //protocol start with date tag -> min length 10
			|| !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", substr($protocol_name, 0,10))) { //date format yyyy-mm-dd
			return NULL;
		}
		$p = new Protocol($a);
		$p->committee = $committee;
		$p->committee_id = $this->db->getCreateCommitteeByName($committee)['id'];
		$p->name = $protocol_name;
		$p->url = parent::$protomap[$p->committee][0].':'.$p->name;
		$p->date = date_create_from_format('Y-m-d His', substr($p->name, 0,10).' 000000');
		
		$dbprotocols = $this->db->getProtocols($committee);
		if (array_key_exists($p->name, $dbprotocols)){
			$p->id = $dbprotocols[$p->name]['id'];
			$p->draft_url = $dbprotocols[$p->name]['draft_url'];
			$p->public_url = $dbprotocols[$p->name]['public_url'];
		}
		$dbresolution = $this->db->getResolutionByPTag($committee, $protocol_name, true);
		if ($dbresolution != NULL && count($dbresolution) >= 1){
			$p->agreed_on = $dbresolution[0]['id'];
		}
		if ($load_attachements){
			prof_flag('get wiki attachement list');
			$p->attachements = $x->listAttachements(parent::$protomap[$p->committee][0].':'.$p->name );
			if ($p->attachements == false) $p->attachements = [];
			prof_flag('got wiki attachement list');
		}
		// use legislatur map
		$tmp_l = $this->db->getLegislaturByDate($p->date->format('Y-m-d'));
		if (count($tmp_l) == 0) $tmp_l = $this->db->getCurrentLegislatur();
		$p->legislatur = intval($tmp_l['number']);
		
		$date_legi = date_create_from_format('Y-m-d His', $tmp_l['start']. ' 000000');
		$p->legislatur_week = intval(floor($p->date->diff($date_legi)->days/7))+1;
		
		return $p;
	}
	
	/**
	 * class constructor
	 * @param Database $db
	 * @param AuthHandler $auth
	 * @param Template $template
	 */
	function __construct($db, $auth, $template){
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * ACTION plist
	 * (stura) protocol list
	 * render and show protocol list
	 * displays 
	 */
	public function plist(){
		//permission - edit this to add add other committee
		$perm = 'stura';
		
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		prof_flag('wiki request');
		$intern = $x->getPagelistAutoDepth(parent::$protomap[$perm][0]);
		if (!$intern) $intern = [];
		prof_flag('wiki request end');
		$extern = [];
		if (parent::$protomap[$perm][0] != parent::$protomap[$perm][1]){
			prof_flag('wiki request');
			$extern = $x->getPagelistAutoDepth(parent::$protomap[$perm][1]);
			if (!$extern) $extern = [];
			prof_flag('wiki request end');
		}
		$dbprotocols = $this->db->getProtocols($perm);
		$dbhasreso = $this->db->getProtocolHasResoByCommittee($perm);
		$i_path_lng = strlen(parent::$protomap[$perm][0]) + 1;
		$e_path_lng = strlen(parent::$protomap[$perm][1]) + 1;
		$counter = ['intern' => count($intern), 'published' => count($extern), 'draft' => 0];
		// ------------------------
		// mark protocols that are published but dont exist intern anymore
		$intern_and_extern = [];
		foreach ($intern as $k => $v){
			$name = substr($v, $i_path_lng);
			$intern_and_extern[$name]['intern'] = true;
		}
		foreach ($extern as $k => $v){
			$name = substr($v, $e_path_lng);
			$intern_and_extern[$name]['extern'] = true;
		}
		foreach ($dbprotocols as $name => $p){
			if (isset($p['draft_url'])){
				$intern_and_extern[$name]['draft'] = true;
				$counter['draft']++;
			}
			if (isset($dbhasreso[$name])){
				$intern_and_extern[$name]['reso'] = true;
			}
			if (isset($p['agreed']) && $p['agreed'] > 0){
				$intern_and_extern[$name]['agreed'] = true;
			}
			$intern_and_extern[$name]['id'] = $p['id'];
		}
		krsort($intern_and_extern);
		
		//load template
		$this->t->setTitlePrefix('Protokolle - '.ucwords( $perm, " \t\r\n\f\v-"));
		$this->t->appendCssLink('proto.css', 'screen,projection');
		$this->t->appendJsLink('protocol.js');
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [ //pass arrays by reference
			'int_ext' 	=> &$intern_and_extern, 
			'committee' => $perm,
			'counter' 	=> &$counter
		]);
		$this->t->printPageFooter();
	}
	
	/**
	 * ACTION pedit
	 * (stura) show modify edit
	 */
	public function pedit_view(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'proto' => ['regex', 
				'pattern' => '/^([2-9]\d\d\d)-(0[1-9]|1[0-2])-([0-3]\d)((-|_)([a-zA-Z0-9]){1,30}((-|_)?([a-zA-Z0-9]){1,2}){0,30})?$/'
			]
		];
		$vali = new Validator();
		$vali->validateMap($_GET, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->renderErrorPage(403, null);
			} else if($vali->getLastErrorCode() == 404){
				$this->renderErrorPage(404, null);
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->t->printPageHeader();
				echo '<h3>'.$vali->getLastErrorMsg().'</h3>';
				$this->t->printPageFooter();
			}
		} else if (!checkUserPermission($vali->getFiltered()['committee'])) {
			$this->renderErrorPage(403, null);
		} else {
			$p = $this->loadWikiProtoBase($vali->getFiltered()['committee'], $vali->getFiltered()['proto'], true);
			if ($p === NULL) {
				$this->renderErrorPage(404, null);
				return;
			}
			$this->t->setTitlePrefix('Protokollkontrolle');
			$this->t->appendCssLink('proto.css', 'screen,projection');
			$this->t->appendJsLink('protocol.js');
			$this->t->printPageHeader();
			echo $this->getChallenge(); // get/echo post challenge

			//run protocol parser
			$ph = new protocolHelper();
			$ph->parseProto($p, $p->agreed_on === NULL );
			//insert protocol link + status
			protocolOut::printProtoStatus($p);
			//protocol errors
			protocolOut::createProtoTagErrors($p);
			protocolOut::printProtoParseErrors($p);
			//list Attachements
			protocolOut::printAttachements($p);
			//resolution list
			protocolOut::printResolutions($p);
			//show todo-/fixme-/deleteme list
			protocolOut::printTodoElements($p);
			
			//echo protocol diff
			echo $p->preview;
			//TODO detect Legislatur
	
			$this->t->printPageFooter();
		}
	}
	
	/**
	 * ACTION p_publish
	 * (stura) publish protocol
	 */
	public function p_publish(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'proto' => ['regex', 
				'pattern' => '/^([2-9]\d\d\d)-(0[1-9]|1[0-2])-([0-3]\d)((-|_)([a-zA-Z0-9]){1,30}((-|_)?([a-zA-Z0-9]){1,2}){0,30})?$/'
			],
			'period' => ['integer',
				'min' => '1',
				'max' => '99',
				'error' => 'Ungültige Ligislatur.'
			],
			'attach' => ['array',
				'empty',
				'false',
				'error' => 'Ungültige Protokollanhänge.',
				'validator' => ['regex',
					'pattern' => '/^(([a-zA-Z0-9\-_äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß])+((\.)([a-zA-Z0-9\-_äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß])+)*)$/'
				]
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
		} else if (parent::$protomap[$vali->getFiltered('committee')][0] == parent::$protomap[$vali->getFiltered('committee')][1]) {
			// on save dont allow intern == extern protocol path =>> parse view is ok, but no storing
			//may allow partial save like Todos, Fixmes, resolutions...
			http_response_code (403);
			$this->json_result = ['success' => false, 'eMsg' => 'Your not allowed to store this protocol.'];
			$this->print_json_result();
		} else {
			$p = $this->loadWikiProtoBase($vali->getFiltered('committee'), $vali->getFiltered()['proto'], true);
			if ($p === NULL) {
				$this->json_not_found();
				return;
			}
			//run protocol parser
			$ph = new protocolHelper();
			$ph->parseProto($p, $p->agreed_on === NULL, true);
			protocolOut::createProtoTagErrors($p);
			//---------------------------------
			// check and store
			// check for fatal errors -> abort
			if (isset($p->parse_errors['f']) && count($p->parse_errors['f']) > 0){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Protokoll entkält kritische Fehler:<strong><br>* '.implode('<br>* ', $p->parse_errors['f'] ).'</strong>'
				];
				$this->print_json_result();
				return;
			}
			//---------------------------------
			//check attachements
			$copy_attachements = []; //this attachements will be copied
			$tmp_attach = $vali->getFiltered('attach');
			foreach($p->attachements as $attach){
				$tmp = explode(':', $attach);
				$name = end($tmp);
				$key = array_search($name, $tmp_attach);
				if ($key !== false){
					$copy_attachements[]=$attach;
					unset($tmp_attach[$key]);
				}
			}
			unset($tmp);
			//now tmp_attach should be empty
			if (count($tmp_attach) > 0){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Unbekannte Dateianhänge:<strong><br>* '.implode('<br>* ', $tmp_attach ).'</strong>'
				];
				$this->print_json_result();
				return;
			}
			//---------------------------------
			//check legislatur
			if ($p->legislatur !== $vali->getFiltered('period') && abs($p->legislatur - $vali->getFiltered('period')) > 1 && checkUserPermission('legislatur_all')){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Du bist nicht berechtigt, die Legislaturnummer um mehr als 1 zu ändern.'
				];
				$this->print_json_result();
				return;
			}
			$p->legislatur = $vali->getFiltered('period');
			
			//---------------------------------
			//create protocol in wiki
			$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
			prof_flag('write wiki page');
			$put_res = $x->putPage(parent::$protomap[$vali->getFiltered('committee')][1].':'.$p->name, $p->external, ['sum' => 'GENERIERT mit '.BASE_TITLE.' von ('. $this->auth->getUserName().')']);
			prof_flag('wiki page written');
			if ($put_res == false){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim Veröffentlichen. (Code: '.$x->getStatusCode().')'
				];
				error_log('Proto Publish: Could not publish. Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():''));
				$this->print_json_result();
				return;
			}
			$is_draft = true;
			if ($p->agreed_on === NULL){
				$p->public_url = NULL;
				$p->draft_url = parent::$protomap[$vali->getFiltered()['committee']][1].':'.$p->name;
			} else {
				$is_draft = false;
				$p->public_url = parent::$protomap[$vali->getFiltered()['committee']][1].':'.$p->name;
				$p->draft_url = NULL;
			}
			
			//---------------------------------
			//create/update protocol in db
			$this->db->createUpdateProtocol($p);
			
			//---------------------------------
			//create/update resolutions
			$db_resolutions = $this->db->getResolutionByOnProtocol($p->id, true);
			//remember protocol resolution
			$proto_reso_new = [];
			//insert new resolutons, modify existing, delete old
			foreach ($p->resolutions as $reso){
				$reso['on_protocol'] = $p->id;
				//update existing resolutions...
				if (isset($db_resolutions[$reso['r_tag']])){
					$reso['id'] = $db_resolutions[$reso['r_tag']]['id'];
					//error if resolution protocol tag was changed
					if ($db_resolutions[$reso['r_tag']]['type_long'] == 'Protokoll'
						&& $db_resolutions[$reso['r_tag']]['p_tag'] !== $reso['p_tag']){
						$this->json_result = [
							'success' => false,
							'eMsg' => 'Protokollbeschlüsse müssen in der Reihenfolge bleiben, in der diese initial erstellt wurden.'
						];
						error_log('Proto Publish: User "'.$this->auth->getUsername()." tried to change protocol resolition order. r_tag: {$reso['r_tag']} -- old_p_tag: {$db_resolutions[$reso['r_tag']]['p_tag']} -- new_p_tag: {$reso['p_tag']} ");
					} else {
						//update resolution
						$this->db->updateResolution($reso);
					}
					unset($db_resolutions[$reso['r_tag']]);
				} else { //create resolution
					if ($reso['type_long'] == 'Protokoll'){
						$proto_reso_new[] = $reso;
					} else {
						$this->db->createResolution($reso);
					}
				}
			}
			//delete others
			foreach ($db_resolutions as $reso){
				//if old protocol exist with same text, dont create new and keep old
				$skip_delete = false;
				if ($reso['type_long'] == 'Protokoll'){
					foreach ($proto_reso_new as $key => $new_p_reso){
						if ($reso['noraw'] == 1 && $reso['text'] == $new_p_reso['Titel']	  // resolution crawled with resolution list
							|| $reso['noraw'] == 0 && $reso['text'] == $new_p_reso['text'] ){// crawled on protocol with this tool
							$skip_delete = true;
							unset($proto_reso_new[$key]);
						}
						if ($skip_delete) break;
					}
				}
				if ($skip_delete) continue;
				//keep linked protocol resolutions
				if ($reso['p_tag'] !== NULL && $reso['pid'] != null){
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Protokollbeschlüsse, welche mit einem veröffentlichten Protokoll verlinkt sind, können nicht gelöscht werden.'
					];
					error_log('Proto Publish: User "'.$this->auth->getUsername()." tried to delete linked protocol resolition {$reso['r_tag']} --- p_tag: {$reso['p_tag']}");
				} else {
					$this->db->deleteResolutionById($reso['id']);
				}
			}
			//create new protocoll resolutions now
			foreach ($proto_reso_new as $reso){
				$this->db->createResolution($reso);
			}
			//---------------------------------
			//create/update/delete todo|fixme|deleteme
			$db_todo = $this->db->getTodosByProtocol($p->id, true);
			foreach($p->todos as $proto_todo)
			{
				$proto_todo['on_protocol'] = $p->id;
				//create missing todo
				if (!isset($db_todo[$proto_todo['hash']])){
					$this->db->createTodo($proto_todo);
				} else {
					//ignore existing
					unset($db_todo[$proto_todo['hash']]);
				}
			}
			//delete only not 'done' todos
			$del_ids = [];
			foreach($db_todo as $todo)
			{
				if ($todo['done'] == 0){
					$del_ids[] = $todo['id'];
				}
			}
			if (count($del_ids) > 0){
				$this->db->deleteTodoById($del_ids);
			}
			//---------------------------------
			//test if attachements need to be removed
			$put_url = ($is_draft == true)?$p->draft_url : $p->public_url;
			//check existing attachements
			$current_attachements = $x->listAttachements($put_url);
			if ($current_attachements == false) $current_attachements = [];
			//copy missing attachements
			foreach($copy_attachements as $att){
				$tmp = explode(':', $att);
				$name = end($tmp);
				$exists = array_search($put_url.':'.$name, $current_attachements);
				if ($exists !== false){
					unset($current_attachements[$exists]);
					continue;
				}
				$data = $x->getAttachement($att);
				if ($data) {
					$x->putAttachement($put_url.':'.$name, $data, ['ow' => true]);
				} else {
					error_log("Couldn't fetch attachement: $att");
				}
			}
			unset($tmp);
			//remove not needed attachements
			foreach($current_attachements as $del_att){
				$x->deleteAttachement($del_att);
			}
			
			http_response_code (200);
			$this->json_result = [
				'success' => true,
				'msg' => 'Protokoll erfolgreich erstellt',
				'timing' => prof_print(false)['sum']
			];
			$this->print_json_result();
		}
	}
}