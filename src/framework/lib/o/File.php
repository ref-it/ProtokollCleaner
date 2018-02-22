<?php
/**
 * File.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 17.02.18 22:13
 */

class File
{
    private $datum;
    private $Filename;
    private $wikipath;

    function __construct($date, $file)
    {
        $this->datum = $date;
        $this->Filename = Main::$inputpath . "/" . $file;
        $this->wikipath = $file;
    }

    function getDate() : Date
    {
        return $this->datum;
    }
    function getFilename()
    {
        return $this->Filename;
    }

    function getFilenameWiki()
    {
        Useroutput::makeDump($this->wikipath);
        return $this->wikipath;
    }
    function getOutputFilename()
    {
        return $this->getDate()->Filname();
    }

    function getWikiPath()
    {
        return $this->getDate()->WikiPath();
    }
    function getgermanDate()
    {
        return $this->datum->GermanDate();
    }
}

?>