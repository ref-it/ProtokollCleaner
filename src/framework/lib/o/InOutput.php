<?php
/**
 * InOutput.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 17.02.18 22:28
 */


class InOutput
{
    static function ReadFile($Filepath) : Array
    {
        $result = array();
        if ($fl = fopen($Filepath, "r"))
        {
            while(!feof($fl)) {
                $result[] = fgets($fl);
            }
            fclose($fl);
        }
        return $result;
    }

    static function WriteTestWiki($name, $content = [])
    {
        $fileAsString = "";
        foreach ($content as $item) {
            $fileAsString = $fileAsString . PHP_EOL . $item;
        }
        require_once(SYSBASE . '/framework/class.wikiClient.php');
        $x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
        echo '<pre>';
        var_dump($x->putSpielwiese($name, $fileAsString));
        echo '</pre>';
    }
    static function WriteFile($FileName, $content) : bool
    {
    	if (Main::$DisableWrite) {
    	   return true;
        }
        try {
            if ($fl = fopen($FileName, "w+")) {
                foreach ($content as $line) {
                    fwrite($fl, $line);
                }
            }
            fclose($fl);
        }
        catch (Exception $e)
        {
            Useroutput::PrintHorizontalSeperator();
            Useroutput::PrintLineDebug($e);
            return false;
        }
        $check = true;
        do {
            if (strpos($FileName, "/")) {

            } else {

            }
        } while ();
        $FileName =
            self::WriteTestWiki($FileName, $content);
        return true;
    }

}

?>