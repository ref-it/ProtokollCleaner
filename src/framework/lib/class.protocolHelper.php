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
	
	private $isLineError = false;
	private $lineError = '';
	
	private static $tagRegex = '/(({{tag>[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*([ ]*[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*)*( )*}}|===== geschlossener Teil =====|===== öffentlicher Teil =====)+)/';
	private static $oldTags = ['===== geschlossener Teil =====', '===== öffentlicher Teil ====='];
	
	
	public function parseProto($gremium, $protokoll, $isdraft = false, $nopreview = false){
		prof_flag('parseProto_start');
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		prof_flag('get wikipage');
		$a = $x->getPage(PROTOMAP[$gremium][0].':'.$protokoll);
		prof_flag('got single page');
		$p = NULL;
		if ($a) {
			$p = new Protocol($a);
		} else {
			echo 'Protocol not found';
			return;
		}
		
		$isInternal = false;	// dont copy internal parts to public part
		$isLineError = false;	// found parsing error
		$lastTagClosed = true; 	// prevent duplicate closing tags and closing internal part before opening
		$writeDraftText = 1;
		
		//only fill preview or output
		if (!$nopreview) $p->preview = protocolDiff::generateHeader();
		
		
		// parser main loop - loop throught $protocol lines
		// create internal <==> external diff
		foreach($p->text_a as $linenumber => $line){
			//detect protocol head to insert draft state
			if ($isdraft) {
				if ($writeDraftText >= 3) {
					if (!$nopreview) $p->preview .= protocolDiff::generateCopiedChangedLine('====== ENTWURF - PROTOKOLL ======');
					else $p->external .= '====== ENTWURF - PROTOKOLL ======'."\n";
					$isdraft = false;
				}
				if ($writeDraftText >= 2) {
					if (strpos($line, "}}") !== false){
						$writeDraftText = 3;
					}
				} else if ($writeDraftText >= 1){
					if (strpos($line, "{{template>:vorlagen:Protokoll") !== false){
						$writeDraftText = 2;
					}
				}
			}
			// detect nonpublic parts
			$matches = [];
			$match = preg_match(self::$tagRegex, $line, $matches, PREG_OFFSET_CAPTURE, 0);
			$matchcount = 0;
			$changed = false; // don't allow other text on state changing lines + don' place internal external changing tags to external script
			if ($match === 1 && $matches[0][1]>=0){
				if ($matches[0][0] == self::$oldTags[0]){ // count opening and closing tags
					$p->tags['old'] = isset($p->tags['old'])? $p->tags['old'] + 1 : 1;
					$isInternal = true;
					$changed = true;
				} else if ($matches[0][0] == self::$oldTags[1]) { // count opening and closing tags
					$p->tags['old'] = (isset($p->tags['old']) && $p->tags['old'] > 0)? $p->tags['old'] - 1 : 0;
					$isInternal = false;
					$changed = true;
				} else {  // count opening and closing tags //split found tags
					$m = $matches[0][0]; // clean matches ...
					$m = str_replace(['{{tag>', '  ', '}}'], ['',' ',''], $m);
					$m = trim(str_replace(['{{tag>', '  ', '}}'], ['',' ',''], $m));
					$m = explode(' ', $m);
					foreach ($m as $single_tag) { //handle multiple tags inside tag area
						if (strlen($single_tag)>2 && substr($single_tag,0, 2) != 'no') {
							$p->tags[$single_tag] = isset($p->tags[$single_tag])? $p->tags[$single_tag] + 1 : 1;
							if ($single_tag.PROTO_INTERNAL_TAG) $isInternal = true;
							$changed = true;
						} else {
							$p->tags[$single_tag] = (isset($p->tags[$single_tag]) && $p->tags[$single_tag] > 0)? $p->tags[$single_tag] - 1 : 0;
							if($single_tag == 'no'.PROTO_INTERNAL_TAG) $isInternal = false;
							$changed = true;
						}
					}
				}
				if ($changed) { // internal/external state changed - test for errors
					$tmp_line = trim(str_replace($matches[0][0], '', $line));
					if ($tmp_line != ''){
						$this->isLineError = true;
						$this->lineError = "Please use a new line to seperate internal and external parts.";
						if (!$nopreview) $p->preview .= protocolDiff::generateErrorChangedLine($line);
						break;
					}
					if ($lastTagClosed == !$isInternal){
						$this->isLineError = true;
						$this->lineError = "Duplicate closing tag or closing before opening found.";
						if (!$nopreview) $p->preview .= protocolDiff::generateErrorChangedLine($line);
						break;
					} else {
						$lastTagClosed = !$isInternal;
					}
				}
			}
			if ($isInternal || $changed){ // mark changes on preview
				if (!$nopreview) $p->preview .= protocolDiff::generateRemovedLine($line);
			} else { // only copy public lines
				if (!$nopreview) $p->preview .= protocolDiff::generateCopiedLine($line);
				else $p->external .= "$line\n";
			}
		}
		if ($this->isLineError == true){ // error handling: show error to user
			if (!$nopreview) $p->preview .= protocolDiff::generateErrorLine($this->lineError);
			else $p->external .= protocolDiff::generateErrorChangedLine($this->lineError);
		}
		
		
		
		/*
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
		*/
		$p->preview .= protocolDiff::generateFooter();
		
		

		prof_flag('parseProto_end');
		// return pointer to protocol object
		return $p;
		
		//echo '<pre>'; var_dump($p); echo '</pre>';
		//TODO open and close tags
		//TODO detect internal part
		//TODO detect todos
		//TODO detect resolutions
		//TODO cleanup array
		//TODO check attachements
		echo 'run parser';
	}
}

?>