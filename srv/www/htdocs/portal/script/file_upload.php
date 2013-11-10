<?php

/*
 * script/file_upload.php v1.0
 * iframe include, file upload form and upload-error reporting
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */


require_once('../config.php');

if (!isset($_COOKIE['invis'])) die();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	$uploadErrors = array(
		UPLOAD_ERR_INI_SIZE => 'Datei zu groß',								//The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		UPLOAD_ERR_FORM_SIZE => 'Datei zu groß.',							//The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		UPLOAD_ERR_PARTIAL => 'Datei wurde nur teilweise hochgeladen',		//'The uploaded file was only partially uploaded.',
		UPLOAD_ERR_NO_FILE => 'Keine Datei wurde hochgeladen',				//'No file was uploaded.',
		UPLOAD_ERR_NO_TMP_DIR => 'Kein temporäres Verzeichnis', 			//'Missing a temporary folder.',
		UPLOAD_ERR_CANT_WRITE => 'Datei konnte nicht gespeichert werden',	//'Failed to write file to disk.',
		UPLOAD_ERR_EXTENSION => '???', 		//'File upload stopped by extension.',
	);
	
	$file = $_FILES['invis_upload_file'];
	
	if ($file['error'] == 0) {
		
		if ($file['size'] > 0) {
			move_uploaded_file($file['tmp_name'], $PORTAL_UPLOAD_DIR . '/' . basename($file['name']));
			echo '<p>Die Datei "' . $file['name'] . '" wurde hochgeladen.</p>';
		} else
			echo '<p>Ein Fehler ist aufgetreten: Leere Datei?</p>';
	} else {
		echo '<p>Ein Fehler ist aufgetreten: ' . $uploadErrors[$file['error']] . '</p>';
	}
	echo '<a onclick="parent.location.href=parent.location.href" style="cursor: pointer; color: #ff0000; font-size: 0.8em; text-decoration: underline;">Noch eine Datei nochladen</a>';
	die;
}

	$id = $_GET['id'];
	
	$max = ini_get('upload_max_filesize');
	$maxfile_ext = substr($max, -1, 1);
		
	switch ($maxfile_ext) {
		case 'K': $multi = 1024; break;
		case 'M': $multi = 1024 * 1024; break;
		case 'G': $multi = 1024 * 1024 * 1024; break;
		default: $multi = 1;
	}
	
	$maxfile_size = intval(substr($max, 0, count($max))) * $multi;
?>

<form enctype="multipart/form-data" id="upload_form" action="file_upload.php" method="POST">
	<input type="hidden" name="<?php echo ini_get('apc.rfc1867_name'); ?>" id="progress_key"  value="<?php echo $id; ?>"/>
	<input type="hidden" name ="MAX_FILE_SIZE" value="<?php echo $maxfile_size; ?>"/>
	<input type="file" id="invis_upload_file" name="invis_upload_file" size="40"><br/>
	<input onclick="window.parent.startProgress('<?php echo $id; ?>'); return true;" type="submit" value="Hochladen"/>
</form>