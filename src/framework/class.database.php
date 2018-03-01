<?php
/**
 * FRAMEWORK ProtocolHelper
 * database connection
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
 */
class Database
{
	/**
	 * database member
	 * @var Database
	 * @see Database.php
	 */
	public $db;
	
	/**
	 * db error state: last request was error or not
	 * @var bool
	 */
	private $_isError = false;
	
	/**
	 * last error message
	 * @var $string
	 */
	private $msgError = '';
	
	/**
	 * db state: db was closed or not
	 * @var bool
	 */
	private $_isClose = false;
	
	/**
	 * Contains affected rows after update, delete and insert requests
	 * set by memberfunction: protectedInsert
	 * @var integer
	 */
	private $affectedRows = 0;

	/**
	 * class constructor
	 */
	function __construct()
	{
		$this->db = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
		if ($this->db->connect_errno) {
			$this->_isError = true;
			$this->msgError = "Connect failed: ".$this->db->connect_error."\n";
		    printf($this->msgError);
		    exit();
		} else {
			$this->db->set_charset(DB_CHARSET);
		}
	}
	
	// ======================== HELPER FUNCTIONS ======================================================
	
	/**
	 * generate reference array of array
	 * @param array $arr
	 * @return array
	 */
	function refValues($arr){
		if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
		{
			$refs = array();
			foreach($arr as $key => $value)
				$refs[$key] = &$arr[$key];
			return $refs;
		}
		return $arr;
	}
	
	/**
	 * escape string by database
	 * @param string $in
	 * @return string escaped string
	 */
	function escapeString($in){
		return $this->db->real_escape_string($in);
	}
	
	// ======================== BASE FUNCTIONS ========================================================
	
	/**
	 * run SQL query in database and fetch result set
	 * uses mysqli_bind to prevent SQL injection
	 * @param string $sql SQL query string
	 * @param string $bind_type bind type for database
	 * @param string|array $bind_params variable/parameterset for bind
	 * @return array fetched resultset
	 */
	private function getResultSet($sql, $bind_type = NULL, $bind_params = NULL){ //use to bind params
		if ($bind_params !== NULL && !is_array($bind_params)){
			$bind_params = array($bind_params);
		}
		$return = array();
		$stmt = $this->db->prepare($sql);
		if ($stmt === false){ //yntax errors, missing privileges, ...
			$this->_isError = true;
			$this->msgError = 'Prepare Failed: ' . htmlspecialchars($this->db->error);
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return $return;
		}
		if ($bind_type && $bind_params){
			
			$bind_list[] = $bind_type;
            for ($i=0; $i<count($bind_params);$i++) 
            {
                $bind_name = 'bind' . $i;
                $$bind_name = $bind_params[$i];
                $bind_list[] = &$$bind_name;
            }
			$ret = call_user_func_array(array($stmt, 'bind_param'), $bind_list);
			if ( $ret === false ) { // number of parameter doesn't match the placeholders in the statement, type conflict, ...
				$this->_isError = true;
				$this->msgError = 'Bind Parameter Failed: ' . htmlspecialchars($this->db->error);
				error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
				$this->affectedRows = -1;
				return $return;
			}
		}
		
		$result = $stmt->execute();
		if ($result === false){
			$this->_isError = true;
			$this->msgError = 'Execute Failed: ' . htmlspecialchars($this->db->error);
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return $return;
		} else {
			$this->_isError = false;
		}
		$result = $stmt->get_result();
		
		$return = $result->fetch_all(MYSQLI_ASSOC);
		$this->affectedRows = $stmt->affected_rows;
		$stmt->close();
		return $return;
	}
	
	/**
	 * run SQL query in database and fetch result set
	 * ! be careful with user input, check them for sql injection
	 * @param $string $sql
	 * @return multitype:unknown
	 */
	function queryResult($sql){ //use for no secure params
		$results = array();
		$result = mysqli_query($this->db, $sql);
        if ($result) {
        	$ii = 0;
            foreach ($result as $key => $value) {
            	$ii++;
                $results [] = $value;
            }

        	/* free result set */
        	mysqli_free_result($result);
        	$this->_isError = false;
        	$this->msgError = '';
        	$this->affectedRows = $ii;
        } else {
        	$this->_isError = true;
        	$this->msgError = $this->db->error."\n";
        	error_log($this->msgError);
        	$this->affectedRows = -1;
        }
        return $results;
	}
	
