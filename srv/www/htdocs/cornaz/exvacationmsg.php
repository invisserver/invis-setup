<?php

$mysubject = $_POST['mysubject'];
$mymessage = $_POST['mymessage'];
#Strings bereinigen
$subject = stripslashes($mysubject);
$message = stripslashes($mymessage);

# Erzeugen einer privaten Urlaubsantwort
$margin = "Ihre Abwesenheitsnachricht";
	$info = "<p><hr size=\"1\" noshade width=\"300\" center></p><center><b>Ihr Betreff / Subject lautet:</b><p>$subject<p></center><center><b>Ihre Nachricht lautet:</b><p>$message<p></center><hr size=\"1\" noshade width=\"300\" center>";
	site_info($margin, $info);

# Erzeugen der Datei
$datei = "/var/lib/cornaz/vacation/$corusername.msg";
	if (!file_exists ("/var/lib/cornaz/vacation/$corusername.msg")) {
		$fp = fopen ($datei, "w");
		$inhalt = utf8_decode("Subject: $mysubject\n$mymessage");
		fputs ($fp, "$inhalt");
		fclose($fp);

	} else {
		if (!is_writeable($datei)){
		echo "pech gehabt - Datei in Bearbeitung<p>";
	} else {
		$fp = fopen ($datei, "w");
		$inhalt = utf8_decode("Subject: $mysubject\n$mymessage");
		fputs ($fp, "$inhalt");
		fclose($fp);
	}}
	$datei = "/var/lib/cornaz/vacation/$corusername.forward";
	$vorgang = "Sie treten Ihren Urlaub <b>an</b>. Wirklich?";
	$fp = fopen ($datei, "w");
	$inhalt = "\\$corusername, \"|/usr/bin/vacation $corusername\"";
	fputs ($fp, "$inhalt");
	fclose($fp);
	exec ("sudo /var/lib/cornaz/bin/holiday");
?>
