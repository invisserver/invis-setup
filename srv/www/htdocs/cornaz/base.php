<?php
# CorNAz
# Script zur Manipulation der Fetchmail Steuerdatei .fetchmailrc
# Author Stefan Schaefer email: st-schaefer@fsproductions.de
# (c) 2008 Stefan Schaefer - FSP Computer & Netzwerke
# (c) 2012 Ingo Goeppert - invis-server.org
# License: GPLv3

//Includes einbinden
include ("./inc/html.inc.php");
include ("./inc/config.inc.php");
include ("./inc/classes.inc.php");


//Session
session_start();
session_name("cornaz");
#echo session_id();

#Session und Umgebungsvariablen übernehmen
#$webserver = $_SERVER["SERVER_NAME"];
$corprogram = $_SESSION["corprogram"];


#Formularvariablen übernehmen
$corusername = $_SESSION["corusername"];
$corpassword = $_SESSION["corpassword"];

//Inhaltsdatei ermitteln oder festlegen
if (!isset($_REQUEST['file'])) {
	$inhalt = "inhalt.php";
} else {
	$inhalt = $_REQUEST['file'];
}

if(isset($corpassword)) {
	// Aktuellen Status ermitteln
	$un = strlen($corusername);
	$unx = 0;
	// echo "$un<br>";
	// Einlesen der Datei .fetchmailrc in ein Array
	$fetchmailrc_b = file ("/var/lib/cornaz/build/.fetchmailrc");
	$stat = 0;
	// Statusüberprüfung
	foreach ($fetchmailrc_b as $zeile) {
		$unx = strlen(strstr($zeile, "$corusername"))-1;
		#echo "$unx<br>";
		$n = strlen(chop($zeile)) - $unx;
		if (substr(chop($zeile), $n, $un) == $corusername) {
		$stat = $stat + 1;
		}
	}
	if ($stat >= 1) {
		$status="Anwesend";
	} else {
		$status="Abwesend";
	}
	
	// Anwesend aber trotzdem im Urlaub
	if ($status == "Anwesend") {
		if (file_exists ("/var/lib/cornaz/vacation/$corusername.binweg")) {
			$status="Urlaub";
		}}

	// Oeffnen der neuen Seite
	$sitename = "eMail Accounts verwalten";

	site_head($corprogram, $sitename, $corbgcolor);

	//Inhalt einfügen
	include ("./$inhalt");
	
	// Seite schliessen
	$cormainpage = "<a href=\"$corwebserver" . "cornaz/base.php\">Hauptmenü</a>";
	$corwebserverlink = "<a href=\"$corwebserver\"><div allign=\"right\">invis Portal</div></a>";

	site_end($cormainpage, $corfooter, $corwebserverlink);
} else {
	header("Location: $corwebserver" . "cornaz/");
}

?>