<?php
if ($status == "Urlaub") {
	$margin = "Abwesend";
	$info = "<hr size=\"1\" noshade width=\"300\" center><p>Hallo <font color=\"#EE4000\"><b>$corusername</b></font>, Sie möchten sich als <font color=\"#EE4000\"><b>Abwesend</b></font> eintragen, haben allerdings eine Urlaubsbenachrichtigung aktiviert.<br>
	Auch wenn es paradox erscheinen mag, ergibt diese Konstellation keinen Sinn.<br>
	Wenn Sie möchten, dass eingehende Mails automatisch beantwortet werden, müssen Sie als <font color=\"#EE4000\"><b>Anwesend</b></font> geführt sein. Nur wenn Ihre eMails abgeholt werden, können sie auch automatisch beantwortet werden.<br>
	Um das automatische Abholen Ihrer Mails zu stoppen (Status: \"Abwesend\"), beenden Sie zunächst die aktivierte Urlaubsbenachrichtigung über die Schaltfläche <b>\"Urlaubsende\"</b> auf der CorNAz-Hauptseite.</p><hr size=\"1\" noshade width=\"300\" center>";
	site_info($margin, $info);
} else {
	//Abwesend
	$vorgang = "Sie haben sich als <font color=\"#EE4000\"><b>\"Abwesend\"</b></font> eingetragen.";
	$n = count($fetchmailrc_b);
	$i = 0;
	foreach ($fetchmailrc_b as $key){
		$unx = strlen(strstr($key, "$corusername"))-1;
		$nx = strlen(chop($key)) - $unx;
		if (substr(chop($key), $nx, $un) == $corusername) {
		unset ($fetchmailrc_b[$i]);
	}
	$i++;
	}
	$ausgabe = "<b>Status: </b> Ihre eMails werden vorübergehend nicht abgeholt.";
	$fh = fopen("/var/lib/cornaz/build/.fetchmailrc","w+");
	foreach ($fetchmailrc_b as $zeile) {
		fwrite ($fh, "$zeile");
	}
	fclose($fh);
	exec ("sudo /var/lib/cornaz/bin/fetchcopy");

	#Info Zeile
	$margin = "";
	$info = "<p><hr size=\"1\" noshade width=\"300\" center><p>
	<center>$vorgang</center><p><center>$ausgabe</center><p>
	<hr size=\"1\" noshade width=\"300\" center><p>";
	site_info($margin, $info);
	//imap_close($login);
}

?>