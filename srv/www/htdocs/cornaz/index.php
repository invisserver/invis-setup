<?php
# CorNAz
# Script zur Manipulation der Fetchmail Steuerdatei .fetchmailrc
# Author Stefan Schaefer email: st-schaefer@fsproductions.de
# (c) FSP Computer & Netzwerke May 2008
# License: GPLv3

include ("./inc/html.inc.php");
include ("/etc/invis/portal/config.php");

session_start();
session_name("cornaz");

$version = "0.9.10";
$corprogram = "+++ CorNAz $version +++";
$_SESSION["corprogram"] = $corprogram;
$sitename = "CorNAz";

# Oeffnen der neuen Seite
$sitename = "eMail Accounts verwalten";

site_head($corprogram, $sitename, $COR_BG_COLOR);

#Info Zeile
$margin = "";
$info = "<p><b>Dieses Programm dient zur Verwaltung Ihrer eMail Konten.</b></p>
	<p>Sie können Ihren Server anweisen eMails aus externen Postfächern abzurufen,<br>
	Urlaubsbenachrichtigungen erstellen sowie externe Konten hinzufügen oder entfernen.</p>
	<p>Zugang erhalten Sie mit Ihren System-Zugangsdaten.</p>";

site_info($margin, $info);

#Formular oeffnen
$script = "./login.php";
open_form($script);

$margin = ("Zugangsdaten");
unpw($margin);

$val = ("Anmelden");
submit_row($val);
close_form();
//$corwebserverlink = "<a href=\"$COR_WEBSERVER\">invis Portal</a>";

site_end( "&nbsp;", $PORTAL_FOOTER, "&nbsp;" );
