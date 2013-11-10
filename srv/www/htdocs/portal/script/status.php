<?php

/* script/status.php v1.0
 * AJAX script, displaying several server status messages/numbers
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2010 Stefan Schäfer, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
	require_once('../config.php');

// check if request comes from internal address
$EXTERNAL_ACCESS = (substr($_SERVER['REMOTE_ADDR'], 0, strripos($_SERVER['REMOTE_ADDR'], '.')) != $DHCP_IP_BASE);

// Script not allowed from external without login!
if ($EXTERNAL_ACCESS && !isset($_COOKIE['invis'])) die();

	
	$CMD = $_POST['c'];
	
if ($CMD == 'basic_info') {
	echo '<b>Servername:</b><br />' . shell_exec('hostname -f') . '<br />';
	echo '<span style="font-size: 0.7em;">(' . trim(shell_exec('uname -r')) .')</span><br /><br />';
	
	echo '<b>Serverzeit:</b><br />' . shell_exec('date +"%d.%m.%Y, %H:%M"') . '<br /><br />';
	
	$uptime = intval(shell_exec('cat /proc/uptime | cut -d"." -f1'));
	
	// 60 = 1m
	// 3600 = 1h
	// 86400 = 1d
	
	$up_d = floor($uptime / 86400);
	$up_h = floor(($uptime - $up_d * 86400) / 3600);
	$up_m = floor(($uptime - $up_d * 86400 - $up_h * 3600) / 60);
	
	$uptime_string = "$up_d Tage, $up_h Stunden, $up_m Minuten";
	echo '<b>Uptime:</b><br />' . $uptime_string . '<br /><br />';			// neu
//	echo '<b>Uptime:</b><br />' . shell_exec('uptime') . '<br /><br />';	// alt
}
elseif ($CMD == 'inet_info'){
	$file_inet = file('/var/spool/results/inetcheck/inetcheck');
	echo '<b>Internet:</b><br />';
	echo '<span style="font-size: 0.8em;">Zeit: ' . $file_inet[0] . ' Uhr</span><br/>';
	echo 'Status: ';
	switch(intval($file_inet[1])) {
		case 0: echo '<b style="color: green;">online</b>'; break;
		case 1: echo '<b style="color: orange;">kein DNS</span></b>'; break;
		case 2: echo '<b style="color: orange;">schlechte Verbindung</span></b>'; break;
		case 3: echo '<b style="color: red">offline</b>'; break;
	}
	echo '<br />';
	echo '<span style="font-size: 0.8em;">IP: <b>' . ((isset($file_inet[2]))?$file_inet[2]:'-') . '</b></span>';
	echo '<br /><br /><br /><br />';
}
elseif ($CMD == 'hd_info') {
	$file_raid = file('/var/spool/results/diskchecker/status');
	echo '<b>Festplatten:</b><br />';
	$raid_error = false;
	for ($i = 1; $i < count($file_raid); $i++) {
		// RAID or HD
		$data = explode(' ', $file_raid[$i]);
		$tmp = substr($data[0], 0, 2);
		
		if ($tmp == 'md') {
			echo 'RAID-Verbund <b><i>' . $data[0] . '</i></b>';
			if ($data[1] == 'nOK') {
				$raid_error = true;
				echo ': <b style="font-size: 0.8em; color: red;">' . $data[2] . '</b>';
			} else
				echo ': <b style="font-size: 0.8em; color: green;">' . $data[1] . '</b>';
		} else if ($tmp == 'sd') {
			echo 'Festplatte <b><i>' . $data[0] . '</i></b>';
			if ($data[1] == 'OK') {
				echo ': <b style="font-size: 0.8em; color: green;">' . $data[1]. ' ' . $data[2] . '°C</b>';
			} else {
				$raid_error = true;
				echo ': <b style="font-size: 0.8em; color: red;">Smart-Fehler ' . $data[2] . '°C</b>';
			}
		} else echo $tmp;
		echo '<br />';
	}
	echo '<span style="font-size: 0.8em;">Zeit: ' . $file_raid[0] . ' Uhr</span><br />';
	if ($raid_error) {
		echo '<b style="font-size: 0.8em; color: red;">Ein Fehler ist aufgetreten, bitte wenden Sie sich umgehend an Ihren Administrator!</b><br />';
	}
}
elseif ($CMD == 'capacity_info') {
	echo '<b>Festplattenauslastung:</b>';
	echo '</td></tr><tr><td valign="top" align="center">';
	echo '<table border="0" style="font-size: 0.9em; border: 1px solid #e0e0e0;">';
	echo '<tr><th>Verzeichnis</th><th align="center">% belegt</th><th align="center">GB belegt</th><th align="center">GB gesamt</th></tr>';
	foreach ($STATUS_WATCH_DIRS as $dir) {
		echo '<tr>';
		$total = disk_total_space($dir) / 1024 / 1024 / 1024;
		$free = disk_free_space($dir) / 1024 / 1024 / 1024;
		$used = $total - $free;
		$used_factor = $used / $total;
		$used_percent = $used_factor * 100;
		
		$max = 550;
		
		$red = dechex(128 + 127 * $used_factor);
		$green = dechex(255 - 127 * $used_factor);
		
		echo "<th>$dir</th>" .'
			<td align="center">' . round($used_percent, 2) . '</td>
			<td align="center">' . round($used, 2) . '</td>
			<td align="center">' . round($total, 2) . '</td>';
		
		echo"<tr>
				<td colspan='4'>
					<div style='width: " . $max . "px; border: 1px solid #000000'>
						<div style='padding: 2px; width: ".($max * $used_factor)."px; border-right: 1px solid black; background-color: #".$red.$green."55;'>&nbsp;</div>
						</div>
					</td>
			</tr>";
	}
	echo "</table>";
}
elseif ($CMD == 'backup_info') {
	// Status-Datei einlesen
	$file_backup = file('/var/spool/results/backup/status');
	
	$now = time();
	$last = intval($file_backup[0]);
	$diff_days = floor(($now - $last) / (60 * 60 * 24));

	echo '<b>Backup:</b><br>';
	echo '<span style="font-size: 0.9em;"> Zeit: ' . date('d.m.Y, H:i', $last) . '</span><br />';
	// Stefan -- Multiline Results added.
	// Jetzt Zeilen 2 bis X in der Status-Datei durchgehen.
	foreach($file_backup as $num => $line) {
	    if ($num != 0) {
		$line = explode(" ", $line);
		// Achtung hierbei ist "0" ok, da wir ansonsten nicht die exit-codes von rsync ausgeben könnten.
		$backup_state = ($line[1] == 0)? '<b style="color: green; font-size: 0.9em;">Erfolgreich</b>':'<b style="color: red; font-size: 0.9em;">Fehler (Nr: ' .  $line[1] . ')</b>';
		echo '<span style="font-size: 0.9em;"> Status: '. $backup_state . ' Quelle: ' . $line[0] .'</span><br />';
	    }
	}
	// Nächstes Backup 
	if ($diff_days > $STATUS_BACKUP_TIMER) {
		$overdue = ($diff_days - $STATUS_BACKUP_TIMER);
		echo "<span style='font-size: 0.8em; color: red; font-weight: bold;'>Backup $overdue Tage überfällig!</span>";
		}
	else
		echo "<span style='font-size: 0.8em;'>Nächstes Backup in <u>" . ($STATUS_BACKUP_TIMER - $diff_days) . "</u> Tagen</span>";
	echo "<br/>";

	// Ist die Dasiplatte voll?
	$file_diskfull = file('/var/spool/results/backup/full');
	$disk_state = ($file_diskfull[0] < 90)? '<b style="color: green; font-size: 0.8em;">'. $file_diskfull[0] .'</b>':'<b style="color: red; font-size: 0.8em;">'. $file_diskfull[0] .'</b>';
	echo '<span style="font-size: 0.8em;"> Datensicherungsplatte zu '. $disk_state . '% voll.</span><br />';
	echo "<br/>";

}
?>
