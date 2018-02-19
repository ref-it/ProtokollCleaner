<?php
/**
 * DecissionList.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 19.02.18 01:06
 */

class DecissionList
{
    private $DecissioList;
    private $newDecissions;
    public function __construct() // or any other method
    {
        $this->newDecissions= array();
        $this->DecissioList = Array();
        $this->DecissioList = InOutput::ReadFile(Main::$newDecissionList);
    }
    private function addDecissions($fn, $SitzungsNumer)
    {
        $result = array();
        foreach ($this->DecissioList as $line)
        {
            $result[] = $line;
        }
        $result[] = "^ Woche ". $SitzungsNumer . " vom [[" .Main::$restDecissionListTitel . $fn . "]]   ^^^" . PHP_EOL;
        foreach ($this->newDecissions as $line2)
        {
            $result[] = $line2;
        }
        InOutput::WriteFile(Main::$newDecissionList, $result);
    }
    private function crawlDecission($Protokoll, $legislatur, $Sitzungsnummer)
    {
        $financialDecissionNumberF = 1;
        $financialDecissionNumberH = 1;
        $DecissionNumber = 1;
        foreach ($Protokoll as $line)
        {
            if (strpos($line, "template>:vorlagen:stimmen") ===false)
            {
                continue;
            }
            if ((strpos($line, "beschließt") !== false) and  (strpos($line, "Protokoll") !== false ) and (strpos($line, "Sitzung") !== false ) and (strpos($line, "angenommen") !== false )) {
                $addedLine="| " .$legislatur."/".$Sitzungsnummer."-".$DecissionNumber . " | Protokoll | ";
                $text = substr($line,strpos($line,"=") +1 );
                $text = substr($text, 0, strpos($text, "|"));
                $addedLine = $addedLine . $text . "|";
                $this->newDecissions[] = $addedLine . PHP_EOL;
                $DecissionNumber = $DecissionNumber + 1;
            }
            else if ((strpos($line, "beschließt") !== false) and  (strpos($line, "Haushaltsverantwortliche") !== false ) and (strpos($line, "Budget") !== false )) {
                $addedLine="| " .$legislatur."/".$Sitzungsnummer."-H".$financialDecissionNumberH . " | Finanzen | ";
                $text = substr($line,strpos($line,"=") +1 );
                $text = substr($text, 0, strpos($text, "|"));
                $addedLine = $addedLine . $text . "|";
                $this->newDecissions[] = $addedLine . PHP_EOL;
                $financialDecissionNumberH = $financialDecissionNumberH + 1;
            }
            else if ((strpos($line, "beschließt") !== false) and  (strpos($line, "angenommen") !== false ) and (strpos($line, "Budget") !== false )) {
                $addedLine="| " .$legislatur."/".$Sitzungsnummer."-F".$financialDecissionNumberF . " | Finanzen | ";
                $text = substr($line,strpos($line,"=") +1 );
                $text = substr($text, 0, strpos($text, "|"));
                $addedLine = $addedLine . $text . " |";
                $this->newDecissions[] = $addedLine . PHP_EOL;
                $financialDecissionNumberF = $financialDecissionNumberF + 1;
            }
        }
    }
    private function crawlSitzungsnummer($Protokoll) :string
    {
        foreach ($Protokoll as $line )
        {
            if(strpos($line, "======") !== false)
            {
                $result = substr($line, strpos($line, "======") + 7);
                $result = substr($result, 0, strpos($result, '.'));
                $result = str_replace(" ", "", $result);
                if (strlen($result) === 1)
                {
                    $result = "0" . $result;
                }
                return $result;
            }
        }
        return -1;
    }
    public function processProtokoll($Protokoll, $Legislatur, $fn)
    {
        $SitzungsNummer = $this->crawlSitzungsnummer($Protokoll);
        $this->crawlDecission($Protokoll, $Legislatur, $SitzungsNummer);
        $this->addDecissions($fn, $SitzungsNummer);
    }
}