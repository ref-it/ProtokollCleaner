<?php
/**
 * PAGE FILE admin
 * Application starting point
 *
 * @package         BVG Bestellinformationssystem
 * @category        page
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
// ===== load framework =====
if (!file_exists ( dirname(__FILE__, 4).'/config.php' )){
	echo 'No configuration file found!. Please create and edit "config.php".';
	die();
}
require_once (dirname(__FILE__, 4).'/config.php');

if (!checkUserPermission('user')){
	header("Location: " . BASE_URL.'?page_error=403');
	$db->close();
	die();
}

$t = new template();
$t->setTitlePrefix('User');
$t->appendJsLink('user.js');

$t->printPageHeader(); ?>
	<?php if($_SESSION['USER_ID'] != 0) echo '<input type="hidden" id="fchal" name="'.$_SESSION['FORM_CHALLANGE_NAME'].'" value="'.$_SESSION['FORM_CHALLANGE_VALUE'].'">'?>
	<h2 class="printonly">Statistiken</h2>
	<div class="half_container statistics admin"><h3 class="noprint">Statistik</h3><h3 class="printonly">Allgemein</h3>
		<table class="striped">
			<tr>
				<th></th>
				<th></th>
			</tr>
			<tr>
				<th>Nutzer</th>
				<td><?php echo $db->statisticSumUsers();?></td>
			</tr>
			<tr>
				<th>Kunden</th>
				<td><?php echo $db->statisticSumCustomers();?></td>
			</tr>
			<tr>
				<th>Bestellungen Insgesamt</th>
				<td><?php echo $db->statisticSumOrders();?></td>
			</tr>
			<tr>
				<th>Bestellungen <?php $current_year = date_create()->format('Y'); echo $current_year;?></th>
				<td><?php echo $db->statisticSumOrdersYear($current_year);?></td>
			</tr>
			<tr>
				<th>Kommentare</th>
				<td><?php echo $db->statisticSumComments();?></td>
			</tr>
		</table>
	</div>

	<div class="clear"></div>
			
<?php $t->printPageFooter(); ?>