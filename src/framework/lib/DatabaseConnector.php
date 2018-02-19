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

    public function __construct() // or any other method
    {
        $this->publishedDraft = Array();
        $this->publishedFinal = Array();
        $this->financialDecission = Array();
        $this->decissionList = array();
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
            }
        }
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
        InOutput::WriteFile(Main::$helperFilePath, $lines);
    }
}

?>