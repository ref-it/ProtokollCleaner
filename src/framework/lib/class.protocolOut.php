<?php
/**
 * create html strings for protocols
 * 
 * protocolOut.php (old: VisualCopyEmulator.php)
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        lib
 * @author 	Martin S.
 * @author 	michael g
 * @author 	Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @since 	18.02.18 18:02
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

class protocolOut
{
	/**
	 * contains constant PROTOMAP
	 * @var array
	 */
	private static $protomap = PROTOMAP;
	
	// Protocol Internal <--> Public Diff =========================================================

    /**
     * return div protocol diff table header
     * @return string
     */
    protected static function generateDiffHeader()
    {
    	return self::generateDiffLegend(). "<div class='difftable'>\n".
     		"<div class='headline noselect'>\n".
     			"<span>Line</span>\n".
     			"<span>+</span>\n".
     			"<span>-</span>\n".
     			"<span>C</span>\n".
     			"<span>Content</span>\n".
     		"</div>\n";
    }

    /**
     * return protocol diff table legend
     * @return string
     */
    protected static function generateDiffLegend()
    {
    	return "<h3>Protokollvorschau</h3>\n".
    		"<div class='protolegend'><div>\n".
    		"<span>Legende</span>\n".
    		'<div><span class="color border border-dark"></span><span class="symbol">+</span><span class="desc">Veröffentlichte Zeilen - Werden in generiertes Protokoll übernommen</span></div>'."\n".
    		'<div><span class="color border border-dark"></span><span class="symbol">-</span><span class="desc">Nicht öffentliche Zeilen - Werden nicht in generiertes Protokoll übernommen</span></div>'."\n".
    		'<div><span class="color border border-dark"></span><span class="symbol">C</span><span class="desc">Automatisch ergänzte Zeilen</span></div>'."\n".
    		"</div></div>\n";
    }
  
    /**
     * return removed protocol line (red) (diff table)
     * @param string $line
     * @return string
     */
    protected static function generateDiffRemovedLine($line)
    {
        return "<div class='line removed'>\n".
        	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
        "</div>\n";
    }
    
    /**
     * return normal cloned protocol line (white) (diff table)
     * @param string $line
     * @return string
     */
    protected static function generateDiffCopiedLine($line)
    {
    	return "<div class='line normal'>\n".
	    	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    	"</div>\n";
    }
    
    /**
     * return error protocol line (orange) (diff table)
     * @param string $line
     * @return string
     */
    protected static function generateDiffErrorLine($line)
    {
    	return "<div class='line error'>\n".
    		'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    		"</div>\n";
    }
    
    /**
     * return changed protocol line (gray) (diff table)
     * @param string $line
     * @return string
     */
    protected static function generateDiffCopiedChangedLine($line)
    {
    	return "<div class='line changed'>\n".
	    	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    	"</div>\n";
    }
    
    /**
     * return div protocol diff table footer
     * @return string
     */
    protected static function generateDiffFooter()
    {
        return "</div>\n";
    }
    
    // Protocol echo/return Functions =========================================================
    
    /**
     * echo protocol status in html form
     * @param Protocol $p Protocol object
     * @param boolean $includeUrls call printProtoLinks automatically
     */
	public static function printProtoStatus($p, $includeUrls = true){
		echo '<div class="protostatus">';
		echo '<div class="general">';
		echo '<span class="committee"><span>Gremium:</span><span>'.$p->committee.'</span></span>';
		echo '<span class="date"><span>Protokoll vom:</span><span data-name="'.$p->name.'">'.$p->date->format('d.m.Y').'</span></span>';
		echo '<span class="state"><span>Status:</span><span>'.
			(($p->id == NULL)? 'Nicht öffentlich': 
			(($p->draft_url!=NULL)?'Entwurf öffentlicht':
			(($p->public_url!=NULL)?'Veröffentlicht':'Unbekannt'))).'</span></span>';
		echo '<span class="legislatur"><span>Legislatur:</span><span>'.
			 '<div class="fa fa-info css-tooltip mr-1 btn btn-outline-primary" tabindex="0"><span class="tooltiptext">Sollte dieser Wert nicht stimmen, informiert bitte den Konsul, oder Referat-IT um diesen dauerhaft zu aktualisieren.</span></div>'
			.'<button type="button" class="btn btn-outline-primary sub">-</button><span>'.$p->legislatur.'</span><button type="button" class="add btn btn-outline-primary">+</button></span></span>';
		echo '<span class="sitzung"><span>Legislatur-Woche:</span><span>'.$p->legislatur_week.'</span></span>';
		echo '<span class="sitzung"><span>Sitzung:</span><span>'.$p->protocol_number.'</span></span>';
		echo '<span class="resolutions"><span>Angenommene Beschlüsse:</span><span>'.count($p->resolutions).'</span></span>';
		
		echo '<span class="todo"><span>Todo:</span><span>'.(
			((isset($p->todos['todo']['public']))? count($p->todos['todo']['public']): 0)
			+((isset($p->todos['todo']['intern']))? count($p->todos['todo']['intern']): 0)
		).'</span></span>';
		echo '<span class="fixme"><span>FixMe:</span><span>'.(
			((isset($p->todos['fixme']['public']))? count($p->todos['fixme']['public']): 0)
			+((isset($p->todos['fixme']['intern']))? count($p->todos['fixme']['intern']): 0)
		).'</span></span>';
		echo '<span class="deleteme"><span>DeleteMe:</span><span>'.(
			((isset($p->todos['deleteme']['public']))? count($p->todos['deleteme']['public']): 0)
			+((isset($p->todos['deleteme']['intern']))? count($p->todos['deleteme']['intern']): 0)
		).'</span></span>';
		if ($includeUrls) self::printProtoLinks($p);
		echo '</div></div>';
	}
    
    /**
     * echo protocol links to wiki page in html form
     * @param Protocol $p
     */
    public static function printProtoLinks($p){
    	echo '<div class="protolinks">';
    	echo '<a class="btn btn-primary mr-1 reload" href="">Reload</a>';
    	if (!$p->public_url){
    		echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][0]).'/'.$p->name.'?do=edit" target="_blank">Edit Protocol</a>';
    	} else {
    		echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][0]).'/'.$p->name.'" target="_blank">View Intern</a>';
    	}
    	if ($p->draft_url){
    		echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][1]).'/'.$p->name.'" target="_blank">View Draft</a>';
    	}
    	if ($p->public_url){
    		echo '<a class="btn btn-primary mr-1" href="'.WIKI_URL.'/'.str_replace(':', '/', self::$protomap[$p->committee][1]).'/'.$p->name.'" target="_blank">View Public</a>';
    	} else {
    		echo '<button class="btn btn-danger mr-1 commit" type="button">'.(($p->agreed_on === NULL)?'Entwurf ': '' ).'Veröffentlichen</button>';
    	}
    	echo '</div>';
    }
    
    /**
     * echo protocol tag errors in html form
     * @param Protocol $p Protocol object
     */
    public static function createProtoTagErrors($p){
    	foreach($p->tags as $tag => $state){
    		if ($state == 0){
    			continue;
    		}
    		if ($tag == 'old'){
    			$p->parse_errors['f'][] = 'Nicht-Öffentlicher Teil wurde nicht geschlossen.';
    		} else {
    			$e = "Der Tag '$tag' wurde häufiger ";
    			if ($state > 0){
    				$e.= 'geöffnet als geschlossen.';
    			} else {
    				$e.= 'geschlossen als geöffnet.';
    			}
    			$p->parse_errors['n'][] = $e;
    		}
    	}
    }
    
    /**
     * echo protocol (parse) errors in html form
     * @param Protocol $p Protocol object
     */
    public static function printProtoParseErrors($p){
    	$opened = false;
    	if (isset($p->parse_errors['f'])) foreach($p->parse_errors['f'] as $err){ //fatal errors
    		if (!$opened){
    			echo '<div class="error parseerrors"><h3>Fehler</h3>';
    			$opened = true;
    		}
    		echo '<div class="perror fatal alert alert-danger border-danger">';
    		echo $err;
    		echo '</div>';
    	}
    	if (isset($p->parse_errors['n'])) foreach($p->parse_errors['n'] as $err){ //all other errors (n)ormal
    		if (!$opened){
    			echo '<div class="error parseerrors"><h3>Fehler</h3>';
    			$opened = true;
    		}
    		echo '<div class="perror alert alert-danger">';
    		echo $err;
    		echo '</div>';
    	}
    	if ($opened) {
    		echo  '</div>';
    	}
    }
    
    /**
     * echo protocol resolutions in html form
     * @param Protocol $p Protocol object
     */
    public static function printResolutions($p){
    	$opened = false;
    	foreach($p->resolutions as $pos => $reso){
    		if (!$opened){
    			echo '<div class="resolutionlist"><h3>Beschlüsse</h3>';
    			$opened = true;
    		}
    		echo '<div class="resolution alert alert-info">';
    		echo "<strong class='fixedwidth'>[{$reso['r_tag']}]</strong> {$reso['Titel']}";
    		echo '<input class="resotoggle" id="reso_toggle_'.$pos.'" type="checkbox" value="1">';
    		echo '<label tabindex="0" class="label resotoggle btn btn-outline-info" for="reso_toggle_'.$pos.'"></label>';
    		echo '<div class="togglebox" tabindex="-1">';
    		if (isset($reso['Ja'])) echo "<span class='yes'>Ja: {$reso['Ja']}</span>";
    		if (isset($reso['Nein'])) echo "<span class='no'>Nein: {$reso['Nein']}</span>";
    		if (isset($reso['Enthaltungen'])) echo "<span class='abstention'>Enthaltungen: {$reso['Enthaltungen']}</span>";
    		echo "<span class='result'>Beschluss: {$reso['Beschluss']}</span>";
    		if (isset($reso['p_tag'])){
    			if ($reso['p_tag']){
    				echo "<span class='ptag'>Protokoll: {$reso['p_tag']}</span>";
    			} else {
    				echo "<span class='ptag'>Protokoll: PARSE ERROR</span>";
    			}
    		}
    		echo "<span class='category'>Kategorie: {$reso['type_long']}</span>";
    		echo '</div></div>';
    	}
    	if ($opened) {
    		echo '</div>';
    	}
    }
    
    /**
     * echo protocol attachements in html form
     * @param Protocol $p Protocol object
     */
    public static function printAttachements($p){
    	$opened = false;
    	if (is_array($p->attachements))
    		foreach($p->attachements as $pos => $attach){
	    		if (!$opened){
	    			echo '<div class="attachlist"><h3>Anhänge</h3>';
	    			echo '<p><i>Alle hier angehakten Dateien werden automatisch mit veröffentlicht.</i></p>';
	    			echo '<div class="attachementlist alert alert-info">';
	    			$opened = true;
	    		}
	    		echo '<div class="line"><input type="checkbox" value="1" id="attach_check_'.$pos.'" checked>';
	    		$split = explode(':', $attach);
	    		echo '<label class="resolution noselect" for="attach_check_'.$pos.'"><span>'.end($split).'</span>';
	    		echo '<a href="'.WIKI_URL.'/'.str_replace(':', '/', $attach).'" target="_blank">';
	    		echo 'Öffnen';
	    		echo '</a></label></div>';
	    	}
    	if ($opened) {
    		echo '</div></div>';
    	}
    }

    /**
     * echos todo entries
     * @param Protocol $p
     * @param array $headlineMap maps todo['type'] to an headline
     * @param array $print only print todos of types in this array
     */
    public static function printTodoElements($p, $headlineMap = ['todo' => 'Todo', 'fixme' => 'FixMe', 'deleteme' => 'DeleteMe'], $print = ['todo', 'fixme', 'deleteme']){
    	$out = [];
    	foreach ($p->todos as $todo) {
    		$out[$todo['type']][] = '<div class="line '.$todo['type'].(($todo['intern'])?' intern':'').' alert alert-warning">'.
     			(($todo['intern'])?'<strong>[Intern]</strong> ':'').
     			preg_replace('/('.$todo['type'].')/i', '<span class="highlight">$1</span>', $todo['text']).
    			'</div>';
    	}
    	foreach($out as $type => $texts){
    		if (!in_array($type, $print)) continue;
    		echo '<div class="'.$type.'list"><h3>'.$headlineMap[$type].'</h3>';
    		foreach ($texts as $html) echo $html;
    		echo '</div>';
    	}
    }
}