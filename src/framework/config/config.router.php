<?php 
/**
 * Routing array
 * GET Request will trigger Template Class
 * POST Requests will be handled with JsonHandler
 * 
 * REQUEST METHOD => ROUTE => [PERMISSION, DATA]
 *
 * @var array
 */
$routes = [
	//		URL_ROUTE		PERMISSION		CONTROLLER	ACTION
	'GET' => [
		'/'					=> ['stura',	'base' , 	'home'],
		'admin'				=> ['ref-it',	'admin', 	'admin'],
	],
	'POST' => [
		'admin/savemail'	=> ['ref-it',	'admin', 	'save_mail'],
	]
];

/**
 * raw routes will call controller without initialising template
 * REQUEST METHOD => ROUTE => [PERMISSION, DATA]
 * @var array
 */
$rawRoutes = [
	
];

/**
 * navigation array
 * 
 * Path => [Permission, Alias, Symbol, Image]
 * @var array
 */

$navigation = [
	'/' 	=> ['stura', 	'Home', 	'&#xf015',	''],
	'admin' => ['ref-it', 	'Admin', 	'&#xf085;',	'gearLogo.png'],
];

?>