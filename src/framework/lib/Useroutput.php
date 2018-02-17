<?php
/**
 * Useroutput.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 17.02.18 22:21
 */


class Useroutput
{
    static function PrintLine($output)
    {
        echo $output . "<br />" . PHP_EOL;
    }

    static function PrintLineDebug($output)
    {
        echo $output . "<br />" . PHP_EOL;
    }
    static function PrintHorizontalSeperator()
    {
        echo "<br /><hr /><br />" . PHP_EOL;
    }
}

?>