<?php
/**
 * LegislaturCrawler.php
 * @author Martin S.
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 19.02.18 16:23
 */

class LegislaturCrawler
{
    public static function getLegislatur($date, $sn)
    {
        settype($date, Date::class);
        $result = Main::$DatabaseCon->getlegislatur(substr($date->Filname(), 0, 10));
        if ($result === -1)
        {
            if (($date->Year() === date("Y")) or ( intval($date->Year()) === intval(date("Y") -1 )))
            {
                if (intval(Main::$DatabaseCon->getLastSitzungsnummer()) < intval($sn) )
                {
                    $result = Main::$DatabaseCon->getCurrentLegislatur();
                    Main::$DatabaseCon->setNewSitzungsnummer(strval(intval($sn)+1));
                }
                else
                {
                    $result = strval(intval(Main::$DatabaseCon->getCurrentLegislatur()) + 1);
                    Main::$DatabaseCon->setCurrentLegislatur($result);
                }
            }
            else
            {
                throw new Exception("An Error Occured.");
            }
        }
        return $result;
    }
}