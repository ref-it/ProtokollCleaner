<?php
/**
 * CONFIG FILE ProtocolHelper
 * Application initialisation
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        classes
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
class database
{
	/**
	 * database member
	 * @var database
	 * @see database.php
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
	
	// ======================== USER FUNCTIONS ========================================================
	//user getter ------------------------------------
	
	/**
	 * get userdata by username
	 * @param string $username
	 * @return array userdata
	 */
	function getUserByName( $username ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."users` WHERE username = ? AND deleted = 0;";
		$result =  $this->getResultSet($sql, "s", $username);
		if (count($result) == 1){
			return $result[0];
		} else {
			return NULL;
		}
	}
	
	/**
	 * get userdata by email address
	 * @param string $email
	 * @return array userdata
	 */
	function getUserByEmail( $email ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."users` WHERE email IS NOT NULL AND email != '' AND email = ? AND deleted = 0;";
		$result =  $this->getResultSet($sql, "s", $email);
		if (count($result) == 1){
			return $result[0];
		} else {
			return NULL;
		}
	}
	
	/**
	 * get userdata by user_id
	 * @param string $user_id user_id
	 * @param bool $allow_deleted show deleted users too
	 * @return array userdata
	 */
	function getUserById( $user_id, $allow_deleted = false ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."users` WHERE id = ?". ((!$allow_deleted)?" AND deleted = 0":"").";";
		$result =  $this->getResultSet($sql, "i", $user_id);
		if (count($result) == 1){
			return $result[0];
		} else {
			return NULL;
		}
	}
	
	/**
	 * get all userdata
	 * @return array usersets
	 */
	function getUserMap(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."users` WHERE deleted = 0";
		$result =  $this->getResultSet($sql);
		$return = array();
		foreach ($result as $line){
			$return[$line['id']] = $line;
		}
		return $return;
	}
	
	//user functions----------------------------------
	
	/**
	 * store current time as last login time of user
	 * @param integer $user_id
	 * @return affected rows
	 */
	function setUserLastLogin ( $user_id ){
		$sql = "UPDATE `".TABLE_PREFIX."users` SET `last_login` = '".time()."' WHERE `id` = ? AND deleted = 0;";
		$this->protectedInsert($sql, 'i', array($user_id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * creates user in db
	 * @param string $user
	 * @param string $pw hashed + salten + peppered
	 * @return int user_id of new user
	 */
	function createUser( $user, $pw ){
		if ($user == '' || $pw == ''){
			return 0;
		}
		$sql = "INSERT INTO `".TABLE_PREFIX."users` SET `username` = ?, `password` = ?;";
		$this->protectedInsert($sql, 'ss', array($user, $pw));
		return $this->lastInsertId();
	}
	
	/**
	 * creates user in db and set all available permissions for this user
	 * @param string $user
	 * @param string $pw hashed password
	 * @return int user_id of new user
	 */
	function createUserFullPermission( $user, $pw ){
		$ret = $this->createUser($user, $pw);
		if ($ret){
			$sql = "INSERT INTO `".TABLE_PREFIX."user_permission` (permission_id, user_id) SELECT id as permission_id, ".$ret." as user_id FROM `".TABLE_PREFIX."permissions`";
			$this->query($sql);
		}
		return $ret;
	}
	
	/**
	 * delete user from db + delete user permissions
	 * -> soft delete
	 * @param integer $user_id
	 * @return integer affected rows
	 */
	function deleteUser( $user_id ){
		$userset = $this->getUserById($user_id, false);
		if ($userset){
			//remove permissions
			$sql = "DELETE FROM `".TABLE_PREFIX."user_permission` WHERE `user_id` = ?;";
			$this->protectedInsert($sql, 'i', array($userset['id']));
			//update user
			$new_username = '_'.$userset['id'].'_'.$userset['username'];
			$sql = "UPDATE `".TABLE_PREFIX."users` SET `deleted` = 1, `username` = ?, `email` = NULL, `deleted_email` = ?, password_reset_challenge = NULL WHERE `id` = ? AND deleted = 0;";
			$this->protectedInsert($sql, 'ssi', array($new_username, $userset['email'], $userset['id']));
			$result = $this->affectedRows();
			return ($result > 0)? $result : 0;
		}
		return 0;
	}
	
	/**
	 * set userdata: email, alias for user
	 * @param array $userset user data as array
	 * @return integer affected rows
	 */
	function updateUserData ($userset){
		if (!is_array($userset)){
			throwException("No valid Userset given.");
			return 0;
		} else {
			$sql = "UPDATE `".TABLE_PREFIX."users` SET `email` = ?, `alias` = ? WHERE `id` = ? AND deleted = 0;";
			$this->protectedInsert($sql, 'ssi', array($userset['email'], $userset['alias'], $userset['id']));
			$result = $this->affectedRows();
			return ($result > 0)? $result : 0;
		}
	}
	
	/**
	 * update user password
	 * -> resets password_reset_challenge
	 * @param integer $user_id 
	 * @param string $pw password hash
	 * @return integer affected rows
	 */
	function updateUserPw( $user_id, $pw){
		//clear challenge
		$sql = "UPDATE `".TABLE_PREFIX."users` SET `password_reset_challenge` = NULL, `password` = ? WHERE `id` = ? AND deleted = 0;";
		$this->protectedInsert($sql, 'si', array($pw, $user_id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
		
	}
	
	/**
	 * sets user password reset challenge and last reset request time
	 * @param integer $user_id
	 * @param string $challenge
	 * @return integer affected rows
	 */
	function setUserMailChallange( $user_id, $challenge ){
		$sql = "UPDATE `".TABLE_PREFIX."users` SET `password_reset_challenge` = ?, `last_password_reset_request` = '".time()."' WHERE `id` = ? AND deleted = 0;";
		$this->protectedInsert($sql, 'si', array($challenge, $user_id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	// user permission--------------------------------
	
	/**
	 * returns permissions for user by user_id
	 * @param integer $user_id
	 * @return array
	 */
	function getUserPermissions( $user_id ){
		$sql = "SELECT p.name FROM `".TABLE_PREFIX."user_permission` up, `".TABLE_PREFIX."permissions` p WHERE up.user_id = ? AND up.permission_id = p.id";
		$result = $this->getResultSet($sql, "i", $user_id);
		$return = array();
		foreach ($result as $permission){
			$return[] = $permission['name'];
		}
		return $return;
	}
	
	/**
	 * returns list of all permissions
	 * @return array
	 */
	function getPermissionMap(){
		$sql = "SELECT p.* FROM `".TABLE_PREFIX."permissions` p";
		$result = $this->getResultSet($sql);
		return $result;
	}
	
	/**
	 * returns list of all permissions + counter for users with this permission
	 * @return array
	 */
	function getPermissionUsages(){
		$sql = "SELECT p.*, COUNT(up.user_id) AS 'usage' FROM `".TABLE_PREFIX."permissions` p LEFT JOIN `".TABLE_PREFIX."user_permission` AS up ON p.id = up.permission_id GROUP BY p.id";
		$result = $this->getResultSet($sql);
		return $result;
	}
	
	/**
	 * returns userlist with pid_list for each user
	 * pid_list ist commaseperated string of integers
	 * @return multitype:
	 */
	function getUserPermissionMap(){
		$sql = "SELECT GROUP_CONCAT(p.id SEPARATOR ',') as pid_list, u.username as username, u.id as uid FROM `".TABLE_PREFIX."users` u LEFT JOIN `".TABLE_PREFIX."user_permission` up ON up.user_id = u.id LEFT JOIN `".TABLE_PREFIX."permissions` p ON up.permission_id = p.id WHERE u.deleted = 0 GROUP BY u.id";
		$result = $this->getResultSet($sql);
		return $result;
	}
	
	/**
	 * update sets permissions to users
	 * @param integer $user_id
	 * @param integer $permission_id
	 * @param integer $value allowed values : 0, 1 0 removes permission, 1 set permission
	 * @return integer affected rows
	 */
	function setUserPermission($user_id, $permission_id, $value){
		$sql = '';
		if ($value === 0){
			$sql = "DELETE FROM `".TABLE_PREFIX."user_permission` WHERE `user_id` = ? AND `permission_id` = ? ;";
		} else if ($value === 1) {
			$sql = "INSERT IGNORE INTO `".TABLE_PREFIX."user_permission` (`user_id`, `permission_id`) VALUES (?, ?) ;";
		} else {
			return 0;
		}
		$this->protectedInsert($sql, 'ii', array($user_id, $permission_id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	// ======================== SETTINGS FUNCTIONS ========================================================
	
	/**
	 * returns all settings stored in settings table
	 * @return array settingsarray format: [settingskey] => value 
	 */
	function getSettings(){
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
	function setSettings( $key, $value ){
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
	 * returns all delivery types
	 * @return array
	 */
	function getDeliveryTypes(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."delivery_types` ORDER BY `id`;";
		return $this->queryResult($sql);
	}
	
	/**
	 * returns delivery type by id
	 * @param integer $id
	 * @return array
	 */
	function getDeliveryTypeById( $id ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."delivery_types` WHERE `id` = ?;";
		$result = $this->getResultSet($sql, "i", $id);
		if (count($result) > 0){
			return $result[0];
		} else {
			return $result;
		}
	}
	
	/**
	 * returns all customers
	 * @return array
	 */
	function getCustomers(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."customers` ORDER BY `id`;";
		return $this->queryResult($sql);
	}
	
	/**
	 * returns customer by id
	 * @param integer $id
	 * @return array
	 */
	function getCustomerById( $id ){
		$sql = "SELECT * FROM `".TABLE_PREFIX."customers` WHERE `id` = ?;";
		$result = $this->getResultSet($sql, "i", $id);
		if (count($result) > 0){
			return $result[0];
		} else {
			return $result;
		}
	}

	/**
	 * returns list of last comments
	 * @param integer $limit limit resultset
	 * @return array
	 */
	function getLastComments( $limit ){
 		$sql = "SELECT B.* FROM (SELECT A.* FROM `".TABLE_PREFIX."comments` as A ORDER BY A.created_on DESC LIMIT ?) as B";
 		return $this->getResultSet($sql, "i", $limit);
	}

	/**
	 * returns all comments connected to order
	 * @param integer $order_id
	 * @return array
	 */
	function getCommentsByOrderId ( $order_id ){
		$sql = "SELECT A.* FROM `".TABLE_PREFIX."comments` as A WHERE A.order_id = ? ORDER BY A.created_on DESC";
		return $this->getResultSet($sql, 'i', $order_id);
	}
	
	/**
	 * return comment by id
	 * @param integer $id
	 * @return array
	 */
	function getCommentById ( $id ){
		$sql = "SELECT A.* FROM `".TABLE_PREFIX."comments` as A WHERE A.id = ?";
		$result = $this->getResultSet($sql, 'i', $id);
		if (count($result) > 0){
			return $result[0];
		} else {
			return $result;
		}
	}
	
	/**
	 * returns list of last orders
	 * @param integer $limit limit resultset
	 * @return array
	 */
	function getLastOrders( $limit ){
		$sql = 	"SELECT o.id, o.part_number, o.created_on, o.ordered_on, o.expected_delivery_date as ex_delivery, description_link_item as link_item, o.delivered, o.storno_on, cu.name as custumer, dt.name as delivery_type, coo.comment, o.attach ".
				"FROM `".TABLE_PREFIX."orders` o ".
				"LEFT JOIN silmph__customers cu ON cu.id = o.customer ".
				"LEFT JOIN silmph__delivery_types dt ON dt.id = o.delivery_type ".
				"LEFT JOIN (SELECT co.order_id as o_id, ".
					"GROUP_CONCAT(co.comment SEPARATOR '<br>') as comment ".
					"FROM silmph__comments co ".
					"GROUP BY co.order_id) coo ON coo.o_id = o.id ".
				"ORDER BY o.created_on DESC ".
				"LIMIT ?;";
		return $this->getResultSet($sql, 'i' , $limit);
	}
	
	/**
	 * return order by id
	 * @param integer $id
	 * @return array
	 */
	function getOrderById( $id ){
		$sql = 	"SELECT o.* FROM `".TABLE_PREFIX."orders` o WHERE o.id = ?";
		$result = $this->getResultSet($sql, 'i', $id);
		if (count($result) > 0){
			return $result[0];
		} else {
			return $result;
		}
	}
	
	/**
	 * return order autocomplete entry by id
	 * @param integer $id
	 * @return array
	 */
	function getAutocompleteOrderById ( $id ){
		$sql = "SELECT A.* FROM `".TABLE_PREFIX."autocomplete_order_pn_l_d` as A WHERE A.id = ?";
		$result = $this->getResultSet($sql, 'i', $id);
		if (count($result) > 0){
			return $result[0];
		} else {
			return $result;
		}
	}
	
	/**
	 * returns all order autocomplete entries
	 * @return array
	 */
	function getAutocompleteOrder(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."autocomplete_order_pn_l_d` ORDER BY `id`;";
		return $this->queryResult($sql);
	}
	
	/**
	 * returns all order autocomplete entries
	 * @return array
	 */
	function getAutocompleteOrderAccepted(){
		$sql = "SELECT * FROM `".TABLE_PREFIX."autocomplete_order_pn_l_d` WHERE `accepted`=1 ORDER BY `id`;";
		return $this->queryResult($sql);
	}
	
	/**
	 * returns order autocomplete entries which matching partnumber(like)
	 * @param string $pn
	 * @return array
	 */
	function getAutocompleteOrderByPartnumber($pn){
		$epn = $this->escapeString($pn);
		$sql = 'SELECT * FROM ('.
						'SELECT A.*, 1 as sortkey FROM `'.TABLE_PREFIX.'autocomplete_order_pn_l_d` as A '.
						"WHERE A.accepted=1 AND A.part_number = '$epn' ".
					'UNION '.
						'SELECT A.*, 2 as sortkey FROM `'.TABLE_PREFIX.'autocomplete_order_pn_l_d` as A '.
						"WHERE A.accepted=1 AND A.part_number LIKE '$epn%' ".
					'UNION '.
						'SELECT A.*, 3 as sortkey FROM `'.TABLE_PREFIX.'autocomplete_order_pn_l_d` as A '.
						"WHERE A.accepted=1 AND A.part_number LIKE '%$epn%' ".
				') as C GROUP BY id ORDER BY sortkey ASC, part_number ASC;';
		return $this->queryResult($sql);
	}
	
	/**
	 * returns order autocomplete entries which matching partnumber(like)
	 * @param array $list
	 * @return array
	 */
	function getAutocompleteOrderByWordlist($list){
		$sql = 'SELECT * FROM (';
		$sql .= 'SELECT A.*, 1 as sortkey FROM `'.TABLE_PREFIX.'autocomplete_order_pn_l_d` as A WHERE A.accepted=1 AND (';
		foreach ($list as $key => $word){
			$sql.=	(($key!=0)?'OR ':'')."A.part_number = '$word' ".
					"OR A.comment = '$word' ".
					"OR A.link = '$word' ";
		}
		
		$sql .= ') UNION SELECT A.*, 1 as sortkey FROM `'.TABLE_PREFIX.'autocomplete_order_pn_l_d` as A WHERE A.accepted=1 ';
		foreach ($list as $key => $word){
			$sql.=	"AND (A.part_number LIKE '$word%' ".
				"OR A.comment LIKE '%$word%' ".
				"OR A.link LIKE '%$word%' ";
			$sql.=	') ';
		}
		
		$sql .= ') as C GROUP BY id ORDER BY sortkey ASC, part_number ASC;';
		return $this->queryResult($sql);
	}
	
	// --------- STATISTIC FUCNTIONS -------------------------------------
	
	/**
	 * return sum of customers
	 * @return integer
	 */
	function statisticSumCustomers(){
		$sql = "SELECT COUNT(*) as counter FROM `".TABLE_PREFIX."customers`";
		$result = $this->queryResult($sql);
		return $result[0]['counter'];
	}
	
	/**
	 * return sum of users
	 * @return integer
	 */
	function statisticSumUsers(){
		$sql = "SELECT COUNT(*) as counter FROM `".TABLE_PREFIX."users`";
		$result = $this->queryResult($sql);
		return $result[0]['counter'];
	}
	
	/**
	 * return sum of comments
	 * @return integer
	 */
	function statisticSumComments(){
		$sql = "SELECT AUTO_INCREMENT - 1 as counter FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".DB_NAME."' AND TABLE_NAME = '".TABLE_PREFIX."comments';";
		$result = $this->queryResult($sql);
		return $result[0]['counter'];
	}

	/**
	 * return sum of orders
	 * @return integer
	 */
	function statisticSumOrders(){
		$sql = "SELECT COUNT(*) as counter FROM `".TABLE_PREFIX."orders`";
		$result = $this->queryResult($sql);
		return $result[0]['counter'];
	}
	
	/**
	 * return sum of orders or given year
	 * @param integer $year
	 * @return integer
	 */
	function statisticSumOrdersYear( $year ){
		$date = $year."-01-01 00:00:00";
		$dateP1Y = ($year+1)."-01-01 00:00:00";
		$sql = "SELECT COUNT(*) as counter FROM `".TABLE_PREFIX."orders` WHERE `created_on` >= ? AND `created_on` <= ?;";
		$result = $this->getResultSet($sql, 'ss' , array($date, $dateP1Y));
		return $result[0]['counter'];
	}

	// --------- CREATE FUCNTIONS -----------------------------------------
	
	/**
	 * create delivey type
	 * @param string $name
	 * @return integer id new delivery type
	 */
	function createDeleveryTypes($name){
		$sql = "INSERT INTO `".TABLE_PREFIX."delivery_types` SET `name` = ?;";
		$this->protectedInsert($sql, 's', $name);
		return $this->lastInsertId();
	}
	
	/**
	 * create customer
	 * @param string $name
	 * @param string $customer number
	 * @return integer id new customer
	 */
	function createCustomer($name, $customer_nr){
		$sql = '';
		if ($customer_nr != null){
			$sql = "INSERT INTO `".TABLE_PREFIX."customers` SET `name` = ?, `customer_number` = ?;";
			$this->protectedInsert($sql, 'ss', array($name, $customer_nr));
		} else {
			$sql = "INSERT INTO `".TABLE_PREFIX."customers` SET `name` = ?, `customer_number` = NULL;";
			$this->protectedInsert($sql, 's', $name);
		}
		return $this->lastInsertId();
	}
	
	/**
	 * create Order
	 * @param string $part_number
	 * @param integer $customer
	 * @param integer $user_id
	 * @param string $description
	 * @return integer id new order
	 */
	function createOrder($part_number, $customer, $user_id, $description = NULL, $attach = NULL){
		$customer = intval($customer);
		if ($customer <= 0){
			return 0;
		}
		$sql = '';
		if ($description != null){
			$sql = "INSERT INTO `".TABLE_PREFIX."orders` SET `part_number` = ?, `customer` = ?, `created_by` = ?, `description_link_item` = ?, `attach` = ?;";
			$this->protectedInsert($sql, 'siiss', array($part_number, $customer, $user_id, $description, $attach));
		} else {
			$sql = "INSERT INTO `".TABLE_PREFIX."orders` SET `part_number` = ?, `customer` = ?, `created_by` = ?, `description_link_item` = NULL, `attach` = ?;";
			$this->protectedInsert($sql, 'siis', array($part_number, $customer, $user_id, $attach));
		}
		return $this->lastInsertId();
	}
	
	/**
	 * create AutocompleteOrder
	 * @param string $part_number
	 * @param string $linkdesc
	 * @param string $comment
	 * @return integer id new order
	 */
	function createAutocompleteOrder($part_number, $description = NULL, $comment = NULL){
		$sql = "INSERT INTO `".TABLE_PREFIX."autocomplete_order_pn_l_d` SET `part_number` = ?, `link` = ?, `comment` = ?;";
		$this->protectedInsert($sql, 'sss', array($part_number, $description, $comment));
		return $this->lastInsertId();
	}
	
	/**
	 * create AutocompleteOrder
	 * @param string $part_number
	 * @param string $linkdesc
	 * @param string $comment
	 * @return integer id new order
	 */
	function createAutocompleteOrderNotExist($part_number, $description = NULL, $comment = NULL){
		$sql = "INSERT IGNORE INTO `".TABLE_PREFIX."autocomplete_order_pn_l_d` SET `part_number` = ?, `link` = ?, `comment` = ?;";
		$this->protectedInsert($sql, 'sss', array($part_number, $description, $comment));
		return $this->lastInsertId();
	}
	
	/**
	 * create comment
	 * @param integer $order_id
	 * @param integer $user_id
	 * @param string $comment
	 * @return integer id comment
	 */
	function createComment($order_id, $user_id, $comment){
		if ($comment == ''){
			return 0;
		}
		$order_id = intval($order_id);
		if ($order_id <= 0){
			return 0;
		}
		$sql = "INSERT INTO `".TABLE_PREFIX."comments` SET `order_id` = ?, `created_by` = ?, `comment` = ?;";
		$this->protectedInsert($sql, 'iis', array($order_id, $user_id, $comment));
		return $this->lastInsertId();
	}
	
	// --------- update FUCNTIONS ------------------
	
	/**
	 * update customer: set customer number
	 * @param integer $id
	 * @param string $customer_nr
	 * @return integer affected rows
	 */
	function updateCustomerNr($id, $customer_nr){
		$sql = '';
		if ($customer_nr != null){
			$sql = "UPDATE `".TABLE_PREFIX."customers` SET `customer_number` = ? WHERE `id` = ?;";
			$this->protectedInsert($sql, 'si', array($customer_nr, $id));
		} else {
			$sql = "UPDATE `".TABLE_PREFIX."customers` SET `customer_number` = NULL WHERE `id` = ?;";
			$this->protectedInsert($sql, 'i', array($id));
		}
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: storno order to order
	 * @param integer $id
	 * @return integer affected rows
	 */
	function stornoOrder($id){
		$sql = "UPDATE `".TABLE_PREFIX."orders` SET `storno_on` = CURRENT_TIMESTAMP WHERE `id` = ? AND `storno_on` IS NULL AND `delivered` IS NULL AND `ordered_on` IS NULL;";
		$this->protectedInsert($sql, 'i', array($id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order partnumber
	 * @param integer $id
	 * @param integer $pn partnumber
	 * @return integer affected rows
	 */
	function updateOrderPartnumber($id, $pn){
		$sql = "UPDATE `".TABLE_PREFIX."orders` SET `part_number` = ? WHERE `id` = ? AND `storno_on` IS NULL AND `delivered` IS NULL AND `ordered_on` IS NULL;";
		$this->protectedInsert($sql, 'si', array($pn, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order item link description
	 * @param integer $id
	 * @param integer $ld link_description
	 * @return integer affected rows
	 */
	function updateOrderLinkdesc($id, $ld){
		$sql = "UPDATE `".TABLE_PREFIX."orders` SET `description_link_item` = ? WHERE `id` = ? AND `storno_on` IS NULL AND `delivered` IS NULL AND `ordered_on` IS NULL;";
		$this->protectedInsert($sql, 'si', array($ld, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order customer_id
	 * @param integer $id
	 * @param integer $cid customerid
	 * @return integer affected rows
	 */
	function updateOrderCustomerid($id, $cid){
		$sql = "UPDATE `".TABLE_PREFIX."orders` SET `customer` = ? WHERE `id` = ? AND `storno_on` IS NULL AND `delivered` IS NULL AND `ordered_on` IS NULL;";
		$this->protectedInsert($sql, 'ii', array($cid, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: set order delivered
	 * @param integer $id
	 * @return integer affected rows
	 */
	function tickDeliveredOrder($id){
		$sql = "UPDATE `".TABLE_PREFIX."orders` SET `delivered` = CURRENT_TIMESTAMP WHERE `id` = ? AND `delivered` IS NULL";
		$this->protectedInsert($sql, 'i', array($id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: set ordered
	 * @param integer $order_id
	 * @param integer $deli_type_id
	 * @param string $expected_date datetime string
	 */
	function orderOrder($order_id, $deli_type_id = NULL, $expected_date){
		$dety_id = NULL;
		if(is_numeric($deli_type_id) && intval($deli_type_id)){
			$dety_id = intval($deli_type_id);
		}
		if (is_int($dety_id)){
			$sql = "UPDATE `".TABLE_PREFIX."orders` SET `ordered_on` = CURRENT_TIMESTAMP, `delivery_type` = ?, `expected_delivery_date` = ? WHERE `id` = ? AND `ordered_on` IS NULL";
			$this->protectedInsert($sql, 'isi', array($dety_id, $expected_date, $order_id));
		} else {
			$sql = "UPDATE `".TABLE_PREFIX."orders` SET `ordered_on` = CURRENT_TIMESTAMP, `expected_delivery_date` = ? WHERE `id` = ? AND `ordered_on` IS NULL";
			$this->protectedInsert($sql, 'si', array($expected_date, $order_id));
		}
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order autocomplete partnumber
	 * @param integer $id
	 * @param integer $pn partnumber
	 * @return integer affected rows
	 */
	function updateAutocompleteOrderPartnumber($id, $pn){
		$sql = "UPDATE `".TABLE_PREFIX."autocomplete_order_pn_l_d` SET `part_number` = ? WHERE `id` = ?;";
		$this->protectedInsert($sql, 'si', array($pn, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order autocomplete item link description
	 * @param integer $id
	 * @param integer $ld link_description
	 * @return integer affected rows
	 */
	function updateAutocompleteOrderLinkdesc($id, $ld){
		$sql = "UPDATE `".TABLE_PREFIX."autocomplete_order_pn_l_d` SET `link` = ? WHERE `id` = ?;";
		$this->protectedInsert($sql, 'si', array($ld, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order autocomplete comment
	 * @param integer $id
	 * @param integer $co comment
	 * @return integer affected rows
	 */
	function updateAutocompleteOrderComment($id, $co){
		$sql = "UPDATE `".TABLE_PREFIX."autocomplete_order_pn_l_d` SET `comment` = ? WHERE `id` = ?;";
		$this->protectedInsert($sql, 'si', array($co, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: update order autocomplete accept
	 * @param integer $id
	 * @param integer $co comment
	 * @return integer affected rows
	 */
	function updateAutocompleteOrderAccept($id, $aa){
		$sql = "UPDATE `".TABLE_PREFIX."autocomplete_order_pn_l_d` SET `accepted` = ? WHERE `id` = ?;";
		$this->protectedInsert($sql, 'ii', array($aa, $id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
	
	/**
	 * update order: delete order autocomplete -> delete entry
	 * @param integer $id
	 * @return integer affected rows
	 */
	function deleteAutocompleteOrder($id){
		$sql = "DELETE FROM `".TABLE_PREFIX."autocomplete_order_pn_l_d` WHERE `id` = ?;";
		$this->protectedInsert($sql, 'i', array($id));
		$result = $this->affectedRows();
		return ($result > 0)? $result : 0;
	}
}
?>