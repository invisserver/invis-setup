<?php
# CorNAz
# Script zur Manipulation der Fetchmail Steuerdatei .fetchmailrc
# Author Stefan Schaefer email: st-schaefer@fsproductions.de
# (c) FSP Computer & Netzwerke May 2008
# License: GPLv3

include ("./inc/html.inc.php");
include ("./inc/config.inc.php");

session_start();
session_name("cornaz");

$version = "0.9.9";
$corprogram = "+++ CorNAz $version +++";
$_SESSION["corprogram"] = $corprogram;
$sitename = "CorNAz";

# Oeffnen der neuen Seite
$sitename = "eMail Accounts verwalten";

site_head($corprogram, $sitename, $corbgcolor);

#Info Zeile
$margin = "";
$info = "<p><b>Dieses Programm dient zur Verwaltung Ihrer eMail Konten.</b></p>
	<p>Sie können Ihren Server anweisen eMails aus externen Postfächern abzurufen,<br>
	Urlaubsbenachritigungen erstellen sowie externe Konten hinzufügen oder entfernen.</p>
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
$corwebserverlink = "<a href=\"$corwebserver\">invis Portal</a>";

site_end( "&nbsp;", $corfooter, $corwebserverlink );
