<?php

/*
 * inc/transfer.section.inc.php v1.0
 * portal drop-in, offering user specific downloads and upload
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */


if (!isset($CONF)) die;
$upload_id = uniqid('');

?>

<script type="text/javascript">

function startProgress(id) {
	$('progress_border').setStyle({display: 'block'});
	var data = invis.getCookie('invis').evalJSON();
	invis.request('script/ajax.php', progressResponse, {c: 'upload_progress', u: data.uid, id: id});
}
function progressResponse(request) {
	var data = request.responseText.evalJSON();
	if (data[1]) {
		var pro = Math.floor((data[1].current / data[1].total) * 100);
		$('progress_frame').setStyle({display: 'block', width: pro+'%'});
		$('progress_text').update(data[1].current + ' / ' + data[1].total + ' Byte');
	} else {
		startProgress(data[0]);
		return;
	}
	
	
	if (data[1].done != 1 && data[1].cancel_upload == null) {
		startProgress(data[0]);
	} else {
		if (data[1].cancel_upload != 0) {
			$('progress_frame').setStyle({backgroundColor: '#ff0000'});
			$('progress_text').update('Fehler! Bitte warten ...');
			window.setTimeout(stopProgress, 10 * 1000);
		} else {
			$('progress_frame').setStyle({backgroundColor: '#00ff00'});
			window.setTimeout(stopProgress, 2 * 1000);
		}
	}
}
function stopProgress() {
	$('progress_border').setStyle({display: 'none'});
	$('progress_text').setStyle({display: 'none'});
}

</script>

<div style="border: 1px solid #b0b0b0; padding: 5px;">
<?php

$download_path = $PORTAL_DOWNLOAD_DIR . '/' . $USER_DATA -> uid;
if ($handle = opendir($download_path)) {
	echo '<table style="font-size: 0.8em; font-family: Courier New, courier; width: 100%;" cellpadding="0" cellspacing="0">';
	echo "<caption style='font-size: 1.2em; text-align: left;'>Verzeichnis: <b>/downloads/" . $USER_DATA -> uid . "</b></caption>";
	echo '<tr><th style="border-bottom: 1px solid #b0b0b0; text-align: left;">Dateiname</th><th style="border-bottom: 1px solid #b0b0b0;text-align: right;">Änderung</th><th style="border-bottom: 1px solid #b0b0b0; text-align: right;">Größe (Byte)</th></tr>';
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != ".." && is_file($download_path . '/' . $file)) {
			$filedata = lstat($download_path . '/' . $file);
			echo '<tr>';
			echo "<td><a href='script/download.php?f=$file'>$file</a></td>";
			echo '<td align="right">' . date('Y-m-d, g:i', $filedata['mtime']) . '</td>';
			echo '<td align="right">' . $filedata['size'] . '</td>';
			echo '</tr>';
		}
	}
	closedir($handle);
	echo '</table>';
}
?>
</div>

<p>
	<b><u>Hinweis:</u></b>
	<ul>
		<li>Hochgeladene Dateien werden nach einem Virenscan in ein entsprechende Verzeichnis verschoben, sie erscheinen <b><i>nicht</i></b> im persönlichen Download-Verzeichnis.</li>
		<li>Die <i>maximale</i> Dateigröße beträgt <b><?php echo ini_get('upload_max_filesize')?>B</b>, größere Dateien werden nur bis zu dieser Größe hochgeladen und dann <u>abgebrochen</u>.</li>
	</ul>
	<iframe id="theframe" name="theframe" src="script/file_upload.php?id=<?php echo $upload_id; ?>" style="border: none; padding: 0px; margin: 0px; height: 70px; width: 100%;">
	</iframe>
	
	<div id="progress_border" style="display: none; border: 1px solid black; width: 400px; height: 20px;">
		<div id="progress_frame" style="background-color: #ff6633; width: 0%; height: 100%;"></div>
	</div>
	<span id="progress_text"></span>
</p>

