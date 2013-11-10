<?php
/* 
 * inc/admin.section.php v1.0
 * portal drop-in, administration site, user/group/host and links for external tools
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2009 "wurzel" -- anonyme Spende aus unserem Forum -- Danke dafuer
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

	if (!isset($CONF)) die;
	require_once('ldap.php');
?>

<table id='admin-table' cellpadding='0' cellspacing='0'>
	<tr>
		<td class='admin-menu'>
			<b style="color: #b0b0b0;">lokale Tools</b><br />
			<ul>
				<li onclick="invis.request('script/ajax.php', userListResponse, {c: 'user_list'});" title="Benutzer, juhu!!">Benutzer</li>
				<li onclick="invis.request('script/ajax.php', groupListResponse, {c: 'group_list'});" title="Gruppen ... naja">Gruppen</li>
				<li onclick="invis.request('script/ajax.php', hostListResponse, {c: 'host_list'});" title="PCs ... ach hör doch auf ...!!">PCs</li>
			</ul>
			<br /><b style="color: #b0b0b0;">externe Tools</b><br />
			<ul>
<?php

echo '<li onclick="window.location=\'' . $_SERVER['SCRIPT_NAME'] . '?sn=admin\'"><u>Kurzinfos</u></li>';
// 0:guest, 1:user, 2:admin
$usertype = 0;
if (isset($USER_DATA)) $usertype = 1;
if ($USER_IS_ADMIN) $usertype = 2;

$conn = connect();
$bind = bind($conn);

$server = isset($_SERVER['HTTPS']) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];
// ab hier von "wurzel" -- Links im Adminbereich mit abweichenden Ports funktionieren jetzt.
$serverNoPort = isset($_SERVER['HTTPS']) ? 'https://'.$_SERVER['SERVER_ADDR'] : 'http://'.$_SERVER['SERVER_ADDR'];

    $result = search($conn, "ou=iPortal,ou=Informationen,$LDAP_SUFFIX", 'iportentryposition=administration');
    if ($result) {
            for($i = 0; $i < $result['count']; $i++) {
                    $entry = cleanup($result[$i]);
                    if ($entry['iportentryactive'] == 'FALSE') continue;
                    $type = $entry['iportentrypriv'];
                    if (strstr($entry['iportentryurl'], '[servername]') === false)
                            $url = 'http://'.$entry['iportentryurl'];
                    elseif (strstr($entry['iportentryurl'], '[servername]:') !== false)
                    $url = str_replace('[servername]', $serverNoPort, $entry['iportentryurl']);

                    else
                            $url = str_replace('[servername]', $server, $entry['iportentryurl']);
                    //echo '<li onclick="window.location.href=\''. $url . '\'">' . $entry['iportentrybutton'] . '</li>';
                    echo "<li onclick=\"window.open('$url', '_blank')\">" . $entry['iportentrybutton'] . '</li>';
            }
    } else {
            echo ldap_error($conn);
    }
// bis hier von "wurzel"
?>
			</ul>

			
		</td>
		<td id='admin-content'>
			<div id='admin-content-title'>Titel</div>
			<div id='admin-content-content'>
				<h2>Kurzinfos</h2>
<?php
	
$result = search($conn, "ou=iPortal,ou=Informationen,$LDAP_SUFFIX", 'iportentryposition=administration');
if ($result) {
	for($i = 0; $i < $result['count']; $i++) {
		$entry = cleanup($result[$i]);
		if ($entry['iportentryactive'] == 'FALSE') continue;
		$type = $entry['iportentrypriv'];
		/*
		if (strstr($entry['iportentryurl'], '[servername]') === false)
			$url = 'http://'.$entry['iportentryurl'];
		else
			$url = str_replace('[servername]', $server, $entry['iportentryurl']);
		*/
		echo '<p style="text-align: justify;"><b>' . $entry['iportentrybutton'] . ':</b> <span style="font-size: 0.9em;">' . $entry['iportentrydescription'] . '</span></p>';
	}
} else {
	echo ldap_error($conn);
}
	
?>
			</div>
		</td>
	</tr>

</table>

<?php
?>
