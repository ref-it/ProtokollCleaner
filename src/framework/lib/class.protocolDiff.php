<?php
/**
 * search for internal part in protocolls
 * checks open and closing tags
 * protocolDiff.php (old: VisualCopyEmulator.php)
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 18.02.18 18:02
 */

class protocolDiff
{
    //write table header to stdout
    public static function generateHeader()
    {
    	return "<div class='difftable'>\n".
     		"<div class='headline noselect'>\n".
     			"<span>Line</span>\n".
     			"<span>+</span>\n".
     			"<span>-</span>\n".
     			"<span>C</span>\n".
     			"<span>Content</span>\n".
     		"</div>\n";
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