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

    static function WriteFile($FileName, $content) : bool
    {
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
        return true;
    }

}

?>