<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 18.10.17
 * Time: 09:54
 */

class Main
{
    public static $inputpath; //intern part of the Wiki, where the files which will be cleaned are
    public static $outputpath; //public part of the Wiki, where the cleaned files will be saved
    public static $decissionList; //List off StuRa Decissions
    public static $helperFilePath; //List off StuRa Decissions
    public static $starttag; //start tag of cleaning area
    public static $endtag; //end tag of cleaning area
    public static $debug; //all as Text on Browser
    public static $onlyNew; //only new financial decissions
    public static $postData; //set to true if you want to post data to another website
    public static $PostUrl; //destination for Posting of financial decission list
    public static $startMonth;    //Day,
    public static $startYear;  //Month and
    public static $startday;    //Year of First protokoll which will be cleaned

    //Arbeitsvariablen
    public static $financialResolution = array();
    private $files;
    private $knownDecissions;


    public function __construct() // or any other method
    {
        $switch = false;
        $listFilesCurrentDir = scandir(getcwd());
        foreach ($listFilesCurrentDir as $file)
        {
            if ($file === "config.php")
            {
                include 'config.php';
                $switch = true;
                break;
            }
        }
        if ($switch === false) { //just for example parsing in files
            Main::$inputpath  = getcwd() . "/examples/intern/"; //intern part of the Wiki, where the files which will be cleaned are
            Main::$outputpath = getcwd() . "/examples/extern/"; //public part of the Wiki, where the cleaned files will be saved
            Main::$decissionList = getcwd() . "/examples/beschluesse.txt"; //List off StuRa Decissions
            Main::$helperFilePath = getcwd() . "/examples/help.txt"; //List off StuRa Decissions
            Main::$starttag   = "intern"; //start tag of cleaning area
            Main::$endtag     = "nointern"; //end tag of cleaning area
            Main::$debug = true ; //all as Text on Browser
            Main::$onlyNew = false; //only new financial decissions
            Main::$postData = false; //set to true if you want to post data to another website
            Main::$PostUrl = "http://localhost"; //destination for Posting of financial decission list
            Main::$startMonth = 01;    //Day,
            Main::$startYear  = 2016;  //Month and
            Main::$startday   = 01;    //Y
        }
        if (Main::$debug)
        {
            if ($switch == false) {
                echo "Die Reserve-Config wurde genutzt. <br />" . PHP_EOL;
            }
            else
            {
                echo "Die Config wurde genutzt. <br />" . PHP_EOL;
            }
        }
    }

    public function getAllFiles()
    {
        $this->knownDecissions = array();
        $this->files = array();
        $alledateien = scandir(Main::$inputpath); //Ordner "files" auslesen
        foreach ($alledateien as $datei) { // Ausgabeschleife
            $length = strlen($datei);
            if((substr($datei, 0,1) == ".") or (substr($datei, $length - 4, 4) != ".txt") or ($length !== 14) )
            {
                continue;
            }
            $Date = $this->getDateFromFileName($datei);
            if(intval($Date->Year()) < Main::$startYear)
            {
                continue;
            }
            if((intval($Date->Year()) === Main::$startYear) and (intval($Date->Month()) < Main::$startMonth))
            {
                continue;
            }
            if((intval($Date->Year()) === Main::$startYear) and (intval($Date->Month()) === Main::$startMonth) and (intval($Date->Day()) < Main::$startday))
            {
                continue;
            }
            $file = new File($Date, $datei);
            $fn = Main::$outputpath . "/" . $file->getOutputFilename();
            $check = $this->checkApproved($file->getgermanDate());
            if($check)
            {
                echo "Published as Final: ";
            }
            else
            {
                echo "Published as Draft: ";
            }
            $this->copy($file->getFilename(), $fn, $check);
            $this->files[] = $file;
        }
        echo "<br /><br /><br />" . PHP_EOL;
        $this->readAlreadyKnownFinancialDecissions();
        $this->exportFinancial();
        if (Main::$postData) {
            $this->sendData();
        }
        $this->writeHelperFile();
    }
    function copy($fileName, $fn, $check)
    {
        $OffRec=false;
        $lines = array();
        if ($fl = fopen($fileName, "r")) {
            while(!feof($fl)) {
                $line = fgets($fl);
                # do same stuff with the $line
                if(!$OffRec and strpos($line, "tag>" . Main::$starttag) !== false) {
                    $OffRec=true;
                    continue;
                }
                if(!$OffRec)
                {
                    if(strpos($line, "======") !== false and !$check)
                    {
                        $firstpart = substr($line, strpos($line, "======"), 6 );
                        $secondpart = substr($line, strpos($line, "======") + 6, strlen($line) -1 );
                        $lines[] = $firstpart . " Entwurf:" . $secondpart;
                    }
                    else {
                        $lines[] = $line;
                    }
                    continue;
                }
                if($OffRec and strpos($line, "tag>" . Main::$endtag) !== false) {
                    $OffRec=false;
                }

            }
            fclose($fl);
        }
        if($fl = fopen($fn, "w+")) {
            foreach ($lines as $line) {
                fwrite($fl, $line);
            }
        }
        fclose($fl);
        echo substr($fileName, strlen($fileName)-14, strlen($fileName) -1) . "<br />" . PHP_EOL;
    }

