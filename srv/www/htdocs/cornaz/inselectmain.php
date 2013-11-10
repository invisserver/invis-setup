<?php
# Script zur Auswahl der primären email-Adresse
# Dieses Script liest alle fetchmail-Accounts des Users $Username
# aus dem LDAP Verzeichnis und listet sie auf. Sie koennen
# dann die gewünschte Adresse auswählen.
	# Verbindung zum LDAP Server aufbauen
	$ditcon=ldap_connect("$corldaphost");
	# LDAP Protokoll auf Version 3 setzen
	if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    		echo "Kann das Protokoll nicht auf Version 3 setzen";
	# Am LDAP per SimpleBind anmelden
	if ($ditcon) {
    	// bind mit passendem dn für aktulisierenden Zugriff
    		//echo $basedn;
		$dn=("uid=$corusername,$corbasedn");
    		$r=ldap_bind($ditcon,$dn,"$corpassword");
		$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
		//$filter="(fspLocalMailAddress=$username*)";
		$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
		$sr=ldap_search($ditcon, $dn, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
		//print $entries["count"]." Einträge gefunden<p>";
		ldap_close($ditcon);
	} else {
    		echo "Verbindung zum LDAP Server nicht möglich!";
	}
	# Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
	array_shift($entries);
	#Info Zeile
	$margin = "Ihre Mailkonten";
	$info = "<font size=\"-1\">Die folgende Liste zeigt alle für Sie eingerichteten Mailkonten.<br>Über die Schaltfläche \"Löschen\" können Sie einzelne Konten wieder aus der Serverkonfiguration entfernen.<br>Es gehen dabei keine bereits empfangenen Mails verloren.</font>";
	site_info($margin, $info);
	$i=0;
	foreach ($entries as $val) {
		#Formular oeffnen
		$script = "./delacc.php";
		open_form($script);
		$Adresse = $entries[$i]["fspextmailaddress"][0];
		$Server = $entries[$i]["fspextmailserver"][0];
		$extuser = $entries[$i]["fspextmailusername"][0];
		$margin = ("Löschen?");
		$inhalt_s1 = array("<input type=submit value=Löschen>","70");
		$inhalt_s2 = array("<input type=hidden name=account value=$Adresse>User: <b>$corusername</b>","100");
		$inhalt_s3 = array("Account: <b>$Adresse</b> - $Server - $extuser<p>","600");
		$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
		table_row_n($val_n, $margin);
		$i++;
		#Formular schliessen
		close_form();
	}


?>
