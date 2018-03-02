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
		$this->t->appendCssLink('logging.css', 'screen,projection');
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__);
		$this->t->printPageFooter();
	}
	
	private static function htmlLogLine($text){
		echo '<p class="logline"><i>'.$text.'</i></p>';
	}
	
	/**
	 * ACTION crawl legislatur numbers
	 */
	public function crawlLegislatur(){
		//permission - edit this to add add other committee
		$perm = 'stura';
		$this->t->appendCssLink('logging.css', 'screen,projection');
		$this->t->printPageHeader();
		echo '<h3>Crawler - Legislatur</h3>';
		echo '<div class="logging">';
		
		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		self::htmlLogLine('Read from '. WIKI_URL.'/'.parent::$protomap[$perm][2] . '...');
		prof_flag('wiki request - resolutionlist');
		$rawlist = explode("\n",$x->getPage(parent::$protomap[$perm][2]));
		prof_flag('wiki request end');
		
		$legislaturRegex = '/=(=)+( )*Legislatur\D*(\d+).*=(=)+/im';
		$weekRegex = '/.*(?<!ersti|ersti-|fest|fest |tags)Woche( +)(\d+).*/i';
		$date1Reg = '/(\d{1,2}(\.| )*(Januar|Februar|März|April|Mai|Juni|Juli|August|September|Oktober|November|Dezember)(\D){0,3}(\d{2,4}))/i';
		$date2Reg = '/(\d\d\d\d\D\d\d\D\d\d)/i';
		
		self::htmlLogLine('');
		self::htmlLogLine('interpret data...');
		$currentLegislatur = 0;
		$currentlegiWeek = 0;
		$out = [];
		foreach ($rawlist as $line){
			$date = false;
			$line = trim($line);
			if (preg_match($legislaturRegex, $line, $matches) == 1){
				self::htmlLogLine("Found legislatur: $matches[3]");
				$currentLegislatur = $matches[3];
			}
			if($currentLegislatur != 0){
				if (preg_match($weekRegex, $line, $matches2) == 1){
					$currentlegiWeek = $matches2[2];
					//try to parse date
					$tmpline = trim(trim(trim(explode('vom', $line)[1]),'^'));
					if (preg_match($date1Reg, $line, $matches3) == 1){
						$tmp_match = str_replace([
							'Januar',
							'Februar',
							'März',
							'April',
							'Mai',
							'Juni',
							'Juli',
							'August',
							'September',
							'Oktober',
							'November',
							'Dezember', '.', ' '], [
								'01','02','03','04','05','06','07','08','09','10','11','12', '', '-'
							], $matches3[1]);
						$date = date_create_from_format('d-m-Y', $tmp_match);
					}
					if (!$date && preg_match($date2Reg, $line, $matches4) == 1){
						$date = date_create_from_format('Y-m-d', $matches4[1]);
					}
					if (!$date){
						self::htmlLogLine("Found week but cant interpret date: $line -> $currentlegiWeek -> $tmpline");
					}
					$out[$currentLegislatur][$date->format('Y-m-d')] = ['date' => $date, 'week' => $currentlegiWeek, 'key' => $date->format('Y-m-d')];
					
				}
			}
		}
		
		self::htmlLogLine('First loop complete.');
		self::htmlLogLine('');
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
		self::htmlLogLine('Second loop complete.');
		self::htmlLogLine('');
		
		self::htmlLogLine('Subtract 1 day from start date and use as end date for previous legislatur');
		self::htmlLogLine('');
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
			self::htmlLogLine("L: $legi [ $start <> $end ]");
			$affected = $this->db->createLegislatur(['number' => $number, 'start' => $start , 'end' => $end]);
			$counter ++;
			if ($affected) $written_lines++;
		}
		self::htmlLogLine('Done. Written '.$written_lines.' Lines.');
		self::htmlLogLine('');
		echo '</div>';
		
		$this->t->printPageFooter();
	}
}

?>