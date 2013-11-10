<?php

/*
 * script/download.php v1.1
 * download requested file
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

require_once('../config.php');
require_once('MIME/Type.php');

if (isset($_COOKIE['invis']))
	$USER = json_decode($_COOKIE['invis'], true);
else
{
	error_log("Unauthorized access (1, download.php).");
	die();
}

$usr = $USER['uid'];
$fname = $_GET['f'];

$basepath = "$PORTAL_DOWNLOAD_DIR/$usr";
$path = "$PORTAL_DOWNLOAD_DIR/$usr/$fname";

// Pfad pruefen (Schutz gegen ../../../ usw.!)
if (dirname(realpath($path)) != $basepath) {
    echo "File not found!";
    error_log("File \"" . $path . "\" path access error (2, download.php).");
    die();
}

$size = filesize($path);

//$mime = "application/force-download";
$mime = MIME_Type::autoDetect($path);

// set headers
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: public');
header('Content-Description: File Transfer');
header("Content-Type: $mime");
header("Content-Disposition: attachment; filename=\"$fname\"");
header('Content-Transfer-Encoding: binary');
header("Content-Length: $size");

// download
// @readfile($file_path);
$file = @fopen($path, 'rb');
if ($file) {
	while(!feof($file)) {
		print(fread($file, 1024*8));
		flush();
		if (connection_status() != 0) {
			@fclose($file);
			error_log("User \"" . $usr . "\" download file \"" . $path . "\" failed (3, download.php).");
			die();
		}
	}
	@fclose($file);
}

// Track downloads in apache logfile
error_log("User \"" . $usr . "\" downloaded file \"" . $path . "\" (4, download.php).");

// track downloads in logfile
// i.e.: append($log, "entry");
?>