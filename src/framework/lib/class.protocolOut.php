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
    //write table header to stdout
    public static function generateHeader()
    {
    	return self::generateLegend(). "<div class='difftable'>\n".
     		"<div class='headline noselect'>\n".
     			"<span>Line</span>\n".
     			"<span>+</span>\n".
     			"<span>-</span>\n".
     			"<span>C</span>\n".
     			"<span>Content</span>\n".
     		"</div>\n";
    }
    //write table header to stdout
    public static function generateLegend()
    {
    	return "<h3>Protokollvorschau</h3>\n".
    		"<div class='protolegend'><div>\n".
    		"<span>Legende</span>\n".
    		'<div><span class="color border border-dark"></span><span class="symbol">+</span><span class="desc">Veröffentlichte Zeilen - Werden in generiertes Protokoll übernommen</span></div>'."\n".
    		'<div><span class="color border border-dark"></span><span class="symbol">-</span><span class="desc">Nicht öffentliche Zeilen - Werden nicht in generiertes Protokoll übernommen</span></div>'."\n".
    		'<div><span class="color border border-dark"></span><span class="symbol">C</span><span class="desc">Automatisch ergänzte Zeilen</span></div>'."\n".
    		"</div></div>\n";
    }
    //write removed protocol line (red)
    public static function generateRemovedLine($line)
    {
        return "<div class='line removed'>\n".
        	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
        "</div>\n";
    }
    //write normal copied protocol line (white)
    public static function generateCopiedLine($line)
    {
    	return "<div class='line normal'>\n".
	    	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    	"</div>\n";
    }
    //write error protocol line (orange)
    public static function generateErrorLine($line)
    {
    	return "<div class='line error'>\n".
    		'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    		"</div>\n";
    }
    //write changed protocol line (gray)
    public static function generateCopiedChangedLine($line)
    {
    	return "<div class='line changed'>\n".
	    	'<span></span><span></span><span></span><span></span><span>'.htmlspecialchars($line)."</span>\n".
    	"</div>\n";
    }
    //write table footer to stdout
    public static function generateFooter()
    {
        return "</div>\n";
    }
}