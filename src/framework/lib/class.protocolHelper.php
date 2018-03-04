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
require_once (FRAMEWORK_PATH."/lib/class.protocolOut.php");

/**
 * implement protocol functions
 * @author michael g
 * @author schlobi
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 */
class protocolHelper extends protocolOut
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
			'sitzung' => '/=(=)+( )*(\d+).?( )*StuRa-Sitzung.*=(=)+/i'
		]
	];
	
	private static $personFinder = '/(\[\[person:(\s)*([a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+( [a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+)*)(\s)*\]\])/i';
	
	private static $resolutionParts = [
		'titel=' => 'Titel', 
		'j=' => 'Ja',
		'n=' => 'Nein',
		'L=' => 'Link',
		'e=' => 'Enthaltungen',
		's=' => 'Beschluss',
	];
	
	public static $resolutionType = [[
			'match' => ['Protokoll', 'beschließt', 'Sitzung', '\d+'], 
			'long' => 'Protokoll',
			'short' => 'P'
		], [ //old resolutions on resolist
			'match' => ['Protokoll', 'vom', 'bestätigt', '\d\d\d\d'], 
			'long' => 'Protokoll',
			'short' => 'P'
		], [
			'match' => ['Tagesordnung'], 
			'long' => 'Tagesordnung',
			'short' => 'T'
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
	
	private static $tagRegex = '/(({{tag>[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*([ ]*[a-zA-Z0-9]+(_[a-zA-Z0-9]+)*)*( )*}}|=(=)+( )*geschlossener Teil( )*=(=)+|=(=)+( )*(ö|Ö)ffentlicher Teil( )*=(=)+)+)/i';
	private static $oldTags = ['/^=(=)+( )*geschlossener Teil( )*=(=)+$/i', '/^=(=)+( )*(ö|Ö)ffentlicher Teil( )*=(=)+$/i'];
	private static $ignoreTags = [];
	
	private static $monthReplaceMap = [
		'Januar' 	=> '01',
		'Februar' 	=> '02',
		'März' 		=> '03',
		'April' 	=> '04',
		'Mai' 		=> '05',
		'Juni' 		=> '06',
		'Juli' 		=> '07',
		'August' 	=> '08',
		'September' => '09',
		'Oktober' 	=> '10',
		'November' 	=> '11',
		'Dezember' 	=> '12'
	];
	private static $date1Reg = '/(\d{1,2}(\.| )*(Januar|Februar|März|April|Mai|Juni|Juli|August|September|Oktober|November|Dezember)(\D){0,3}(\d{2,4}))/i';
	
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
	 * parse resolution Type
	 * @param $text resolution text
	 * @return array ['type_short' => , 'type_long' => ]
	 */
	public static function parseResolutionType ($text){
		$return = [];
		//detect type by pregmatches
		foreach (protocolHelper::$resolutionType as $type){
			$pattern = [];
			//matches as string
			if (isset($type['match'])){
				if (is_array($type['match'])){
					foreach ($type['match'] as $mat){	$pattern[] = '/.*('.$mat.').*/';	}
				} else {
					$pattern[] = '/.*('.$type['match'].').*/';
				}
			}
			//regex pattern
			if (isset($type['pattern'])){
				if (is_array($type['pattern'])){
					$pattern = $type['pattern'];	
				}
				else {
					$pattern = [$type['pattern']]; 	
				}
			}
			//handle multiple and match pattern
			$matches = true;
			foreach ($pattern as $subpattern){
				if (!preg_match($subpattern, $text)){
					$matches = false;
					break;
				}
			}
			if ($matches){
				$return['type_short'] = $type['short'];
				$return['type_long'] = $type['long'];
				break;
			}
		}
		return $return;
	}
	
	/**
	 * parse protocol resolution to p_tag
	 * @param string $text
	 * @param string $committee
	 * @return array ['p_link_date' => , 'p_tag' => ]
	 */
	public static function parseResolutionProtocolTag($text, $committee){
		//parse date on protocols resolutions
		$return = [];
		
		//parse protocoltag
		$date = [];
		if (preg_match(self::$date1Reg, $text, $matches3) == 1){
			$tmp_match = str_replace( array_keys(self::$monthReplaceMap), array_values(self::$monthReplaceMap), $matches3[1]);
			$tmp_match = str_replace(['.', ' '], ['', '-'], $tmp_match);
			$date[] = date_create_from_format('d-m-Y', $tmp_match);
		}
		$tmp = trim(str_replace('  ',' ',preg_replace('/[^\d. -]/', '', str_replace('|',' ', $text))), " \t\n\r\0\x0B-.");
		$tmp = str_replace('.', '-', $tmp);
		$tmpdateList = explode(' ',$tmp);
		foreach($tmpdateList as $dateElem){
			$pdate = false;
			if (strlen($tmp) >= 10){
				$tmp2 = substr($dateElem, 0, 10);
				$pdate = date_create_from_format('d-m-Y His', $tmp2.' 000000');
				if ($pdate){
					$date[] = $pdate;
					continue;
				}
			}
			if (strlen($tmp) >= 10){
				$tmp2 = substr($dateElem, 0, 10);
				$pdate = date_create_from_format('Y-m-d His', $tmp2.' 000000');
				if ($pdate){
					$date[] = $pdate;
					continue;
				}
			}
			if (strlen($tmp) >= 8){
				$tmp2 = substr($dateElem, 0, 8);
				$pdate = date_create_from_format('d-m-y His', $tmp2.' 000000');
				if ($pdate){
					$date[] = $pdate;
					continue;
				}
			}
			if (strlen($tmp) >= 6){
				$tmp2 = substr($dateElem, 0, 8);
				$pdate = date_create_from_format('j-n-y His', $tmp2.' 000000');
				if ($pdate){
					$date[] = $pdate;
					continue;
				}
			}
		}
		
		if(count($date) == 0){
			$return['p_link_date'] = false;
			$return['p_tag'] = 0;
		} else if(count($date) == 1){
			$return['p_link_date'] = [$date[0]->format('Y-m-d')];
			$return['p_tag'] = $committee.':'.$return['p_link_date'][0];
		} else if(count($date) == 2 && $date[0]->format('Y-m-d') == $date[1]->format('Y-m-d')) {
			$return['p_link_date'] = [$date[0]->format('Y-m-d')];
			$return['p_tag'] = $committee.':'.$return['p_link_date'][0];
		} else {
			$return['p_tag'] = '';
			foreach ($date as $pos => $d){
				$return['p_link_date'][] = $date[$pos]->format('Y-m-d');
				$return['p_tag'].= (($pos != 0)?'|':'').$committee.':'.$date[$pos]->format('Y-m-d');
			}
			$return['multiple'] = true;
		}
		return $return;
	}
	
	/**
	 * categorize and split raw resolution strings to array
	 * 
	 * @param string $resolution raw resolution text
	 * @param Protocol $p
	 * @return array parsed resolution [title, type_short, type_long, p_tag, text|raw]
	 */
	public static function parseResolution($resolution, $p, $overwriteType = NULL, $committee = NULL){
		$esolution = htmlspecialchars($resolution);
		$result = ['text' => $resolution];
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
			$tmpType = self::parseResolutionType($result['Titel']);
			$result['type_short'] = $tmpType['type_short'];
			$result['type_long'] = $tmpType['type_long'];
		}
		if ($result['type_long'] == 'Protokoll'){
			$tmpPtag = self::parseResolutionProtocolTag($result['Titel'], isset($p)? $p->committee : $committee);
			if (!$tmpPtag['p_link_date']){
				$p->parse_errors['f'][] = "<strong>Parse Error: Protokolldatum</strong> Dem folgenden Protokollbeschluss konnte kein Datum entnommen werden. Gesuchtes format: dd.mm.yy, dd-mm-yy, dd.mm.yyyy oder dd-mm-yyyy<br><i>{$result['Titel']}</i>";
			} else if(isset($tmpPtag['multiple'])) {
				$p->parse_errors['f'][] = "<strong>Parse Error: Protokolldatum</strong> Bitte nur einen Beschluss pro Protokoll.<br><i>{$result['Titel']}</i>";
			} else {
				$result['p_tag'] = $tmpPtag['p_tag'];
			}
		} else {
			$result['p_tag'] = NULL;
		}
		//check intern flag
		$result['id'] = NULL;
		$result['intern'] = ($result['type_long'] == 'Intern')? 1 : 0;
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
			$p->resolutions[$pos]['r_tag'] = "$legislatur/{$p->legislatur_week}-{$reso['type_short']}".str_pad($count[$reso['type_short']], 2, '0', STR_PAD_LEFT);
		}
	}
	
	/**
	 * 
	 * @param Protocol $p
	 * @param string $addDraftText
	 * @param string $nopreview
	 */
	public function parseProto($p, $addDraftText = false, $nopreview = false){
		prof_flag('parseProto_start');
		
		$isInternal = false;	// dont copy internal parts to public part
		$this->isLineError = false;	// found parsing error
		$lastTagClosed = true; 	// prevent duplicate closing tags and closing internal part before opening
		$writeUserText = 1;		// used to detect protocol head
		$alc = 0;			//additional line counter remember if script adds
		$rlc = 0;			//removed line counter
		
		//init preg_match results
		$pregFind = ['todo' => [], 'resolution' => [], 'fixme' => [], 'deleteme' => []];	// contains preg matches (todos, fixmes, resolutions)
		
		//only fill preview or output
		if (!$nopreview) $p->preview = self::generateDiffHeader();
		
		// parser main loop - loop throught $protocol lines
		// create internal <==> external diff
		foreach($p->text_a as $linenumber => $line){
			//detect protocol head to insert draft state + publishung user
			if ($writeUserText == 3) {
				if (!$nopreview){
					if ($addDraftText){
						$p->preview .= self::generateDiffCopiedChangedLine('====== ENTWURF - PROTOKOLL ======');
						$alc++;
					}
				} else {
					if ($addDraftText){
						$p->external .= '====== ENTWURF - PROTOKOLL ======'."\n";
						$alc++;
					}			
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
						if (in_array($single_tag, self::$ignoreTags)){
							$p->tags[$single_tag] = 0; // mark tag used
							continue; //but skip counting
						}	
						if (strlen($single_tag)>2 && substr($single_tag,0, 2) != 'no') {
							$p->tags[$single_tag] = isset($p->tags[$single_tag])? $p->tags[$single_tag] + 1 : 1;
							if ($single_tag == PROTO_INTERNAL_TAG){
								$isInternal = true;
								$changed = true;
							}
						} else {
							$p->tags[$single_tag] = (isset($p->tags[$single_tag]) && $p->tags[$single_tag] > 0)? $p->tags[$single_tag] - 1 : 0;
							if($single_tag == 'no'.PROTO_INTERNAL_TAG){
								$isInternal = false;
								$changed = true;
							}
						}
					}
				}
				if ($changed) { // internal/external state changed - test for errors
					$tmp_line = trim(str_replace($matches[0][0], '', $line));
					if ($tmp_line != ''){
						$this->isLineError = true;
						$this->lineError = "Please use a new line to seperate internal and external parts.";
						$p->parse_errors['f'][] = $this->lineError;
						if (!$nopreview) $p->preview .= self::generateDiffErrorLine($line);
						break;
					}
					if ($lastTagClosed == !$isInternal){ //test for duplicates
						$this->isLineError = true;
						$this->lineError = "Duplicate closing tag or closing before opening found.";
						$p->parse_errors['f'][] = $this->lineError;
						if (!$nopreview) $p->preview .= self::generateDiffErrorLine($line);
						break;
					} else {
						$lastTagClosed = !$isInternal;
					}
				}
			}
			if ($isInternal || $changed){ // mark changes on preview
				if (!$nopreview) $p->preview .= self::generateDiffRemovedLine($line);
				$rlc++;
			} else { // only copy public lines
				if (!$nopreview) $p->preview .= self::generateDiffCopiedLine($line);
				else $p->external .= "$line\n";
			}
			//remember line number: original protocol; preview; export
			//used in next two for loops
			$linekey = $linenumber.':'.($linenumber + $alc +1).':'.($linenumber + $alc +1 -$rlc).':';
			//detect fixme, todo, resolutions
			//maybe run this on whole text globally
			foreach(self::$regexFinder['no_multimatch'] as $key => $pattern){
				if (preg_match($pattern, $line)){
					$pregFind[$key][($isInternal)?'intern':'public'][$linekey.'0'] = trim($line, " \t\n\r\0\x0B*");
				}
			}
			//detect fixme, todo, resolutions
			//maybe run this on whole text globally
			foreach(self::$regexFinder['multimatch'] as $key => $pattern){
				$tmp_matches = preg_match_all($pattern, $line);
				if ($tmp_matches){
					$pregFind[$key][($isInternal)?'intern':'public'][$linekey.'1'] = [trim($line, " \t\n\r\0\x0B*"), $tmp_matches];
				}
			}
		}
		if ($this->isLineError == true){ // error handling: show error to user
			if (!$nopreview) $p->preview .= self::generateDiffErrorLine($this->lineError);
			else $p->external .= self::generateDiffErrorLine($this->lineError);
		}
		
		if (!$nopreview){
			//print table footer
			$p->preview .= self::generateDiffFooter();
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
		$tmp_todo = [
			'deleteme' => $pregFind['deleteme'],
			'fixme' => $pregFind['fixme'],
			'todo' => $pregFind['todo']
		];
		$p->todos = self::todo2linearArray($tmp_todo, $p->id);
		
		//add protocol numnber (sitzungnummer)
		if (isset($pregFind['sitzung']) && preg_match(self::$regexFinder['no_multimatch']['sitzung'], array_values($pregFind['sitzung']['public'])[0], $tmp_sitzung_matches) == 1){
			$p->protocol_number = $tmp_sitzung_matches[3]; 
		} else {
			$p->protocol_number = -1;
			$p->parse_errors['f'][] = "Sitzungsnummer konnte nicht erkannt werden.";
		}
		
		// object
		return $p;
		//TODO detect Legislatur
	}
	
	/**
	 * recreate todo array to linear array
	 * @param array $todos
	 * @return array linear todo array
	 */
	public static function todo2linearArray ($todos, $pid) {
		$result = [];
		foreach ($todos as $type => $todos2){
			foreach ($todos2 as $intern_indicator => $todos3){
				foreach ($todos3 as $lines => $todo){
					$lineInfo = explode(':', $lines);
					$result[] = [
						'id' => null,
						'on_protocol' => $pid,
						'user' => (preg_match(self::$personFinder, $todo[0], $matches))? $matches[3] : NULL,
						'done' => false,
						'text' => $todo[0],
						'type' => $type,
						'line' => $lineInfo[0],
						'hash' => md5($todo[0].$lineInfo[0].$type),
						'intern' => (($intern_indicator == 'intern')? 1:0)
					];
				}
			}
		}
		return $result;
	}
}

?>