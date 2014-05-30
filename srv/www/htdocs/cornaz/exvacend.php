<?php
//Anwesend
		$datei = "$COR_PATH/vacation/$corusername.binda";
		$vorgang = "Willkommen zurück <font color=\"#EE4000\"><b>$corusername</b></font>. Sie hatten hoffentlich einen erholsamen Urlaub.";
		$fp = fopen ($datei, "w");
		fputs ($fp, " ");
		fclose($fp);
		exec ("sudo $COR_PATH/bin/backhome");

#Info Zeile
$margin = "";
$info = "</center><p><hr size=\"1\" noshade width=\"300\" center><p>
<center>$vorgang</center><p><center>$ausgabe</center><p>
<hr size=\"1\" noshade width=\"300\" center><p>";
site_info($margin, $info);

?>