    function getDateFromFileName($Filename)
    {
        $length = strlen($Filename);
        $Name = substr($Filename, 0, $length - 4);
        $d = substr($Name, 8, 2);
        $m = substr($Name, 5, 2);
        $y = substr($Name, 0, 4);
        $Date = new Date($y,$m,$d);
        return $Date;
    }

    function checkApproved($germanDate)
    {
        if ($fl = fopen(Main::$decissionList, "r")) {
            while (!feof($fl)) {
                $line = fgets($fl);
                # do same stuff with the $line
                if ((strpos($line, "beschließt") !== false) and  (strpos($line, "Protokoll") !== false ) and (strpos($line, "Sitzung") !== false ) and (strpos($line, $germanDate) !== false)) {
                    return true;
                }
            }
        }
        fclose($fl);
        return false;
    }

    function exportFinancial()
    {
        if($fl = fopen(Main::$decissionList, "r"))
        {
            while (!feof($fl)) {
                $line = fgets($fl);
                if ((strpos($line, "Budget") !== false)) {
                    if (strpos($line, "https://helfer.stura.tu-ilmenau.de/FinanzAntragUI/") !== false) {
                        if (!(strpos($line, "<del>") !== false))
                        {
                            $line = $this->formatLine($line, true);
                            if(Main::$debug)
                            {
                                echo  $line;
                            }
                        }
                    }
                    else
                    {
                        if ((!(strpos($line, "<del>") !== false)) and ! Main::$onlyNew)
                        {
                            $line = $this->formatLine($line, false);
                            if(Main::$debug)
                            {
                                echo  $line;
                            }
                        }
                    }
                }
            }
        }
    }

    function formatLine($line, $withToken)
    {
        $lineStart = substr($line, strpos($line, "|")+1);
        $lineStart = substr($lineStart, 0, strpos($lineStart, "|"));
        $lineStart = str_replace(" ", "", $lineStart);
        if($this->checkAlreadyPostedData($lineStart))
        {
            return  "";
        }
        if($withToken)
        {
            $lineEnd = substr($line, strpos($line, "|")+1);
            $lineEnd = substr($lineEnd, strpos($lineEnd, "|") +1 );
            $lineEnd = substr($lineEnd, strpos($lineEnd, "|") +1 );
            $lineEnd = substr($lineEnd, strpos($lineEnd, "FinanzAntragUI/") + 15);
            $lineEnd = substr($lineEnd, 0 , strpos($lineEnd, "|") );
            $lineEnd = str_replace(" ", "", $lineEnd);
            $lineS = $lineStart . "#-#" . $lineEnd . "<br />" . PHP_EOL;
            Main::$financialResolution[$lineEnd] = $lineStart;
        }
        else
        {
            Main::$financialResolution[] = $lineStart;
            $lineS = $lineStart . "#-#" . "not found <br />" . PHP_EOL;
        }
        $this->knownDecissions[] = $lineStart . PHP_EOL;
        return $lineS;
    }

    function sendData()
    {
        $url = Main::$PostUrl;
        $content = json_encode(Main::$financialResolution); //PHP Array

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        //execute Postback
        $ret = curl_exec($curl);
        echo $ret . "<br />" . PHP_EOL;  //kann auch eine bel. if abfrage zum testen des ergebnisses sein
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); //tut bestimmt sinnvolle dinge
        echo $status . "<br />" . PHP_EOL;
        if(strpos($status, "200") !== false)
        {
            echo "everything went fine <br />" . PHP_EOL;
        }
        else
        {
            exit($status);
        }
        curl_close($curl); //beendet verbindung, oder so
    }

    function readAlreadyKnownFinancialDecissions()
    {
        if ($fl = fopen(Main::$helperFilePath, "r")) {
            while (!feof($fl)) {
                $line = fgets($fl);
                # do same stuff with the $line
                $this->knownDecissions[] = $line;
            }
        }
        fclose($fl);
        return false;
    }

    function checkAlreadyPostedData($DecissionKey):bool
    {
        foreach ($this->knownDecissions as $line)
        {
            if (strpos($line, $DecissionKey) !== false)
            {
                return true;
            }
        }
        return false;
    }

    function writeHelperFile()
    {
        echo "write Storagefile <br />" . PHP_EOL;
        if($fl = fopen(Main::$helperFilePath, "w")) {
            foreach ($this->knownDecissions as $line) {
                fwrite($fl, $line);
            }
        }
        fclose($fl);
    }
}

class Date
{
    function __construct($Year, $Month, $Day)
    {
        $this->y = $Year;
        $this->m = $Month;
        $this->d = $Day;
    }

    private $y;
    private $m;
    private $d;

    function Year()
    {
        return $this->y;
    }
    function Month()
    {
        return $this->m;
    }
    function Day()
    {
        return $this->d;
    }
    function GermanDate()
    {
        return $this->d . "." . $this->m . "." . $this->y;
    }
    function Filname()
    {
        return $this->y . "-" . $this->m . "-" . $this->d . ".txt";
    }

}
class File
{
    private $datum;
    private $Filename;

    function __construct($date, $file)
    {
        $this->datum = $date;
        $this->Filename = Main::$inputpath . "/" . $file;
    }

    function getDate() : Date
    {
        return $this->datum;
    }
    function getFilename()
    {
        return $this->Filename;
    }
    function getOutputFilename()
    {
        return $this->getDate()->Filname();
    }
    function getgermanDate()
    {
        return $this->datum->GermanDate();
    }
}
$haupt = new Main();

$haupt->getAllFiles();


?>