	/**
	 * run query on database -> set affected rows
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 */
	function query($sql){
        if (mysqli_query($this->db, $sql)) {
        	$this->affectedRows = $this->db->affected_rows;
        	$this->_isError = false;
            return $this->affectedRows;
        } else {
        	$this->affectedRows = -1;
        	$this->_isError = true;
        	$this->msgError = $this->db->error."\n";
        	return false;
        }
	}
	
	/**
	 * run SQL query in database -> set affected rows
	 * @param string $sql SQL query string
	 * @param string $bind_type bind type for database
	 * @param string|array $bind_params variable/parameterset for bind
	 */
	private function protectedInsert($sql, $bind_type = NULL, $bind_params = NULL){ //use to bind params
		if ($bind_params !== NULL && !is_array($bind_params)){
			$bind_params = array($bind_params);
		}
		$stmt = $this->db->prepare($sql);
		if ($stmt === false){ //yntax errors, missing privileges, ...
			$this->_isError = true;
			$this->msgError = 'Prepare Failed: ' . htmlspecialchars($this->db->error);
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return false;
		}
		if ($bind_type && $bind_params){
			$bind_list[] = $bind_type;
			for ($i=0; $i<count($bind_params);$i++)
			{
				$bind_name = 'bind' . $i;
				$$bind_name = $bind_params[$i];
				$bind_list[] = &$$bind_name;
			}
			$ret = call_user_func_array(array($stmt, 'bind_param'), $bind_list);
			if ( $ret === false ) { // number of parameter doesn't match the placeholders in the statement, type conflict, ...
				$this->_isError = true;
				$this->msgError = 'Bind Parameter Failed: ' . htmlspecialchars($this->db->error);
				error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
				$this->affectedRows = -1;
				return false;
			}
		}
		$result = $stmt->execute();
		$this->affectedRows = $stmt->affected_rows;
		if ($result === false){
			$this->_isError = true;
			$this->msgError = 'Execute Failed: ' . htmlspecialchars($this->db->error);
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			return false;
		} else {
			$this->_isError = false;
		}
		return;
	}
	
	/**
	 * db: return las inserted id
	 * @return int last inserted id
	 */
	function lastInsertId(){
		return $this->db->insert_id;
	}
	
	/**
	 * db: return affected rows
	 * @return int affected rows
	 */
	function affectedRows(){
		return $this->affectedRows;
	}
	
	/**
	 * run query on database -> return last inserted id, sets affected rows
	 * ! be careful with user input, check them for sql injection
	 * @param string $sql
	 * @return int last inserted id
	 */
	function queryInsert($sql){
        if (mysqli_query($this->db, $sql)) {
        	$this->_isError = false;
        	$this->affectedRows = $this->db->affected_rows;
            return $this->db->insert_id;
        } else {
        	$this->affectedRows = -1;
        	$this->_isError = true;
        	$this->msgError = $this->db->error."\n";
        	return false;
        }
	}
	
	/**
	 * @return int $this->_isError
	 */
	public function isError(){
		return $this->_isError;
	}
	
	/**
	 * @return bool $this->_isClose
	 */
	public function isClose(){
		return $this->_isClose;
	}
	
	/**
	 * @retun string last error message
	 */
	public function getError(){
		return $this->msgError;
	}
	
