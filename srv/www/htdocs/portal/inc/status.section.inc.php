<?php
/* 
 * inc/status.section.php v1.0
 * portal drop-in, requesting/displaying server status information
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
 
if (!isset($CONF)) die;
?>

<script type="text/javascript">

new Ajax.PeriodicalUpdater(
	'basic_info',
	'script/status.php',
	{ method: 'post', frequency: 30, parameters: {c: 'basic_info'}}
);

new Ajax.PeriodicalUpdater(
	'inet_info',
	'script/status.php',
	{ method: 'post', frequency: 20, parameters: {c: 'inet_info'}}
);

new Ajax.PeriodicalUpdater(
	'hd_info',
	'script/status.php',
	{ method: 'post', frequency: 45, parameters: {c: 'hd_info'}}
);

new Ajax.PeriodicalUpdater(
	'capacity_info',
	'script/status.php',
	{ method: 'post', frequency: 5, parameters: {c: 'capacity_info'}}
);

<?php 
if (isset($STATUS_BACKUP_TIMER))
	echo "new Ajax.PeriodicalUpdater('backup_info', 'script/status.php', { method: 'post', frequency: 60, parameters: {c: 'backup_info'}});";

if (isset($STATUS_APCUPSD))
	echo "new Ajax.PeriodicalUpdater('usv_status', 'script/status.php', { method: 'post', frequency: 60, parameters: {c: 'usv_status'}});";
?>



</script>

<!-- <h3><u>Serverstatistiken</u></h3> -->

<table width="100%" border="0">
	<tr>
		<!-- left -->
		<td valign="top" align="left" rowspan="2" width="27%">
			<span id="basic_info"></span>
			<span id="hd_info"></span>
		</td>
		
		<!-- right -->
		<td valign="top" align="left">
			<table rowspan="2" width="100%">
				<tr>
					<td valign="top"><span id="inet_info"></span></td>
					<?php if (isset($STATUS_BACKUP_TIMER)) echo '<td valign="top"><span id="backup_info"></span></td>'; ?>
					<?php if ($STATUS_APCUPSD == true) echo '<td valign="top"><span id="usv_status"></span></td>'; ?>
				</tr>
			</table>
			<span id="capacity_info"></span>
		</td>
	</tr>
</table>

<td align="right"><div id="uptime"></div></td>