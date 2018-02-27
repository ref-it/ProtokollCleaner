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
	
	private static $regexFinder = [
		'multimatch' => [
			'todo' => '/(?<!alte )todo/i',
			'fixme' => '/(?<!alte )fixme/i',
			'deleteme' => '/(?<!alte )deleteme/i',
		],
		'no_multimatch' => [
			'resolution' => '/^{{template>:vorlagen:stimmen.*(angenommen)(?!.*abgelehnt).*$/i',
			'sitzung' => '/(=)+ (\d)+. StuRa-Sitzung (=)+/'
		]		
	];
	
	private static $resolutionParts = [
		'titel=' => 'Titel', 
		'j=' => 'Ja',
		'n=' => 'Nein',
		'e=' => 'Enthaltungen',
		's=' => 'Beschluss',
	];
	
	private static $resolutionType = [[
			'match' => ['Protokoll', 'beschließt', 'Sitzung'], 
			'long' => 'Protokoll',
			'short' => 'P'
		], [
			'match' => ['Haushaltsverantwortliche', 'beschließt', 'Budget'], 
			'long' => 'Finanzen',
			'short' => 'H'
		], [
			'match' => ['beschließt', 'Budget'], 
			'long' => 'Finanzen',
			'short' => 'F'
		], [
			'match' => ['Ordnung'], 
			'long' => 'Ordnung',
			'short' => 'O'
		], [
			'match' => ['Gründung|Auflösung|Leiter|Mitglied|wählt|bestätigt'], 
			'long' => 'Wahl',
			'short' => 'W'
		], [
			'match' => ['beschließt|bestätigt', 'Amt'], 
			'long' => 'Wahl',
			'short' => 'W'
		], [
			'pattern' => '/.*/', 
			'long' => 'Sonstiges',
			'short' => 'S'
		]
	];
	
	private static $highlightKeywords = [
		'angestellte', 'todo', 'fixme', 'deleteme', 'rdb'
	];
	
	private $isLineError = false;
	private $lineError = '';
	
	private static $tagRegex = '/(({{tag>[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*([ ]*[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*)*( )*}}|(=)+ geschlossener Teil (=)+|(=)+ öffentlicher Teil (=)+)+)/';
	private static $oldTags = ['/^(=)+ geschlossener Teil (=)+$/', '/^(=)+ öffentlicher Teil (=)+$/'];
	
	/**
	 * categorize and split raw resolution strings to array
	 * 
	 * @param array $resolutions array of raw resolution strings
	 * @param Protocol $p
	 * @param array $overwriteType overwrites Type ['short' => '', 'long' => '']
	 * @return array of resolutions [[text, type_short, type_long, p_tag], ...]
	 */
	private static function parseResolutionArray($resolutions, $p, $overwriteType = NULL){
		$result = [];
		//parse resolutions: categorize, and split to array
		foreach ($resolutions as $rawres){
			$result[] = self::parseResolution($rawres, $p, $overwriteType);
		}
		return $result;
	}
	
	/**
	 * categorize and split raw resolution strings to array
	 * 
	 * @param string $resolution raw resolution text
	 * @param Protocol $p
	 * @return array parsed resolution [text|title, type_short, type_long, p_tag, raw]
	 */
	private static function parseResolution($resolution, $p, $overwriteType = NULL){
		$result = ['raw' => $resolution];
		$parts = array_map('trim', explode('|', $resolution));
		foreach ($parts as $pos => $text){
			$text = str_replace('}}', '', $text);
			foreach (self::$resolutionParts as $query => $key){
				if (preg_match('/^'.$query.'/i', $text)){
					$result[$key] = htmlspecialchars(substr($text, strlen($query)));				
				}
			}
		}
		if ($overwriteType !== NULL){
			$result['type_short'] = $overwriteType['short'];
			$result['type_long'] = $overwriteType['long'];
		} else {
			//detect type by pregmatches
			foreach (self::$resolutionType as $type){
				$pattern = [];
				//matches as string
				if (isset($type['match'])){
					if (is_array($type['match'])){
						foreach ($type['match'] as $mat){
							$pattern[] = '/.*('.$mat.').*/';
						}
					} else {
						$pattern[] = '/.*('.$type['match'].').*/';
					}
				}
				//regex pattern
				if (isset($type['pattern'])){
					if (is_array($type['pattern'])){
						$pattern = $type['pattern'];
					} else {
						$pattern = [$type['pattern']];
					}
				}
				//handle multiple and match pattern
				$matches = true;
				foreach ($pattern as $subpattern){
					if (!preg_match($subpattern, $result['Titel'])){
						$matches = false;
						break;
					}
				}
				if ($matches){
					$result['type_short'] = $type['short'];
					$result['type_long'] = $type['long'];
					break;
				}
			}
		}
		if ($result['type_long'] == 'Protokoll'){
			//parse protocoltag
			$tmp = $result['Titel'];
			$tmp = preg_replace('/[^\d.-]/', '', $tmp);
			$tmp = str_replace('.', '-', $tmp);
			$date = false;
			//try to parse date
			if (strlen($tmp) >= 10){
				$tmp = substr($tmp, 0, 10);
				$date = date_create_from_format('d-m-Y', $tmp);
			} else if (strlen($tmp) >= 8){
				$tmp = substr($tmp, 0, 8);
				$date = date_create_from_format('d-m-y', $tmp);
			}			
			if ($date) {
				$result['p_tag'] = $p->committee.':'.$date->format('Y-m-d');
			} else {
				$result['p_tag'] = false;
				$p->parse_errors[] = "<strong>Parse Error: Protokolldatum</strong> Dem folgenden Protokollbeschluss konnte kein Datum entnommen werden. Gesuchtes format: dd.mm.yy oder dd-mm-yy<br><i>{$result['Titel']}</i>";
			}
			
		}
		return $result;
	}
	
	/**
	 * add resolution tag
	 *
	 * @param Protocol $p protocol
	 * @param integer $legislatur
	 */
	private static function numberResolutionArray($p, $legislatur){
		//parse resolutions: categorize, and split to array
		$count = [];
		foreach ($p->resolutions as $pos => $reso){
			$count[$reso['type_short']] = (isset($count[$reso['type_short']])? $count[$reso['type_short']]+1: 1);
			$p->resolutions[$pos]['r_tag'] = "$legislatur/{$p->date->format('W')}-{$reso['type_short']}{$count[$reso['type_short']]}";
		}
	}
	
	/**
	 * 
	 * @param Protocol $p
	 * @param string $addDraftText
	 * @param string $nopreview
	 */
	public function parseProto($p, $publising_user, $addDraftText = false, $nopreview = false){
		prof_flag('parseProto_start');
		
		$isInternal = false;	// dont copy internal parts to public part
		$this->isLineError = false;	// found parsing error
		$lastTagClosed = true; 	// prevent duplicate closing tags and closing internal part before opening
		$writeUserText = 1;		// used to detect protocol head
		
		//init preg_match results
		$pregFind = ['todo' => [], 'resolution' => [], 'fixme' => []];		// contains preg matches (todos, fixmes, resolutions)
		
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
				if (preg_match(self::$oldTags[0], $matches[0][0])){ // count opening and closing tags
					$p->tags['old'] = isset($p->tags['old'])? $p->tags['old'] + 1 : 1;
					$isInternal = true;
					$changed = true;
				} else if (preg_match(self::$oldTags[1], $matches[0][0])) { // count opening and closing tags
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
						$p->parse_errors[] = $this->lineError;
						if (!$nopreview) $p->preview .= protocolDiff::generateErrorLine($line);
						break;
					}
					if ($lastTagClosed == !$isInternal){
						$this->isLineError = true;
						$this->lineError = "Duplicate closing tag or closing before opening found.";
						$p->parse_errors[] = $this->lineError;
						if (!$nopreview) $p->preview .= protocolDiff::generateErrorLine($line);
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
			//maybe run this on whole text globally
			foreach(self::$regexFinder['no_multimatch'] as $key => $pattern){
				if (preg_match($pattern, $line)){
					$pregFind[$key][($isInternal)?'intern':'public'][] = $line;
				}
			}
			//detect fixme, todo, resolutions
			//maybe run this on whole text globally
			foreach(self::$regexFinder['multimatch'] as $key => $pattern){
				$tmp_matches = preg_match_all($pattern, $line);
				if ($tmp_matches){
					$pregFind[$key][($isInternal)?'intern':'public'][] = [$line, $tmp_matches];
				}
			}
		}
		if ($this->isLineError == true){ // error handling: show error to user
			if (!$nopreview) $p->preview .= protocolDiff::generateErrorLine($this->lineError);
			else $p->external .= protocolDiff::generateErrorLine($this->lineError);
		}
		
		if (!$nopreview){
			//print table footer
			$p->preview .= protocolDiff::generateFooter();
			//highlight keywords
			$re = '/('.implode('|',self::$highlightKeywords ).')/i';
			$subst = '<span class="highlight">$1</span>';
			$p->preview = preg_replace($re, $subst, $p->preview);
		}
		prof_flag('parseProto_end');
		
		//categorize pregmatches
		if (isset($pregFind['resolution']['public']))
			$p->resolutions = $p->resolutions +  self::parseResolutionArray($pregFind['resolution']['public'], $p);
		if (isset($pregFind['resolution']['intern']))
			$p->resolutions = $p->resolutions + self::parseResolutionArray($pregFind['resolution']['intern'], $p, ['I', 'Intern']);
		//create resolution tags
		self::numberResolutionArray($p, $p->legislatur);
		
		// add todos and fixmes
		$p->todos['fixme'] = $pregFind['fixme'];
		$p->todos['todo'] = $pregFind['todo'];
		$p->todos['deleteme'] = $pregFind['deleteme'];
		
		//add protocol numnber (sitzungnummer)
		if (isset($pregFind['sitzung'])){
			$p->protocol_number = intval(preg_replace('/[^\d]/', '', $pregFind['sitzung']['public'][0]));
		} else {
			$p->protocol_number = -1;
			$p->parse_errors[] = "Sitzungsnummer konnte nicht erkannt werden.";
		}
		
		// object
		return $p;
		//TODO detect Legislatur
	}
}

?>