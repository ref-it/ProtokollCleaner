<?php
/**
 * CONFIG FILE ProtocolHelper
 * Application initialisation
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        configuration
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
 * @since 08.03.2017
 * @package SILMPH_framework
 */
class template
{
	/**
	 * 
	 * @var database $db
	 * @var array $scripts
	 * @var array $css
	 * @var array $modals
	 */
	private $db;
	private $scripts;
	private $css;
	private $modals;

	/**
	 * 
	 * @var string $title_prefix
	 * @var boolean $header_printed
	 * @var string|boolean $logged_in_user
	 * @var string $extra_body_class
	 */
	private $title_prefix;
	private $header_printed;
	private $logged_in_user;
	private $extra_body_class;
	
	/**
	 * 
	 * @var boolean $_isLogout
	 */
	private $_isLogout = false;

	/**
	 * template constructor
	 * @param boolean $appendDefaultScripts append default js scripts set to template
	 * @param boolean $appendDefaultCSS append default css scripts set to template
	 * @param boolean $appendMessages append message js to template
	 */
	function __construct($appendDefaultScripts = true, $appendDefaultCSS = true, $appendMessages = true)
	{
		$this->scripts = array();
		$this->css = array();
		$this->modals = array();
		$this->title_prefix = '';
		$this->header_printed = false;
		$this->logged_in_user = false;
		$this->extra_body_class = '';
		
		if(isset($_SESSION['LOGOUT'])){
			$this->_isLogout = $_SESSION['LOGOUT'];
			unset($_SESSION['LOGOUT']);
		}
		if(isset($_SESSION['USER_ID']) && $_SESSION['USER_ID'] > 0){
			$this->logged_in_user = $_SESSION['USER_SET']['username'];
		}
		
		global $db;
		$this->db = $db;
		if ($appendDefaultScripts){
			$this->appendJsLink('jquery-3.1.1.min.js');
			$this->appendJsLink('screenfull.js');
			$this->appendJsLink('base.js');
		}
		if ($appendDefaultCSS){
			$this->appendCssLink('style.css', 'screen,projection');
			$this->appendCssLink('print.css', 'print');
			$this->appendCssLink('fontello.css');
			$this->appendCssLink('fontello-ie7.css');
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
			if(count($_SESSION['MESSAGES']) > 0){
				foreach ($_SESSION['MESSAGES'] as $msg){
					$this->appendJsInline("$(document).ready(function(){ silmph__add_message('".$msg[0]."', MESSAGE_TYPE_".$msg[1].", 3000); });");
				}
				$_SESSION['MESSAGES'] = array();
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
		include (dirname(__FILE__)."/../templates/".TEMPLATE."/header.phtml");
	}

	/**
	 * print template footer
	 */
	public function printPageFooter(){
		include (dirname(__FILE__)."/../templates/".TEMPLATE."/footer.phtml");
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
}
?>