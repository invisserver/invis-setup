<?php
# Funktionen zum dynamischen Aufbau von Webseiten
# Seitenanfang

function site_head($programm, $sitename, $bgcolor) {
	echo ("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">");
	echo ("<html>");
	echo ("<head>");
	echo ("<title>$sitename</title>");
	echo ("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">");
	echo ("<meta name=\"author\" content=\"Stefan Schaefer, FSP\">");
	echo ("</head>");
	echo ("<body style='font-family:sans-serif' bgcolor=\"$bgcolor\" link=\"darkblue\" alink=\"red\" vlink=\"#fb8000\"
		text=\"black\" marginwidth=\"0\" marginheight=\"0\" leftmargin=\"0\"
		topmargin=\"0\">");
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tbody>");
	echo ("<tr>");
	echo ("<td width=\"100\" bgcolor=\"#e0e0e0\">&nbsp;</td>");
	echo ("<td height=\"68\" width=\"110\" bgcolor=\"#e0e0e0\">");
	echo ("<a href=\"http://www.invis-server.org\"><img src=\"./pics/invis-logo.png\" border=\"0\" alt=\"Logo\"></a>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"68\" bgcolor=\"#e0e0e0\">");
	echo ("<div align=\"center\"><small><font face=\"Courier New,Courier,mono\" size=\"4\" color=\"darkgrey\">");
	echo ("<b><h2>$programm</h2></b></font></small></div>");
	echo ("</td>");
	echo ("<td width=\"100\" bgcolor=\"#e0e0e0\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody>");
	echo ("</table>");
}
# Seitenende
function site_end($cormainpage, $corfooter, $COR_WEBSERVER){
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tbody>");
	echo ("<tr>");
	echo ("<td width=\"100\" bgcolor=\"#e0e0e0\">&nbsp;</td>");
	echo ("<td valign=\"top\" width=\"110\" bgcolor=\"#e0e0e0\"><br></td>");
	echo ("<td bgcolor=\"#e0e0e0\">");
		echo ("<table border=\"0\" width=\"100%\" bgcolor=\"#e0e0e0\" cellpadding=\"3\" cellspacing=\"0\">");
		echo ("<tbody>");
		echo ("<tr>");
		echo ("<td valign=\"middle\" width=\"130\">$cormainpage</td>");
		echo ("<td valign=\"middle\" align=\"middle\">$corfooter</td>");
		echo ("<td valign=\"middle\" width=\"130\" align=\"right\">$COR_WEBSERVER</td>");
		echo ("</tr>");
		echo ("</tbody>");
		echo ("</table>");
	echo ("</td>");
	echo ("<td width=\"100\" bgcolor=\"#e0e0e0\"> &nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody>");
	echo ("</table>");
	echo ("</body>");
	echo ("</html>");
}

# Info Zeile / Leer Zeile
function site_info($margin, $info) {
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tbody>");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" height=\"30\" width=\"110\" bgcolor=\"#fafafa\"><b><font color=darkgrey>$margin</font></b></td>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"30\"><center>$info</center>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Textfelder
function text_row($margin, $text, $result, $size, $type){
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" width=\"110\" height=\"30\" bgcolor=\"#fafafa\"><b><font color=white>$margin</font></b>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"30\"><center>");
		echo ("<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">");
		echo ("<tbody>");
		echo ("<tr>");
		echo ("<td valign=\"middle\" width=\"100\">$text<input type=\"$type\" size=\"$size\" name=\"$result\"></input>");
		echo ("</td>");
		echo ("</tr>");
		echo ("</tbody>");
		echo ("</table>");
	echo ("</center>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Automatisches Erzeugen von Select-Feldern
function build_select($result, $name, $multiple = '', $size = 1) {
	$code = "<select $multiple name=\"$name\" size=$size>";
	foreach( $result as $row ) {
		$code .= "<option value=\"" . $row . "\">";
		$code .= $row;
		$code .= "</option>\n";
	}
	$code .= "</select>";
	return $code;
}

# Tabellenzeile Username & Passwort
function unpw($margin) {
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" height=\"40\" width=\"110\" bgcolor=\"#fafafa\"><b><font color=\"silver\">$margin</font></b>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"40\"><center>");
		echo ("<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">");
		echo ("<tbody>");
		echo ("<tr><td>");
		echo ("<p>Ihr Username:<br><input type=\"text\" size=\"40\" name=\"username\"></p>
			<p>Ihr Passwort:<br><input type=\"password\" size=\"40\" name=\"password\"></p>");
		echo ("</td></tr>");
		echo ("</tbody>");
		echo ("</table>");
	echo ("</center>");
	echo ("</td>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Submit oder Reset
function submit_row($val){
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" width=\"110\" height=\"40\" bgcolor=\"#fafafa\"><b><font color=silver>Submit</font></b>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"40\"><center>");
		echo ("<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">");
		echo ("<tbody>");
		echo ("<tr>");
		echo ("<td valign=\"middle\" width=\"100\"><input type=\"submit\" size=\"40\" value=\"$val\">");
		echo ("</td>");
		echo ("<td valign=\"middle\" width=\"100\"><input type=\"reset\" size=\"40\" name=\"Reset\">");
		echo ("</td>");
		echo ("</tr>");
		echo ("</tbody>");
		echo ("</table>");
	echo ("</center>");
	echo ("</td>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Tabellenzeile mit n Spalten
function table_row_n($val_n, $margin){
	echo ("<table border=\"0\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" height=\"20\" width=\"110\" bgcolor=\"#fafafa\"><b><font color=darkgrey>$margin</font></b>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"20\">");
		echo ("<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">");
		echo ("<tbody>");
		echo ("<tr>");
		$i = 0;
		foreach ($val_n as $td) {
			echo ("<td valign=\"top\" width=\"$td[1]\"><fontface=\"arial\">$td[0]</font></td>");
		}
		echo ("</tr>");
		echo ("</tbody>");
		echo ("</table>");
	echo ("</td>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Schaltflächenzeile mit n Spalten
function button_row_n($val_n, $margin, $script){
	echo ("<table border=\"0\"  width=\"100%\" cellpadding=\"3\" cellspacing=\"0\"><tbody>");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" height=\"40\" width=\"110\" bgcolor=\"#fafafa\"><b><font color=\"darkgrey\">$margin</font></b></td>");
	//echo ("<td width=\"340\">&nbsp;</td>");
	echo ("<td align=\"center\" height=\"40\">");
		echo ("<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\"><tbody>");
		echo ("<tr align=\"justify\">");
		foreach ($val_n as $button) {
			echo ("<td width=\"200\" align=\"center\" valign=\"middle\"><form action=\"$script\" method=\"post\"><input type=\"hidden\" name=\"file\" value=\"$button[1]\"></input><input type=\"hidden\" name=\"bgcolor\" value=\"$button[2]\"></input><input type=\"submit\" value=\"$button[0]\"></input></form></td>");
		};
		echo ("</tr>");
		echo ("</tbody></table>");
	echo ("</td>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Tabellenzeile mit Textblock
function textblock($margin, $title, $value, $name) {
	echo ("<table border=\"1\" width=\"100%\" cellpadding=\"3\" cellspacing=\"0\">");
	echo ("<tr>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("<td valign=\"top\" height=\"40\" bgcolor=\"#fafafa\"><b><font color=white>$margin</font></b>");
	echo ("</td>");
	echo ("<td valign=\"middle\" height=\"40\">");
		echo ("<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">");
		echo ("<tbody>");
		echo ("<tr>");
		echo ("<td valign=\"middle\" width=\"200\"><font face=\"arial\"> $title: </font><br><textarea name=\"$name\" cols=\"80\" rows=\"8\" wrap=\"virtual\">$value</textarea>");
		echo ("</td>");
		echo ("</tr>");
		echo ("</tbody>");
		echo ("</table>");
	echo ("</center>");
	echo ("</td>");
	echo ("<td width=\"100\">&nbsp;</td>");
	echo ("</tr>");
	echo ("</tbody></table>");
}

# Formular anfangen
function open_form($script){
	echo ("<form action=\"$script\" method=\"post\">");
}

# Formular beenden
function close_form(){
	echo ("</form>");
}

?>
