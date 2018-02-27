<?php
/**
 * create html strings for protocols
 * checks open and closing tags
 * protocolOut.php (old: VisualCopyEmulator.php)
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 18.02.18 18:02
 */

class protocolOut
{
	// Protocol Internal <--> Public Diff =========================================================

    /**
     * return div protocol diff table header
     * @return string
     */
    public static function printDiffHeader()
    {
    	return self::printDiffLegend(). "<div class='difftable'>\n".
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
    public static function printDiffLegend()
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
    public static function printDiffRemovedLine($line)
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
    public static function printDiffCopiedLine($line)
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
    public static function printDiffErrorLine($line)
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
    public static function printDiffCopiedChangedLine($line)
    {
    	return "<div class='line changed'>\n".
	    	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    	"</div>\n";
    }
    
    /**
     * return div protocol diff table footer
     * @return string
     */
    public static function printDiffFooter()
    {
        return "</div>\n";
    }
}