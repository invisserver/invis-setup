<?php
# Oeffnet eine vorhandene Nachrichtendatei und uebergibt diese in Formular zur Nachrichtenbearbeitung.

//Konfiguration einbinden
include ("./inc/config.inc.php");

// Dateinamen einlesen
$myfile = $_FILES['myfile']['tmp_name'];

session_start();
session_name("cornaz");
#echo session_id();
session_register("mailsubject");
session_register("mailbody");

$filehandle = fopen($myfile, "r");
$mailsubject = fgets($filehandle,10000);
$mailsubject = str_replace("Subject: ","",$mailsubject);
#echo $subject;
#echo "<p>Ende der ersten Zeile <p>";
$mailbody = fread($filehandle, 4096);
#echo "<p>$mail";
fclose($filehandle);

$_SESSION['mailsubject'] = $mailsubject;
$_SESSION['mailbody'] = $mailbody;

header ("Location: $COR_WEBSERVER" . "cornaz/base.php?file=invacationmsg.php");
?>