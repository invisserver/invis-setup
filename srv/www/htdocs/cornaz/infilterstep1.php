<?php
# Script zum Anlegen eines neuen Mailaccounts


#Info Zeile
$margin = "Mail Filter";
$info = "<b>Über diese Seite können Sie eingehende Mails von Ihrem Server filtern lassen.</b><p><font size=\"-1\">
	Eine Email besteht aus zwei Teilen: <font color=\"#EE4000\"><b>Kopf (Header)</b></font> und <font color=\"#EE4000\"><b>Text (Body)</b></font>.
	Der Kopf enthält alle möglichen Informationen, wie etwa <font color=\"#EE4000\"><b>Betreff (Subject), Absenderadresse (from),
	Datum (date)</b></font> sowie weitere Informationen über den Weg den eine Email genommen hat.
	Demgegenüber enthält der <font color=\"#EE4000\"><b>Text (Body)</b></font> den eigentlichen Inhalt einer Email, sowie gegebenfalls einen,
	oder mehrere Anhänge. Darüber hinaus kann der Inhalt als reiner Text oder als HTML-Code vorliegen.
	Jeden dieser Teile können Sie auf bestimmte Inhalte untersuchen. Werden diese Inhalte
	gefunden, können Sie entscheiden, was mit einer solchen Email geschehen soll. Sie haben die Möglichkeit Emails zu löschen, zurückzuweisen, in einen bestimmten Mail-Ordner einsortiern zu lassen oder sie an eine beliebige Adresse weiterleiten.</font>";
site_info($margin, $info);
#Formular oeffnen
$script = "./base.php";
open_form($script);

$margin = ("Kriterium");
$inhalt_s1 = array("Filtername: <br> <input type=\"text\" size=\"20\" name=\"filtername\">","100");
$inhalt_s2 = array("Komponente: <br> <SELECT NAME=\"component\" SIZE=\"1\">
				<OPTION VALUE=\"header\">Kopf
				<OPTION VALUE=\"address\">Adressen
				<OPTION VALUE=\"body\">Inhalt
				</SELECT>","100");
$inhalt_s3 = array("Kurzbeschreibung: <br> <input type=\"text\" size=\"60\" name=\"description\">","100");
$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
table_row_n($val_n, $margin);


# Submit und Reset
$val = "Weiter zu Schritt 2";
submit_row($val);


echo "<input type=\"hidden\" name=\"file\" value=\"infilterstep2.php\" />\n";
#Formular schliessen
close_form();

#Info Zeile
$margin = "Erläuterungen";
$info = "<b>Die Erstellung eines Filters läuft in zwei Schritten ab:</b><p>
	Geben Sie hier im ersten Schritt Ihrem Filter einen Namen, wählen Sie aus,<br>
	welcher Teil Ihrer Mail untersucht werden soll und versehen Sie das Ganze mit<br>
	einer Kurzbeschreibung.";
site_info($margin, $info);
