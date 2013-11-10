<?php
//Eingabeformular für neuen Mail-Account

$mpvendor = $_REQUEST['mpvendor'];

if ( $mpvendor == "sonstiger" ) {
	$code="<SELECT NAME=\"protokoll\" SIZE=\"1\">
			<OPTION VALUE=\"pop3\" CHECKED>POP3
			<OPTION VALUE=\"pop3s\">POP3s
			<OPTION VALUE=\"imap\">IMAP
			<OPTION VALUE=\"imaps\">IMAPS
			</SELECT>";
} else {
	// Alle Mailprovider aus LDAP holen
	$mp = new mailprovider();
	$mpproto = $_REQUEST['proto'];
	$ldapbinddn = "uid=$corusername,$corbasedn";
	$result = $mp->readmailprovider($mpvendor,$ldapbinddn,$corpassword,$basedn,$corldaphost);
	$text1 = "Gewählter Mailprovider: <font color=\"#EE4000\"><b>$mpvendor</b></font>";
	$text3 = "Schema der Benutzerkennung für den gewählten Mailprovider: <font color=\"#EE4000\"><b>$mp->mpusername</b></font>";
	if ( $mpproto == "imap" ) {
		if ( isset($mp->mpimapserver) ) {
			$mailserver = $mp->mpimapserver;
			if ( $mp->mpimapssl == TRUE ) {
				$proto = "imaps";
			} else {
				$proto = "imap";
			}
		} else {
			echo $mailserver = $mp->mppopserver;
			if ( $mp->mppopssl == TRUE ) {
				$proto = "pops";
			} else {
				$proto = "pop";
			}
			$text2 = "Leider unterstützt Ihr Mailprovider kein <font color=\"#EE4000\"><b>IMAP</b></font>.<br>Liegen Ihnen andere Informationen vor können Sie Mailserver und Protokoll von Hand ändern.<br>";
		}
	} else {
			$mailserver = $mp->mppopserver;
			if ( $mp->mppopssl == TRUE ) {
				$proto = "pop3s";
			} else {
				$proto = "pop3";
			}
	}
	$code = "<input type= \"text\" name=\"protokoll\" value=\"$proto\" size=\"6\">";
}


#Info Zeile
$margin = "Mail Account";
$info = "Über diese Seite können Sie Ihren Server veranlassen weitere externe Postfächer abzurufen.<br> Alle darin eingehenden Mails werden Ihrem lokalen Postfach <font color=\"#EE4000\">$corusername@$corlocalmaildomain</font> zugeordnet.<p>
	<b>Zur Einrichtung benötigen Sie folgende Informationen:</b><br>
	Ihre eMail-Adresse, die Benutzerkennung für das Postfach und das zugehörige Passwort.<p>
	<b>Zusatzinformationen:</b><br>$text1<br>
	$text2$text3<br><font color=\"#EE4000\">Alle erforderlichen Informationen erhalten Sie von Ihrem Mail-Provider.</font>";
site_info($margin, $info);



// Formular oeffnen
$script = "./base.php";
open_form($script);

$margin = ("Zugangsdaten");
$inhalt_s1 = array("externe eMail-Adresse: <br> <input type=\"text\" size=\"30\" name=\"extaddress\">","100");
$inhalt_s2 = array("Server: <br> <input type= \"text\" name=\"mailserver\" value=\"$mailserver\" size=\"20\">","100");
$inhalt_s3 = array("Protokoll: <br> $code ","40");
$inhalt_s4 = array("Benutzerkennung: <br> <input type=\"text\" size=\"15\" name=\"kennung\">","100");
$inhalt_s5 = array("Passwort: <br> <input type=\"password\" size=\"12\" name=\"extpasswd\">","100");
$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3, $inhalt_s4, $inhalt_s5);
table_row_n($val_n, $margin);


echo "<input type=\"hidden\" name=\"file\" value=\"excreateacc.php\" />\n";
# Submit und Reset
$val = "Account anlegen";
submit_row($val);

#Formular schliessen
close_form();
?>