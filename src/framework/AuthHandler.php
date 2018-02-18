<?php
//Dummy SimpleSAML handler
// will be replaced with real one on live system.
class  AuthHandler{
    private static $instance; //singelton instance of this class
    private $saml;
    
    private $attributes;
    
    private function __construct($SIMPLESAML, $SIMPLESAMLAUTHSOURCE){
        //create session
		session_start();
		$this->attributes = [
			'displayName' => 'Michael G',
			'mail' => 'michaelg@example.org',
			'groups' => ['stura', 'ref-it'],
			'eduPersonPrincipalName' => ['michaguser'],
		];
    }
    
    public static function getInstance(){
        if (!isset($instance)){
            global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE;
            self::$instance = new AuthHandler($SIMPLESAML, $SIMPLESAMLAUTHSOURCE);
        }
        return self::$instance;
    }
    
    function getUserFullName(){
        $this->requireAuth();
        return $this->getAttributes()["displayName"][0];
    }
    
    function getUserMail(){
        $this->requireAuth();
        return $this->getAttributes()["mail"][0];
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
				$_SESSION['SILMPH']['MESSAGE'] = ['New Session Started!'];
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
			$_SESSION['SILMPH']['MESSAGES'] = [["Sie haben sich erfolgreich abgemeldet.", 'INFO']];
			$_SESSION['SILMPH']['MESSAGES'][] = ['New Session Started!', 'INFO'];
			header('Location: '.BASE_URL . $_SERVER['PHP_SELF']);
			die();
		}
		
		if ($_SESSION['SILMPH']['USER_ID'] === 0 && ( isset($_GET['login']) || strpos($_SERVER['REQUEST_URI'], '&login=1') !== false || strpos($_SERVER['REQUEST_URI'], '?login=1') !== false )){
			$_SESSION['SILMPH']['MESSAGES'][] = ["Sie haben sich erfolgreich angemeldet.", 'INFO'];
			$_SESSION['SILMPH']['USER_ID'] = 1;
		}
		
		if ($_SESSION['SILMPH']['USER_ID'] === 0){
			echo 'Please Login: <a href="'.BASE_URL.'?login=1'.'">Use THIS LINK</a>';
			die();
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
        if (isset($attributes["mail"]) && isset($attributes["mail"][0]))
            return $attributes["mail"][0];
        return null;
    }
    
    function getLogoutURL(){
    	return BASE_URL . '?logout=1';
    }
    
    function logout($param = NULL){
    	header('Location: '.BASE_URL . '?logout=1');
		die();
    }
}
