<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 17.02.18
 * Time: 22:21
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