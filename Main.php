<!DOCTYPE html>
<html>
<body>

<h1>ProtokollCleaner</h1>

<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 18.10.17
 * Time: 09:54
 */

class Main
{
    public static $inputpath  = "/home/martin/test/intern/"; //intern part of the Wiki, where the files which will be cleaned are
    public static $outputpath = "/home/martin/test/public/"; //public part of the Wiki, where the cleaned files will be saved
    public static $starttag   = "intern"; //start tag of cleaning area
    public static $endtag     = "nointern"; //end tag of cleaning area
    private $startMonth = 01;    //Day,
    private $startYear  = 2016;  //Month and
    private $startday   = 01;    //Year of First protokoll which will be cleaned


    private $files;

    function getAllFiles()
    {
        $this->files = array();
        $alledateien = scandir(Main::$inputpath); //Ordner "files" auslesen
        foreach ($alledateien as $datei) { // Ausgabeschleife
            $length = strlen($datei);
            if((substr($datei, 0,1) == ".") and (substr($datei, $length - 4, 4) != ".txt") and ($length < 5))
            {
                continue;
            }
            $Date = $this->getDateFromFileName($datei);
            if(intval($Date->Year()) < $this->startYear)
            {
                continue;
            }
            if((intval($Date->Year()) === $this->startYear) and (intval($Date->Month()) < $this->startMonth))
            {
                continue;
            }
            if((intval($Date->Year()) === $this->startYear) and (intval($Date->Month()) === $this->startMonth) and (intval($Date->Day()) < $this->startday))
            {
                continue;
            }
            $file = new File($Date, $datei);
            $fn = Main::$outputpath . "/" . $file->getOutputFilename();
            $this->copy($file->getFilename(), $fn, $this->checkApproved($file->getgermanDate()));

            $this->files[] = $file;


            echo $Date->GermanDate() . "<br />";
        }

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
                    if(strpos($line, "======") !== false and $check)
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
                echo $line . "<br />";
            }
        }
        fclose($fl);
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
        $this->files = array();
        $alledateien = scandir(Main::$inputpath); //Ordner "files" auslesen
        foreach ($alledateien as $datei) { // Ausgabeschleife
            if ($fl = fopen(Main::$inputpath . "/" . $datei, "r")) {
                while(!feof($fl)) {
                    $line = fgets($fl);
                    # do same stuff with the $line
                    if (strpos($line, "template>:vorlagen:stimmen|Titel=Der StuRa beschließt das Protokoll der Sitzung vom " . $germanDate . " in der im Wiki vorliegenden Fassung.") !== false)
                    {
                        if (strpos($line, "S=angenommen") !== false) {
                            return true;
                        }
                    }
                }
                fclose($fl);
            }
        }
        return false;
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

    function getDate()
    {
        return $this->datum;
    }
    function getFilename()
    {
        return $this->Filename;
    }
    function getOutputFilename()
    {
        return $this->datum->Filname();
    }
    function getgermanDate()
    {
        return $this->datum->GermanDate();
    }
}
$haupt = new Main();

$haupt->getAllFiles();


?>
</body>
</html>