	/**
	 * close db connection
	 */
	function close(){
		if (!$this->_isClose){
			$this->_isClose = true;
			if ($this->db) $this->db->close();
		}
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
	 * @return array protocol list by protocol name
	 */
	public function getProtocols( $committee , $draftOnly = false , $publicOnly = false ){
		$a = ($draftOnly)? ' AND P.draft_url IS NOT NULL' : '';
		$a .= ($publicOnly)? ' AND P.public_url IS NOT NULL' : '';
		//TODO optional join and count todos and resolutions
		$sql = "SELECT P.*, G.id as gid, G.name as gname FROM `".TABLE_PREFIX."protocol` P, `".TABLE_PREFIX."gremium` G WHERE P.gremium = G.id AND G.name = ?$a;";
		$result = $this->getResultSet($sql, 's', $committee);
		
		$r = [];
		foreach ($result as $pro){
			$r[$pro['name']] = $pro;
		}
		return $r;
	}
	
	/**
	 * return protocol resolutions by gremium and protocol name
	 * used to check if protocol was accepted
	 * @param string $committee
	 * @param string $protocolName
	 * @return NULL|array
	 * @throws Exception
	 */
	public function getResolutionByPTag( $committee, $protocolName ){
		if (!is_string($committee) || $committee === ''
			|| !is_string($protocolName) || $protocolName === '' ) {
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require two nonempty strings.';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$tag = $committee.':'.$protocolName;
		$sql = "SELECT * FROM `".TABLE_PREFIX."resolution` R WHERE R.p_tag = ?;";
		$result = $this->getResultSet($sql, 's', $tag);
		$r = [];
		foreach ($result as $res){
			$r[] = $res;
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
	public function getResolutionByOnProtocol( $pid , $link_acccepting_protocols = false){
		if (intval($pid).'' !== ''.$pid || $pid < 1) {
			$emsg = 'Wrong parameter in Database function ('.__FUNCTION__.'). ';
			$emsg.= 'Require int value.';
			error_log( $emsg );
			throw new Exception($emsg);
			return NULL;
		}
		$sql = "SELECT R.*, P.id as 'accepts_pid' FROM `".TABLE_PREFIX."resolution` R".
		(($link_acccepting_protocols)? ' LEFT JOIN `'.TABLE_PREFIX.'protocol` P ON P.agreed = R.id':'')
		." WHERE R.on_protocol = ?;";
		$result = $this->getResultSet($sql, 'i', $pid);
		$r = [];
		foreach ($result as $res){
			$r[$res['r_tag']] = $res;
		}
		return $r;
	}
	
	/**
	 * return commitee array if exists
	 * @param string $committeeName committee (gremium) name
	 * @return array commitee element
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
	 * return commitee array
	 * if committee does not exist create it
	 * @param string $committeeName committee (gremium) name
	 * @return array|false commitee element
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
	
	// --------- DELETE FUCNTIONS -----------------------------------------
	
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
	 * return create committe in database and return commitee array
	 * @param string $committeeName committee (gremium) name
	 * @return array|false commitee element
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
		$pattern = 'sssiiiss';
		$data = [
			$p->url,
			$p->name,
			$p->date->format('Y-m-d'),
			$p->agreed_on,
			$p->committee_id,
			$p->legislatur,
			$p->draft_url,
			$p->public_url,
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
					`public_url` = ?
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
				`public_url`)
			VALUES(?,?,?,?,?,?,?,?) ";
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
	 * create resolution
	 * @param array $r resolution element array
	 * @return boolean|new id
	 */
	public function createResolution($r){
		$pattern = 'isssssi';
		$data = [
			$r['on_protocol'],
			$r['type_short'],
			$r['type_long'],
			$r['text'],
			$r['p_tag'],
			$r['r_tag'],
			$r['intern']
		];
		$sql = "INSERT INTO `".TABLE_PREFIX."resolution`
			(	`on_protocol`, 
				`type_short`, 
				`type_long`, 
				`text`, 
				`p_tag`, 
				`r_tag`, 
				`intern`)
			VALUES(?,?,?,?,?,?,?) ";
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
	
	// --------- update FUCNTIONS ------------------
	/**
	 * update resolution
	 * @param array $r resolution element array
	 * @return boolean
	 */
	public function updateResolution($r){
		$pattern = 'isssssii';
		$data = [
			$r['on_protocol'],
			$r['type_short'],
			$r['type_long'],
			$r['text'],
			$r['p_tag'],
			$r['r_tag'],
			$r['intern'],
			$r['id']
		];
		$sql = "UPDATE `".TABLE_PREFIX."resolution`
			SET `on_protocol` = ?,
				`type_short` = ?,
				`type_long` = ?,
				`text` = ?,
				`p_tag` = ?,
				`r_tag` = ?,
				`intern` = ?
			WHERE `id` = ?;";
		$this->protectedInsert($sql, $pattern, $data);
		$result = $this->affectedRows();
		if ($this->affectedRows() > 0){
			return true;
		} else {
			return false;
		}
	}
	
}
?>