<?php
/* 
 * inc/help.section.php v1.0
 * portal drop-in, displaying (application) help info
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2012 Ingo Goeppert, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

if (!isset($CONF)) die;

require_once('ldap.php');

// 0:guest, 1:user, 2:admin
$usertype = 0;
if (isset($USER_DATA)) $usertype = 1;
if ($USER_IS_ADMIN) $usertype = 2;

$conn = connect();
$bind = bind($conn);

$server = isset($_SERVER['HTTPS']) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];

$result = search($conn, "ou=iPortal,ou=Informationen,$LDAP_SUFFIX", 'iportentryposition=dokumentation');
if ($result) {
	echo '<table>';
	for($i = 0; $i < $result['count']; $i++) {
		$entry = cleanup($result[$i]);
		if ($entry['iportentryactive'] == 'FALSE') continue;
		$type = $entry['iportentrypriv'];
		if (strstr($entry['iportentryurl'], '[servername]') === false)
			$url = 'http://'.$entry['iportentryurl'];
		else
			$url = str_replace('[servername]', $server, $entry['iportentryurl']);
		
		if ($type == 'guest' || ($type == 'user' && $usertype > 0)) {
			echo '<tr>';
			echo '<td style="text-align: center; background-color: #fafafa; border: 1px solid #b0b0b0; padding: 3px;"><a style="text-decoration: none; color: #ff0000;" href="'. $url .'">' . $entry['iportentrybutton'] . '</a></td>';
			echo '<td style="font-size: 0.9em;">'. $entry['iportentrydescription'] .'</td>';
			echo '</tr>';
		}
	}
	echo '</table>';
} else {
	echo ldap_error($conn);
}
?>
<script type="text/javascript">

function supportMail() {
	$('form_button').hide();
	$('form_status_img').show();
	$('form_status_txt').update(' Wird gesendet ...')
	
	var from = $('form_origin').value;
	var data = $H();
	data.set('from', from);
	data.set('msg', $('form_content').value);
	data.set('subject', $('form_subject').value);
	
	invis.setCookie('invis-request', data.toJSON());
	invis.request('script/ajax.php', supportMailResponse, {c: 'support_mail', u: from});
}

function supportMailResponse(request) {
	if (request.responseText == '0') {
		$('form_button').show();
		$('form_status_img').hide();
		$('form_status_txt').update('Ihre Anfrage wurde verschickt');
	} else {
		$('form_status_img').hide();
		$('form_status_txt').update('Fehler: ' + request.responseText);
	}
}

</script>

<div style="padding: 10px;">
	<h3>Kontaktformular:</h3>
	<p>Nutzen Sie dieses Formular um mit Ihren IT-Dienstleister in Kontakt zu treten.</p>
	<form onsubmit="return false;">
		<table width="450px" border="0">
			<tr>
				<td width="50px"><label for="form_origin" style="font-size: 0.8em;">Absender:</label></td>
				<td><input id="form_origin" type="text" style="width: 100%; padding: 2px; border: 1px solid #b0b0b0; background-color: #e0e0e0; font-style: italic; color: #808080;" value="<?php echo($USER_DATA -> uid); ?>" readonly="readonly" /></td>
			</tr>
				<td><label for="form_subject" style="font-size: 0.8em;">Betreff:</label></td>
				<td><input id="form_subject" type="text" style="width: 100%; padding: 2px; border: 1px solid #b0b0b0; font-weight: bold;" /></td>
			</tr>
			<tr>
				<td colspan="2"><textarea id="form_content" style="width: 100%; border: 1px solid #b0b0b0;" cols="50" rows="20"></textarea></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><input id="form_button" style="border: 1px solid #b0b0b0; cursor: pointer; margin: 2px; padding: 3px; color: #000000; background-color: #fafafa;" type="button" value="Senden" onclick="supportMail();"/></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><table><tr><td id="form_status_img" style="display: none;"><img src="images/ajax-loader.gif" /></td><td id="form_status_txt" style="font-size: 0.8em; vertical-align: middle;"></td></tr></table></td>
			</tr>
		</table>
	</form>
</div>