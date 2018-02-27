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
		'protolist'			=> ['stura',	'protocol', 	'plist'],
		'protoedit'			=> ['stura',	'protocol', 	'pedit_view'],
	],
	'POST' => [
		'admin/savemail'	=> ['ref-it',	'admin', 	'mail_update_setting'],
		'admin/testmail'	=> ['ref-it',	'admin', 	'mail_testmessage'],
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
	'/' 	=> ['stura', 	'Home', 	'&#xf015;',	''],
	'admin' => ['ref-it', 	'Admin', 	'&#xf085;',	'gearLogo.png'],
	'protolist' => ['ref-it', 	'Protokolle', 	'&#xf266;',	'log.png'],
	'https://stura.tu-ilmenau.de/impressum' => ['stura', 'Impressum', '&#xf129;', ''],
	'https://www.tu-ilmenau.de/impressum/datenschutz/' => ['stura', 'Datenschutz', '&#xf1c0;', ''],
];
if (DEBUG >= 1) {
	$routes['GET']['dev'] = ['ref-it', 'dev',  'link'];
	$navigation['dev'] = ['ref-it', 'Dev', '&#xf20e;', ''];

	$routes['GET']['devWiki'] = ['ref-it', 'dev', 'link2'];
	$navigation['devWiki'] = ['ref-it', 'devWiki', '&#xf20e;', ''];
	
	$routes['GET']['wiki'] = ['ref-it', 'dev',  'wiki'];
	$navigation['wiki'] = ['ref-it', 'WikiTest', '&#xf266;', ''];

    $routes['GET']['wikiPut'] = ['ref-it', 'dev', 'putwiki'];
    $navigation['wikiPut'] = ['ref-it', 'WikiTestPut', '&#xf266;', ''];

    $routes['GET']['data'] = ['ref-it', 'dev', 'Data'];
    $navigation['data'] = ['ref-it', 'WriteFiles', '&#xf0c5;', ''];

    $routes['GET']['del'] = ['ref-it', 'dev', 'DeleteFiles'];
    $navigation['del'] = ['ref-it', 'Delete Files', '&#xf1f8;', ''];
}

/**
 * provide granular permissions
 * ['permission']
 * @var array
 */
$permission_map = [
	'stura' => 'stura',
	'ref-it' => 'ref-it'
];

?>