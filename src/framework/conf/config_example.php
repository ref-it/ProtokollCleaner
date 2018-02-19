<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 30.10.17
 * Time: 20:25
 */
Main::$inputpath  = "/var/www/dokuwiki/troll/intern/"; //intern part of the Wiki, where the files which will be cleaned are
Main::$outputpath = "/var/www/dokuwiki/troll/public/"; //public part of the Wiki, where the cleaned files will be saved
Main::$decissionList = "/var/www/dokuwiki/troll/beschluesse.txt"; //List off StuRa Decissions
Main::$helperFilePath = "/var/www/helper/help.txt"; //List off StuRa Decissions
Main::$starttag   = "intern"; //start tag of cleaning area
Main::$endtag     = "nointern"; //end tag of cleaning area
Main::$debug = true ; //all as Text on Browser
Main::$onlyNew = false; //only new financial decissions
Main::$postData = false; //set to true if you want to post data to another website
Main::$PostUrl = "http://localhost"; //destination for Posting of financial decission list
Main::$startMonth = 01;    //Day,
Main::$startYear  = 2016;  //Month and
Main::$startday   = 01;    //Year of First protokoll which will be cleaned
Main::$copiedLineColor = "lightgreen"; #Color for copied Line in Diff
Main::$copiedEditedLineColor = "lightsteelblue"; #Color for copied edited Line in Diff
Main::$removedLineColor = "lightcoral"; #Standard Color for removed Line in Diff
Main::$notDoubled = false; #do you want copy protokolls doubled
Main::$newDecissionList = "/var/www/dokuwiki/troll/beschluesseNeu.txt"; #Path to new DecissionList
Main::$restDecissionListTitel = ":examples:intern:"; #rest Titel after 'week of'
Main::$ignoreDBPublishedList = false; #ignores Database already published list
Main::$EnableLegislaturAutomization = false; #enables Legislaturnummerautomatisiserung