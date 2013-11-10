<?php
	// Hauptseite
	#Info Zeile
	$margin = "";
	$info = "<p><b>Sie befinden sich auf der Haupseite von CorNAz.</b><br>
	Diese Seite ist die Schaltzentrale für alle Funktionen des Programms.</p>";
	site_info($margin, $info);
	//$localaddress="$corusername@$corlocalmaildomain";
	// Status ausgeben
	$ditcon=ldap_connect("$corldaphost");
	# LDAP Protokoll auf Version 3 setzen
	if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    		echo "Kann das Protokoll nicht auf Version 3 setzen";
	# Am LDAP per SimpleBind anmelden
	if ($ditcon) {
    		// bind mit passendem dn für aktulisierenden Zugriff
		$dn=("uid=$corusername,$corbasedn");
		$r=ldap_bind($ditcon,$dn, "$corpassword");
		$filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
		$justthese = array("fspMainMailAddress");
		$sr=ldap_search($ditcon, $dn, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
		//print $entries["count"]." Einträge gefunden<p>";
	}
	$mainsendaddress = $entries[0]["fspmainmailaddress"][0];
	$margin = "Status";
	$info = "<font size=\"-1\"><b>Sie sind angemeldet als Benutzer: <font color=\"#EE4000\">$corusername</font><br>Ihre lokale Mail-Adresse lautet: <font color=\"#EE4000\">$corusername@$corlocalmaildomain</font><br>Ihre derzeitige Absendeadresse lautet: <font color=\"#EE4000\">$mainsendaddress</font><br>Ihr aktueller Status ist: <font color=\"#EE4000\">$status</font></b></font><hr>";

	site_info($margin, $info);

	// Schaltflächenleiste 1 für Funktionen
	$margin = "Funktionen";
	$script = "base.php";
	$val1 = array("   Abwesend   ", "exabsent.php", "lightgrey");
	$val2 = array("Urlaubsbeginn", "invacationmsg.php", "lightgrey");
	$val3 = array("Konto hinzufügen", "increateacc.php", "lightgrey");
	$val4 = array("Filter anlegen", "infilterstep1.php", "lightgrey");
	$val_n = array($val1,$val2,$val3,$val4);
	button_row_n($val_n, $margin, $script);

	// Schaltflächenleiste 2 für Funktionen
	$margin = "&nbsp;";
	$script = "base.php";
	$val1 = array("   Anwesend   ", "expresent.php", "lightgrey");
	$val2 = array(" Urlaubsende ", "exvacend.php", "lightgrey");
	$val3 = array("Konto entfernen", "indeleteacc.php", "lightgrey");
	$val4 = array("Filter löschen", "indeletefilter.php", "lightgrey");
	$val_n = array($val1,$val2,$val3,$val4);
	button_row_n($val_n, $margin, $script);

	$margin = "";
	$info = "<hr>";
	site_info($margin, $info);

	// Verbindung zum LDAP Server aufbauen
	//$ditcon=ldap_connect("$corldaphost");
	// LDAP Protokoll auf Version 3 setzen
	//if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    	//	echo "Kann das Protokoll nicht auf Version 3 setzen";
	// Am LDAP per SimpleBind anmelden
	if ($ditcon) {
    	// bind mit passendem dn für aktulisierenden Zugriff
    		//echo $basedn;
		$dn=("uid=$corusername,$corbasedn");
    		$r=ldap_bind($ditcon,$dn,"$corpassword");
		$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
		//$filter="(fspLocalMailAddress=$username*)";
		$justthese = array( "fspExtMailAddress", "fspLocalMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
		$sr=ldap_search($ditcon, $dn, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
		//print $entries["count"]." Einträge gefunden<p>";
		ldap_close($ditcon);
	} else {
    		echo "Verbindung zum LDAP Server nicht möglich!";
	}
	// Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
	array_shift($entries);
#Info Zeile
$margin = "Mailkonten";
$info = "<font size=\"-1\">Die folgenden Liste zeigt alle für Sie eingerichteten Mailkonten an. Sie können daraus die Email-Adresse wählen, die für den Mailversand verwendet werden soll.</font>";
site_info($margin, $info);
$i=0;
foreach ($entries as $val) {
	//Formular oeffnen
	$script = "./base.php";
	open_form($script);
	echo "<input type=\"hidden\" name=\"file\" value=\"exselectmain.php\" />\n";
	$Adresse = $entries[$i]["fspextmailaddress"][0];
	$Server = $entries[$i]["fspextmailserver"][0];
	$extuser = $entries[$i]["fspextmailusername"][0];
	$localaddress = $entries[$i]["fsplocalmailaddress"][0];
	$margin = ("&nbsp;");
	$inhalt_s1 = array("<input type=submit value=Auswählen>","60");
	$inhalt_s2 = array("<input type=hidden name=account value=$Adresse><input type=hidden name=localaddress value=$localaddress>","10");
	$inhalt_s3 = array("Account: <font color=\"#EE4000\"><b>$Adresse</b></font> - $Server - $extuser<p>","600");
	$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
	table_row_n($val_n, $margin);
	$i++;
	//Formular schliessen
	close_form();
}

?>