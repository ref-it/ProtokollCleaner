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
		echo '<a class="btn btn-primary mr-1" href="" class="btn reload">Reload</a>';
		echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][0]).'/'.$p->name.'?do=edit" class="btn" target="_blank">Edit Protocol</a>';
		if ($p->draft_url){
			echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][1]).'/'.$p->name.'" class="btn" target="_blank">View Draft</a>';
		}
		if ($p->public_url){
			echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][1]).'/'.$p->name.'" class="btn" target="_blank">View Public</a>';
		} else {
			echo '<button class="btn btn-danger mr-1" type="button" class="btn">'.(($p->agreed_on === NULL)?'Entwurf ': '' ).'Veröffentlichen</button>';
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
		echo '<span class="committee"><span>Gremium:</span><span>'.$p->committee.'</span></span>';
		echo '<span class="date"><span>Protokoll vom:</span><span>'.$p->date->format('d.m.Y').'</span></span>';
		echo '<span class="state"><span>Status:</span><span>'.
			(($p->id == NULL)? 'Nicht öffentlich': 
			(($p->draft_url!=NULL)?'Entwurf':
			(($p->public_url!=NULL)?'Veröffentlicht':'Unbekannt'))).'</span></span>';
		echo '<span class="legislatur"><span>Legislatur:</span><span><button type="button" class="btn btn-outline-primary sub">-</button>'.$p->legislatur.'<button type="button" class="add btn btn-outline-primary">+</button></span></span>';
		echo '<span class="sitzung"><span>Sitzung:</span><span>'.$p->protocol_number.'</span></span>';
		echo '<span class="resolutions"><span>Angenommene Beschlüsse:</span><span>'.count($p->resolutions).'</span></span>';
		
		echo '<span class="sitzung"><span>TODOs:</span><span>'.(
			((isset($p->todos['todo']['public']))? count($p->todos['todo']['public']): 0)
			+((isset($p->todos['todo']['intern']))? count($p->todos['todo']['intern']): 0)
		).'</span></span>';
		echo '<span class="sitzung"><span>Fixme:</span><span>'.(
			((isset($p->todos['fixme']['public']))? count($p->todos['fixme']['public']): 0)
			+((isset($p->todos['fixme']['intern']))? count($p->todos['fixme']['intern']): 0)
		).'</span></span>';
		if ($includeUrls) self::printProtoLinks($p);
		echo '</div></div>';
	}
	
	/**
	 * echo protocol tag errors in html form
	 * @param Protocol $p Protocol object
	 */
	private static function createProtoTagErrors($p){
		foreach($p->tags as $tag => $state){
			if ($state == 0){
				continue;
			}
			if ($tag == 'old'){
				$p->parse_errors[] = 'Nicht-Öffentlicher Teil wurde nicht geschlossen.';
			} else {
				$e = "Der Tag '$tag' wurde häufiger ";
				if ($state > 0){
					$e.= 'geöffnet als geschlossen.';
				} else {
					$e.= 'geschlossen als geöffnet.';
				}
				$p->parse_errors[] = $e;
			}
		}
	}
	
	/**
	 * echo protocol (parse) errors in html form
	 * @param Protocol $p Protocol object
	 */
	private static function printProtoParseErrors($p){
		$opened = false;
		foreach($p->parse_errors as $err){
			if (!$opened){
				echo '<div class="error parseerrors"><h3>Parsing Errors</h3>';
				$opened = true;
			}
			echo '<div class="perror alert alert-danger">';
			echo $err;
			echo '</div>';
		}
		if ($opened) {
			echo '</div>';
		}
	}
	
	/**
	 * echo protocol resolutions in html form
	 * @param Protocol $p Protocol object
	 */
	private static function printResolutions($p){
		$opened = false;
		foreach($p->resolutions as $pos => $reso){
			if (!$opened){
				echo '<div class="resolutionlist"><h3>Beschlüsse</h3>';
				$opened = true;
			}
			echo '<div class="resolution alert alert-info">';
			echo "<strong>[{$reso['r_tag']}]</strong> {$reso['Titel']}";
			echo '<input class="resotoggle" id="reso_toggle_'.$pos.'" type="checkbox" value="1">';
			echo '<label tabindex="0" class="label resotoggle btn btn-outline-info" for="reso_toggle_'.$pos.'"></label>';
			echo '<div class="togglebox" tabindex="-1">';
			if (isset($reso['Ja'])) echo "<span class='yes'>Ja: {$reso['Ja']}</span>";
			if (isset($reso['Nein'])) echo "<span class='no'>Nein: {$reso['Nein']}</span>";
			if (isset($reso['Enthaltungen'])) echo "<span class='abstention'>Enthaltungen: {$reso['Enthaltungen']}</span>";
			echo "<span class='result'>Beschluss: {$reso['Beschluss']}</span>";
			if (isset($reso['p_tag'])){
				if ($reso['p_tag']){
					echo "<span class='ptag'>Protokoll: {$reso['p_tag']}</span>";
				} else {
					echo "<span class='ptag'>Protokoll: PARSE ERROR</span>";
				}
			}
			echo "<span class='category'>Kategorie: {$reso['type_long']}</span>";
			echo '</div></div>';
		}
		if ($opened) {
			echo '</div>';
		}
	}
	
	/**
	 * echo protocol attachements in html form
	 * @param Protocol $p Protocol object
	 */
	private static function printAttachements($p){
		$opened = false;
		if (is_array($p->attachements))
			foreach($p->attachements as $pos => $attach){
				if (!$opened){
					echo '<div class="attachlist"><h3>Anhänge</h3>';
					echo '<p><i>Alle hier angehakten Dateien werden automatisch mit veröffentlicht.</i></p>';
					echo '<div class="attachementlist alert alert-info">';
					$opened = true;
				}
				echo '<div class="line"><input type="checkbox" value="1" id="attach_check_'.$pos.'" checked>';
				$split = explode(':', $attach);
				echo '<label class="resolution noselect" for="attach_check_'.$pos.'"><span>'.end($split).'</span>';
				echo '<a href="'.WIKI_URL.'/'.str_replace(':', '/', $attach).'" target="_blank">';
				echo 'Öffnen';
				echo '</a></label></div>';
			}
		if ($opened) {
			echo '</div></div>';
		}
	}
	
	/**
	 * echo protocol fixmes in html form
	 * @param Protocol $p Protocol object
	 */
	private static function printTodos($p){
		$opened = false;
		if (isset($p->todos['todo']['public']))
			foreach($p->todos['todo']['public'] as $pos => $todo){
				if (!$opened){
					echo '<div class="todolist"><h3>TODOs</h3>';
					$opened = true;
				}
				echo '<div class="todo alert alert-warning">';
				echo preg_replace('/(todo)/i', '<span class="highlight">$1</span>', $todo[0]);
				echo '</div>';
			}
		if (isset($p->todos['todo']['intern']))
			foreach($p->todos['todo']['intern'] as $pos => $todo){
			if (!$opened){
				echo '<div class="todos intern">';
			}
			echo '<div class="todo">';
			echo '<strong>(Intern)</strong> ' . preg_replace('/(todo)/i', '<span class="highlight">$1</span>', $todo[0]);
			echo '</div>';
		}
		if ($opened) {
			echo '</div>';
		}
	}
	/**
	 * echo protocol fixmes in html form
	 * @param Protocol $p Protocol object
	 */
	private static function printFixmes($p){
		$opened = false;
		if (isset($p->todos['fixme']['public']))
			foreach($p->todos['fixme']['public'] as $pos => $fixme){
				if (!$opened){
					echo '<div class="fixmelist"><h3>FIXMEs</h3>';
					$opened = true;
				}
				echo '<div class="fixme alert alert-warning">';
				echo preg_replace('/(fixme)/i', '<span class="highlight">$1</span>', $fixme[0]);
				echo '</div>';
			}
		if (isset($p->todos['fixme']['intern']))
			foreach($p->todos['fixme']['intern'] as $pos => $fixme){
				if (!$opened){
					echo '<div class="fixme intern">';
				}
				echo '<div class="fixme">';
				echo '<strong>(Intern)</strong> ' . preg_replace('/(fixme)/i', '<span class="highlight">$1</span>', $fixme[0]);
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
			$p = $this->loadWikiProtoBase($vali->getFiltered()['committee'], $vali->getFiltered()['proto'], true);
			if ($p === NULL) {
				$this->renderErrorPage(404, null);
				return;
			}
			$this->t->printPageHeader();
			
			//run protocol parser
			$ph = new protocolHelper();
			$ph->parseProto($p, $this->auth->getUserFullName(), $p->agreed_on === NULL );
			//insert protocol link + status
			self::printProtoStatus($p);
			//protocol errors
			self::createProtoTagErrors($p);
			self::printProtoParseErrors($p);
			//resolution list
			self::printResolutions($p);
			//show todo list
			self::printTodos($p);
			//show fixme list
			self::printFixmes($p);
			//list Attachements
			self::printAttachements($p);
			
			//echo protocol diff
			echo $p->preview;
			
			//TODO detect Legislatur
	
			$this->t->printPageFooter();
		}
	
	}
	
}