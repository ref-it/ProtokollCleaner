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
	 * contains constant PROTOMAP
	 * @var array
	 */
	private static $protomap = PROTOMAP;
	
	/**
	 * request protocol from wiki and load basic information from database
	 * 
	 * basic information:
	 * 		name
	 * 		url
	 * 		comittee
	 * 		(committe_id) if protocol is known in database
	 * 		date
	 * 		(id) if protocol is known in database
	 * 		(draft_url) if protocol is known in database
	 * 		(public_url) if protocol is known in database
	 * 
	 * @param string $committee
	 * @param string $protocol_name
	 * @return Protocol|NULL
	 */
	private function loadWikiProtoBase ($committee, $protocol_name, $load_attachements = false){
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		prof_flag('get wiki page');
		$a = $x->getPage(self::$protomap[$committee][0].':'.$protocol_name);
		prof_flag('got wiki page');
		if ($a == false //dont accept non existing wiki pages
			|| $a == ''
			|| strlen($protocol_name) < 10 //protocol start with date tag -> min length 10
			|| !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", substr($protocol_name, 0,10))) { //date format yyyy-mm-dd
			echo 'kekse lskdhjfa öjjklf ksaj fsaf 
				sad f
				sd f';
			return NULL;
		}
		$p = new Protocol($a);
		$p->committee = $committee;
		$p->committee_id = NULL;
		$p->name = $protocol_name;
		$p->url = self::$protomap[$p->committee][0].':'.$p->name;
		$p->date = date_create_from_format('Y-m-d', substr($p->name, 0,10));
		
		$dbprotocols = $this->db->getProtocols($committee);
		if (array_key_exists($p->name, $dbprotocols)){
			$p->id = $dbprotocols[$p->name]['id'];
			$p->committee_id = $dbprotocols[$p->name]['gremium'];
			$p->draft_url = $dbprotocols[$p->name]['draft_url'];
			$p->public_url = $dbprotocols[$p->name]['public_irl'];
		}
		$resolution = $this->db->getResolutionByPTag($committee, $protocol_name);
		if ($resolution != NULL && count($resolution) === 1){
			$p->agreed_on = $resolution;
		}
		if ($load_attachements){
			prof_flag('get wiki attachement list');
			$p->attachements = $x->listAttachements(self::$protomap[$p->committee][0].':'.$p->name );
			prof_flag('got wiki attachement list');
		}
		//TODO create legislatur map
		$p->legislatur = intval($this->db->getSettings()['LEGISLATUR']);
		
		return $p;
	}
	
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
	 * ACTION plist
	 * (stura) protocol list
	 * render and show protocol list
	 * displays 
	 */
	public function plist(){
		$this->t->appendJsLink('protocol.js');
		$this->t->printPageHeader();
		
		//permission - edit this to add add other committee
		$perm = 'stura';
		
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		$intern = $x->getSturaInternProtokolls();
		arsort($intern);
		$extern = $x->getSturaProtokolls();
		$drafts = $this->db->getProtocols($perm, true);
		
		$esc_PROTO_IN = str_replace(':', '/', self::$protomap[$perm][0]);
		$esc_PROTO_OUT = str_replace(':', '/', self::$protomap[$perm][1]);
		
		echo '<pre>DD: '; var_dump($drafts); echo '</pre>';
		echo "<h3>Stura - Protokolle</h3>";
		
		echo '<div class="protolist">';
		foreach ($intern as $i){
			$p = substr($i, strrpos($i, ':') + 1);
			if (substr($p,0, 2)!='20') continue;
			$state = (in_array(self::$protomap[$perm][0].":$p", $extern))? 
				'public' : 
				(isset($drafts[$p])? 
					'draft' : 
					'privat');
			echo '<div id="proto-'.$p.'" class="proto '.$state.'">'.
					"<span>$p</span>".
					"<div>".
					(($state!='private')?'<button class="btn" type="button">Bearbeiten</button>':'').
					'<span><a href="'.WIKI_URL.'/'.$esc_PROTO_IN.'/'.$p.'" target="_blank">Intern</a></span>'.
					(($state != 'privat')?
					'<span><a href="'.WIKI_URL.'/'.$esc_PROTO_OUT.'/'.$p.'" target="_blank">Extern</a></span>':'').
			'</div></div>';
		}
		echo '<!div>';
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
		
		if ($vali->getIsError() || !$this->auth->requireGroup($vali->getFiltered()['committee'])){
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
		} else {
			$p = $this->loadWikiProtoBase($vali->getFiltered()['committee'], $vali->getFiltered()['proto'], true);
			if ($p === NULL) {
				$this->renderErrorPage(404, null);
				return;
			}
			$this->t->appendJsLink('protocol.js');
			$this->t->printPageHeader();
			echo $this->getChallenge(); // get post challenge
			
			//run protocol parser
			$ph = new protocolHelper();
			$ph->parseProto($p, $this->auth->getUserFullName(), $p->agreed_on === NULL );
			//insert protocol link + status
			protocolOut::printProtoStatus($p);
			//protocol errors
			protocolOut::createProtoTagErrors($p);
			protocolOut::printProtoParseErrors($p);
			//list Attachements
			protocolOut::printAttachements($p);
			//resolution list
			protocolOut::printResolutions($p);
			//show todo list
			protocolOut::printTodos($p);
			//show fixme list
			protocolOut::printFixmes($p);
			//show delete list
			protocolOut::printDeletemes($p);
			
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
		if ($vali->getIsError() || !$this->auth->requireGroup($vali->getFiltered()['committee'])){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (self::$protomap[$vali->getFiltered()['committee']][0] == self::$protomap[$vali->getFiltered()['committee']][1]) {
			// on save dont allow intern == extern protocol path =>> parse view is ok, but no storing
			//may allow partial save like Todos, Fixmes, resolutions...
			http_response_code (403);
			$this->json_result = ['success' => false, 'eMsg' => 'Your not allowed to store this protocol.'];
			$this->print_json_result();
		} else {
			$p = $this->loadWikiProtoBase($vali->getFiltered()['committee'], $vali->getFiltered()['proto'], true);
			if ($p === NULL) {
				$this->json_not_found();
				return;
			}
			//run protocol parser
			$ph = new protocolHelper();
			$ph->parseProto($p, $this->auth->getUserFullName(), $p->agreed_on === NULL );
			
			//TODO check and store
			//insert protocol link + status
			//protocolOut::printProtoStatus($p);
			//protocol errors
			//protocolOut::createProtoTagErrors($p);
			//protocolOut::printProtoParseErrors($p);
			//list Attachements
			//protocolOut::printAttachements($p);
			//resolution list
			//protocolOut::printResolutions($p);
			//show todo list
			//protocolOut::printTodos($p);
			//show fixme list
			//protocolOut::printFixmes($p);
			//show delete list
			//protocolOut::printDeletemes($p);
			
			
			
			http_response_code (200);
			$this->json_result = ['success' => true, 'msg' => 'Protokoll erfolgreich erstellt'];
			$this->print_json_result();
		}
	}
	
	
}