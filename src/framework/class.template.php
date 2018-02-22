<?php
/**
 * FRAMEWORK Template
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
 * Template class
 * 
 * @author Michael Gnehr <michael@gnehr.de>
 * @since 08.03.2017
 * @package SILMPH_framework
 */
class Template
{
	/**
	 * 
	 * @var array $scripts
	 * @var array $css
	 * @var array $modals
	 */
	private $scripts;
	private $css;
	private $modals;
	
	/**
	 * AuthHandler
	 */
	private $auth;
	
	/**
	 * $navigation array
	 * 
	 * array
	 */
	private $nav;
	
	/**
	 * current url path
	 * string
	 */
	private $path;

	/**
	 * 
	 * string $title_prefix
	 */
	private $title_prefix;
	
	/**
	 * boolean $header_printed
	 */
	private $header_printed;
	
	/**
	 * string|boolean $logged_in_user
	 */
	private $logged_in_user;
	
	/**
	 * string $extra_body_class
	 */
	private $extra_body_class;
	
	/**
	 * @var boolean $_isLogout
	 */
	private $_isLogout = false;

	/**
	 * Template constructor
	 * @param boolean $appendDefaultScripts append default js scripts set to template
	 * @param boolean $appendDefaultCSS append default css scripts set to template
	 * @param boolean $appendMessages append message js to template
	 */
	function __construct(
		$auth = NULL, $navigation=array(), $path = '',
		$appendDefaultScripts = true, $appendDefaultCSS = true, $appendMessages = true)
	{
		$this->auth = $auth;
		$this->nav = $navigation;
		$this->path = $path;
		$this->scripts = array();
		$this->css = array();
		$this->modals = array();
		$this->title_prefix = '';
		$this->header_printed = false;
		$this->logged_in_user = false;
		$this->extra_body_class = '';
		
		if(isset($auth)){
			$this->logged_in_user = $this->auth->getUserFullName();
		}
		
		if ($appendDefaultScripts){
			$this->appendJsLink('libs/jquery-3.1.1.min.js');
			$this->appendJsLink('libs/bootstrap.min.js');
			//$this->appendJsLink('libs/screenfull.js');
			$this->appendJsLink('base.js');
		}
		if ($appendDefaultCSS){
			$this->appendCssLink('bootstrap.min.css', 'screen,projection');
			$this->appendCssLink('font-awesome.css');
			$this->appendCssLink('style.css', 'screen,projection');
			$this->appendCssLink('proto.css', 'screen,projection');
			$this->appendCssLink('print.css', 'print');
		}
		if ($appendMessages){
			//info admin creation possible
			if (defined('ENABLE_ADMIN_INSTALL') && ENABLE_ADMIN_INSTALL) {
				$this->appendJsInline("$(document).ready(function(){ silmph__add_message('Die Konstante ENABLE_ADMIN_INSTALL ist als wahr definiert. Adminregistrierungen sind ohne Passwort möglich. Bitte editieren Sie aus Sicherheitsgründen die Datei \"config.php\" entprechend der Kommentare.', MESSAGE_TYPE_WARNING, 0); });");
			}
			if (intval("9223372036854775807")!=9223372036854775807) {
				// 32-bit
				$this->appendJsInline("$(document).ready(function(){ silmph__add_message('Der Server unterstützt keine 64-bit Zahlen. Bitte wechseln Sie auf ein 64-bit System um Probleme zu vermeiden.', MESSAGE_TYPE_WARNING, 0); });");
			}
			//Logoutmessage
			if ($this->_isLogout){
				$this->appendJsInline("$(document).ready(function(){ silmph__add_message('".$this->_isLogout[0]."', MESSAGE_TYPE_".$this->_isLogout[1].", 3000); });");
			}
			//messages
			if(count($_SESSION['SILMPH']['MESSAGES']) > 0){
				foreach ($_SESSION['SILMPH']['MESSAGES'] as $msg){
					$this->appendJsInline("$(document).ready(function(){ silmph__add_message('".$msg[0]."', MESSAGE_TYPE_".$msg[1].", 3000); });");
				}
				$_SESSION['SILMPH']['MESSAGES'] = array();
			}
		}
	}

