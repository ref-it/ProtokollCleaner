<?php
/**
 * CONTROLLER Resolution Controller
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

class ResolutionController extends MotherController
{
	/**
	 * class constructor
	 * @param Database $db        	
	 * @param AuthHandler $auth        	
	 * @param Template $template        	
	 */
	public function __construct($db, $auth, $template)
	{
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * load and parse resolutions from database into array
	 * @param string $gremium
	 * @param false|integer $pid protocol id
	 */
	private function loadDBReso($gremium, $pid = false, $order = 'DESC', $year = NULL){
		//permission - edit this to add add other committee
		prof_flag('db read');
		if ($pid !== false){
			$resos = $this->db->getResolutionByCommittee($gremium, $pid, $order);
		} else {
			$resos = $this->db->getResolutionByCommittee($gremium, NULL, $order);
		}
		prof_flag('db read done');
		//parse resolutions: categorize, and split to array
		foreach ($resos as $pos => $rawres){
			if ($year != NULL && $year.'' != substr($rawres['date'], 0,4)){
				unset($resos[$pos]);
				continue;
			}
			if ($rawres['noraw'] == 0){
				$tmp = protocolHelper::parseResolution($rawres['text'], NULL, NULL, $gremium);
				$resos[$pos] = array_merge( $tmp, $resos[$pos]);
			} else {
				$resos[$pos]['Titel'] = $rawres['text'];
				if (1 == preg_match('/(explizit( *)abgelehnt|ist( *)abgelehnt|>( *)abgelehnt)/i', $rawres['text'])){
					$resos[$pos]['Beschluss'] = 'abgelehnt';
				} else {
					$resos[$pos]['Beschluss'] = 'angenommen';
				}
			}
			$resos[$pos]['date_obj'] = date_create_from_format('Y-m-d His', $rawres['date'].' 000000' );
		}
		return $resos;
	}
	
	/**
	 * load protocol list from db
	 * @param string $gremium
	 */
	private function loadDBProtos($gremium){
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		prof_flag('wiki request');
		$intern = $x->getPagelistAutoDepth(parent::$protomap[$gremium][0]);
		prof_flag('wiki request end');
		$extern = [];
		if (parent::$protomap[$gremium][0] != parent::$protomap[$gremium][1]){
			prof_flag('wiki request');
			$extern = $x->getPagelistAutoDepth(parent::$protomap[$gremium][1]);
			prof_flag('wiki request end');
		}
		$dbprotocols = $this->db->getProtocols($gremium);
		$i_path_lng = strlen(parent::$protomap[$gremium][0]) + 1;
		$e_path_lng = strlen(parent::$protomap[$gremium][1]) + 1;
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
			}
			if (isset($p['agreed']) && $p['agreed'] > 0){
				$intern_and_extern[$name]['agreed'] = true;
			}
			$intern_and_extern[$name]['id'] = $p['id'];
		}
		return $intern_and_extern;
	}
	
	/**
	 * ACTION list resolutions
	 * draw resolution list
	 */
	public function rlist(){
		//calculate accessmap
		$validator_map = [
			'pid' => ['integer',
				'min' => 1,
			],
		];
		$vali = new Validator();
		$vali->validateMap($_GET, $validator_map, false);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->renderErrorPage(403, null);
			} else if($vali->getLastErrorCode() == 404){
				$this->renderErrorPage(404, null);
			} else {
				$this->renderErrorPage(404, null);
			}
			return;
		}

		//permission - edit this to add add other committee
		$perm = 'stura';
		$y = (isset($_GET) && isset($_GET['y']) && intval($_GET['y'], 10))? intval($_GET['y'], 10): NULL;
		if ($y){
			if ($y < 1990 || $y > 2999){
				$y = NULL;
			}
		}
		$order = (isset($_GET) && isset($_GET['order']) && ($_GET['order'] === 'ASC' || $_GET['order'] === 'asc'))? 'ASC' : 'DESC';
		$resos = $this->loadDBReso(
			$perm, 
			(isset($vali->getFiltered()['pid']))? 
				$vali->getFiltered('pid'): 
				false,
			$order,
			($y)? $y: NULL
			);
		
		$this->t->setTitlePrefix('Beschlussliste - '.ucwords( $perm, " \t\r\n\f\v-"));
		$this->t->appendCssLink('reso.css', 'screen,projection');
		$this->t->appendJsLink('reso.js');
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'reso' => &$resos,
			'committee' => $perm,
			'year' => $y,
			'order' => $order,
			'pid' => (isset($vali->getFiltered()['pid']))? $vali->getFiltered()['pid'] : NULL,
		]);
		$this->t->printPageFooter();
	}
	
	public function resoToWiki($noecho = false) {
		prof_flag('Reso To Wiki');
		$perm = 'stura';
		
		//access permission
		if (!checkUserPermission($perm)&&!checkUserPermission('cronwiki')) {
			$this->json_access_denied();
		}
		
		// load resolutions
		$resos = $this->loadDBReso($perm, false, 'ASC');
		//load protocols
		$dbprotocols = $this->loadDBProtos($perm);
		
		// create wiki text
		prof_flag('create WikiText');
		ob_start();
		$this->includeTemplate('wikiresolist', [
			'reso' => &$resos,
			'proto' => &$dbprotocols,
			'committee' => $perm,
		]);
		$wikiText = ob_get_clean();
		
		// write to wiki
		prof_flag('Write Resolist To Wiki');
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		
		$ok = $x->putPage( 
			parent::$protomap[$perm][2], 
			$wikiText, 
			['sum' => 'GENERIERT mit '.BASE_TITLE.' von ('. $this->auth->getUserName().')']);
		if (!$ok){
			error_log('NewProto -> WIKI: Could not write. Request: Put Page - '.parent::$protomap[$perm][2].' - Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():''));
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Fehler beim Schreiben. (Code: '.$x->getStatusCode().')'
			];
		}
		if (!$ok && !$noecho){
			$this->print_json_result();
			return;
		} elseif (!$ok && $noecho){
			$this->json_result['eMsg'] = 'NewProto -> WIKI: Could not write. Request: Put Page - '.parent::$protomap[$perm][2].' - Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():'');
			return $this->json_result;
		}
		// Return result and timing
		prof_flag('Done');
		
		$this->json_result = [
			'success' => true,
			'msg' => 'Beschlussliste erfolgreich aktualisiert.',
			'timing' => prof_print(false)['sum']
		];
		if (!$noecho){
			http_response_code (200);
			$this->print_json_result();
			return;
		} else {
			return $this->json_result;
		}
	}
}

?>