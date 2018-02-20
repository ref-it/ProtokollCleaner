<?php
/**
 * ShowFiles.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 20.02.18 20:15
 */

include 'Main.php';

$haupt = new Main();

echo "<p style='border-color: black; border-style: solid; border-width: 1px;'>" . PHP_EOL;
foreach (InOutput::ReadFile(Main::$PathToToDOList) as $line) {
    echo $line . "<br />" . PHP_EOL;
}
echo "</p><br /><br />" . PHP_EOL;

echo "<p style='border-color: black; border-style: solid; border-width: 1px;'>" . PHP_EOL;
foreach (InOutput::ReadFile(Main::$helperFilePath) as $line) {
    echo $line . "<br />" . PHP_EOL;
}
echo "</p><br /><br />" . PHP_EOL;
echo "<p style='border-color: black; border-style: solid; border-width: 1px;'>" . PHP_EOL;
foreach (InOutput::ReadFile(Main::$decissionList) as $line) {
    echo $line . "<br />" . PHP_EOL;
}
echo "</p><br /><br />" . PHP_EOL;
echo "<p style='border-color: black; border-style: solid; border-width: 1px;'>" . PHP_EOL;
foreach (InOutput::ReadFile(Main::$newDecissionList) as $line) {
    echo $line . "<br />" . PHP_EOL;
}
echo "</p><br /><br />" . PHP_EOL;