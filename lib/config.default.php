<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 18.11.17
 * Time: 17:30
 */

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