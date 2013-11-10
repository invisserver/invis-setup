<?php

//Strings bereinigen
$vergleichswert = stripslashes($_POST["vergleichswert"]);
$information = stripslashes($_POST["information"]);
$description = stripslashes($_POST["description"]);

// Variablen übernehmen
$filtername=$_POST["filtername"];
$bedingung=$_POST["bedingung"];
$attribute=$_POST["attribute"];
$aktion=$_POST["aktion"];
$component=$_POST["component"];

// Keine Ahnung :-(
if ($component == "body") {
	switch ($attribute) {
		case "text":
			$attribute=":content \" text/plain\"";
		break;
		case "html":
			$attribute=":content \" text/html\"";
		break;
		case "raw":
			$attribute=":raw";
		break;
}}

// Anlegen eines Filters im LDAP Verzeichnis

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
	// Daten vorbereiten
    $filter["fspMailFilterName"]="$filtername";
    $filter["fspMailFilterDescription"]="$description";
    $filter["fspMailFilterTestCommand"]="$component";
    $filter["fspMailFilterTestAttribute"]="$attribute";
    $filter["fspMailFilterComparativeOperator"]="$bedingung";
    $filter["fspMailFilterComparativeValue"]="$vergleichswert";
    $filter["fspMailFilterAction"]="$aktion";
    $filter["fspMailFilterInfo"]="$information";
    $filter["objectclass"]="top";
    $filter["objectclass"]="fspMailFilter";
    $dn2 = ("fspMailFilterName=$filtername,$dn");

    // hinzufügen der Daten zum Verzeichnis
    $r=ldap_add($ditcon, $dn2, $filter);
    ldap_close($ditcon);
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

$margin = "Ihre Filterdaten";
$info = "<center><b>Es wurde folgender Filter angelegt:</b></center><br>
<center>Beschreibung: <font color=\"#EE4000\"><b>$description</b></font></center><br>
<center>Name des Filters: <font color=\"#EE4000\"><b>$filtername</b></font></center><br>
<center>Gewählte email Komponente: <font color=\"#EE4000\"><b>$component</b></font></center><br>
<center>Gewähltes Attribut: <font color=\"#EE4000\"><b>$attribute</b></font></center><br>
<center>Vergleichsbedingung: <font color=\"#EE4000\"><b>$bedingung</b></font></center><br>
<center>Vergleichswert: <font color=\"#EE4000\"><b>$vergleichswert</b></font></center><br>
<center>Gewählte Aktion: <font color=\"#EE4000\"><b>$aktion</b></font></center><br>
<center>Aktionsinformationen: <font color=\"#EE4000\"><b>$information</b></font></center><br>
<hr size=\"1\" noshade width=\"300\">";
site_info($margin, $info);



?>
