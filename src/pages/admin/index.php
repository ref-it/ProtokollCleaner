<?php
/**
 * PAGE FILE admin
 * Application starting point
 *
 * @package         TODO
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

if (!checkUserPermission('admin')){
	header("Location: " . BASE_URL.'?page_error=403');
	$db->close();
	die();
}

$t = new template();
$t->setTitlePrefix('Admin');
$t->appendJsLink('admin.js');

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
	<div class="half_container mail_settings admin"><h3 class="noprint">E-Maileinstellungen</h3><h3 class="printonly">E-Maileinstellungen</h3>
		<?php $settings = $db->getSettings(); ?>
		<table class="striped">
			<tr>
				<th></th>
				<th></th>
			</tr>
			<tr>
				<th>SMTP Ausgangs-Server</th>
				<td><div class="editable mail" tabindex="0" data-value="smtp_host" data-validator="host"><?php echo $settings['SMTP_HOST']; ?></div></td>
			</tr>
			<tr>
				<th>SMTP Nutzer</th>
				<td><div class="editable mail" tabindex="0" data-value="smtp_user" data-validator="username"><?php echo $settings['SMTP_USER']; ?></div></td>
			</tr>
			<tr>
				<th>SMTP Passwort</th>
				<td><div class="editable mail" tabindex="0" data-value="mail_password" data-validator="password"><?php for ($i=0; $i<16; $i++) echo '&#8226;'; ?></div></td>
			</tr>
			<tr>
				<th>SMTP Sicherheit</th>
				<td><div class="editable mail" tabindex="0" data-value="smtp_secure" data-validator="ssltls"><?php echo $settings['SMTP_SECURE']; ?></div></td>
			</tr>
			<tr>
				<th>SMTP Port</th>
				<td><div class="editable mail" tabindex="0" data-value="smtp_port" data-validator="integer"><?php echo $settings['SMTP_PORT']; ?></div></td>
			</tr>
			<tr>
				<th>Absender E-Mailadresse</th>
				<td><div class="editable mail" tabindex="0" data-value="mail_from" data-validator="mail"><?php echo $settings['MAIL_FROM']; ?></div></td>
			</tr>
			<tr>
				<th>Absender Name</th>
				<td><div class="editable mail" tabindex="0" data-value="mail_from_alias" data-validator="user"><?php echo $settings['MAIL_FROM_ALIAS']; ?></div></td>
			</tr>
		</table>
		<div class="addfooterspace"></div>
		<div class="footerline">
			<button type="button" class="submit" title="Einstellungen testen. Dabei wird eine E-Mail versendet.">Einstellungen testen</button>
		</div>
	</div>
	<div class="clear"></div>
			
<?php $t->printPageFooter(); ?>