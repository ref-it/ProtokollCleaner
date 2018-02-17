<?php
/**
 * INDEX FILE ProtocolHelper
 * Application starting point
 *
 * @author michael g
 * @author Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 17.02.2018
 *
 */
require_once ("../config.php");

$t = new template();

	$t->printPageHeader();
	 ?>
	<h2 class="printonly">Hauptmenu</h2>	
	<?php if (function_exists('checkUserPermission')){ ?>
		<?php if (checkUserPermission('permission1') || checkUserPermission('permission2')){ ?>
			<div class="button_container">
				<a href="/pages/page1/index.php" target="_self" title="Page 1">
					<h3>Page 1</h3>
					<div class="img" style="background-image: url('/images/page01.png')"></div>
				</a>
			</div>
		<?php } if (checkUserPermission('user')){ ?>
			<div class="button_container">
				<a href="/pages/user/index.php" target="_self" title="Benutzereinstellungen">
					<h3>Benutzer</h3>
					<div class="img" style="background-image: url('/images/usersSettings.png')"></div>
				</a>
			</div>
		<?php } if (checkUserPermission('admin')){ ?>
			<div class="button_container">
				<a href="/pages/admin/index.php" target="_self" title="Admin">
					<h3>Admin</h3>
					<div class="img" style="background-image: url('/images/gearLogo.png')"></div>
				</a>
			</div>
		<?php } ?>
		<div class="clear"></div>
	<?php } 
	
	$t->printPageFooter();

$db->close();