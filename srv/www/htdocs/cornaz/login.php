<?php
# CorNAz
# Script zur Manipulation der Fetchmail Steuerdatei .fetchmailrc
# Author Stefan Schaefer email: st-schaefer@fsproductions.de
# (c) FSP Computer & Netzwerke May 2008,2014
# License: GPLv3

//Konfiguration einbinden
include ("/etc/invis/portal/config.php");

//Session
session_start();
session_name("cornaz");
#echo session_id();

#Session und Umgebungsvariablen übernehmen
$corprogram = $_SESSION["corprogram"];


#Formularvariablen übernehmen
$corusername = $_POST["username"];
$corpassword = $_POST["password"];

$_SESSION["corusername"] = $corusername;
$_SESSION["corpassword"] = $corpassword;


# Login am IMAP-Server
$login = @imap_open("{localhost:143/tls/novalidate-cert}INBOX", $corusername, $corpassword);
if ($login == false) {
	echo "<p><center><b>$corprogram</b></center><p>";
	echo "<hr size=\"1\" noshade width=\"300\" center><p>";
	echo "<center>Unbekannter Benutzername oder falsches Passwort</center><p>";
	echo "<center><a href=\"$COR_WEBSERVER" . "cornaz\">Zurück zur Startseite</a></center><p>";
	echo "<hr size=\"1\" noshade width=\"300\" center><p>";
} else {
	header("Location: $COR_WEBSERVER" . "cornaz/base.php");
}