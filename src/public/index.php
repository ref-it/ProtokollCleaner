<?php
/**
 * INDEX FILE ProtocolHelper
 * Application starting point
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        application
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
// ===== load framework =====
if (!file_exists ( realpath(dirname(__FILE__) . '/..').'/config.php' )){
	echo "No configuration file found! Please create and edit 'config.php'";
	die();
}
require_once (realpath(dirname(__FILE__) . '/..').'/config.php');