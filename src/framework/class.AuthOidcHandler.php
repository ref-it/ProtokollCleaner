<?php
/**
 * FRAMEWORK ProtocolHelper
 * AuthOidcHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			29.07.2026
 * @platform        PHP
 * @requirements    PHP 8.1 or higher
 */

use Jumbojett\OpenIDConnectClient;

require_once (dirname(__FILE__).'/Singleton.php');
require_once (dirname(__FILE__).'/class.AuthHandler.php');

/**
 * OpenID Connect Auth Handler
 * extends Singleton class
 * handles OpenID Connect Authentification (Authorization Code Flow)
 * replaces the former AuthSamlHandler
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			29.07.2026
 * @platform        PHP
 * @requirements    PHP 8.1 or higher
 */
class AuthOidcHandler extends Singleton implements AuthHandler{
	private static $PROVIDER_URL;
	private static $CLIENT_ID;
	private static $CLIENT_SECRET;
	private static $REDIRECT_URI;
	private static $CALLBACK_PATH;
	private static $SCOPES;
	private static $AUTHGROUP;
	private static $ADMINGROUP;
	private static $GROUPS_CLAIM;
	private static $GREMIEN_CLAIM;
	private static $SESSION_MAX_AGE;

	private $oidc;

	/**
	 * return instance of this class
	 * singleton class
	 * return same instance on every call
	 * @return AuthOidcHandler
	 */
	public static function getInstance(...$pars):AuthOidcHandler{
		return parent::getInstance(...$pars);
	}

	/**
	 * class constructor
	 * protected cause of extended singleton class
	 */
	protected function __construct(){
		session_start();
		$this->oidc = new OpenIDConnectClient(self::$PROVIDER_URL, self::$CLIENT_ID, self::$CLIENT_SECRET);
		$this->oidc->setRedirectURL(self::$REDIRECT_URI);
		if (self::$SCOPES){
			$this->oidc->addScope(array_filter(array_map('trim', explode(',', self::$SCOPES))));
		}
		$this->requireAuth();
	}

	final static protected function static__set($name, $value){
		if (property_exists(get_class(), $name))
			self::$$name = $value;
		else
			throw new Exception("$name ist keine Variable in " . get_class());
	}

	/**
	 * return true if the current request targets the dedicated OIDC redirect_uri
	 * @return bool
	 */
	private function isCallbackRequest(){
		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
		return rtrim($path, '/') === rtrim(BASE_SUBDIRECTORY . self::$CALLBACK_PATH, '/');
	}

