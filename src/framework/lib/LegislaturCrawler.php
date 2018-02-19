<?php
/**
 * LegislaturCrawler.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 19.02.18 16:23
 */

class LegislaturCrawler
{

    public function __construct()
    {

    }
    public function getLegislatur($date, $sn)
    {
        settype($date, Date::class);
        $result = Main::$DatabaseCon->getlegislatur(substr($date->Filname(), 0, 10));
        if ($result === -1)
        {

        }
        return $result;
    }
}