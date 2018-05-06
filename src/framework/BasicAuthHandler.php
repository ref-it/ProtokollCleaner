<?php
//Dummy SimpleSAML handler
// will be replaced with real one on live system.
class  BasicAuthHandler{
    private static $instance; //singelton instance of this class
    
    private static $usermap;
    
    private $attributes;
    
    private function __construct(){
        //create session
		session_start();
		self::$usermap = CRON_USERMAP;
    }
    
    public static function getInstance(){
        if (!isset(self::$instance)){
            self::$instance = new BasicAuthHandler();
        }
        return self::$instance;
    }
    
    function getUserFullName(){
        $this->requireAuth();
        return $this->getAttributes()["displayName"];
    }
    
    function getUserMail(){
        $this->requireAuth();
        return $this->getAttributes()["mail"];
    }
    
    function getAttributes(){
        return $this->attributes;
    }
    
    function requireAuth(){
    	//check IP and user agent
		if(isset($_SESSION['SILMPH']) && isset($_SESSION['SILMPH']['CLIENT_IP']) && isset($_SESSION['SILMPH']['CLIENT_AGENT'])){
			if ($_SESSION['SILMPH']['CLIENT_IP'] != $_SERVER['REMOTE_ADDR'] || $_SESSION['SILMPH']['CLIENT_AGENT'] != $_SERVER ['HTTP_USER_AGENT']){
				//die or reload page is IP isn't the same when session was created -> need new login
				session_destroy();
				session_start();
				header("Refresh: 0");
				die();
			}
		} else {
			$_SESSION['SILMPH']['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['SILMPH']['CLIENT_AGENT'] = $_SERVER ['HTTP_USER_AGENT'];
		}
		
		if(!isset($_SESSION['SILMPH']['USER_ID'])){
			$_SESSION['SILMPH']['USER_ID'] = 0;
		}
		
		if(!isset($_SESSION['SILMPH']['LAST_ACTION'])){
			$_SESSION['SILMPH']['LAST_ACTION'] = time();
		}
		
		if(!isset($_SESSION['SILMPH']['MESSAGES'])){
			$_SESSION['SILMPH']['MESSAGES'] = array();
		}
		
		//check logout request
		if ($_SESSION['SILMPH']['USER_ID'] !== 0 && ( isset($_GET['logout']) || strpos($_SERVER['REQUEST_URI'], '&logout=1') !== false || strpos($_SERVER['REQUEST_URI'], '?logout=1') !== false )){
			session_destroy();
			session_start();
			header('Location: '.BASE_URL . $_SERVER['PHP_SELF']);
			die();
		}
		
		if (!isset($_SERVER['PHP_AUTH_USER'])){
			$_SESSION['SILMPH']['USER_ID'] = 0;
			header('WWW-Authenticate: Basic realm="basic_'.BASE_TITLE.'_realm"');
			header('HTTP/1.0 401 Unauthorized');
		} else {
			$_SESSION['SILMPH']['USER_ID'] = 0;
			if (isset(self::$usermap[$_SERVER['PHP_AUTH_USER']]) && 
				self::$usermap[$_SERVER['PHP_AUTH_USER']]['password'] == $_SERVER['PHP_AUTH_PW']){
				$this->attributes = array_slice(self::$usermap[$_SERVER['PHP_AUTH_USER']], 1 );
			} else {
				header('WWW-Authenticate: Basic realm="basic_'.BASE_TITLE.'_realm"');
				header('HTTP/1.0 401 Unauthorized');
				echo '<strong>You are not allowd to access this page. Please Login.</strong>';
				die();
			}
		}
    }
    
	function requireGroup($group){
        $this->requireAuth();
        if (!$this->hasGroup($group)){
           return false;
        }
        return true;
    }
    
    /**
     * @param string $group     String of groups
     * @param string $delimiter Delimiter of the groups in $group
     *
     * @return bool
     */
    function hasGroup($group, $delimiter = ","){
        $attributes = $this->getAttributes();
        if (count(array_intersect(explode($delimiter, strtolower($group)), array_map("strtolower", $attributes["groups"]))) == 0){
            return false;
        }
        return true;
    }
    
    function getUsername(){
        $attributes = $this->getAttributes();
        if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0]))
            return $attributes["eduPersonPrincipalName"][0];
        if (isset($attributes["mail"]) && isset($attributes["mail"]))
            return $attributes["mail"];
        return null;
    }
    
    function getLogoutURL(){
    	return BASE_URL.BASE_SUBDIRECTORY . '?logout=1';
    }
    
    function logout($param = NULL){
    	header('Location: '.BASE_URL.BASE_SUBDIRECTORY . '?logout=1');
		die();
    }
}