	/**
	 * add javascrip
	 * @param string $scriptname scriptname in scriptfolder
	 */
	public function appendJsLink( $scriptname ){
		$this->scripts[] = "<script src=\"/js/$scriptname\" type=\"text/javascript\"></script>";
	}

	/**
	 * add inline javascript
	 * @param string $script
	 */
	public function appendJsInline( $script ){
		$this->scripts[] = '<script type="text/javascript">' . $script . '</script>';
	}

	/**
	 * append ccs style file
	 * @param string $stylename css style filename in js directory
	 * @param string $media html media tag if necessary
	 */
	public function appendCssLink( $stylename, $media = NULL ){
		$this->css[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/$stylename\"".(($media)? ' media="'.$media.'"' : '')."/>";
	}
	
	/**
	 * prepend ccs style file
	 * @param string $stylename css style filename in js directory
	 * @param string $media html media tag if necessary
	 */
	public function prependCssLink( $stylename, $media = NULL ){
		array_unshift ( $this->css, "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/$stylename\"".(($media)? ' media="'.$media.'"' : '')."/>");
	}
	
	/**
	 * append inline css style into head
	 * @param string $css
	 */
	public function appendCssInline( $css ){
		$this->css[] = '<style>'.$css.'</style>';
	}
	
	/**
	 * append html in modal section of template
	 * @param string $html
	 */
	public function appendModal( $html ){
		$this->modals[] = $html;
	}

	/**
	 * return js scripts html code
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function printScript($echo = false){
		$result = '';
		foreach ($this->scripts as $value) {
			$result .= "\t\t\t$value\n";
		}
		if ($echo) echo $result;
		return $result;
	}

	/**
	 * return css part html code
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function printCss($echo = false){
		$result = '';
		foreach ($this->css as $value) {
			$result .= "\t\t\t$value\n";
		}
		if ($echo) echo $result;
		return $result;
	}

	/**
	 * return modal html text
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function printModal($echo = false){
		$result = '';
		foreach ($this->modals as $value) {
			$result .= "\t\t\t$value\n";
		}
		if ($echo) echo $result;
		return $result;
	}

	/**
	 * set Template Title prefix
	 * suffix is set in config.php
	 * @param string $prefix
	 */
	public function setTitlePrefix($prefix){
		$this->title_prefix = $prefix;
	}
	
	/**
	 * set extra classname to body tag
	 * @param string $class
	 */
	public function setExtraBodyClass($class){
		$this->extra_body_class = trim(strip_tags($class));
	}
	
	/**
	 * return true if printPageHeader() was called before
	 * @return boolean
	 */
	public function isHeaderPrinted() {
		return $this->header_printed;
	}
	
	/**
	 * print template header
	 */
	public function printPageHeader(){
		$this->header_printed = true;
		include (dirname(__FILE__, 2)."/templates/".TEMPLATE."/header.phtml");
	}

	/**
	 * print template footer
	 */
	public function printPageFooter(){
		if (DEBUG >= 1){
			prof_flag('template_footer');
		}
		include (dirname(__FILE__, 2)."/templates/".TEMPLATE."/footer.phtml");
	}

	/**
	 * return page title text
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function getTitle($echo = false){
		$result = "";
		if ($this->title_prefix) $result .= $this->title_prefix . ' - ';
		$result .= BASE_TITLE;
		if ($echo) echo $result;
		return $result;
	}
	
	/**
	 * @return array list of current available navigation entries
	 */
	public function getNavigation(){
		$out = [];
		if ($this->auth !== NULL){
			foreach ($this->nav as $route => $data){
				if ($this->auth->hasGroup($data[0])){
					$key = $route;
					if ($key != '/' && substr($key, 0, 4) != 'http') $key = '/'.$key;
					$out[$key] = array_slice($data, 1);
					if ($this->path === $route){
						$out[$key]['active'] = true;
					}
				}
			}
		}
		return $out;
	}
}
?>