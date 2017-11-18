<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 18.11.17
 * Time: 21:33
 */

include 'lib/Main.php';

$haupt = new Main();

echo $haupt::$inputpath . "<br />" .PHP_EOL;
$haupt->Main();

?>