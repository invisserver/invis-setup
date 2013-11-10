<?php
//Eingabeformular für neuen Mail-Account
#Info Zeile
$margin = "Mail Account";
$info = "Über diese Seite können Sie Ihren Server veranlassen weitere externe Postfächer abzurufen.<br> Alle darin eingehenden Mails werden Ihrem lokalen Postfach <font color=\"#EE4000\">$corusername@$corlocalmaildomain</font> zugeordnet.<p>
	<b>Im ersten Schritt benötigen Sie lediglich den Namen Ihres Mailproviders.</b><p>
	<font color=\"#EE4000\">Ist Ihr Mailprovider nicht in der vorgegebenen Liste enthalten wählen Sie <b>\"sonstiger\"</b> aus.</font>";
site_info($margin, $info);

// Alle Mailprovider aus LDAP holen
$mp = new mailprovider();
$mpvendor = "*";
$ldapbinddn = "uid=$corusername,$corbasedn";
$result = $mp->readmailprovider($mpvendor,$ldapbinddn,$corpassword,$basedn,$corldaphost);

foreach ( $result as $mp ) {
    $mailproviders[] = $mp['fspmailprovidervendor'][0];
}

// Ergebnis-Array sortieren
sort($mailproviders);

$mailproviders[0] = "sonstiger";

// Formular oeffnen
$script = "./base.php";
open_form($script);

// Select-Code erzeugen
$margin = "";
$name = "mpvendor";
$code = build_select($mailproviders, $name);

// Select-Feld erzeugen
$val1=array("$code", "130");
$val2=array("<font size=\"-1\">Klicken Sie auf den Pfeil um die Liste zu sehen.</font>", "340");
$val_n=array($val1, $val2);
table_row_n($val_n, $margin);

// Checkbox erzeugen
$val1=array("<input type=\"checkbox\" name=\"proto\" value=\"imap\">", "20");
$val2=array("<font size=\"-1\">IMAP bevorzugen, wenn der Porvider dies anbietet. (Infos dazu siehe Handbuch S. 16)</font>", "500");
$val_n=array($val1, $val2);
table_row_n($val_n, $margin);


echo "<input type=\"hidden\" name=\"file\" value=\"increateacc2.php\" />\n";
# Submit und Reset
$val = "Weiter zu Schritt 2";
submit_row($val);

#Formular schliessen
close_form();
?>