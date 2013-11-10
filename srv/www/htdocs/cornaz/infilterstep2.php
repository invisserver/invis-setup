<?php
# Script zum Anlegen eines neuen Mailaccounts

#Postvariablen übernehmen
$filtername=$_POST["filtername"];
$component=$_POST["component"];
$description=$_POST["description"];

#Info Zeile
$margin = "Mail Filter";
$info = "<b>Über diese Seite können Sie eingehende emails von Ihrem Server filtern lassen.</b><p>
	Vervollständigen Sie hier Ihren Filter.<br>
	Die <b>Bedingung</b> \"enthält (+)\" erlaubt die Verwendung von Jokerzeichen wie <font color=\"#EE4000\"><b>*</b></font> und <font color=\"#EE4000\"><b>?</b></font>.";
site_info($margin, $info);
#Formular oeffnen
$script = "./base.php";
open_form($script);

# Filterbau Teil 2
switch ($component) {
	case "header":
		# Eingabezeilen
		$margin = ("Filtername");
		$inhalt_s1 = array("Filtername: <font color=\"#EE4000\"><b>$filtername</b></font>","200");
		$inhalt_s2 = array("Zu untersuchender Teil der Email: <font color=\"#EE4000\"><b>Kopf</b></font>
				<input type=\"hidden\" name=\"filtername\" value=\"$filtername\">
				<input type=\"hidden\" name=\"component\" value=\"$component\">
				<input type=\"hidden\" name=\"description\" value=\"$description\">","300");
		$val_n = array($inhalt_s1 , $inhalt_s2);
		table_row_n($val_n, $margin);
		
		$margin = ("Kriterium");
		$inhalt_s1 = array("Komponente: <br> <SELECT NAME=\"attribute\" SIZE=\"1\">
				<OPTION VALUE=\"subject\">Betreff
				<OPTION VALUE=\"date\">Datum
				</SELECT>","100");
		$inhalt_s2 = array("Bedingung: <br> <SELECT NAME=\"bedingung\" SIZE=\"1\">
				<OPTION VALUE=\"contains\">enthält
				<OPTION VALUE=\"matches\">enthält (+)
				<OPTION VALUE=\"is\">ist gleich
				</SELECT>","100");
		$inhalt_s3 = array("Vergleichswert: <br> <input type=\"text\" size=\"60\" name=\"vergleichswert\">","100");
		$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
		table_row_n($val_n, $margin);

		$margin = ("Aktion");
		$inhalt_s1 = array("Aktion: <br> <SELECT NAME=\"aktion\" SIZE=\"1\">
				<OPTION VALUE=\"discard\">löschen
				<OPTION VALUE=\"reject\">zurückweisen
				<OPTION VALUE=\"fileinto\">in Ordner
				<OPTION VALUE=\"redirect\">weiterleiten
				</SELECT>","100");
		$inhalt_s2 = array("Information: <br> <input type=\"text\" size=\"80\" name=\"information\">","100");
		$val_n = array($inhalt_s1, $inhalt_s2);
		table_row_n($val_n, $margin);
	break;
	case "address":
	# Eingabezeilen
		$margin = ("Filtername");
		$inhalt_s1 = array("Filtername: <font color=\"#EE4000\"><b>$filtername</b></font>","200");
		$inhalt_s2 = array("Zu untersuchender Teil der Email: <font color=\"#EE4000\"><b>Adressen</b></font>
				<input type=\"hidden\" name=\"filtername\" value=\"$filtername\">
				<input type=\"hidden\" name=\"component\" value=\"$component\">
				<input type=\"hidden\" name=\"description\" value=\"$description\">","300");
		$val_n = array($inhalt_s1 , $inhalt_s2);
		table_row_n($val_n, $margin);

		$margin = ("Kriterium");
		$inhalt_s1 = array("Komponente: <br> <SELECT NAME=\"attribute\" SIZE=\"1\">
				<OPTION VALUE=\"from\">Absender
				<OPTION VALUE=\"to\">Empfänger
				<OPTION VALUE=\"cc\">Carbon Copy (CC)
				<OPTION VALUE=\"bcc\">Blind Carbon Copy (BCC)
				</SELECT>","100");
		$inhalt_s2 = array("Bedingung: <br> <SELECT NAME=\"bedingung\" SIZE=\"1\">
				<OPTION VALUE=\"contains\">enthält
				<OPTION VALUE=\"matches\">enthält (+)
				<OPTION VALUE=\"is\">ist gleich
				</SELECT>","100");
		$inhalt_s3 = array("Vergleichswert: <br> <input type=\"text\" size=\"60\" name=\"vergleichswert\">","100");
		$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
		table_row_n($val_n, $margin);

		$margin = ("Aktion");
		$inhalt_s1 = array("Aktion: <br> <SELECT NAME=\"aktion\" SIZE=\"1\">
				<OPTION VALUE=\"discard\">löschen
				<OPTION VALUE=\"reject\">zurückweisen
				<OPTION VALUE=\"fileinto\">in Ordner
				<OPTION VALUE=\"redirect\">weiterleiten
				</SELECT>","100");
		$inhalt_s2 = array("Information: <br> <input type=\"text\" size=\"80\" name=\"information\">","100");
		$val_n = array($inhalt_s1, $inhalt_s2);
		table_row_n($val_n, $margin);
	break;
	case "body":
	# Eingabezeilen
		$margin = ("Filtername");
		$inhalt_s1 = array("Filtername: <font color=\"#EE4000\"><b>$filtername</b></font>","200");
		$inhalt_s2 = array("Zu untersuchender Teil der Email: <font color=\"#EE4000\"><b>Inhalt</b></font>
				<input type=\"hidden\" name=\"filtername\" value=\"$filtername\">
				<input type=\"hidden\" name=\"component\" value=\"$component\">
				<input type=\"hidden\" name=\"description\" value=\"$description\">","300");
		$val_n = array($inhalt_s1 , $inhalt_s2);
		table_row_n($val_n, $margin);

		$margin = ("Kriterium");
		$inhalt_s1 = array("Komponente: <br> <SELECT NAME=\"attribute\" SIZE=\"1\">
				<OPTION VALUE=\"raw\">Rohformat
				<OPTION VALUE=\"text\">Text-Format
				<OPTION VALUE=\"html\">HTML-Format
				</SELECT>","100");
		$inhalt_s2 = array("Bedingung: <br> <SELECT NAME=\"bedingung\" SIZE=\"1\">
				<OPTION VALUE=\"contains\">enthält
				<OPTION VALUE=\"matches\">enthält (+)
				<OPTION VALUE=\"is\">ist gleich
				</SELECT>","100");
		$inhalt_s3 = array("Vergleichswert: <br> <input type=\"text\" size=\"60\" name=\"vergleichswert\">","100");
		$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
		table_row_n($val_n, $margin);

		$margin = ("Aktion");
		$inhalt_s1 = array("Aktion: <br> <SELECT NAME=\"aktion\" SIZE=\"1\">
				<OPTION VALUE=\"discard\">löschen
				<OPTION VALUE=\"reject\">zurückweisen
				<OPTION VALUE=\"fileinto\">in Ordner
				<OPTION VALUE=\"redirect\">weiterleiten
				</SELECT>","100");
		$inhalt_s2 = array("Information: <br> <input type=\"text\" size=\"80\" name=\"information\">","100");
		$val_n = array($inhalt_s1, $inhalt_s2);
		table_row_n($val_n, $margin);

	break;
}	
echo "<input type=\"hidden\" name=\"file\" value=\"excreatefilter.php\" />\n";
# Submit und Reset
$val = "Filter anlegen";
submit_row($val);

#Formular schliessen
close_form();

#Info Zeile
$margin = "Erläuterungen";
$info = "<font size=\"-1\"><b>Abhängig von der gewählten Aktion benötigt Ihr Server zusätzliche Informationen:</b><br>
	Aktion \"<font color=\"#EE4000\"><b>löschen</b>\"</font> benötigt keine Informationen.<br>
	Aktion \"<font color=\"#EE4000\"><b>zurückweisen</b>\"</font> benötigt eine Begründung (möglichst in englisch).<br>
	Aktion \"<font color=\"#EE4000\"><b>in Ordner</b>\"</font> benötigt den Namen des gewünschen Mailordners.<br> Existiert ein Ordner nicht, wird er automatisch angelegt.<br>
	Aktion \"<font color=\"#EE4000\"><b>weiterleiten</b>\"</font> benötigt die Empfängeradresse.</font>";
site_info($margin, $info);

