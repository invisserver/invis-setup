<?php

$account = $_REQUEST["account"];
# Verbindung zum LDAP Server aufbauen
$ditcon=ldap_connect("$corldaphost");  // Annahme: der LDAP Server befindet
                               	    // sich auf diesem Host
# LDAP Protokoll auf Version 3 setzen
if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    echo "Kann das Protokoll nicht auf Version 3 setzen";
# Am LDAP per SimpleBind anmelden
if ($ditcon) {
    // bind mit passendem dn für aktulisierenden Zugriff
    $dn=("uid=$corusername,$corbasedn");
    $r=ldap_bind($ditcon,$dn,"$corpassword");
	// Löschen eines Mail-Accounts
	$dn2 = ("fspExtMailAddress=$account,uid=$corusername,$corbasedn");
	ldap_delete($ditcon, $dn2);
    ldap_close($ditcon);
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

//Status wechseln um neuen Account aufzunehmen
if ( $status == "Anwesend" ) {
		$n = count($fetchmailrc_b);
	$i = 0;
	foreach ($fetchmailrc_b as $key){
		$unx = strlen(strstr($key, "$corusername"))-1;
		$nx = strlen(chop($key)) - $unx;
		if (substr(chop($key), $nx, $un) == $corusername) {
		unset ($fetchmailrc_b[$i]);
	}
	$i++;
	}
		$fh = fopen("/var/cornaz/build/.fetchmailrc","w+");
	foreach ($fetchmailrc_b as $zeile) {
		fwrite ($fh, "$zeile");
	}
	fclose($fh);
	exec ("sudo /var/cornaz/bin/fetchcopy");
	# Verbindung zum LDAP Server aufbauen
	$ditcon=ldap_connect("$corldaphost");
	# LDAP Protokoll auf Version 3 setzen
	if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
		echo "Kann das Protokoll nicht auf Version 3 setzen";
		# Am LDAP per SimpleBind anmelden
		if ($ditcon) {
    			// bind mit passendem dn für aktulisierenden Zugriff
			$dn=("uid=$corusername,$corbasedn");
  			$r=ldap_bind($ditcon,$dn, "$corpassword");
			$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
			$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
			$sr=ldap_search($ditcon, $dn, $filter, $justthese);
			$entries = ldap_get_entries($ditcon, $sr);
			#	print $entries["count"]." Einträge gefunden<p>";
			ldap_close($ditcon);
		} else {
			echo "Verbindung zum LDAP Server nicht möglich!";
		}
		# Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
		array_shift($entries);
		$i=0;
		foreach ($entries as $zugangsdaten) {
			$fh = fopen("/var/cornaz/build/.fetchmailrc","a");
			$Server = $entries[$i]["fspextmailserver"][0];
			$Proto = $entries[$i]["fspextmailproto"][0];
			$Extuser = $entries[$i]["fspextmailusername"][0];
			$Passwd = $entries[$i]["fspextmailuserpw"][0];
			$Opts = $entries[$i]["fspmailfetchopts"][0];
			$zeile = ("poll $Server proto $Proto user $Extuser pass $Passwd is $corusername $Opts\n");
			fwrite($fh, "$zeile");
			fclose($fh);
			$i++;
		}
		exec ("sudo /var/cornaz/bin/fetchcopy");
		$ausgabe = "<b>Status:</b> Das regelmäßige Abrufen Ihrer eMails wurde für folgende Adressen aktiviert:<p>";
		$i=0;
		foreach ($entries as $zugangsdaten) {
			$Address = $entries[$i]["fspextmailaddress"][0];
			$ausgabe = "$ausgabe <b>$Address</b><p>";
			$i++;
	}

}

#Info Zeile
$margin = "Mailkonten";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p>
<p><center>Der Account <b>$account</b> wurde gelöscht.</center></p>
<p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);
?>