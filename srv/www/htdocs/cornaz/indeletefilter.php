<?php
# Script zum Loeschen von Mail-Filtern
# Dieses Script liest alle MailFilter des Users $User
# aus dem LDAP Verzeichnis und listet sie auf. Sie koennen
# dann einzelne zum Loeschen ausgewaehlt werden.

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
    $r=ldap_bind($ditcon,$dn, "$corpassword");
	$filter="(&(fspMailFilterName=*))";
	$justthese = array( "fspMailFilterName", "fspMailFilterAction", "fspMailFilterComparativeValue");
	$sr=ldap_search($ditcon, $dn, $filter, $justthese);
	$entries = ldap_get_entries($ditcon, $sr);

	
#	print $entries["count"]." Einträge gefunden<p>";

    ldap_close($ditcon);
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}
# Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
array_shift($entries);

#Info Zeile
$margin = "Mail-Filter löschen";
$info = "Über diese Seite können Sie Ihre vorhandenen Mail-Filter löschen<br><font color=\"red\"><b>Achtung: Es erfolgt keine weitere Nachfrage!</b></font>";
site_info($margin, $info);
$i=0;
foreach ($entries as $val) {
#Formular oeffnen
$script = "./base.php";
open_form($script);
echo "<input type=\"hidden\" name=\"file\" value=\"exdeletefilter.php\" />\n";
$Filter = $entries[$i]["fspmailfiltername"][0];
$Action = $entries[$i]["fspmailfilteraction"][0];
$Vergleichswert = $entries[$i]["fspmailfiltercomparativevalue"][0];

$margin = ("Löschen?");
$inhalt_s1 = array("<input type=submit value=Löschen>","80");
$inhalt_s2 = array("<input type=hidden name=filter value=$Filter>User: <font color=\"#EE4000\"><b>$corusername</b></font>","200");
$inhalt_s3 = array("Filtername: <b>$Filter</b> - Aktion: <b>$Action</b> - Suchbegriff: <b>$Vergleichswert</b><p>","1000");
$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
table_row_n($val_n, $margin);

$i++;
#Formular schliessen
close_form();}

?>
