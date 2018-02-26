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
	
	/**
	 * 
	 * @param Protocol $p
	 * @param string $addDraftText
	 * @param string $nopreview
	 */
	public function parseProto($p, $publising_user, $addDraftText = false, $nopreview = false){
		prof_flag('parseProto_start');
		
		$isInternal = false;	// dont copy internal parts to public part
		$isLineError = false;	// found parsing error
		$lastTagClosed = true; 	// prevent duplicate closing tags and closing internal part before opening
		$writeUserText = 1;		// used to detect protocol head
		
		//only fill preview or output
		if (!$nopreview) $p->preview = protocolDiff::generateHeader();
		
		// parser main loop - loop throught $protocol lines
		// create internal <==> external diff
		foreach($p->text_a as $linenumber => $line){
			//detect protocol head to insert draft state + publishung user
			if ($writeUserText == 3) {
				if (!$nopreview){
					if ($addDraftText) $p->preview .= protocolDiff::generateCopiedChangedLine('====== ENTWURF - PROTOKOLL ======');
					$p->preview .= protocolDiff::generateCopiedChangedLine('====== GENERIERT mit '.BASE_TITLE.' von ('.$publising_user.') ======'."\n");
				} else {
					if ($addDraftText) $p->external .= '====== ENTWURF - PROTOKOLL ======'."\n";
					$p->external .= '====== GENERIERT mit '.BASE_TITLE.' von ('.$publising_user.') ======'."\n";
				}
				$addDraftText = false;
				$writeUserText = 0;
			}
			if ($writeUserText == 2) {
				if (strpos($line, "}}") !== false){
					$writeUserText++;
				}
			} else if ($writeUserText == 1){
				if (strpos($line, "{{template>:vorlagen:Protokoll") !== false){
					$writeUserText++;
				}
			}

			// detect nonpublic/internal parts
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
			//detect fixme, todo, resolutions
			
			
			
		}
		if ($this->isLineError == true){ // error handling: show error to user
			if (!$nopreview) $p->preview .= protocolDiff::generateErrorLine($this->lineError);
			else $p->external .= protocolDiff::generateErrorChangedLine($this->lineError);
		}
		
		
		$p->preview .= protocolDiff::generateFooter();
		prof_flag('parseProto_end');
		
		// object
		return $p;
		
		
		
		//TODO detect todos
		//TODO detect resolutions
		//TODO cleanup array
		//TODO check attachements
		//TODO detect Legislatur
	}
}

?>