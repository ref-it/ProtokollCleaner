<?php
/**
 * FRAMEWORK LIB Protocol Controller
 * implement protocol functions
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        lib
 * @author 			michael g
 * @author 			martin s
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (FRAMEWORK_PATH."/config/config.protocol.php");
require_once (FRAMEWORK_PATH."/lib/class.protocol.php");
require_once (FRAMEWORK_PATH."/lib/class.protocolDiff.php");

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
	
	private static $tagRegex = '/(({{tag>[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*([ ]*[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*)*( )*}}|===== geschlossener Teil =====|===== öffentlicher Teil =====)+)/';
	private static $oldTags = ['===== geschlossener Teil =====', '===== öffentlicher Teil ====='];
	
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
		
		
		$isInternal = false;
		
		// parser main loop - loop throught $protocol lines
		foreach($p->text_a as $linenumber => $line){
			$matches = [];
			$match = preg_match(self::$tagRegex, $line, $matches, PREG_OFFSET_CAPTURE, 0);
			while (preg_match === 1 && $matches[0][1]>=0){
				
			}
			
		
		
		
		$protodiff = protocolDiff::generateHeader();
		$OffRec = false;
		$countInTag = 0;
		$countOutTag = 0;
		
		
			
			
		
				if (strpos($line, "tag>" . 'intern') !== false)
				{
					$countInTag = $countInTag + 1;
				}
				if (strpos($line, "tag>" . 'extern') !== false)
				{
					if ($countInTag === 0)
					{
						Useroutput::PrintLine("<p>Warning Endtag vor Anfangstag</p><br />" .PHP_EOL);
					}
					if ($countInTag === $countOutTag)
					{
						Useroutput::PrintLine("<p>Warning Endtag vor Anfangstag</p><br />" .PHP_EOL);
					}
					$countOutTag = $countOutTag + 1;
				}
				if(!$OffRec and strpos($line, "tag>" . 'intern') !== false) {
					$OffRec=true;
					$protodiff .= protocolDiff::generateRemovedLine($line);
					continue;
				}
				if(!$OffRec)
				{
					if(strpos($line, "======") !== false and !$check)
					{
						$firstpart = substr($line, strpos($line, "======"), 6 );
						$secondpart = substr($line, strpos($line, "======") + 6, strlen($line) -1 );
						$newTitel = $firstpart . " Entwurf:" . $secondpart;
						$protodiff .= protocolDiff::generateCopiedChangedLine($newTitel);
					}
					else {
						$protodiff .= protocolDiff::generateCopiedLine($line);
					}
					continue;
				}
				if($OffRec and strpos($line, "tag>" . Main::$endtag) !== false) {
					$OffRec=false;
				}
				$protodiff .= protocolDiff::generateRemovedLine($line);
		
		}
		
		$protodiff .= protocolDiff::generateFooter();
		
		echo $protodiff;
		
		//echo '<pre>'; var_dump($p); echo '</pre>';
		//TODO open and close tags
		//TODO detect internal part
		//TODO detect todos
		//TODO detect resolutions
		//TODO cleanup array
		echo 'run parser';
	}
}

?>