	/**
	 * handle session and user login
	 */
	function requireAuth(){
		if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] && !isset($_SESSION['SILMPH']['OIDC_CLAIMS'])){
			header('HTTP/1.0 401 UNATHORISED');
			die("Login nicht (mehr) gueltig");
		}

		//local logout trigger, mirrors the ?logout=1 pattern used by AuthDummyHandler
		if (isset($_GET['oidclogout'])){
			$idToken = $_SESSION['SILMPH']['OIDC_ID_TOKEN'] ?? null;
			session_destroy();
			session_start();
			try {
				if ($idToken){
					//signOut() redirects to the provider's end_session_endpoint and exits
					$this->oidc->signOut($idToken, BASE_URL.BASE_SUBDIRECTORY);
				}
			} catch (\Throwable $e){
				//provider has no end_session_endpoint or logout failed - fall back to local logout only
			}
			header('Location: '.BASE_URL.BASE_SUBDIRECTORY);
			die();
		}

		$sessionValid = isset($_SESSION['SILMPH']['OIDC_CLAIMS'])
			&& isset($_SESSION['SILMPH']['OIDC_AUTH_TIME'])
			&& (time() - $_SESSION['SILMPH']['OIDC_AUTH_TIME']) < self::$SESSION_MAX_AGE;

		if (!$sessionValid){
			//never remember the callback URL itself as the "return to" target - would cause a redirect loop
			if (!$this->isCallbackRequest() && !isset($_REQUEST['code']) && !isset($_REQUEST['error'])){
				//about to redirect to the provider - remember where the user wanted to go
				$_SESSION['SILMPH']['OIDC_REQUESTED_URI'] = $_SERVER['REQUEST_URI'];
			}
			try {
				//returns false (and redirects/exits internally) if no code/id_token is present yet
				$authenticated = $this->oidc->authenticate();
			} catch (\Throwable $e){
				header('HTTP/1.0 500 Internal Server Error');
				die("OIDC Login fehlgeschlagen: ".htmlspecialchars($e->getMessage()));
			}
			if ($authenticated){
				$claims = json_decode(json_encode($this->oidc->getVerifiedClaims()), true) ?? [];
				try {
					$userInfo = json_decode(json_encode($this->oidc->requestUserInfo()), true);
					if (is_array($userInfo)){
						$claims = array_merge($claims, $userInfo);
					}
				} catch (\Throwable $e){
					//userinfo endpoint optional - id token claims are sufficient
				}
				$_SESSION['SILMPH']['OIDC_CLAIMS'] = $claims;
				$_SESSION['SILMPH']['OIDC_AUTH_TIME'] = time();
				$_SESSION['SILMPH']['OIDC_ID_TOKEN'] = $this->oidc->getIdToken();
				$redirectTarget = $_SESSION['SILMPH']['OIDC_REQUESTED_URI'] ?? (BASE_URL.BASE_SUBDIRECTORY);
				unset($_SESSION['SILMPH']['OIDC_REQUESTED_URI']);
				header('Location: '.$redirectTarget);
				die();
			}
			//unreachable in practice: authenticate() exits via redirect when $authenticated is false
		}

		if(!$this->hasGroup(self::$AUTHGROUP)){
			header('HTTP/1.0 403 FORBIDDEN');
			die("Du besitzt nicht die nötigen Rechte um diese Seite zu sehen.");
		}

		//session client info - session fixation prevention
		if(!isset($_SESSION['SILMPH']['CLIENT_IP'])
			|| $_SESSION['SILMPH']['CLIENT_IP'] != $_SERVER['REMOTE_ADDR']
			|| (isset($_SESSION['SILMPH']['CLIENT_AGENT']) && $_SESSION['SILMPH']['CLIENT_AGENT'] != ((isset($_SERVER['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR']))){
			$_SESSION['SILMPH']['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['SILMPH']['CLIENT_AGENT'] = ((isset($_SERVER['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR']);
		}

		//init messagehandler
		if (!isset($_SESSION['SILMPH']['MESSAGES'])) {
			$_SESSION['SILMPH']['MESSAGES'] = [];
		}
	}

	/**
	 * return current user attributes (claims from id_token + userinfo endpoint)
	 * @return array
	 */
	function getAttributes(){
		return $_SESSION['SILMPH']['OIDC_CLAIMS'] ?? [];
	}

	/**
	 * read a (possibly nested, dot-separated) claim as a list of strings
	 * e.g. "groups" or "realm_access.roles" for Keycloak-style role claims
	 * @param string $claimPath
	 * @return array
	 */
	private function getClaimAsList($claimPath){
		$value = $this->getAttributes();
		foreach (explode('.', $claimPath) as $part){
			if (is_array($value) && array_key_exists($part, $value)){
				$value = $value[$part];
			} else {
				return [];
			}
		}
		if (is_string($value)) return [$value];
		if (is_array($value)) return $value;
		return [];
	}

	/**
	 * return user mail address
	 * @return string
	 */
	function getUserMail(){
		$this->requireAuth();
		$a = $this->getAttributes();
		return $a['email'] ?? null;
	}

	/**
	 * return user displayname
	 * @return string
	 */
	function getUserFullName(){
		$this->requireAuth();
		$a = $this->getAttributes();
		if (!empty($a['name'])) return $a['name'];
		if (!empty($a['given_name']) || !empty($a['family_name'])){
			return trim(($a['given_name'] ?? '').' '.($a['family_name'] ?? ''));
		}
		return $a['preferred_username'] ?? ($a['email'] ?? null);
	}

	/**
	 * return username or user mail address
	 * if not set return null
	 * @return string|NULL
	 */
	function getUsername(){
		$a = $this->getAttributes();
		return $a['preferred_username']
			?? $a['eduPersonPrincipalName']
			?? $a['email']
			?? $a['sub']
			?? null;
	}

	/**
	 * check group permission - die on error
	 * return true if successfull
	 * @param string $groups    String of groups
	 * @return bool  true if the user has one or more groups from $group
	 */
	function requireGroup($group){
		$this->requireAuth();
		if (!$this->hasGroup($group)){
			header('HTTP/1.0 403 Unauthorized');
			echo 'You have no permission to access this page.';
			die();
		}
		return true;
	}

	/**
	 * check group permission - return result of check as boolean
	 * @param string $groups    String of groups
	 * @param string $delimiter Delimiter of the groups in $group
	 * @return bool  true if the user has one or more groups from $group
	 */
	function hasGroup($groups, $delimiter = ","){
		if (trim($groups) === ''){
			//no group required - any authenticated user passes
			return true;
		}
		$userGroups = $this->getClaimAsList(self::$GROUPS_CLAIM);
		if (empty($userGroups)){
			return false;
		}
		if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map("strtolower", $userGroups))) == 0){
			return false;
		}
		return true;
	}

	function hasGremium($gremien, $delimiter = ","){
		$userGremien = $this->getClaimAsList(self::$GREMIEN_CLAIM);
		if (empty($userGremien)){
			return false;
		}
		if (count(array_intersect(explode($delimiter, strtolower($gremien)), array_map("strtolower", $userGremien))) == 0){
			return false;
		}
		return true;
	}

	/**
	 * return boolean if admin is on group list
	 * @return bool
	 */
	function isAdmin(){
		return $this->hasGroup(self::$ADMINGROUP);
	}

	/**
	 * return log out url
	 * points to a local endpoint that clears the app session and forwards
	 * to the provider's end_session_endpoint (RP-Initiated Logout)
	 * @return string
	 */
	function getLogoutURL(){
		return BASE_URL.BASE_SUBDIRECTORY.'?oidclogout=1';
	}

	/**
	 * send html header to redirect to logout url
	 */
	function logout(){
		header('Location: '. $this->getLogoutURL());
		die();
	}
}
