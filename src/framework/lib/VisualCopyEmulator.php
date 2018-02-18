<?php
/**
 * VisualCopyEmulator.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 18.02.18 18:02
 */

class VisualCopyEmulator
{
    public static function generateDiffTable($Protokoll, $check)
    {
        $ln = 0;
        echo self::generateHeader();
        $OffRec = false;
        $countInTag = 0;
        $countOutTag = 0;
        foreach ($Protokoll as $line)
        {
            $ln = $ln +1 ;
            if (strpos($line, "tag>" . Main::$starttag) !== false)
            {
                $countInTag = $countInTag + 1;
            }
            if (strpos($line, "tag>" . Main::$endtag) !== false)
            {
                if ($countInTag === 0)
                {
                    echo "<p>Warning Endtag vor Anfangstag</p><br />" .PHP_EOL;
                }
                if ($countInTag === $countOutTag)
                {
                    echo "<p>Warning Endtag vor Anfangstag</p><br />" .PHP_EOL;
                }
                $countOutTag = $countOutTag + 1;
            }
            if(!$OffRec and strpos($line, "tag>" . Main::$starttag) !== false) {
                $OffRec=true;
                echo self::generateRemovedLine($line, $ln);
                continue;
            }
            if(!$OffRec)
            {
                if(strpos($line, "======") !== false and !$check)
                {
                    $firstpart = substr($line, strpos($line, "======"), 6 );
                    $secondpart = substr($line, strpos($line, "======") + 6, strlen($line) -1 );
                    $newTitel = $firstpart . " Entwurf:" . $secondpart;
                    echo self::generateCopiedChangedLine($newTitel, $ln);
                }
                else {
                    echo self::generateCopiedLine($line, $ln);
                }
                continue;
            }
            if($OffRec and strpos($line, "tag>" . Main::$endtag) !== false) {
                $OffRec=false;
            }
            echo self::generateRemovedLine($line, $ln);
        }
        echo self::generateFooter();
    }
    private static function generateHeader() :string
    {
        $head="<table style='border-collapse: collapse; border-color: black; border-style: solid; border-width: 1px; text-align: center'>".PHP_EOL;
        $head=$head."<tr>".PHP_EOL;
        $head=$head."<th style='width: auto'>ln</th>".PHP_EOL;
        $head=$head."<th style='width: 2em'>+</th>".PHP_EOL;
        $head=$head."<th style='width: 2em'>-</th>".PHP_EOL;
        $head=$head."<th style='width: 2em'></th>".PHP_EOL;
        $head=$head."<th style='width: auto'></th>".PHP_EOL;
        $head=$head."</tr>".PHP_EOL;
        return $head;
    }
    private static function generateRemovedLine($line, $ln) :string
    {
        $lineresult = "<tr style='background-color: ". Main::$removedLineColor .";'>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none; '>".strval($ln)."</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'></td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>-</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'></td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; text-align: right; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>".$line."</td>".PHP_EOL;
        $lineresult = $lineresult."</tr>".PHP_EOL;
        return $lineresult;
    }
    private static function generateCopiedLine($line, $ln) :string
    {
        $lineresult = "<tr style='background-color: ". Main::$copiedLineColor .";'>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>".strval($ln)."</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>+</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'></td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'></td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; text-align: left; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>".$line."</td>".PHP_EOL;
        $lineresult = $lineresult."</tr>".PHP_EOL;
        return $lineresult;
    }
    private static function generateCopiedChangedLine($line, $ln) :string
    {
        $lineresult = "<tr style='background-color: ". Main::$copiedEditedLineColor .";'>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>".strval($ln)."</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>+</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'></td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>C</td>".PHP_EOL;
        $lineresult = $lineresult."<td style='border-width: 1px; text-align: center; border-style: solid; border-left-color: black; border-right-color: black; border-top: none; border-bottom: none;'>".$line."</td>".PHP_EOL;
        $lineresult = $lineresult."</tr>".PHP_EOL;
        return $lineresult;
    }
    private static function generateFooter() : string
    {
        $footer="</table>".PHP_EOL;
        return $footer;
    }
}