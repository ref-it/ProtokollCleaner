<?php
/**
 * DatabaseConnector.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 18.02.18 23:28
 */

class DatabaseConnector
{
    private $publishedDraft;
    private $publishedFinal;
    private $financialDecission;
    private $decissionList;
    private $Legislaturliste;
    private $cl;
    private $lsn;

    public function __construct() // or any other method
    {
        $this->publishedDraft = Array();
        $this->publishedFinal = Array();
        $this->financialDecission = Array();
        $this->decissionList = array();
        $this->Legislaturliste = array();
        self::readHelperFile();
    }

    private function readHelperFile()
    {
        $lines = InOutput::ReadFile(Main::$helperFilePath);
        foreach ($lines as $line) {
            $changedLine=str_replace(PHP_EOL, "", $line);
            if (substr($line, 0, 2) === "pd") {
                $this->publishedDraft[] = substr($changedLine, 2);
            } else if (substr($line, 0, 2) === "pf") {
                $this->publishedFinal[] = substr($changedLine, 2);
            } else if (substr($line, 0, 2) === "fd") {
                $this->financialDecission[] = substr($changedLine, 2);
            } else if (substr($line, 0, 2) === "dl") {
                $this->decissionList[] = substr($changedLine, 2);
            } else if (substr($line, 0, 2) === "ln") {
                $this->Legislaturliste[] = substr($changedLine, 2);
            } else if (substr($line, 0, 2) === "cl") {
                $this->cl = substr($changedLine, 2);
            }  else if (substr($line, 0, 2) === "ls") {
                $this->cl = substr($changedLine, 2);
            }
        }
    }

    public function getLastSitzungsnummer() : string
    {
        return $this->lsn;
    }
    public function getCurrentLegislatur() : string
    {
        return $this->cl;
    }
    public function getlegislatur($datumUS) : string
    {
        foreach ($this->Legislaturliste as $Date) {
            $begin = substr($Date, 0,10);
            $end = substr($Date, 10,10);
            $Legislatur = substr($Date, 20);
            settype($startdatum, Date::class);
            settype($enddatum, Date::class);
            settype($datum, Date::class);
            $startdatum = new Date(substr($begin,0,4), substr($begin, 5,2), substr($begin,7,2));
            $enddatum = new Date(substr($end,0,4), substr($end, 5,2), substr($end,7,2));
            $datum = new Date(substr($datumUS,0,4), substr($datumUS, 5,2), substr($datumUS,7,2));
            if (intval($datum->Year()) > intval($enddatum->Year()) )
            {
                continue;
            }
            if ((intval($datum->Month()) > intval($enddatum->Month()) and (intval($datum->Year()) === intval($enddatum->Year()))))
            {
                continue;
            }
            if (( intval($datum->Day()) > intval($enddatum->Day()) ) and (intval($datum->Month()) === intval($enddatum->Month()) and (intval($datum->Year()) === intval($enddatum->Year()))))
            {
                continue;
            }
            if (intval($datum->Year()) < intval($startdatum->Year()) )
            {
                continue;
            }
            if ((intval($datum->Month()) < intval($startdatum->Month()) and (intval($datum->Year()) === intval($startdatum->Year()))))
            {
                continue;
            }
            if (( intval($datum->Day()) < intval($startdatum->Day()) ) and (intval($datum->Month()) === intval($startdatum->Month()) and (intval($datum->Year()) === intval($startdatum->Year()))))
            {
                continue;
            }
            return $Legislatur;
        }
        return -1;
    }
    public function alreadyOnDecissionList($ProtokollName)
    {
        return in_array($ProtokollName, $this->decissionList);
    }
    public function knownDecissionFinancial($Decssion): bool
    {
        return in_array($Decssion, $this->financialDecission);
    }
    public function alreadyPublishedFinal($fn): bool
    {
        if (Main::$ignoreDBPublishedList)
        {
            return false;
        }
        return in_array($fn, $this->publishedFinal);
    }
    public function alreadyPublishedDraft($fn) : bool
    {
        if (Main::$ignoreDBPublishedList)
        {
            return false;
        }
        return in_array($fn, $this->publishedDraft);
    }
    public function newPublishedDraft($fn)
    {
        $this->publishedDraft[] = $fn;
        $this->writeHelperFile();
    }
    public function newPublishedFinal($fn)
    {
        $this->publishedFinal[] = $fn;
        $this->writeHelperFile();
    }
    public function newFinancialDecission($DecissionNumber)
    {
        $this->financialDecission[] = $DecissionNumber;
        $this->writeHelperFile();
    }
    public function addToDecissionList($ProtokollName)
    {
        $this->decissionList[] = $ProtokollName;
        $this->writeHelperFile();
    }
    public function removeFromDraft($fn)
    {
        $this->publishedDraft = array_diff($this->publishedDraft, array($fn));
        $this->writeHelperFile();
    }
    private function writeHelperFile()
    {
        $lines = Array();
        foreach ($this->publishedDraft as $line)
        {
            $lines[] = "pd" . $line . PHP_EOL;
        }
        foreach ($this->publishedFinal as $line)
        {
            $lines[] = "pf" . $line . PHP_EOL;
        }
        foreach ($this->financialDecission as $line)
        {
            $lines[] = "fd" . $line . PHP_EOL;
        }
        foreach ($this->decissionList as $line)
        {
            $lines[] = "dl" . $line . PHP_EOL;
        }
        foreach ($this->Legislaturliste as $line)
        {
            $lines[] = "ln" . $line . PHP_EOL;
        }
        $lines[] = "cl" . $this->cl . PHP_EOL;
        $lines[] = "ls" . $this->cl . PHP_EOL;
        InOutput::WriteFile(Main::$helperFilePath, $lines);
    }
    private function newLegislatur($Startdatum, $EndDatum, $Legislaturnummer)
    {
        $this->Legislaturliste[] = $Startdatum  . $EndDatum . $Legislaturnummer .PHP_EOL;
        $this->writeHelperFile();
    }
    public function setCurrentLegislatur($ln)
    {
        $this->cl = $ln;
    }
    public  function setNewSitzungsnummer($sn)
    {
        $this->lsn = $sn;
    }
}

?>