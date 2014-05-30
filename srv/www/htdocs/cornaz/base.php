<?php
# CorNAz
# Script zur Manipulation der Fetchmail Steuerdatei .fetchmailrc
# Author Stefan Schaefer email: st-schaefer@fsproductions.de
# (c) 2008,2014 Stefan Schaefer - FSP Computer & Netzwerke
# (c) 2012 Ingo Goeppert - invis-server.org
# License: GPLv3

//Includes einbinden
include ("./inc/html.inc.php");
include ("/etc/invis/portal/config.php");
include ("./inc/classes.inc.php");

//Session
session_start();
session_name("cornaz");

#Session und Umgebungsvariablen übernehmen
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
	$fetchmailrc_b = file ("$COR_FETCHMAILRC_BUILD");
	$stat = 0;
	// Statusüberprüfung
	foreach ($fetchmailrc_b as $zeile) {
		$unx = strlen(strstr($zeile, "$corusername"))-1;
		// echo "$unx<br>";
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
		if (file_exists ("$COR_PATH/vacation/$corusername.binweg")) {
			$status="Urlaub";
		}}

	// Oeffnen der neuen Seite
	$sitename = "eMail Accounts verwalten";

	site_head($corprogram, $sitename, $COR_BG_COLOR);

	//Inhalt einfügen
	include ("./$inhalt");
	
	// Seite schliessen
	$cormainpage = "<a href=\"$COR_WEBSERVER" . "cornaz/base.php\">Hauptmenü</a>";
	//$corwebserverlink = "<a href=\"$COR_WEBSERVER\"><div allign=\"right\">invis Portal</div></a>";

	site_end($cormainpage, $PORTAL_FOOTER, "&nbsp;" );
} else {
	header("Location: $COR_WEBSERVER" . "cornaz/");
}

?>