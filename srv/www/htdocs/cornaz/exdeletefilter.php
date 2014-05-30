<?php
// Ausgewaehlte Accounts werden geloescht.

$filter = $_POST["filter"];

// Verbindung zum LDAP Server aufbauen
$ditcon=ldap_connect("$LDAP_SERVER");

// LDAP Protokoll auf Version 3 setzen
if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    echo "Kann das Protokoll nicht auf Version 3 setzen";
// Am LDAP per SimpleBind anmelden
if ($ditcon) {
    // bind mit passendem dn für aktulisierenden Zugriff
    $dn=("uid=$corusername,$BASE_DN_USER");
    $r=ldap_bind($ditcon,$dn,"$corpassword");
	// Löschen eines Mail-Accounts
	$dn2 = ("fspMailFilterName=$filter,uid=$corusername,$BASE_DN_USER");
	ldap_delete($ditcon, $dn2);
    ldap_close($ditcon);
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

$margin = "Mailkonten";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p>
<p><center>Der Filter <font color=\"#EE4000\"><b>$filter</b></font> wurde gelöscht.</center></p>
<p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);

?>