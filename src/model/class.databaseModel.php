<?php
use SILMPH\File;
/**
 * FRAMEWORK ProtocolHelper
 * database connection
 * implements all data related functions
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

/* -------------------------------------------------------- */
// Must include code to stop this file being accessed directly
if(defined('SILMPH') == false) { die('Illegale file access /'.basename(__DIR__).'/'.basename(__FILE__).''); }
/* -------------------------------------------------------- */

/**
 * 
 * @author Michael Gnehr <michael@gnehr.de>
 * @since 01.03.2017
 * @package SILMPH_framework
 * @see Database
 */
class DatabaseModel extends Database
{
	
	/**
	 * class constructor
	 */
	function __construct()
	{
		parent::__construct();
	}
	
	// ======================== SETTINGS FUNCTIONS ========================================================
	
	/**
	 * returns all settings stored in settings table
	 * @return array settingsarray format: [settingskey] => value 
	 */
	public function getSettings(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."settings`;";
		$result = $this->getResultSet($sql);
		$return = array();
		foreach ($result as $keyValue){
			$return[$keyValue['key']] = $keyValue['value'];
		}
		return $return;
	}
	
	/**
	 * generate or update setting
	 * @param string $key
	 * @param string|number $value for empty value set '' -> null will be ignored
	 * @return affected rows
	 */
	public function setSettings( $key, $value ){
		if ($value === null || !is_string($key) || trim($key) == '' ) {
			return 0;
		}
		$sql = "INSERT INTO `".TABLE_PREFIX."settings` (`key`, `value`) VALUES(?, ?) ON DUPLICATE KEY UPDATE `value` = ?";
		$this->protectedInsert($sql, "sss", array($key, $value, $value));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	// ======================== DATA FUNCTIONS ========================================================
	// --------- GET FUCNTIONS --------------------------------------
	/**
	 * return protocol list
	 * @param string $committee get protocols of choosen committee
	 * @param boolean $draftOnly only get protocols with draft status
	 * @param boolean $publicOnly only get protocols with public status (overwrites $draftOnly)
	 * @param boolean $notAgreedOnly only get protocols where agreed state is NULL
	 * @param boolean $agreedOnly only get protocols where agreed state is NOT NULL
	 * @return array protocol list by protocol name
	 */
	public function getProtocols( $committee , $draftOnly = false , $publicOnly = false, $notAgreedOnly = false, $agreedOnly = false, $where = ''){
		$a = ($draftOnly)? ' AND P.draft_url IS NOT NULL' : '';
		$a .= ($publicOnly)? ' AND P.public_url IS NOT NULL' : '';
		$a .= ($notAgreedOnly)? ' AND P.agreed IS NULL' : '';
		$a .= ($agreedOnly)? ' AND P.agreed IS NOT NULL' : '';
		//TODO optional join and count todos and resolutions
		$sql = "SELECT P.*, G.id as gid, G.name as gname FROM `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G WHERE P.gremium = G.id AND G.name = ?$a $where;";
		$result = $this->getResultSet($sql, 's', $committee);
		
		$r = [];
		foreach ($result as $pro){
			$r[$pro['name']] = $pro;
		}
		return $r;
	}
	
	/**
	 * return protocol list
	 * @param string $committee get protocols of choosen committee
	 * @param integer $lnum legislaturnumber
	 * @param boolean $draftOnly only get protocols with draft status
	 * @param boolean $publicOnly only get protocols with public status (overwrites $draftOnly)
	 * @return array protocol list by protocol name
	 */
	public function getProtocolsByLegislatur( $committee , $lnum ,$draftOnly = false , $publicOnly = false ){
		$a = ($draftOnly)? ' AND P.draft_url IS NOT NULL' : '';
		$a .= ($publicOnly)? ' AND P.public_url IS NOT NULL' : '';
		//TODO optional join and count todos and resolutions
		$sql = "SELECT P.*, G.id as gid, G.name as gname FROM `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G, `".TABLE_PREFIX."legislatur` L WHERE P.date >= L.start AND L.number = ? AND P.gremium = G.id AND G.name = ?$a;";
		$result = $this->getResultSet($sql, 'is', [$lnum, $committee]);
	
		$r = [];
		foreach ($result as $pro){
			$r[$pro['name']] = $pro;
		}
		return $r;
	}
	
	
	
	/**
	 * update protocol entry, set protocol agreed value
	 * @param NULL|integer $agreed agreed state
	 * @param 
	 * @return boolean|new id
	 */
	public function updateProtocolSetAgreed($agreed, $gid, $date){
		$pattern = 'iis';
		$data = [
			$agreed,
			$gid, 
			$date
		];
	
		$sql = "UPDATE `".TABLE_PREFIX."protocol` SET
				`agreed` = ?
				WHERE `agreed` IS NULL AND `gremium` = ? AND `date` = ? ";
	
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if (!$this->isError()){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * return name => protocol id map
	 * used on protocol list -> for resolution linking
	 * @param string $committee get protocols of choosen committee
	 * @return array protocol list by protocol name
	 */
	public function getProtocolNameIdMap( $committee ){
		$sql = "SELECT P.name, P.id FROM `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G WHERE P.gremium = G.id AND G.name = ?;";
		$result = $this->getResultSet($sql, 's', $committee);
		$r = [];
		foreach ($result as $pro){
			$r[$pro['name']] = $pro['id'];
		}
		return $r;
	}
	
	/**
	 * return protocol resolutions by gremium and protocol name
	 * used to check if protocol was accepted
	 * @param string $committee
	 * @param string $protocolName
	 * @param boolean $useLike
	 * @return NULL|array
	 * @throws Exception
	 */
	public function getResolutionByPTag( $committee, $protocolName, $useLike = false ){
		if (!is_string($committee) || $committee === ''
			|| !is_string($protocolName) || $protocolName === '' ) {
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require two nonempty strings.';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$tag = $committee.':'.$protocolName;
		$sql = (!$useLike)? 
			"SELECT * FROM `".TABLE_PREFIX."resolution` R WHERE R.p_tag = ? ORDER BY R.id DESC;" :
			"SELECT * FROM `".TABLE_PREFIX."resolution` R WHERE R.p_tag LIKE ?  ORDER BY R.id DESC;";
		$result = $this->getResultSet($sql, 's', ($useLike)?"%$tag%":$tag);
		$r = [];
		foreach ($result as $res){
			$r[] = $res;
		}
		return $r;
	}
	
	/**
	 * return resolutions by gremium/committee
	 * used to check if protocol was accepted
	 * @param string $committee
	 * @param integer $pid protocol id - if set this matches only given protocol id
	 * @param string $order ASC|DESC
	 * @return NULL|array
	 * @throws Exception
	 */
	public function getResolutionByCommittee( $committee , $pid = NULL, $order = 'DESC'){
		if (!is_string($committee) || $committee === '') {
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require nonempty string';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$sql = "SELECT R.*, P.date, P.id as pid, P.name as pname FROM `".TABLE_PREFIX."resolution` R, `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G
			WHERE R.on_protocol = P.id
				AND P.gremium = G.id
				AND G.name = ?"
				.((isset($pid) && is_int($pid))?' AND R.on_protocol = ?':'').
			" ORDER BY P.date $order;";
		$data = [$committee];
		if (isset($pid) && is_int($pid)) $data[] = $pid;
		$result = $this->getResultSet($sql, ((isset($pid) && is_int($pid))?'si':'s'), $data );
		$r = [];
		foreach ($result as $res){
			$r[] = $res;
		}
		return $r;
	}
	
	/**
	 * return protocols by gremium/committee
	 * list of protocolls names which has resolution
	 * @param string $committee
	 * @return NULL|array
	 * @throws Exception
	 */
	public function getProtocolHasResoByCommittee( $committee ){
		if (!is_string($committee) || $committee === '') {
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require nonempty string';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$sql = "SELECT DISTINCT P.name
				FROM `".TABLE_PREFIX."resolution` R, `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G
				WHERE R.on_protocol = P.id
					AND P.gremium = G.id
					AND G.name = ?
				ORDER BY P.date ASC;";
		$result = $this->getResultSet($sql, 's', $committee );
		$r = [];
		foreach ($result as $res){
			$r[$res['name']] = true;
		}
		return $r;
	}
	
	/**
	 * return protocol resolutions by protocol id
	 * used to check if protocol was accepted
	 * @param integer $pid protocol id
	 * @param boolean $link_acccepting_protocols
	 * @return array
	 * @throws Exception
	 */
	public function getResolutionByOnProtocol( $pid , $link_acccepting_protocols = false, $link_proto = false){
		if (intval($pid).'' !== ''.$pid || $pid < 1) {
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require int value.';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$sql = "SELECT R.*".(($link_acccepting_protocols || $link_proto)? ", P.date, P.id as pid, P.name as pname":'').
			  " FROM `".TABLE_PREFIX."resolution` R". (($link_proto)?', `'.TABLE_PREFIX.'protocol` P':'').
		(($link_acccepting_protocols)? ' LEFT JOIN `'.TABLE_PREFIX.'protocol` P ON P.agreed = R.id':'')
		." WHERE R.on_protocol = ?".(($link_proto)?' AND R.on_protocol = P.id ORDER BY P.date ASC':'').";";
		$result = $this->getResultSet($sql, 'i', $pid);
		$r = [];
		foreach ($result as $res){
			$r[$res['r_tag']] = $res;
		}
		return $r;
	}
	
	/**
	 * return committee array if exists
	 * @param string $committeeName committee (gremium) name
	 * @return array committee element
	 */
	public function getCommitteebyName( $committeeName ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."gremium` G WHERE G.name = ?;";
		$result = $this->getResultSet($sql, 's', $committeeName);
		
		$g = false;
		foreach ($result as $grm){
			$g = $grm;
		}
		return $g;
	}
	
	/**
	 * return committee array
	 * if committee does not exist create it
	 * @param string $committeeName committee (gremium) name
	 * @return array|false committee element
	 */
	public function getCreateCommitteebyName( $committeeName ){
		$g = $this->getCommitteebyName($committeeName);
		if ($g) {
			return $g;
		} else {
			return $this->createCommitteebyName($committeeName);
		}
	}
	
	/**
	 * return todo array
	 * @param int $pid protocol id
	 * @param boolean $hash_as_key use todohash as hey in return array
	 * @return array todo elements
	 */
	public function getTodosByProtocol( $pid , $hash_as_key = false ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."todos` T WHERE T.on_protocol = ? ORDER BY T.line;";
		$result = $this->getResultSet($sql, 'i', $pid);
		if ($this->isError()) return false;
		$r = [];
		if ($hash_as_key){
			foreach ($result as $res){
				$r[$res['hash']] = $res;
			}
		} else {
			foreach ($result as $res){
				$r[] = $res;
			}
		}
		return $r;
	}
	
	/**
	 * return todo array
	 * @param int $pid protocol id
	 * @param string $hash todo hash value
	 * @param string $gremium
	 * @return NULL|array todo element
	 */
	public function getTodosByHashPidGrem( $pid , $hash, $gremium ){
		$sql = "SELECT T.* FROM `".TABLE_PREFIX."todos` T, `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G WHERE "."T.on_protocol = P.id AND P.gremium = G.id AND G.name = ? AND T.hash = ? AND P.id = ?;";
		$result = $this->getResultSet($sql, 'ssi', [$gremium, $hash, $pid]);
		if ($this->isError()) return false;
		if(count($result) == 1){
			return $result[0];
		} else {
			return NULL;
		}
	}
	
	/**
	 * update todo entry
	 * @param array $t todo element array
	 * @return boolean|new id
	 */
	public function updateTodo($t){
		$pattern = 'isissisii';
		$data = [
			$t['on_protocol'],
			$t['user'],
			$t['done'],
			$t['text'],
			$t['type'],
			$t['line'],
			$t['hash'],
			$t['intern'],
			$t['id']
		];
		$sql = "UPDATE `".TABLE_PREFIX."todos` SET
				`on_protocol` = ?,
				`user` = ?,
				`done` = ?,
				`text` = ?,
				`type` = ?,
				`line` = ?,
				`hash` = ?,
				`intern` = ?
				WHERE `id` = ?";
		
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * return todo array
	 * @param string $gremium protocol id
	 * @param boolean $limit_todo only show todos of type 'todo'
	 * @param false|string $limit_date only show todos newer than $date
	 * @return array todo elements
	 */
	public function getTodosByGremium( $gremium , $limit_todo = false, $limit_date = false ){		
		$sql = "SELECT * FROM `".TABLE_PREFIX."todos` T, `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G WHERE ".(($limit_todo)?"T.type = 'todo' AND ":'')."T.on_protocol = P.id AND P.gremium = G.id AND G.name = ? ".(($limit_date)? "AND P.date >= ? ":'')."ORDER BY T.type, T.done, P.date, P.id, T.line;";
		$data = [$gremium];
		if ($limit_date) {
			$data[] = $limit_date;
		}
		$result = $this->getResultSet($sql, 's'.(($limit_date)?'s':''), $data);
		if ($this->isError()) return false;
		$r = [];
		foreach ($result as $res){
			$r[] = $res;
		}
		return $r;
	}
	
	/**
	 * returns current legislatur
	 * @return array
	 */
	public function getCurrentLegislatur(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."legislatur` L ORDER BY L.number DESC LIMIT 1;";
		$result = $this->getResultSet($sql);
		$return = [];
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * returns current legislatur
	 * @param date $date format Y-m-d
	 * @return array
	 */
	public function getLegislaturByDate($date){
		$sql = "SELECT * FROM `".TABLE_PREFIX."legislatur` L WHERE L.end >= ? AND L.start <= ?";
		$result = $this->getResultSet($sql, 'ss', [$date, $date]);
		$return = [];
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * returns current legislatur
	 * @param int $number
	 * @return array
	 */
	public function getLegislaturByNumber($number){
		$sql = "SELECT * FROM `".TABLE_PREFIX."legislatur` L WHERE L.number = ?";
		$result = $this->getResultSet($sql, 'i', [$number]);
		$return = [];
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * returns current legislatur
	 * @param int $number
	 * @return array
	 */
	public function getLegislaturById($id){
		$sql = "SELECT * FROM `".TABLE_PREFIX."legislatur` L WHERE L.id = ?";
		$result = $this->getResultSet($sql, 'i', [$id]);
		$return = [];
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * returns legislaturen
	 * @return array
	 */
	public function getLegislaturen(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."legislatur` L ORDER BY L.number ASC";
		$result = $this->getResultSet($sql);
		$return = [];
		foreach ($result as $line){
			$return[] = $line;
		}
		return $return;
	}
	
	/**
	 * returns CurrentMembers of committee/gremium
	 * @return array
	 */
	public function getMembers($gremium){
		$sql = "SELECT M.* FROM `".TABLE_PREFIX."current_member` M, `".TABLE_PREFIX."gremium` G WHERE M.gremium = G.id AND G.name = ? ORDER BY M.name";
		$result = $this->getResultSet($sql, 's', [$gremium]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	/**
	 * returns CurrentMembers of committee/gremium
	 * @return array
	 */
	public function getMembersCounting($gremium){
		$sql = "SELECT M.*, COUNT(P1.management) as management, COUNT(P2.protocol) as protocol FROM `".TABLE_PREFIX."current_member` M INNER JOIN `".TABLE_PREFIX."gremium` G ON M.gremium = G.id LEFT JOIN (SELECT P.* FROM `".TABLE_PREFIX."newproto` P WHERE P.generated_url IS NOT NULL) P1 ON P1.management = M.id LEFT JOIN (SELECT P.* FROM `".TABLE_PREFIX."newproto` P WHERE P.generated_url IS NOT NULL) P2 ON P2.protocol = M.id AND P2.generated_url IS NOT NULL WHERE G.name = ? GROUP BY M.name ";
		$result = $this->getResultSet($sql, 's', [$gremium]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	/**
	 * returns member by id
	 * @return array
	 */
	public function getMemberById($id){
		$sql = "SELECT M.*, G.name as 'gname' FROM `".TABLE_PREFIX."current_member` M, `".TABLE_PREFIX."gremium` G WHERE M.gremium = G.id AND M.id = ?";
		$result = $this->getResultSet($sql, 'i', [$id]);
		$return = NULL;
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * create current member entry
	 * @param array $m member element array
	 * @return boolean|new id
	 */
	public function createMember($m){
		$pattern = 'sis';
		$data = [
			$m['name'],
			$m['gremium'],
			$m['job'],
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."current_member`
			(	`name`,
				`gremium`, 
				`job` )
			VALUES(?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * update current member by name
	 * @param array $m member element array
	 * @return boolean|new id
	 */
	public function updateMemberByName($m){
		$pattern = 'iss';
		$data = [
			$m['gremium'],
			$m['job'],
			$m['name'],
		];
		$sql = "UPDATE `".TABLE_PREFIX."current_member` SET
				`gremium` = ?,
				`job` = ?
				WHERE `name` = ?";
		$this->protectedInsert($sql, $pattern, $data);
		return !$this->isError();
	}
	
	/**
	 * update current member by name
	 * @param array $m member element array
	 * @return boolean|new id
	 */
	public function updateMemberById($m){
		$pattern = 'sisi';
		$data = [
			$m['name'],
			$m['gremium'],
			$m['job'],
			$m['id'],
		];
		$sql = "UPDATE `".TABLE_PREFIX."current_member` SET
				`name` = ?,
				`gremium` = ?,
				`job` = ?
				WHERE `id` = ?";
		$this->protectedInsert($sql, $pattern, $data);
		return !$this->isError();
	}
	
	/**
	 * return list of newproto
	 * @param $gremium committee|gremium name
	 * @return array
	 */
	public function getNewprotos($gremium, $key = 'id'){
		$sql = "SELECT NP.* FROM `".TABLE_PREFIX."newproto` NP, `".TABLE_PREFIX."gremium` G WHERE NP.gremium = G.id AND G.name = ? ORDER BY NP.date DESC";
		$result = $this->getResultSet($sql, 's', [$gremium]);
		$return = [];
		foreach ($result as $line){
			if ($key){
				$return[$line[$key]] = $line;
			} else {
				$return[] = $line;
			}	
		}
		return $return;
	}
	
	/**
	 * returns newproto by id
	 * @return array
	 */
	public function getNewprotoById($id){
		$sql = "SELECT NP.*, G.name as 'gname' FROM `".TABLE_PREFIX."newproto` NP, `".TABLE_PREFIX."gremium` G WHERE NP.gremium = G.id AND NP.id = ?";
		$result = $this->getResultSet($sql, 'i', [$id]);
		$return = NULL;
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * returns all open newproto by pending date
	 * @param string $pendingDate select newprotos below or equal this date - format: Y-m-d H:i:s
	 * @param string $ignoreDate select newprotos above or equal this date - format: Y-m-d H:i:s
	 * @return array
	 */
	public function getNewprotoPending($pendingDate, $ignoreDate){
		$sql = "SELECT NP.*, G.name as 'gname' FROM `".TABLE_PREFIX."newproto` NP, `".TABLE_PREFIX."gremium` G WHERE NP.gremium = G.id AND NP.date <= ? AND NP.date >= ? AND NP.invite_mail_done = 0 ORDER BY NP.date ASC";
		$result = $this->getResultSet($sql, 'ss', [$pendingDate, $ignoreDate]);
		$return = [];
		foreach ($result as $line){
			$return[] = $line;
		}
		return $return;
	}
	
	
	/**
	 * create Newproto entry
	 * @param array $n newproto element array
	 * @return boolean|new id
	 */
	public function createNewproto($n){
		$pattern = 'sisiiiissiis';
		$data = [
			$n['date'],
			(isset($n['legislatur'])&&$n['legislatur'])?$n['legislatur']:NULL,
			(isset($n['generated_url'])&&$n['generated_url'])?$n['generated_url']:NULL,
			(isset($n['management'])&&$n['management'])?$n['management']:NULL,
			(isset($n['protocol'])&&$n['protocol'])?$n['protocol']:NULL,
			(isset($n['invite_mail_done'])&&$n['invite_mail_done'])?$n['invite_mail_done']:0,
			(isset($n['invite_telegram_done'])&&$n['invite_telegram_done'])?$n['invite_telegram_done']:0,
			$n['created_by'],
			$n['hash'],
			$n['gremium'],
			isset($n['mail_info_state'])? $n['mail_info_state']: 0,
			isset($n['mail_proto_remember'])? $n['mail_proto_remember']: NULL,
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."newproto`
			(	`date`,
				`legislatur`,
				`generated_url`,
				`management`,
				`protocol`,
				`invite_mail_done`,
				`invite_telegram_done`,
				`created_by`,
				`hash`,
				`gremium`,
				`mail_info_state`
				`mail_proto_remember`)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * update top entry
	 * @param array $t top element array
	 * @return boolean|new id
	 */
	public function updateNewproto($n){
		$pattern = 'sisiiiisssiisi';
		$data = [
			$n['date'],
			(isset($n['legislatur'])&&$n['legislatur'])?$n['legislatur']:NULL,
			(isset($n['generated_url'])&&$n['generated_url'])?$n['generated_url']:NULL,
			(isset($n['management'])&&$n['management'])?$n['management']:NULL,
			(isset($n['protocol'])&&$n['protocol'])?$n['protocol']:NULL,
			(isset($n['invite_mail_done'])&&$n['invite_mail_done'])?1:0,
			(isset($n['invite_telegram_done'])&&$n['invite_telegram_done'])?1:0,
			$n['created_on'],
			$n['created_by'],
			$n['hash'],
			$n['gremium'],
			$n['mail_info_state'],
			$n['mail_proto_remember'],
			$n['id']
		];

		$sql = "UPDATE `".TABLE_PREFIX."newproto` SET
				`date` = ?,
				`legislatur` = ?,
				`generated_url` = ?,
				`management` = ?,
				`protocol` = ?,
				`invite_mail_done` = ?,
				`invite_telegram_done` = ?,
				`created_on` = ?,
				`created_by` = ?,
				`hash` = ?,
				`gremium` = ?,
				`mail_info_state` = ?,
				`mail_proto_remember` = ?
				WHERE `id` = ?";
	
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if (!$this->isError()){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * delete newproto by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	function deleteNewprotoById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."newproto` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		return !$this->isError();
	}
	
	/**
	 * returns tops
	 * @param $gremium committee|gremium name
	 * @return array
	 */
	public function getTops($gremium){
		$sql = "SELECT T.* FROM `".TABLE_PREFIX."tops` T, `".TABLE_PREFIX."gremium` G WHERE T.gremium = G.id AND G.name = ? ORDER BY T.skip_next, T.resort ASC, T.order, T.added_on ASC";
		$result = $this->getResultSet($sql, 's', [$gremium]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	/**
	 * returns tops
	 * @param string $gremium committee|gremium name
	 * @param boolean $countFiles
	 * @return array
	 */
	public function getTopsOpen($gremium, $countFiles = false){
		$sql = '';
		if (!$countFiles) $sql = "SELECT T.* FROM `".TABLE_PREFIX."tops` T, `".TABLE_PREFIX."gremium` G WHERE T.gremium = G.id AND G.name = ? AND T.used_on IS NULL ORDER BY T.skip_next, T.resort ASC, T.order, T.added_on ASC";
		else $sql = "SELECT T.*, COUNT(FI.id) as 'filecounter' FROM `".TABLE_PREFIX."tops` T LEFT JOIN `".TABLE_PREFIX."gremium` G ON T.gremium = G.id LEFT JOIN `".TABLE_PREFIX."fileinfo` FI ON T.id = FI.link WHERE G.name = ? AND T.used_on IS NULL GROUP BY T.id ORDER BY  T.skip_next, T.resort ASC, T.order, T.added_on ASC";
		$result = $this->getResultSet($sql, 's', [$gremium]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	/**
	 * returns tops
	 * @param integer $npid newproto id
	 * @return array
	 */
	public function getTopsByNewproto($npid){
		$sql = "SELECT T.* FROM `".TABLE_PREFIX."tops` T, `".TABLE_PREFIX."gremium` G WHERE T.gremium = G.id AND T.used_on = ? ORDER BY T.skip_next, T.resort ASC, T.order, T.added_on ASC";
		$result = $this->getResultSet($sql, 'i', [$npid]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	/**
	 * returns top by id
	 * @param int $id Top id
	 * @param boolean $countFiles
	 * @return array
	 */
	public function getTopById($id, $countFiles = false){
		$sql = '';
		if (!$countFiles) $sql = "SELECT T.*, G.name as 'gname' FROM `".TABLE_PREFIX."tops` T, `".TABLE_PREFIX."gremium` G WHERE T.gremium = G.id AND T.id = ?";
		else $sql = "SELECT T.*, G.name as 'gname', COUNT(FI.id) as 'filecounter' FROM `".TABLE_PREFIX."tops` T LEFT JOIN `".TABLE_PREFIX."gremium` G ON T.gremium = G.id LEFT JOIN `".TABLE_PREFIX."fileinfo` FI ON T.id = FI.link WHERE T.id = ?";
		$result = $this->getResultSet($sql, 'i', [$id]);
		$return = NULL;
		foreach ($result as $line){
			$return = $line;
		}
		return $return;
	}
	
	/**
	 * update top entry
	 * @param array $t top element array
	 * @return boolean|new id
	 */
	public function updateTop($t){
		$pattern = 'siissssisisiiiii';
		$data = [
			$t['headline'],
			($t['resort'])?$t['resort']:NULL,
			$t['level'],
			($t['person'])?$t['person']:NULL,
			($t['expected_duration'])?$t['expected_duration']:NULL,
			($t['goal'])?$t['goal']:NULL,
			$t['text'],
			$t['gremium'],
			$t['added_on'],
			($t['used_on'])?$t['used_on']:NULL,
			$t['hash'],
			$t['guest'],
			$t['order'],
			$t['skip_next'],
			$t['intern'],
			$t['id']
		];
		$sql = "UPDATE `".TABLE_PREFIX."tops` SET
				`headline` = ?,
				`resort` = ?,
				`level` = ?,
				`person` = ?,
				`expected_duration` = ?,
				`goal` = ?,
				`text` = ?,
				`gremium` = ?,
				`added_on` = ?,
				`used_on` = ?,
				`hash` = ?,
				`guest` = ?,
				`order` = ?,
				`skip_next` = ?,
				`intern` = ?
				WHERE `id` = ?";

		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if (!$this->isError()){
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * create top entry
	 * @param array $t top element array
	 * @return boolean|new id
	 */
	public function createTop($t){
		$pattern = 'siissssiisiiii';
		$data = [
			$t['headline'],
			(isset($t['resort'])&&$t['resort'])?$t['resort']:NULL,
			(isset($t['level'])&&$t['level'])?$t['level']:4,
			(isset($t['person'])&&$t['person'])?$t['person']:NULL,
			(isset($t['expected_duration'])&&$t['expected_duration'])?$t['expected_duration']:NULL,
			(isset($t['goal'])&&$t['goal'])?$t['goal']:NULL,
			(isset($t['text'])&&$t['text'])?$t['text']:'',
			$t['gremium'],
			(isset($t['used_on'])&&$t['used_on'])?$t['used_on']:NULL,
			$t['hash'],
			(isset($t['guest'])&&$t['guest'])?1:0,
			(isset($t['order']))?$t['order']:9999,
			(isset($t['skip_next'])&&$t['skip_next'])?1:0,
			(isset($t['intern'])&&$t['intern'])?1:0
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."tops`
			(	`headline`,
				`resort`,
				`level`,
				`person`,
				`expected_duration`,
				`goal`,
				`text`,
				`gremium`,
				`used_on`,
				`hash`,
				`guest`,
				`order`,
				`skip_next`,
				`intern` )
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	
	
	/**
	 * returns resorts
	 * @return array
	 */
	public function getResorts($gremium){
		$sql = "SELECT R.* FROM `".TABLE_PREFIX."resort` R, `".TABLE_PREFIX."gremium` G WHERE R.gremium = G.id AND G.name = ? ORDER BY R.type ASC, R.name ASC";
		$result = $this->getResultSet($sql, 's', [$gremium]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	// --------- DELETE FUCNTIONS -----------------------------------------
	
	/**
	 * delete member by id
	 * @param integer $id
	 * @return boolean success
	 */
	function deleteMemberById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."current_member` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		return !$this->isError();
	}
	
	/**
	 * delete newproto by member id
	 * @param integer $id
	 * @return boolean success
	 */
	function deleteNewprotoByMemberId($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."newproto` WHERE `management` = ? OR `protocol` = ?;";
		$this->protectedInsert($sql, 'ii', [$id, $id]);
		return !$this->isError();
	}
	
	/**
	 * delete memberid from newproto if newproto generated_url is null
	 * @param integer $id
	 * @return boolean success
	 */
	function deleteMemberOfUncreatedNewprotoByMemberId($id){
		$sql = "UPDATE `".TABLE_PREFIX."newproto` SET `management` = NULL WHERE `management` = ? AND `generated_url` IS NULL";
		$this->protectedInsert($sql, 'i', [$id]);
		if (!$this->isError()){
			$sql = "UPDATE `".TABLE_PREFIX."newproto` SET `protocol` = NULL WHERE `protocol` = ? AND `generated_url` IS NULL";
			$this->protectedInsert($sql, 'i', [$id]);
		}
		return !$this->isError();
	}
	
	/**
	 * delete newproto by member id
	 * but only removes entries if management and protocol are empty, else update and remove id
	 * @param integer $id
	 * @return boolean success
	 */
	function deleteNewprotoByMemberIdSoft($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."newproto` WHERE `generated_url` IS NOT NULL AND ((`management` = ? AND `protocol` IS NULL) OR (`protocol` = ? AND `management` IS NULL) OR (`protocol` = ? AND `management` = ?))";
		$this->protectedInsert($sql, 'iiii', [$id, $id, $id, $id]);
		if (!$this->isError()){
			$pattern = 'i';
			$data = [
				$id,
			];
			$sql = "UPDATE `".TABLE_PREFIX."newproto` SET
				`management` = NULL
				WHERE `management` = ? ";
			$this->protectedInsert($sql, $pattern, $data);	
		}
		if (!$this->isError()){
			$pattern = 'i';
			$data = [
				$id,
			];
			$sql = "UPDATE `".TABLE_PREFIX."newproto` SET
				`protocol` = NULL
				WHERE `protocol` = ? ";
			$this->protectedInsert($sql, $pattern, $data);
		}
		return !$this->isError();
	}
	
	/**
	 * delete tops by member id
	 * @param integer $id
	 * @return boolean success
	 */
	function deleteTopsByMemberId($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."tops` WHERE `used_on` IN (SELECT NP.id FROM `".TABLE_PREFIX."newproto` NP WHERE NP.management = ? OR NP.protocol = ?);";
		$this->protectedInsert($sql, 'ii', [$id, $id]);
		return !$this->isError();
	}
	
	/**
	 * get tops which will be deleted by deleteTopsByMemberIdSoft
	 * @see deleteTopsByMemberIdSoft
	 * @param integer $id
	 * @return array of tops
	 */
	function getDeleteTopsByMemberIdSoft($id){
		$sql = "SELECT * FROM `".TABLE_PREFIX."tops` WHERE `used_on` IN (SELECT NP.id FROM `".TABLE_PREFIX."newproto` NP WHERE (NP.management = ? AND NP.protocol IS NULL) OR (NP.protocol = ? AND NP.management IS NULL) OR (NP.protocol = ? AND NP.management = ?) );";
		$result = $this->getResultSet($sql, 'iiii', [$id, $id, $id, $id]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	/**
	 * delete tops by member id
	 * but only removes entries if management and protocol are empty
	 * @param integer $id
	 * @return integer affected rows
	 */
	function deleteTopsByMemberIdSoft($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."tops` WHERE `used_on` IN (SELECT NP.id FROM `".TABLE_PREFIX."newproto` NP WHERE (NP.management = ? AND NP.protocol IS NULL) OR (NP.protocol = ? AND NP.management IS NULL) OR (NP.protocol = ? AND NP.management = ?) );";
		$this->protectedInsert($sql, 'iiii', [$id, $id, $id, $id]);
		return !$this->isError();
	}
	
	/**
	 * delete top by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	function deleteTopById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."tops` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		return !$this->isError();
	}
	
	/**
	 * delete legislatur by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	function deleteLegislaturById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."legislatur` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * delete resolution by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	function deleteResolutionById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."resolution` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * delete resolution by id
	 * @param integer|array $id delete one or multiple Todo
	 * @return integer affected rows
	 * @throws Exception
	 */
	function deleteTodoById($ids){
		if (is_integer($ids)){
			$ids = [$ids];
		} else if (!is_array($ids)){
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require integer or array of integer.';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		} else {
			$ids = array_values($ids);
		}
		$error = false;
		$where = '';
		$pattern = '';
		$data = [];
		foreach ($ids as $pos => $id){
			if (!is_integer($id)){
				$error = true;
				break;
			}
			if ($pos != 0) $where.= ' OR `id` = ?';
			else $where.= '`id` = ?';
			$pattern.= 'i';
		}		
		if ($error || count($ids) == 0){
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require integer or array of integer.';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$sql = "DELETE FROM `".TABLE_PREFIX."todos` WHERE $where;";
		$this->protectedInsert($sql, $pattern, $ids);
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	// --------- CREATE FUCNTIONS -----------------------------------------
	
	/**
	 * return create committe in database and return committee array
	 * @param string $committeeName committee (gremium) name
	 * @return array|false committee element
	 */
	public function createCommitteeByName( $committeeName ){
		$sql = "INSERT INTO `".TABLE_PREFIX."gremium` (`name`) VALUES (?);";
		$this->protectedInsert($sql, 's', $committeeName);
		if ($this->isError()){
			return false;
		} else {
			return [
				'name' => $committeeName,
				'id' => $this->lastInsertId()
			];
		}
	}
	
	/**
	 * update or create protocol data
	 * @param Protocol $p changes object (set id, update urls)
	 * @return boolean
	 */
	public function createUpdateProtocol($p){
		$sql = ''; 
		$pattern = 'sssiiissi';
		$data = [
			$p->url,
			$p->name,
			($p->date)? ((is_string($p->date))? $p->date : ((is_a($p->date, 'DateTime'))? $p->date->format('Y-m-d H:i:s'): $p->date ))  : NULL,
			$p->agreed_on,
			$p->committee_id,
			$p->legislatur,
			$p->draft_url,
			$p->public_url,
			($p->ignore)? 1 : 0,
		];
		if ($p->id != NULL){
			$pattern.='i';
			$data[] = $p->id;
			$sql = "UPDATE `".TABLE_PREFIX."protocol` 
				SET `url` = ?,
					`name` = ?,
					`date` = ?,
					`agreed` = ?,
					`gremium` = ?, 
					`legislatur` = ?, 
					`draft_url` = ?, 
					`public_url` = ?,
					`ignore` = ?
				WHERE `id` = ?;";
		} else {
			$sql = "INSERT INTO `".TABLE_PREFIX."protocol`
			(	`url`, 
				`name`, 
				`date`, 
				`agreed`, 
				`gremium`, 
				`legislatur`, 
				`draft_url`, 
				`public_url`,
				`ignore`)
			VALUES(?,?,?,?,?,?,?,?,?) ";
		}
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			if ($p->id == NULL){
				$p->id = $this->lastInsertId();
			}
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * update or create protocol data
	 * @param array $p
	 * @return boolean
	 */
	public function createProtocol($p){
		$sql = '';
		$pattern = 'sssiiissi';
		$data = [
			$p['url'],
			$p['name'],
			$p['date'],
			$p['agreed'],
			$p['gremium'],
			$p['legislatur'],
			$p['draft_url'],
			$p['public_url'],
			(isset($p['ignore']) && $p['ignore'])? 1 : 0,
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."protocol`
			(	`url`,
				`name`,
				`date`,
				`agreed`,
				`gremium`,
				`legislatur`,
				`draft_url`,
				`public_url`,
				`ignore`)
			VALUES(?,?,?,?,?,?,?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * create resolution
	 * @param array $r resolution element array
	 * @return boolean|new id
	 */
	public function createResolution($r){
		$pattern = 'isssssii';
		$data = [
			$r['on_protocol'],
			$r['type_short'],
			$r['type_long'],
			$r['text'],
			$r['p_tag'],
			$r['r_tag'],
			$r['intern'],
			(isset($r['noraw'])? $r['noraw'] : 0)
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."resolution`
			(	`on_protocol`, 
				`type_short`, 
				`type_long`, 
				`text`, 
				`p_tag`, 
				`r_tag`, 
				`intern`,
				`noraw`)
			VALUES(?,?,?,?,?,?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * create todo entry
	 * @param array $t todo element array
	 * @return boolean|new id
	 */
	public function createTodo($t){
		$pattern = 'isissisi';
		$data = [
			$t['on_protocol'],
			$t['user'],
			$t['done'],
			$t['text'],
			$t['type'],
			$t['line'],
			$t['hash'],
			$t['intern']
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."todos`
			(	`on_protocol`,
				`user`,
				`done`,
				`text`,
				`type`,
				`line`,
				`hash`,
				`intern`)
			VALUES(?,?,?,?,?,?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * create legislatur
	 * @param array $l legislatur element array
	 * @return boolean|new id
	 */
	public function createLegislatur($l){
		$pattern = 'iss';
		$data = [
			$l['number'],
			$l['start'],
			$l['end']
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."legislatur`
			(	`number`,
				`start`,
				`end`)
			VALUES(?,?,?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	// --------- update FUCNTIONS ------------------
	/**
	 * update resolution
	 * @param array $r resolution element array
	 * @return boolean
	 */
	public function updateResolution($r){
		$pattern = 'isssssiii';
		$data = [
			$r['on_protocol'],
			$r['type_short'],
			$r['type_long'],
			$r['text'],
			$r['p_tag'],
			$r['r_tag'],
			$r['intern'],
			(isset($r['noraw'])? $r['noraw'] : 0),
			$r['id']
		];
		$sql = "UPDATE `".TABLE_PREFIX."resolution`
			SET `on_protocol` = ?,
				`type_short` = ?,
				`type_long` = ?,
				`text` = ?,
				`p_tag` = ?,
				`r_tag` = ?,
				`intern` = ?,
				`noraw` = ?
			WHERE `id` = ?;";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * update legislatur
	 * @param array $l legislatur element array
	 * @return boolean
	 */
	public function updateLegislatur($l){
		$pattern = 'issi';
		$data = [
			$l['number'],
			$l['start'],
			$l['end'],
			$l['id']
		];
		$sql = "UPDATE `".TABLE_PREFIX."legislatur`
			SET `number` = ?,
				`start` = ?,
				`end` = ?
			WHERE `id` = ?;";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * create filedata entry, set datablob null, set diskpath to file
	 * @param string $uploadfile
	 * @return false|int new inserted id or false
	 */
	public function createFileDataPath($filepath){
		$pattern = 's';
		if ($filepath == '') return false;
		$data = [	$filepath	];
		$sql = "INSERT INTO `".TABLE_PREFIX."filedata` ( `diskpath` ) VALUES(?) ";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * create fileentry on table fileinfo
	 * @param File $f
	 * @return false|int new inserted id or false
	 */
	public function createFile($f){
		$pattern = 'ississsis';
		$data = [
			$f->link,
			$f->hashname,
			$f->filename,
			$f->size,
			$f->fileextension,
			$f->mime,
			$f->encoding,
			$f->data,
			($f->added_on)? $f->added_on : date_create()->format('Y-m-d H:i:s')
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."fileinfo`
			(	`link`,
				`hashname`,
				`filename`,
				`size`,
				`fileextension`,
				`mime`,
				`encoding`,
				`data`,
				`added_on` )
			VALUES(?,?,?,?,?,?,?,?,?)";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return $this->lastInsertId();
		} else {
			return false;
		}
	}
	
	/**
	 * update file column 'data' of fileinfo entry
	 * @param File $f
	 * @return boolean success
	 */
	public function updateFile_DataId($f){
		$pattern = 'ii';
		$data = [
			($f->data)? $f->data : NULL,
			($f->id)
		];
		
		$sql = "UPDATE `".TABLE_PREFIX."fileinfo` SET
				`data` = ?
				WHERE `id` = ?";
		
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if (!$this->isError()){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 
	 * @param int $linkId
	 * @param string $filename
	 * @param string $extension
	 */
	public function checkFileExists($linkId, $filename, $extension){
		$sql = "SELECT F.* FROM `".TABLE_PREFIX."fileinfo` F WHERE F.link = ? AND F.filename = ? AND F.fileextension = ?";
		$result = $this->getResultSet($sql, 'iss', [
			$linkId, 
			($filename)? $filename : '', 
			($extension)? $extension : ''
		]);
		$return = [];
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return (count($return) == 1)? true : false;
	}
	
	/**
	 * return list of all existing links
	 * @return array
	 */
	public function getAllFileLinkIds(){
		$sql = "SELECT DISTINCT F.link FROM `".TABLE_PREFIX."fileinfo` F";
		$result = $this->getResultSet($sql);
		$return = [];
		foreach ($result as $line){
			$return[] = $line['link'];
		}
		return $return;
	}
	
	/**
	 * returns fileinfo by id
	 * @return File|NULL
	 */
	public function getFileInfoById($id){
		$sql = "SELECT F.* FROM `".TABLE_PREFIX."fileinfo` F WHERE F.id = ?";
		$result = $this->getResultSet($sql, 'i', [$id]);
		$f = NULL;
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			break;
		}
		return $f;
	}
	
	/**
	 * returns fileinfo by id
	 * @return array <File>
	 */
	public function getFilesByLinkId($id){
		$sql = "SELECT F.* FROM `".TABLE_PREFIX."fileinfo` F WHERE F.link = ?";
		$result = $this->getResultSet($sql, 'i', [$id]);
		$return = [];
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			$return[$line['id']] = $f;
		}
		return $return;
	}
	
	/**
	 * returns fileinfo by filehash
	 * @return File|NULL
	 */
	public function getFileInfoByHash($hash){
		$sql = "SELECT F.* FROM `".TABLE_PREFIX."fileinfo` F WHERE F.hashname = ?";
		$result = $this->getResultSet($sql, 's', [$hash]);
		$f = NULL;
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
		}
		return $f;
	}
	
	/**
	 * delete filedata by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFiledataById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."filedata` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		return !$this->isError();
	}
	
	/**
	 * delete filedata by link id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFiledataByLinkId($linkid){
		$sql = "DELETE FROM `".TABLE_PREFIX."filedata` WHERE `id` IN ( SELECT F.data FROM `".TABLE_PREFIX."fileinfo` F WHERE F.link = ? );";
		$this->protectedInsert($sql, 'i', [$linkid]);
		return !$this->isError();
	}
	
	/**
	 * delete fileinfo by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFileinfoById($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."fileinfo` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', [$id]);
		return !$this->isError();
	}
	
	/**
	 * delete fileinfo by link id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFileinfoByLinkId($linkid){
		$sql = "DELETE FROM `".TABLE_PREFIX."fileinfo` WHERE `link` = ?;";
		$this->protectedInsert($sql, 'i', [$linkid]);
		return !$this->isError();
	}
	
	/**
	 * writes file from filesystem to database
	 * @param string $filename path to existing file
	 * @param integer $filesize in bytes
	 * @return false|int error -> false, last inserted id or
	 */
	public function storeFile2Filedata($filename, $filesize = null){
		return $this->_storeFile2Filedata($filename, $filesize, 'filedata', 'data');
	}
	
	/**
	 * return binary data from database
	 * @param integer $id filedata id
	 * @return false|binary error -> false, binary data
	 */
	public function getFiledataBinary($id){
		return $this->_getFiledataBinary($id, $tablename = 'filedata' , $datacolname = 'data');
	}
	
}
?>