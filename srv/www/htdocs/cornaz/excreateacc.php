<?php
# Dieses Script erzeugt einen neuen fetchmail-Account für den Zugriff auf ein externes
# Postfach. Alle Daten des Accounts werden im lokalem LDAP Verzeichnis unterhalb des
# zugehörigen Users abgelegt.

#Formularvariablen übernehmen
$mailserver=$_POST["mailserver"];
$extaddress=$_POST["extaddress"];
$protokoll=$_POST["protokoll"];
$kennung=$_POST["kennung"];
$extpasswd=$_POST["extpasswd"];
$luser="$corusername@$DOMAIN";

# SSL oder nicht
if ($protokoll == "pop3s" or $protokoll == "imaps") {
	$protokoll = substr($protokoll,0 ,4);
	$fspMailfetchOpts = "here ssl fetchall";
} else {
	$fspMailfetchOpts = "here fetchall";
}

# Verbindung zum LDAP Server aufbauen
$ditcon=ldap_connect("$LDAP_SERVER");
# LDAP Protokoll auf Version 3 setzen
if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    echo "Kann das Protokoll nicht auf Version 3 setzen";

# Am LDAP per SimpleBind anmelden
if ($ditcon) {
    // bind mit passendem dn für aktulisierenden Zugriff
    $dn=("uid=$corusername,$BASE_DN_USER");
    $r=ldap_bind($ditcon,$dn,"$corpassword");
	// Daten vorbereiten
    $account["fspExtMailAddress"]="$extaddress";
    $account["fspExtMailServer"]="$mailserver";
    $account["fspExtMailProto"]="$protokoll";
    $account["fspExtMailUserName"]="$kennung";
    $account["fspExtMailUserPW"]="$extpasswd";
    $account["fspMailfetchOpts"]="$fspMailfetchOpts";
    $account["fspLocalMailAddress"]="$luser";
    $account["objectclass"]="top";
    $account["objectclass"]="fspFetchMailAccount";
    $dn2 = ("fspExtMailAddress=$extaddress,$dn");
    // hinzufügen der Daten zum Verzeichnis
    $r=ldap_add($ditcon, $dn2, $account);
    if ($COR_ZARAFA_ALIAS) {
	$zarafaAlias["zarafaAliases"]="$extaddress";
	$r=ldap_mod_add($ditcon, $dn, $zarafaAlias);
    }
    
	$filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
	$sr=ldap_search($ditcon, $dn, $filter);
	$entries = ldap_get_entries($ditcon, $sr);
	if ($entries["count"] == 0) { 
		// Daten vorbereiten
	    	$account2["fspLocalMailAddress"]="$luser";
    		$account2["fspLocalMailHost"]="$COR_LOCAL_IMAP_SERVER";
    		$account2["fspMainMailAddress"]="$extaddress";
    		$account2["objectclass"]="top";
    		$account2["objectclass"]="fspLocalMailRecipient";
    		$dn2 = ("fspLocalMailAddress=$luser,$dn");
    		// hinzufügen der neuen primär Adresse
    		$r=ldap_add($ditcon, $dn2, $account2);
    		if ($COR_ZARAFA_MAIN_MAIL) {
		    $zarafaMainMail["mail"]="$extaddress";
		    $r=ldap_mod_replace($ditcon, $dn, $zarafaMainMail);
		}
	}
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
		$fh = fopen("$COR_FETCHMAILRC_BUILD","w+");
	foreach ($fetchmailrc_b as $zeile) {
		fwrite ($fh, "$zeile");
	}
	fclose($fh);
	exec ("sudo $COR_PATH/bin/fetchcopy");
	# Verbindung zum LDAP Server aufbauen
	$ditcon=ldap_connect("$LDAP_SERVER");

	# LDAP Protokoll auf Version 3 setzen
	if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
		echo "Kann das Protokoll nicht auf Version 3 setzen";
		# Am LDAP per SimpleBind anmelden
		if ($ditcon) {
    			// bind mit passendem dn für aktulisierenden Zugriff
			$dn=("uid=$corusername,$BASE_DN_USER");
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
			$fh = fopen("$COR_FETCHMAILRC_BUILD","a");
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
		exec ("sudo $COR_PATH/bin/fetchcopy");
		$ausgabe = "<b>Status:</b> Das regelmäßige Abrufen Ihrer eMails wurde für folgende Adressen aktiviert:<p>";
		$i=0;
		foreach ($entries as $zugangsdaten) {
			$Address = $entries[$i]["fspextmailaddress"][0];
			$ausgabe = "$ausgabe <b>$Address</b><p>";
			$i++;
	}

}


$margin = "Ihre Mailkonten";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p><p><center><b>Ihr neuer Zugang wurde mit folgenden Daten angelegt:</b></center></p><p><center>Mail-Server: $mailserver</center></p><p><center>Protokoll: $protokoll</center></p><p><center>Benutzerkennung: $kennung</center></p><p><center>Passwort: $extpasswd</center></p><p><center>Lokale Adresse: $luser</center></p><p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);

?>
