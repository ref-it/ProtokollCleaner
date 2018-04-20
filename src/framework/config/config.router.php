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
	//		URL_ROUTE		PERMISSION GROUP	CONTROLLER		ACTION
	'GET' => [
		'/'					=> ['baseaccess',	'base' , 		'home'],
		'admin'				=> ['admin',		'admin', 		'admin'],
		'protolist'			=> ['protolist',	'protocol', 	'plist'],
		'reso/list'			=> ['resolist',		'resolution', 	'rlist'],
		'reso/towiki'		=> ['resotowiki',	'resolution', 	'resoToWiki'],
		'todo/list'			=> ['todolist',		'todo', 		'tlist'],
		'invite'			=> ['invitebase',	'invitation', 	'ilist'],
		'invite/tedit'		=> ['itopedit',		'invitation', 	'itopedit'],
		'crawl'				=> ['crawler',		'crawler', 		'home'],
		'crawl/legislatur'	=> ['crawler',		'crawler', 		'crawlLegislatur'],
		'crawl/resoproto'	=> ['crawler',		'crawler', 		'crawlResoProto'],
		'protoedit'			=> ['protoedit',	'protocol', 	'pedit_view'],
	],
	'POST' => [
		'admin/savemail'	=> ['admin',		'admin', 		'mail_update_setting'],
		'admin/testmail'	=> ['admin',		'admin', 		'mail_testmessage'],
		'admin/legislatur'	=> ['admin',		'admin', 		'legislatur'],
		'protocol/publish'	=> ['protopublish',	'protocol', 	'p_publish'],
		'todo/update'		=> ['todoupdate',	'todo', 		'tupdate'],
		'invite/tdelete'	=> ['itopdelete',	'invitation', 	'tdelete'],
		'invite/tpause'		=> ['itoppause',	'invitation', 	'tpause'],
		'invite/tsort'		=> ['itopsort',		'invitation', 	'tsort'],
		'invite/tupdate'	=> ['itopupdate',	'invitation', 	'itopupdate'],
		'invite/mdelete'	=> ['imemberdelete','invitation', 	'mdelete'],
		'invite/madd'		=> ['imemberadd',	'invitation', 	'madd'],
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
	'/' 		=> ['baseaccess', 	'Home', 			'&#xf015;',		''],
	'admin' 	=> ['admin', 		'Admin', 			'&#xf085;',		'gearLogo.png'],
	'crawl'	 	=> ['crawler', 		'Crawler', 			'&#xf0e7;',		''],
	'protolist' => ['protolist', 	'Protokolle', 		'&#xf266;',		'log.png'],
	'reso/list' => ['resolist', 	'Beschlussliste', 	'&#xf0cb;',		'log.png'],
	'reso/towiki' => ['resotowiki', 'Beschluss zu Wiki', '&#xf0cb;',	'log.png'],
	'todo/list' => ['todolist', 	'Todos', 			'&#xf046;',		'log.png'],
	'invite'	=> ['invitebase', 	'Sitzungseinladung', '&#xf0e0;',	'log.png'],
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
	'crawler' 		=> 'ref-it,konsul,admin',
	'protolist' 	=> 'ref-it,stura,konsul,admin',
	'resolist' 		=> 'ref-it,stura,konsul,admin',
	'resotowiki' 	=> 'ref-it,stura,konsul,admin',
	'todolist' 		=> 'stura,ref-it,stura,konsul,admin',
	'todoupdate' 	=> 'stura,ref-it,stura,konsul,admin',
	'invitebase' 	=> 'stura,ref-it,stura,konsul,admin',
	'itopdelete' 	=> 'stura,ref-it,stura,konsul,admin',
	'itoppause' 	=> 'stura,ref-it,stura,konsul,admin',
	'itopsort' 		=> 'stura,ref-it,stura,konsul,admin',
	'itopedit' 		=> 'stura,ref-it,stura,konsul,admin',
	'itopupdate'	=> 'stura,ref-it,stura,konsul,admin',
	'imemberdelete' => 'konsul,admin',
	'imemberadd' 	=> 'konsul,admin',
	'protoedit' 	=> 'ref-it,stura,konsul,admin',
	'protopublish' 	=> 'ref-it,stura,konsul,admin',
	'stura' 		=> 'stura',
	'ref-it' 		=> 'ref-it',
	'legislatur_all' => 'ref-it,konsul,admin'	//allow all legislatur numbers on protocols (not oly current +-1)
];

?>