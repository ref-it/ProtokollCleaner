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
	 */
	private $scripts;
	
	/**
	 * 
	 * @var array $css
	 */
	private $css;
	
	/**
	 * meta head tags and other tags inserted to head
	 * @var array $meta_other
	 */
	private $meta_other;
	
	/**
	 * 
	 * @var array $modals
	 */
	private $modals;
	
	/**
	 * array of link arrays
	 * link array
	 * 	['link' => 'target', 'text' => 'linktext', 'symb' => '(optional) fa - symbol: &#xf0cb;']
	 * @var array $floating_links
	 */
	private $floating_links;
	
	/**
	 * @var AuthHandler|BasicAuthHandler
	 */
	private $auth;
	
	/**
	 * $navigation array
	 * 
	 * @var array
	 */
	private $nav;
	
	/**
	 * current url path
	 * @var string
	 */
	private $path;

	/**
	 * 
	 * @var string $title_prefix
	 */
	private $title_prefix;
	
	/**
	 * @var boolean $header_printed
	 */
	private $header_printed;
	
	/**
	 * @var string|boolean $logged_in_user
	 */
	private $logged_in_user;
	
	/**
	 * @var string $extra_body_class
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
		$this->scripts = [];
		$this->css = [];
		$this->meta_other = ['<link rel="shortcut icon" href="'.BASE_SUBDIRECTORY.'images/favicon.ico" type="image/x-icon" />'];
		$this->modals = [];
		$this->floating_links = [];
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
	 * add javascript
	 * @param string $scriptname scriptname relative to scriptfolder
	 * @param boolean $js_relative
	 */
	public function appendJsLink( $scriptname, $js_relative = true ){
		if ($js_relative) $scriptname = BASE_SUBDIRECTORY . 'js/'. $scriptname;
		$this->scripts[] = "<script src=\""."$scriptname\" type=\"text/javascript\"></script>";
	}

	/**
	 * add inline javascript
	 * @param string $script
	 */
	public function appendJsInline( $script ){
		$this->scripts[] = '<script type="text/javascript">' . $script . '</script>';
	}

	/**
	 * get javascript array
	 * @return array of strings
	 */
	public function getJsLinks(){
		return $this->scripts;
	}
	
	/**
	 * set javascript array
	 * @param array $scripts
	 */
	public function setJsLinks($scripts){
		if (is_array($scripts)){
			$this->scripts = $scripts;
			return true;
		}
		return false;
	}
	
	/**
	 * append ccs style file
	 * @param string $stylename css style filename in js directory
	 * @param string $media html media tag if necessary
	 */
	public function appendCssLink( $stylename, $media = NULL, $css_relative = true ){
		if ($css_relative) $stylename = BASE_SUBDIRECTORY . 'css/'. $stylename;
		$this->css[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$stylename\"".(($media)? ' media="'.$media.'"' : '')."/>";
	}
	
	/**
	 * prepend css style file
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
	 * add meta string
	 * meta tag will be opened automatically
	 * meta has to be string with all attributes
	 * @param string $meta meta content
	 */
	public function appendMetaString( $meta ){
		$this->meta_other[] = "<meta $meta >";
	}
	
	/**
	 * add meta string between head meta tag
	 * 
	 * all attributes have to be added as single string
	 * @param array $meta named array: tagname => $content
	 */
	public function appendMeta( $meta ){
		if (!is_array($meta) 
			||  count(array_filter(array_keys($meta), 'is_string')) != count($meta)) throw new Exception('Template: $meta has to be associative array');
		$m = '';
		foreach($meta as $k => $v){
			$m .= (($m != '')?' ':'')."$k=\"$v\"";
		}
		$this->meta_other[] = "<meta $m >";
	}
	
	/**
	 * append to meta_other to page head
	 * @param string $tag
	 */
	public function appendOtherHeadTag($tag) {
		$this->meta_other[] = $tag;
	}
	
	/**
	 * append html in modal section of template
	 * @param string $html
	 */
	public function appendModal( $html ){
		$this->modals[] = $html;
	}
	
	/**
	 * append link to floating box
	 * @param string $target link target
	 * @param string $text link text
	 * @param string|NULL fontAwesome symbol
	 */
	public function appendFloatingLink( $target, $text, $symbol = NULL ){
		$this->floating_links[] = ['link' => $target, 'text' => $text, 'symb' => $symbol];
	}
	
	/**
	 * prepend link to floating box
	 * @param string $target link target
	 * @param string $text link text
	 * @param string|NULL fontAwesome symbol
	 */
	public function prependFloatingLink( $stylename, $media = NULL ){
		array_unshift ( $this->floating_links, ['link' => $target, 'text' => $text, 'symb' => $symbol]);
	}
	
	/**
	 * return js scripts html code
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function printScript($echo = false){
		$result = '';
		foreach ($this->scripts as $value) {
			$result .= "\t\t$value\n";
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
			$result .= "\t\t$value\n";
		}
		if ($echo) echo $result;
		return $result;
	}

	/**
	 * clear meta_other array
	 */
	public function clearMetaOther(){
		$this->meta_other = [];
	}
	
	/**
	 * return meta html text
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function printMetaOther($echo = false){
		$result = '';
		foreach ($this->meta_other as $value) {
			$result .= "\t\t$value\n";
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
			$result .= "\t\t$value\n";
		}
		if ($echo) echo $result;
		return $result;
	}
	
	/**
	 * return floatingLink array
	 * need to be handled in template
	 * @param boolean $echo if echo == true the function echo the result
	 */
	public function getFloatingLinks(){
		return $this->floating_links;
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
		prof_flag('template_footer');
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
				if (isset(Router::getPermissionMap()[$data[0]]) && $this->auth->hasGroup(Router::getPermissionMap()[$data[0]])){
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
