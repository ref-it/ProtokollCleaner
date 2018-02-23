<?php
/**
 * FRAMEWORK LIB Protocol Controller
 * implement protocol functions
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        lib
 * @author 			michael g
 * @author 			schlobi
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (FRAMEWORK_PATH."/config/config.protocol.php");
require_once (FRAMEWORK_PATH."/lib/class.protocol.php");

/**
 * implement protocol functions
 * @author michael g
 * @author schlobi
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 */
class protocolHelper
{
	/**
	 * class constructor
	 */
	function __construct()
	{
	}
	
	public function parseProto($gremium, $protokoll){
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		$a = $x->getPage(PROTOMAP[$gremium][0].':'.$protokoll);
		$p = NULL;
		if ($a) {
			$p = new Protocol($a);
		} else {
			echo 'Protocol not found';
			return;
		}
		
		
		//echo '<pre>'; var_dump($p); echo '</pre>';
		//TODO open and close tags
		//TODO detect internal part
		//TODO detect todos
		//TODO detect resolutions
		echo 'run parser';
	}
}

?>