<?php 
/**
 * Routing array
 * GET Request will trigger Template Class
 * POST Requests will be handled with JsonHandler
 * 
 * REQUEST METHOD => ROUTE => [PERMISSION, CONTROLLER, ACTION]
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
		'protocol/ignore'	=> ['protoignore',	'protocol', 	'p_ignore'],
		'todo/update'		=> ['todoupdate',	'todo', 		'tupdate'],
		'invite/tdelete'	=> ['itopdelete',	'invitation', 	'tdelete'],
		'invite/tpause'		=> ['itoppause',	'invitation', 	'tpause'],
		'invite/tsort'		=> ['itopsort',		'invitation', 	'tsort'],
		'invite/tupdate'	=> ['itopupdate',	'invitation', 	'itopupdate'],
		'invite/npupdate'	=> ['inpupdate',	'invitation', 	'npupdate'],
		'invite/npdelete'	=> ['inpdelete',	'invitation', 	'npdelete'],
		'invite/npinvite'	=> ['inpinvite',	'invitation', 	'npinvite'],
		'invite/nptowiki'	=> ['inp2wiki',		'invitation', 	'nptowiki'],
		'invite/nprestore'	=> ['inprestore',	'invitation', 	'nprestore'],
		'invite/mdelete'	=> ['imemberdelete','invitation', 	'mdelete'],
		'invite/madd'		=> ['imemberadd',	'invitation', 	'madd'],
	]
];

/**
 * cron routes only use basic auth
 * also place routes with empty permissionentry here
 * REQUEST METHOD => ROUTE => [PERMISSION, CONTROLLER, ACTION, DESCRIPTION]
 * @var array
 */
$cronRoutes = [
	'GET' => [
		'todo/manifest'	=> ['',				'todo', 		'manifest'],
		'cron'			=> ['croninfo',		'cron', 		'info' , 'This Page'],
	],
	'POST' => [
		'cron/mail'		=> ['cronmail',		'cron', 		'mail' , 'Trigger auto mail creation.<br><strong>Suggestion: hourly</strong>'],
		'cron/wiki'		=> ['cronwiki',		'cron', 		'wiki' , 'Writes resolution list to wiki.<br><strong>Suggestion: daily 2:00am</strong>' ],
	]
];

/**
 * navigation array
 * 
 * Path => [Permission, Alias, Symbol, Image]
 * @var array
 */
$navigation = [
	'/' 		=> ['baseaccess', 	'Home', 			'&#xf015;',		NULL],
	'admin' 	=> ['admin', 		'Admin', 			'&#xf085;',		''],
	'crawl'	 	=> ['crawler', 		'Crawler', 			'&#xf0e7;',		''],
	'protolist' => ['protolist', 	'Protokolle', 		'&#xf266;',		''],
	'reso/list' => ['resolist', 	'Beschlussliste', 	'&#xf0cb;',		''],
	'todo/list' => ['todolist', 	'Todos', 			'&#xf046;',		''],
	'invite'	=> ['invitebase', 	'Sitzungseinladung', '&#xf0e0;',	''],
	'https://stura.tu-ilmenau.de/impressum' => ['baseaccess', 'Impressum', '&#xf129;', NULL],
	'https://www.tu-ilmenau.de/impressum/datenschutz/' => ['baseaccess', 'Datenschutz', '&#xf1c0;', NULL],
];



if (DEBUG >= 1) {
	$routes['GET']['wiki'] = ['dev', 'dev',  'wiki'];
	$navigation['wiki'] = ['dev', 'WikiTest', '&#xf266;', ''];

    $routes['GET']['wikiPut'] = ['dev', 'dev', 'putwiki'];
    $navigation['wikiPut'] = ['dev', 'WikiTestPut', '&#xf266;', ''];
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
	'crawler' 		=> 'konsul,admin',
	'dev' 			=> 'ref-it,konsul,admin',
	'protolist' 	=> 'ref-it,stura,konsul,admin',
	'resolist' 		=> 'ref-it,stura,konsul,admin',
	'resotowiki' 	=> 'ref-it,stura,konsul,admin',
	'todolist' 		=> 'stura,ref-it,stura-activ,konsul,admin',
	'todoupdate' 	=> 'stura,ref-it,stura-activ,konsul,admin',
	'invitebase' 	=> 'stura,ref-it,stura-activ,konsul,admin',
	'itopdelete' 	=> 'stura,ref-it,stura-activ,konsul,admin',
	'itoppause' 	=> 'stura,ref-it,stura-activ,konsul,admin',
	'itopsort' 		=> 'stura,ref-it,stura-activ,konsul,admin',
	'itopedit' 		=> 'stura,ref-it,stura-activ,konsul,admin',
	'itopupdate'	=> 'stura,ref-it,stura-activ,konsul,admin',
	'inpupdate'		=> 'stura,ref-it,stura-activ,konsul,admin',
	'inpdelete'		=> 'stura,ref-it,stura-activ,konsul,admin',
	'inpinvite'		=> 'stura,ref-it,stura-activ,konsul,admin',
	'inp2wiki'		=> 'stura,ref-it,stura-activ,konsul,admin',
	'inprestore'	=> 'stura,ref-it,stura-activ,konsul,admin',
	'imemberdelete' => 'konsul,admin',
	'imemberadd' 	=> 'konsul,admin',
	'protoedit' 	=> 'ref-it,stura,konsul,admin',
	'protopublish' 	=> 'ref-it,stura,konsul,admin',
	'protoignore' 	=> 'ref-it,stura,konsul,admin',
	'stura' 		=> 'stura, cron',
	'ref-it' 		=> 'ref-it',
	'legislatur_all' => 'ref-it,konsul,admin',	//allow all legislatur numbers on protocols (not oly current +-1)
	'cron'			=> 'cron',
	'croninfo'		=> 'croninfo',
	'cronmail'		=> 'cronmail',
	'cronwiki'		=> 'cronwiki',
];

// handle BASE_SUBDIRECTORIES
if (BASE_SUBDIRECTORY != '/'){
	$tmpf1 = function($a) {
		$tmp1 = [];
		foreach ($a as $k1 => $v1){
			foreach ($v1 as $k2 => $v2){
				$tmp1[BASE_SUBDIRECTORY.(($k2=='/')?'':$k2)]=$v2;
			}
		}
		return $tmp1;
	};
	$tmpf2 = function($a) {
		$tmp1 = [];
		foreach ($a as $k1 => $v1){
			$tmp1[BASE_SUBDIRECTORY.(($k1=='/')?'':$k1)]=$v1;
		}
		return $tmp1;
	};
	//update routes
	$routes = $tmpf1($routes);
	//update cron routes
	$cronRoutes = $tmpf1($cronRoutes);
	//update navigation
	$navigation = $tmpf2($navigation);
	unset($tmpf1);
	unset($tmpf2);
}

?>