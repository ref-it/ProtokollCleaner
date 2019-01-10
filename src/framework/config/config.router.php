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
		'admin/smtpdebug'	=> ['admin',		'admin', 		'smtpdebug'],
		'protolist'			=> ['protolist',	'protocol', 	'plist'],
		'reso/list'			=> ['resolist',		'resolution', 	'rlist'],
		'reso/towiki'		=> ['resotowiki',	'resolution', 	'resoToWiki'],
		'todo/list'			=> ['todolist',		'todo', 		'tlist'],
		'invite'			=> ['invitebase',	'invitation', 	'ilist'],
		'invitestuds'		=> ['ipublic',		'invitation', 	'ipublic'],
		'invite/tedit'		=> ['itopedit',		'invitation', 	'itopedit'],
		'crawl'				=> ['crawler',		'crawler', 		'home'],
		'crawl/legislatur'	=> ['crawler',		'crawler', 		'crawlLegislatur'],
		'crawl/resoproto'	=> ['crawler',		'crawler', 		'crawlResoProto'],
		'protoedit'			=> ['protoedit',	'protocol', 	'pedit_view'],
		'files/npuploader'	=> ['filesnpuploader', 'file',		'npuploader'],
		'files/get'			=> ['filesget',		 'file',		'get'],
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
		'invite/itoprecreate'	=> ['itoprecreate',	'invitation', 	'itoprecreate'],
		'invite/itopnplist'	=> ['itopnplist',	'invitation', 	'itopnplist'],
		'invite/npupdate'	=> ['inpupdate',	'invitation', 	'npupdate'],
		'invite/npdelete'	=> ['inpdelete',	'invitation', 	'npdelete'],
		'invite/npinvite'	=> ['inpinvite',	'invitation', 	'npinvite'],
		'invite/nptowiki'	=> ['inp2wiki',		'invitation', 	'nptowiki'],
		'invite/nprestore'	=> ['inprestore',	'invitation', 	'nprestore'],
		'invite/mdelete'	=> ['imemberdelete','invitation', 	'mdelete'],
		'invite/madd'		=> ['imemberadd',	'invitation', 	'madd'],
		'files/npupload'	=> ['filesnpupload', 'file',		'tfupload'],
		'files/delete'		=> ['filesdelete',	 'file',		'tfremove'],
	]
];

/**
 * cron routes only use basic auth and starts with 'cron'
 * also place routes with empty permissionentry here
 * REQUEST METHOD => ROUTE => [PERMISSION, CONTROLLER, ACTION, DESCRIPTION]
 * @var array
 */
$cronRoutes = [
	'GET' => [
		'todo/manifest'			=> 	['',				'todo', 		'manifest', ''],
		'cron'					=> 	['croninfo',		'cron', 		'info' , 	'This Page'],
	],
	'POST' => [
		'cron/mail'		=> ['cronmail',		'cron', 		'mail' , 'Trigger auto mail creation. (Invitation + Remember)<br><strong>Suggestion: hourly</strong><br><strong>Example: </strong><span>curl --netrc-file cron_protocol_tool.netrc -X POST '.BASE_URL.BASE_SUBDIRECTORY.'cron/mail'],
		'cron/wiki'		=> ['cronwiki',		'cron', 		'wiki' , 'Writes resolution list to wiki.<br><strong>Suggestion: daily 2:00am</strong><br><strong>Example: </strong><span>curl --netrc-file cron_protocol_tool.netrc -X POST '.BASE_URL.BASE_SUBDIRECTORY.'cron/wiki' ],
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
	'invite'	=> ['invitebase', 	'Sitzung',			'&#xf0e0;',		''],
	'invitestuds'	=> ['ipublic', 	'Tagesordnung',		'&#xf276;',		''],
	'https://stura.tu-ilmenau.de/impressum' => ['baseaccess', 'Impressum', '&#xf129;', NULL],
	'https://www.tu-ilmenau.de/impressum/datenschutz/' => ['baseaccess', 'Datenschutz', '&#xf1c0;', NULL],
];

if (DEBUG >= 1) {
	$routes['GET']['wiki'] = ['dev', 'dev',  'wiki'];
	$navigation['wiki'] = ['dev', 'WikiTest', '&#xf1d1;', ''];

    $routes['GET']['wikiPut'] = ['dev', 'dev', 'putwiki'];
    $navigation['wikiPut'] = ['dev', 'WikiTestPut', '&#xf1d1;', ''];
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
	'dev' 			=> 'ref-it,konsul,admin,dev',
	'protolist' 	=> 'sgis',
	'resolist' 		=> 'sgis',
	'resotowiki' 	=> 'konsul,admin',
	'todolist' 		=> 'sgis',
	'todoupdate' 	=> 'stura,konsul,admin',
	'invitebase' 	=> 'stura,konsul,admin',
	'itopdelete' 	=> 'stura,konsul,admin',
	'itoppause' 	=> 'stura,konsul,admin',
	'itopsort' 		=> 'stura,konsul,admin',
	'itopedit' 		=> 'stura,konsul,admin',
	'itopupdate'	=> 'stura,konsul,admin',
	'itoprecreate'	=> 'stura,konsul,admin',
	'itopnplist'	=> 'stura,konsul,admin',
	'inpupdate'		=> 'stura,konsul,admin',
	'inpdelete'		=> 'stura,konsul,admin',
	'inpinvite'		=> 'stura,konsul,admin',
	'inp2wiki'		=> 'stura,konsul,admin',
	'inprestore'	=> 'stura,konsul,admin',
	'imemberdelete' => 'konsul,admin',
	'imemberadd' 	=> 'konsul,admin',
	'ipublic' 		=> 'sgis',
	'protoedit' 	=> 'stura,konsul,admin',
	'protopublish' 	=> 'stura,konsul,admin',
	'protoignore' 	=> 'stura,konsul,admin',
	'stura' 		=> 'stura, cron',
	'ref-it' 		=> 'ref-it',
	'legislatur_all' => 'konsul,admin',	//allow all legislatur numbers on protocols (not oly current +-1)
	'cron'			=> 'cron',
	'croninfo'		=> 'croninfo',
	'cronmail'		=> 'cronmail',
	'cronwiki'		=> 'cronwiki',
	'filesnpuploader' => 'stura,konsul,admin',	//gui
	'filesnpupload' => 'stura,konsul,admin',	//file transfer
	'filesget' 		=> 'stura,konsul,admin',	//file get
	'filesdelete' 	=> 'stura,konsul,admin',	//file delete
];

// handle BASE_SUBDIRECTORIES
if (BASE_SUBDIRECTORY != '/'){
	$tmpf1 = function($a) {
		$tmp1 = [];
		foreach ($a as $k1 => $v1){
			$tmp2 = [];
			foreach ($v1 as $k2 => $v2){
				$key  = substr(BASE_SUBDIRECTORY,1);
				if ($k2!='/') {
					$key.=$k2;
				} else {
					$key = substr($key,0,-1);
				}
				$tmp2[$key]=$v2;
			}
			$tmp1[$k1] = $tmp2;
		}
		return $tmp1;
	};
	$tmpf2 = function($a) {
		$tmp1 = [];
		foreach ($a as $k1 => $v1){
			if (mb_substr($k1, 0, 7) == 'http://' || mb_substr($k1, 0, 8) == 'https://' || mb_substr($k1, 0, 2) == '//'){
				$tmp1[$k1]=$v1;
			} else {
				$tmp1[substr(BASE_SUBDIRECTORY,1).(($k1=='/')?'':$k1)]=$v1;
			}
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
