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
	 * echo protocol links to wiki page in html form
	 * @param Protocol $p
	 */
	private static function printProtoLinks($p){
		echo '<div class="protolinks">';
		echo '<a href="" class="btn reload">Reload</a>';
		echo '<a href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][0]).'/'.$p->name.'?do=edit" class="btn" target="_blank">Edit Protocol</a>';
		if ($p->draft_url){
			echo '<a href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][1]).'/'.$p->name.'" class="btn" target="_blank">View Draft</a>';
		}
		if ($p->public_url){
			echo '<a href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][1]).'/'.$p->name.'" class="btn" target="_blank">View Public</a>';
		}
		echo '</div>';
	}
	
	/**
	 * echo protocol status in html form
	 * @param Protocol $p Protocol object
	 * @param boolean $includeUrls call printProtoLinks automatically
	 */
	private static function printProtoStatus($p, $includeUrls = true){
		echo '<div class="protostatus">';
		echo '<div class="general">';
		echo '<span class="date">Gremium: '.$p->committee.'</span>';
		echo '<span class="date">Protokoll vom: '.$p->date->format('d.m.Y').'</span>';
		echo '<span class="state">Status: '.
			(($p->id == NULL)? 'Nicht öffentlich': 
			(($p->draft_url!=NULL)?'Entwurf':
			(($p->public_url!=NULL)?'Veröffentlicht':'Unbekannt'))).'</span>';
		if ($includeUrls) self::printProtoLinks($p);
		echo '</div></div>';
	}
	
	/**
	 * echo protocol tag errors in html form
	 * @param Protocol $p Protocol object
	 */
	private static function printProtoTagErrors($p){
		$opened = false;
		foreach($p->tags as $tag => $state){
			if ($state == 0){
				continue;
			}
			if (!$opened){
				echo '<div class="error tagerrors">';
			}
			echo '<div class="tagerror">';
			if ($tag == 'old'){
				echo 'Nicht-Öffentlicher Teil wurde nicht geschlossen.';
			} else {
				echo "Der Tag '$tag' wurde häufiger ";
				if ($state > 0){
					echo 'geöffnet als geschlossen.';
				} else {
					echo 'geschlossen als geöffnet.';
				}
			}
			echo '</div>';
		}
		if ($opened) {
			echo '</div>';
		}
	}
	
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
	private function loadWikiProtoBase ($committee, $protocol_name){
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
	 * render and show protocoll list
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
			echo '<div class="proto '.$state.'">'.
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
		} else if (false) {
			//TODO remember on save dont allow intern == extern protocol path =>> parse view is ok, but no storing
			//remove this else here
		} else {
			$p = $this->loadWikiProtoBase($vali->getFiltered()['committee'], $vali->getFiltered()['proto']);
			if ($p === NULL) {
				$this->renderErrorPage(404, null);
				return;
			}
			$this->t->printPageHeader();
			//insert protocol link + status
			self::printProtoStatus($p);
			//run protocol parser
			$ph = new protocolHelper();
			$ph->parseProto($p, $this->auth->getUserFullName(), true);
			self::printProtoTagErrors($p);
			echo $p->preview;
			
			//TODO echo todos, fixme, ...
			//TODO open and close tags
		//TODO detect internal part
		//TODO detect todos
		//TODO detect resolutions
		//TODO cleanup array
		//TODO check attachements
		//TODO detect Legislatur
		//TODO set changed by
			$this->t->printPageFooter();
		}
	
	}
	
}