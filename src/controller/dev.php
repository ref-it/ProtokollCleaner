<?php
/**
 * CONTROLLER Base Controller
 *
 * @package			Stura - Referat IT - ProtocolHelper
 * @category		controller
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform		PHP
 * @requirements	PHP 7.0 or higher
 */

require_once (SYSBASE . '/framework/class._MotherController.php');

class DevController extends MotherController
{
	/**
	 * constructor
	 * @param Database $db
	 * @param AuthHandler $auth
	 * @param Template $template
	 */
	function __construct($db, $auth, $template){
		parent::__construct($db, $auth, $template);
	}

	/**
	 * ACTION wiki
	 */
	public function wiki(){
		$this->t->printPageHeader();
		require_once (SYSBASE.'/framework/class.wikiClient.php');

		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);

		echo '<pre>'; var_dump('wiki time', $x->getTime()); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki title', $x->getTitle()); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki rpc', $x->getXMLRPCAPIVersion()); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki version', $x->getVersion()); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki page List', $x->getPagelist('protokoll')); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki stura page html',
			$x->getPageHTML('protokoll:stura')
		); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki, list stura attachement',
			$x->listAttachements('protokoll:stura')
		); echo '</pre><br><hr>';
		echo '<pre>'; var_dump('wiki, list spielwiese attachement',
			$x->listAttachements('spielwiese:test:utf8test')
		); echo '</pre><br><hr>';

		$this->t->printPageFooter();

		/*
		$this->t->printPageHeader();
		require_once (SYSBASE.'/framework/class.wikiClient.php');

		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		$a = $x->getAttachement('spielwiese:test:utf8test:badge3.png');

		echo '<pre>'; var_dump(
			$x->putAttachement('spielwiese:test:utf8test:badge5.png', $a)
		);
		echo '</pre>';
		echo '<pre>'; var_dump(
			$x->listAttachements('spielwiese:test:utf8test')
		); echo '</pre>';
		
		echo '<pre>'; var_dump(
			$x->listAttachements('spielwiese:test:utf8testdsd')
		); echo '</pre>';

		$this->t->printPageFooter();
		*/
	}

	public function putwiki()
	{
		/* // create wiki page
		$this->t->printPageHeader();
		require_once(SYSBASE . '/framework/class.wikiClient.php');

		$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
		echo '<pre>';
		var_dump($x->putPage('spielwiese/test2', 'blubb'));
		echo '</pre>';
		$this->t->printPageFooter();
		// */

		/* // trigger cronupdate
		require_once(SYSBASE.'/controller/cron.php');
		$cc = new CronController($this->db, $this->auth, $this->t);
		$cc->sgis();
		// */
	}
}
