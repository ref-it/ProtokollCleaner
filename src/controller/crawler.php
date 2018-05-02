<?php
/**
 * CONTROLLER Crawler Controller
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        controller
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (SYSBASE . '/framework/class._MotherController.php');
require_once (SYSBASE.'/framework/class.wikiClient.php');

class CrawlerController extends MotherController
{
	/**
	 * class constructor
	 * @param Database $db        	
	 * @param AuthHandler $auth        	
	 * @param Template $template        	
	 */
	public function __construct($db, $auth, $template)
	{
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * ACTION home
	 */
	public function home(){
		$this->t->setTitlePrefix('Crawler');
		$this->t->appendCssLink('logging.css', 'screen,projection');
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__);
		$this->t->printPageFooter();
	}
	
	private static function htmlLogLine($text, $extra_empty = false, $bold = false, $extra_tab_space = 0){
		if ($bold){ // add tab space before text
			$text = '<strong>'.$text.'</strong>';
		}
		if ($extra_tab_space > 0){ // add tab space before text
			$text = str_repeat('<span class="tab"></span>', $extra_tab_space).$text;
		}
		echo '<p class="logline"><i>'.$text.'</i></p>';
		if ($extra_empty) echo '<p class="logline"><i></i></p>';
	}
	
	private static $legislaturRegex = '/=(=)+( )*Legislatur\D*(\d+).*=(=)+/im';
	private static $weekRegex = '/.*(?<!ersti|ersti-|fest|fest |tags)Woche( +)(\d+).*/i';
	private static $date1Reg = '/(\d{1,2}(\.| )*(Januar|Februar|März|April|Mai|Juni|Juli|August|September|Oktober|November|Dezember)(\D){0,3}(\d{2,4}))/i';
	private static $date2Reg = '/(\d\d\d\d\D\d\d\D\d\d)/i';
	
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
							'Dezember' 	=> '12', 
							'.' 		=> '',
							' ' 		=> '-'];
	
	/**
	 * ACTION crawl legislatur numbers
	 */
	public function crawlLegislatur(){
		//permission - edit this to add add other committee
		$perm = 'stura';
		$this->t->setTitlePrefix('Legislatur Crawler');
		$this->t->appendCssLink('logging.css', 'screen,projection');
		$this->t->printPageHeader();
		echo '<h3>Crawler - Legislatur</h3>';
		echo '<div class="logging">';
		
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		self::htmlLogLine('Read from '. WIKI_URL.'/'.parent::$protomap[$perm][2] . '...', true);
		prof_flag('wiki request - resolutionlist');
		$rawlist = explode("\n",$x->getPage(parent::$protomap[$perm][2]));
		prof_flag('wiki request end');
		
		self::htmlLogLine('interpret data...');
		$currentLegislatur = 0;
		$currentlegiWeek = 0;
		$out = [];
		foreach ($rawlist as $line){
			$date = false;
			$line = trim($line);
			if (preg_match(self::$legislaturRegex, $line, $matches) == 1){
				self::htmlLogLine("Found legislatur: $matches[3]");
				$currentLegislatur = $matches[3];
			}
			if($currentLegislatur != 0){
				if (preg_match(self::$weekRegex, $line, $matches2) == 1){
					$currentlegiWeek = $matches2[2];
					//try to parse date
					$tmpline = trim(trim(trim(explode('vom', $line)[1]),'^'));
					if (preg_match(self::$date1Reg, $line, $matches3) == 1){
						$tmp_match = str_replace( array_keys(self::$monthReplaceMap), array_values(self::$monthReplaceMap), $matches3[1]);
						$date = date_create_from_format('d-m-Y', $tmp_match);
					}
					if (!$date && preg_match(self::$date2Reg, $line, $matches4) == 1){
						$date = date_create_from_format('Y-m-d', $matches4[1]);
					}
					if (!$date){
						self::htmlLogLine("Found week but cant interpret date: $line -> $currentlegiWeek -> $tmpline");
					}
					$out[$currentLegislatur][$date->format('Y-m-d')] = ['date' => $date, 'week' => $currentlegiWeek, 'key' => $date->format('Y-m-d')];
					
				}
			}
		}
		
		self::htmlLogLine('First loop complete.', true);
		self::htmlLogLine('Calculate min max dates...');
		$out2 = [];
		foreach($out as $legi => $dates){
			ksort($dates);
			reset($dates);
			$first = key($dates);
			end($dates);
			$last = key($dates);
			$out2[$legi] = [$first, $last];
			self::htmlLogLine("L: $legi [ $first <> $last ]");
		}
		self::htmlLogLine('Second loop complete.', true);
		
		self::htmlLogLine('Subtract 1 day from start date and use as end date for previous legislatur', true);
		self::htmlLogLine('Write to database.');
		$maxcount = count($out2);
		$counter = 0;
		$out2_keys = array_keys($out2);
		$written_lines = 0;
		foreach($out2 as $legi => $st_ed){
			$number = $legi;
			$start = $st_ed[0];
			$end = '';
			if ($counter + 1 < $maxcount){
				//get date from next entry and subtract 1 day
				$tmp_date = clone $out[$out2_keys[$counter+1]][$out2[$out2_keys[$counter+1]][0]]['date'];
				$tmp_date->sub(new DateInterval('P1D'));
				$end = $tmp_date->format('Y-m-d');
			} else { //current legislatur hasn't a next date -> add one year to start date
				$tmp_date = date_create_from_format('Y-m-d', $start);
				$tmp_date->add(new DateInterval('P1Y'));
				$end = $tmp_date->format('Y-m-d');
			}
			$affected = $this->db->createLegislatur(['number' => $number, 'start' => $start , 'end' => $end]);
			$counter ++;
			if ($affected) $written_lines++;
			self::htmlLogLine("L: $legi [ $start <> $end ]".((!$affected)?' -> key already exists':''));
		}
		self::htmlLogLine('Done. Written '.$written_lines.' Lines.', true);

		echo '</div>';
		$this->t->printPageFooter();
	}
	
	/**
	 * ACTION crawl resolutions and protocols to database
	 */
	public function crawlResoProto(){
		//permission - edit this to add add other committee
		$perm = 'stura';
		$this->t->setTitlePrefix('Protocol and Resolution Crawler');
		$this->t->appendCssLink('logging.css', 'screen,projection');
		$this->t->printPageHeader();
		echo '<h3>Crawler - Protokolle und Beschlüsse</h3>';
		echo '<div class="logging">';
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		// fetch protocols ------------------------------------
		self::htmlLogLine("Load protocols..."); // -------------------------
		prof_flag('wiki request - intern');
		$intern = $x->getPagelistAutoDepth(parent::$protomap[$perm][0]);
		prof_flag('wiki request end');
		$extern = [];
		if (parent::$protomap[$perm][0] != parent::$protomap[$perm][1]){
			prof_flag('wiki request - extern');
			$extern = $x->getPagelistAutoDepth(parent::$protomap[$perm][1]);
			prof_flag('wiki request end');
		}
		self::htmlLogLine("Done.", true);
		
		self::htmlLogLine("Sort Protocols."); // -------------------------
		prof_flag('Sorting...');
		$i_path_lng = strlen(parent::$protomap[$perm][0]) + 1;
		$e_path_lng = strlen(parent::$protomap[$perm][1]) + 1;
		$intern_and_extern = [];
		foreach ($intern as $k => $v){
			$name = substr($v, $i_path_lng);
			if (substr($name,0, 2)!='20') continue;
			$dt = substr($name, 0, 10);
			$intern_and_extern[$dt]['intern'] = $name;
		}
		foreach ($extern as $k => $v){
			$name = substr($v, $e_path_lng);
			if (substr($name,0, 2)!='20') continue;
			$dt = substr($name, 0, 10);
			$intern_and_extern[$dt]['extern'] = $name;
		}
		prof_flag('Done.', true);
		self::htmlLogLine("Done.", true);
		
		self::htmlLogLine("Fetch Committee..."); // -------------------------
		$committee_id = $this->db->getCreateCommitteeByName($perm)['id'];
		self::htmlLogLine("Done.", true);
		
		self::htmlLogLine("Fetch Resolutionlist"); // -------------------------
		prof_flag('wiki request - resolutionlist');
		$rawresolist = explode("\n",$x->getPage(parent::$protomap[$perm][2]));
		prof_flag('wiki request end');
		
		self::htmlLogLine('interpret data...');
		$currentLegislatur = 0;
		$currentlegiWeek = 0;
		$currentDate = '';
		$out = [];
		$line_handled = false;
		foreach ($rawresolist as $linenumber =>  $line){
			$line_handled = false;
			$date = false;
			$line = trim($line);
			if (preg_match(self::$legislaturRegex, $line, $matches) == 1){
				self::htmlLogLine("Found legislatur: $matches[3]");
				$currentLegislatur = $matches[3];
				$line_handled = true;
			}
			if(!$line_handled && $currentLegislatur != 0){
				if (preg_match(self::$weekRegex, $line, $matches2) == 1){
					$currentlegiWeek = $matches2[2];
					//try to parse date
					$tmpline = trim(trim(trim(explode('vom', $line)[1]),'^'));
					if (preg_match(self::$date1Reg, $line, $matches3) == 1){
						$tmp_match = str_replace( array_keys(self::$monthReplaceMap), array_values(self::$monthReplaceMap), $matches3[1]);
						$date = date_create_from_format('d-m-Y', $tmp_match);
					}
					if (!$date && preg_match(self::$date2Reg, $line, $matches4) == 1){
						$date = date_create_from_format('Y-m-d', $matches4[1]);
					}
					if (!$date){
						self::htmlLogLine("Found week but cant interpret date: $line -> $currentlegiWeek -> $tmpline");
					}
					$currentDate = $date->format('Y-m-d');
					$line_handled = true;
				}
			}
			if (!$line_handled //resolution lines
				&& $currentLegislatur != 0 
				&& $currentlegiWeek != ''
				&& $currentDate != ''){
				$line = trim(trim(trim($line),'|'));
				if ($line == '' || $line == '^ Nr ^ Typ ^ Beschluss ^') continue;
				$resoExp = explode('|', $line, 3);
				if (count($resoExp) != 3) {
					self::htmlLogLine("Unhandled line ($linenumber): $line", 0, 1);
				} else {
					foreach ($resoExp as $k => $v){
						//remove '<del>' tags from type keys, only accept them on text line
						$t = ($k != 2)? strip_tags($v) : $v;
						$resoExp[$k] = trim($t, " |\t\n\r\0\x0B");
					}
					self::htmlLogLine("Beschluss {$resoExp[0]}");
					// $resoExp[0] => Beschlussnummer
					// $resoExp[1] => Type
					// $resoExp[2] => Text
					
					//check protocol existence
					if (!isset($intern_and_extern[$currentDate])){
						self::htmlLogLine("Found Resolution, but no matching Protocol:", 0, 1);
						self::htmlLogLine("ResolutionDate: $currentDate", 0, 0, 1);
						self::htmlLogLine("Week: $currentlegiWeek", 0, 0, 1);
						self::htmlLogLine("Legislatur: $currentLegislatur", 0, 0, 1);
						self::htmlLogLine("Tag: {$resoExp[0]}", 0, 0, 1);
						self::htmlLogLine("Type: {$resoExp[1]}", 0, 0, 1);
						self::htmlLogLine("Text: {$resoExp[2]}", 0, 0, 1);
						$intern_and_extern[$currentDate]['noproto']=$currentDate;
					}
					//type short
					$type_short = strtoupper(substr($resoExp[1],0,1));
					
					//fix wrong spelled Long Type: 'Intern', 'Protokoll'
					if ($type_short == 'P') $resoExp[1] = 'Protokoll';
					if ($type_short == 'I') $resoExp[1] = 'Intern';
					
					//calculate resolution type by resolution text
					$tmp_type = protocolHelper::parseResolutionType($resoExp[2]);
					
					//parse date on protocols
					if ($tmp_type['type_long'] == 'Protokoll'){
						$tmpPtag = protocolHelper::parseResolutionProtocolTag($resoExp[2], $perm);
						if (!$tmpPtag['p_link_date']){
							self::htmlLogLine("Found Protocol Resolution, but no Date", 0, 1, 0);
							self::htmlLogLine("ResolutionDate: $currentDate", 0, 0, 1);
							self::htmlLogLine("Week: $currentlegiWeek", 0, 0, 1);
							self::htmlLogLine("Legislatur: $currentLegislatur", 0, 0, 1);
							self::htmlLogLine("Tag: {$resoExp[0]}", 0, 0, 1);
							self::htmlLogLine("Type: {$resoExp[1]}", 0, 0, 1);
							self::htmlLogLine("Text: {$resoExp[2]}", 0, 0, 1);
							
							$intern_and_extern[$currentDate]['reso'][] = [
								'r_tag' => $resoExp[0],
								'type_long' => $resoExp[1],
								'type_short' => $type_short,
								'text' => $resoExp[2],
								'noraw' => true,
								'nth_week' => $currentlegiWeek,
								'legi' => $currentLegislatur,
								'p_tag' => NULL,
								'p_link_date' => false,
							];
						} else {
							if(isset($tmpPtag['multiple'])) self::htmlLogLine("Protocol: multiple Dates", 0, 1, 1);
							//add protocol to protocoll list
							$intern_and_extern[$currentDate]['reso'][] = [
								'r_tag' => $resoExp[0],
								'type_long' => $resoExp[1],
								'type_short' => $type_short,
								'text' => $resoExp[2],
								'noraw' => true,
								'nth_week' => $currentlegiWeek,
								'legi' => $currentLegislatur,
								'p_tag' => $tmpPtag['p_tag'],
								'p_link_date' => $tmpPtag['p_link_date'],
							];
						}
					} else {
						//add protocol to protocoll list
						$intern_and_extern[$currentDate]['reso'][] = [
							'r_tag' => $resoExp[0],
							'type_long' => $resoExp[1],
							'type_short' => $type_short,
							'text' => $resoExp[2],
							'noraw' => true,
							'nth_week' => $currentlegiWeek,
							'legi' => $currentLegislatur,
							'p_tag' => NULL,
							'p_link_date' => false,
						];
					}
				}
			}
		}
		self::htmlLogLine('Matching complete.', true);
		
		self::htmlLogLine('Calculate Legislaturen...'); // -----------------------
		ksort($intern_and_extern);
		$legislatures = $this->db->getLegislaturen();
		$tmpLegis = array_shift($legislatures);
		$lastLegis = 0;
		$showedLegiswarning = false;
		foreach ($intern_and_extern as $key => $value){
			while($tmpLegis != NULL && $key > $tmpLegis['end']){
				$tmpLegis = array_shift($legislatures);
				if ($tmpLegis == NULL){
					$lastLegis = $tmpLegis['number'];
					self::htmlLogLine('Missing Legislaturnumber', 0, 1);
				}			
			}
			$intern_and_extern[$key]['legis'] = $lastLegis;
			if (!$showedLegiswarning 
				&& ($tmpLegis == NULL || $tmpLegis['start'] > $key)){
				self::htmlLogLine('No Legislaturnumber', 0, 1);
				$showedLegiswarning = true;
			}
		}
		self::htmlLogLine('Done.', true);
		
		self::htmlLogLine('Protokolle (' . count($intern_and_extern).')', 0, 1, 0); // -----------------------
		self::htmlLogLine('Write to database...', 0, 0, 0);
		krsort($intern_and_extern);
		$accepted = [];
		$esc_PROTO_IN = str_replace(':', '/', self::$protomap[$perm][0]);
		$esc_PROTO_OUT = str_replace(':', '/', self::$protomap[$perm][1]);
		
		$pcounter = 0;
		foreach ($intern_and_extern as $key => $value){
			//create protocols
			$proto = [
				'url' => (isset($value['intern']))? self::$protomap[$perm][0].':'.$value['intern'] : '',
				'name' => (isset($value['intern'])? $value['intern'] : (isset($value['extern'])? $value['extern'] : $value['noproto'])),
				'date' => $key,
				'agreed' => (isset($accepted[$key])? $accepted[$key] : ((isset($value['extern']))? 0 : NULL)),
				'gremium' => $committee_id,
				'legislatur' => $value['legis'],
				'draft_url' => NULL,
				'public_url' => (isset($value['extern']))?$value['extern']:NULL,
			];
			$proto['proto_id'] = $this->db->createProtocol($proto);
			
			//create resolutions
			if ($proto['proto_id'] != false && isset($value['reso'])){
				foreach ($value['reso'] as $r){
					$reso = [
						'on_protocol' 	=> $proto['proto_id'],
						'type_short' 	=> $r['type_short'],
						'type_long' 	=> $r['type_long'],
						'text' 			=> $r['text'],
						'p_tag' 		=> $r['p_tag'],
						'r_tag' 		=> $r['r_tag'],
						'intern' 		=> 0,
						'noraw' 		=> 1 
					];
					
					$reso['id'] = $this->db->createResolution($reso);
					
					//mark accepted resolutions 
					if ($r['p_link_date'] && count($r['p_link_date'])>0 ){
						foreach ($r['p_link_date'] as $date){
							$accepted[$date] = $reso['id'];
						}
					}
				}
			}
			$pcounter++;
		}
		self::htmlLogLine('Done.', 1, 0, 0);
		
		// ------------------------------------
		echo '</div>';
		$this->t->printPageFooter();
	}
	
	
}

?>