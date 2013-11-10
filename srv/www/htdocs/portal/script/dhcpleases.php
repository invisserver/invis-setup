<?php
/* 
 * script/dhcpleases.php v1.0
 * AJAX script, listing all active MAC - IP entrys in /var/lib/dhcp/dhcpd.leases
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
if (!isset($_COOKIE['invis'])) die();

	$raw = file_get_contents('/var/lib/dhcp/db/dhcpd.leases');
	$pattern_ip = '([0-9]{1,3}\\.){3}[0-9]{1,3}';
	$pattern = "/lease ($pattern_ip) \\{.*?\\}$/ms";
	$table = array();
	preg_match_all($pattern, $raw, $matches);
	foreach ($matches[0] as $match) {
		if (preg_match('/.*binding state active;$.*/ms', $match)) {
			preg_match("/lease ($pattern_ip) \\{/", $match, $ip_match);
			$ip = $ip_match[1];
			
			preg_match('/([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}/', $match, $mac_match);
			$mac = $mac_match[0];
			
			$foo = array('mac' => $mac, 'ip' => $ip);
			$table[$mac] = $foo;
		}
	}

	echo json_encode(array_values($table));
?>
