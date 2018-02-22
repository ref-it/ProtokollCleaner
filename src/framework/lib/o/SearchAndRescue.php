<?php
/**
 * SearchAndRescue.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 21.02.18 17:27
 */

class SearchAndRescue
{
    private static $Replacements = [["|", "oder"], ["@", "[at]"], [PHP_EOL, ""]];

    private function __construct()
    {
    }

    public static function SearchAndReplace($String = ""): String
    {
        $result = $String;
        if (Main::$debug) {
            var_dump(SearchAndRescue::$Replacements);
        }
        foreach (SearchAndRescue::$Replacements as $Replacement) {
            if (Main::$debug) {
                echo '<pre>';
                var_dump($Replacement);
                echo '</pre>';
            }
            $check = true;
            do {
                if (strpos($result, $Replacement[0]) !== false) {
                    $result = substr($result, 0, strpos($result, $Replacement[0])) . $Replacement[1] . substr($result, strpos($result, $Replacement[0]) + 1);
                } else {
                    $check = false;
                }
            } while ($check);
        }
        return $result;
    }
}