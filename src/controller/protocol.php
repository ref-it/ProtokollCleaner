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
		
		$esc_PROTO_IN = str_replace(':', '/', PROTOMAP[$perm][0]);
		$esc_PROTO_OUT = str_replace(':', '/', PROTOMAP[$perm][1]);
		
		echo '<pre>DD: '; var_dump($drafts); echo '</pre>';
		echo "<h3>Stura - Protokolle</h3>";
		
		echo '<div class="protolist">';
		foreach ($intern as $i){
			$p = substr($i, strrpos($i, ':') + 1);
			if (substr($p,0, 2)!='20') continue;
			$state = (in_array(PROTOMAP[$perm][0].":$p", $extern))? 
				'public' : 
				(isset($drafts[$p])? 
					'draft' : 
					'privat');
			echo '<div class="proto '.$state.'">'.
					"<span>$p</span>".
					"<div>".
					(($state!='private')?'<button class="btn" type="button">Bearbeiten</button>':'').
					'<span><a href="'.WIKI_URL.'/'.$esc_PROTO_IN.'/'.$p.'">Intern</a></span>'.
					(($state != 'privat')?
					'<span><a href="'.WIKI_URL.'/'.$esc_PROTO_OUT.'/'.$p.'">Extern</a></span>':'').
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
			$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
			$intern = $x->getSturaInternProtokolls();
			$drafts = $this->db->getProtocols($vali->getFiltered()['committee'], true);
			if (in_array($vali->getFiltered()['proto'], $drafts)){
				$this->t->printPageHeader();
				$ph = new protocolHelper();
				$ph->parseProto($vali->getFiltered()['committee'], $vali->getFiltered()['proto']);
				$this->t->printPageFooter();
			} else if (in_array(PROTOMAP[$vali->getFiltered()['committee']][0].':'.$vali->getFiltered()['proto'], $intern)){
				$this->t->printPageHeader();
				$ph = new protocolHelper();
				$ph->parseProto($vali->getFiltered()['committee'], $vali->getFiltered()['proto']);
				$this->t->printPageFooter();
			} else {
				$this->renderErrorPage(403, null);
			}
		}
	
	}
	
}