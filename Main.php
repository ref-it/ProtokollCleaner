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
    public static $inputpath  = "/home/martin/test/intern/";
    public static $outputpath = "/home/martin/test/public/";
    public static $starttag   = "intern";
    public static $endtag     = "nointern";
    private $startMonth = 01;
    private $startYear  = 2016;
    private $startday   = 01;


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
            $this->copy($file->getFilename(), $fn);

            $this->files[] = $file;


            echo $Date->GermanDate() . "<br />";
        }

    }
    function copy($fileName, $fn)
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
                    $lines[] = $line;
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
}
$haupt = new Main();

$haupt->getAllFiles();


?>
</body>
</html>