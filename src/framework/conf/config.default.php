<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 18.11.17
 * Time: 17:30
 */

Main::$inputpath  = 'examples/intern/'; //intern part of the Wiki, where the files which will be cleaned are
Main::$outputpath = 'examples/extern/'; //public part of the Wiki, where the cleaned files will be saved
Main::$decissionList = 'examples/beschluesse.txt'; //List off StuRa Decissions
Main::$helperFilePath = 'examples/help.txt'; //List off StuRa Decissions
Main::$starttag   = "intern"; //start tag of cleaning area
Main::$endtag     = "nointern"; //end tag of cleaning area
Main::$debug = true ; //all as Text on Browser
Main::$onlyNew = false; //only new financial decissions
Main::$postData = true; //set to true if you want to post data to another website
Main::$PostUrl = "http://localhost"; //destination for Posting of financial decission list
Main::$startMonth = 01;    //Day,
Main::$startYear  = 2016;  //Month and
Main::$startday   = 01;    //Y
Main::$copiedLineColor = "lightgreen"; #Color for copied Line in Diff
Main::$copiedEditedLineColor = "lightsteelblue"; #Color for copied edited Line in Diff
Main::$removedLineColor = "lightcoral"; #Standard Color for removed Line in Diff
Main::$notDoubled = false; #do you want copy protokolls doubled
Main::$decissionList = 'examples/beschluesseNeu.txt'; //List off StuRa Decissions
Main::$restDecissionListTitel = ":examples:intern:"; #rest Titel after 'week of'
Main::$ignoreDBPublishedList = false; #ignores Database already published list
Main::$EnableLegislaturAutomization = false; #enables Legislaturnummerautomatisiserung