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
	//		URL_ROUTE		PERMISSION GROUP		CONTROLLER	ACTION
	'GET' => [
		'/'					=> ['baseaccess',	'base' , 	'home'],
		'admin'				=> ['admin',	'admin', 	'admin'],
		'protolist'			=> ['protolist',	'protocol', 	'plist'],
		'resolist'			=> ['resolist',	'resolution', 	'rlist'],
		'protoedit'			=> ['protoedit',	'protocol', 	'pedit_view'],
	],
	'POST' => [
		'admin/savemail'	=> ['admin',	'admin', 	'mail_update_setting'],
		'admin/testmail'	=> ['admin',	'admin', 	'mail_testmessage'],
		'protocol/publish'	=> ['protopublish',	'protocol', 	'p_publish'],
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
	'/' 	=> ['baseaccess', 	'Home', 	'&#xf015;',	''],
	'admin' => ['admin', 	'Admin', 	'&#xf085;',	'gearLogo.png'],
	'protolist' => ['protolist', 	'Protokolle', 	'&#xf266;',	'log.png'],
	'resolist' => ['resolist', 	'Beschlussliste', 	'&#xf0cb;',	'log.png'],
	'https://stura.tu-ilmenau.de/impressum' => ['baseaccess', 'Impressum', '&#xf129;', ''],
	'https://www.tu-ilmenau.de/impressum/datenschutz/' => ['baseaccess', 'Datenschutz', '&#xf1c0;', ''],
];



if (DEBUG >= 1) {
	$routes['GET']['dev'] = ['dev', 'dev',  'link'];
	$navigation['dev'] = ['dev', 'Dev', '&#xf20e;', ''];

	$routes['GET']['devWiki'] = ['dev', 'dev', 'link2'];
	$navigation['devWiki'] = ['dev', 'devWiki', '&#xf20e;', ''];
	
	$routes['GET']['wiki'] = ['dev', 'dev',  'wiki'];
	$navigation['wiki'] = ['dev', 'WikiTest', '&#xf266;', ''];

    $routes['GET']['wikiPut'] = ['dev', 'dev', 'putwiki'];
    $navigation['wikiPut'] = ['dev', 'WikiTestPut', '&#xf266;', ''];

    $routes['GET']['data'] = ['dev', 'dev', 'Data'];
    $navigation['data'] = ['dev', 'WriteFiles', '&#xf0c5;', ''];

    $routes['GET']['del'] = ['dev', 'dev', 'DeleteFiles'];
    $navigation['del'] = ['dev', 'Delete Files', '&#xf1f8;', ''];
}

/**
 * provide granular permissions + grouping
 * ['permission_group' => 'sgis group']
 * 		sgis_group: one must match to acces this route
 * @var array
 */
$permission_map = [
	'baseaccess' 	=> SIMPLESAML_ACCESS_GROUP,
	'admin' 		=> 'konsul,admin',
	'dev' 			=> 'ref-it,konsul,admin',
	'protolist' 	=> 'ref-it,stura,konsul,admin',
	'resolist' 		=> 'ref-it,stura,konsul,admin',
	'protoedit' 	=> 'ref-it,stura,konsul,admin',
	'protopublish' 	=> 'ref-it,stura,konsul,admin',
	'stura' 		=> 'stura',
	'ref-it' 		=> 'ref-it',
	'legislatur_all' => 'ref-it,konsul,admin'	//allow all legislatur numbers on protocols (not oly current +-1)
];

?>