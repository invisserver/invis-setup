<?php

/* index.php v1.1
 * portal building script
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2010 Stefan Schäfer, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: http://forum.invis-server.org
 */

//
// INCLUDES
//

require_once('inc/invis.inc.php');
require_once('config.php');
require_once('ldap.php');

//
// PREPARATION
//

// check if request comes from internal address
$EXTERNAL_ACCESS = (substr($_SERVER['REMOTE_ADDR'], 0, strripos($_SERVER['REMOTE_ADDR'], '.')) != $DHCP_IP_BASE);
//$EXTERNAL_ACCESS = false;

// load configuration xml-file
$CONF = new InvisConfig();
if (!$CONF -> load("portal.xml")) {
	echo "<h1>Error loading 'portal.xml'!</h1>";
	die();
}

$USER_DATA = null;
$USER_IS_ADMIN = false;
// load cookie for user-details
if (isset($_COOKIE['invis'])) {
	$USER_DATA = json_decode($_COOKIE['invis']);
	// is admin?
	$conn = connect();
	$bind = bind($conn);
	$USER_IS_ADMIN = (array_search($USER_DATA -> uid, ldapAdmins($conn)) !== false);
	unbind($conn);
}

// set PARAMETERS
if (isset($_GET['sn'])) {
	$SECTION_NAME = $_GET['sn'];
}

if (isset($_GET['s'])) {
	$SECTION_INDEX = $_GET['s'];
} else {
	$SECTION_INDEX = 0;
}

// fetch current section, if restricted && !admin default to 0
if (isset($SECTION_NAME)) {
	$CURRENT_SECTION = $CONF -> getSection($SECTION_NAME);
} else {
	$CURRENT_SECTION = null;
}
if ($CURRENT_SECTION == null)
	$CURRENT_SECTION = $CONF -> sections -> item($SECTION_INDEX);

if ($CURRENT_SECTION -> getAttribute('restricted') == 'yes' && !$USER_IS_ADMIN)
	$CURRENT_SECTION = $CONF -> sections -> item(0);

//
// SITE BUILDING
//

// html header
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
			<head>';

echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\"/>";

echo "<title>[invis] " . $CURRENT_SECTION -> getAttribute("title") . "</title>";

// global site wide sources
foreach ($CONF -> sources as $source) {
	$type = $source -> getAttribute("type");
	$src = $source -> getAttribute("src");
	
	if ($type == "css") {
		echo "<link href='$src' media='screen' rel='stylesheet' type='text/css' />\n";
	}
	
	if ($type == "javascript") {
		echo "<script type='text/javascript' src='$src'></script>\n";
	}
}

// section specific sources
foreach ($CURRENT_SECTION -> getElementsByTagName('source') as $source) {
	$type = $source -> getAttribute("type");
	$src = $source -> getAttribute("src");
	
	if ($type == "css") {
		echo "<link href='$src' media='screen' rel='stylesheet' type='text/css' />\n";
	}
	
	if ($type == "javascript") {
		echo "<script type='text/javascript' src='$src'></script>\n";
	}
}


echo "</head>";
echo "<body onload='" . $CONF -> getOnLoadScript() . $CURRENT_SECTION -> getAttribute('onload') . "'>";
// servername inlay
//include('inc/servername.inc.php');

echo <<<HEAD
<div id='header_bg'>
	<div class='site'>
		<div id='header'>
			<div style="position: relative; left: -50px;">
				<a href="http://www.invis-server.org" target="_blank">
					<img border="0" style="float: left; padding-right: 15px;" src="$PORTAL_LOGO_PATH" alt="logo" />
				</a>
				<div style="padding-top: 10px;">;-)</div>
			</div>
		</div>
HEAD;

echo "<div id='menu'>";
echo '<b style="font-size: 0.6em; color: #ff3300;">Server: ' . $FQDN . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b>';

$count = 0;
// section menu
foreach ($CONF -> sections as $section) {
	$restricted = ($section -> getAttribute('restricted') == 'yes');
	$visible_ext = ($section -> getAttribute('visible') == 'ext');
	
	$classes = '';
	
	if ($section === $CURRENT_SECTION)
		$classes = 'active';
	
	if ($restricted) {
		$classes .= ' admin';
	}
	
	// dont show restricted sections if not admin user
	if (($restricted && $USER_IS_ADMIN) || !$restricted) {
		if (!$EXTERNAL_ACCESS && $visible_ext) ;
		else echo "<a class='$classes' href='?sn=" . $section -> getAttribute("linkname") . "'>" . $section -> getAttribute("name") . "</a>";
	}
	$count++;
}
//echo '<div id=\'servername\'align="right"><b style="font-size: 0.6em; color: #ff3300;">Server: ' . $FQDN . '</b></div>';
echo "</div></div></div>";

// section content container
echo "<div id='content-container'>";
	echo "<div class='site'>";
		echo "<h2 class='section-title'>" . $CURRENT_SECTION -> getAttribute("title") . "</h2>";
		echo "<div id='content'>";
		
		// external
		if ($EXTERNAL_ACCESS == true && !isset($USER_DATA)) {
			include('inc/external.access.inc.php');
		}
		// include requested section
		else if (($inc = $CURRENT_SECTION -> getAttribute('inc')) != "") {
			include($inc);
		}
		
		echo "</div>";
	echo "</div>";
echo "</div>";

// site footer
include('inc/site-footer.inc.php');


// overlay and lightbox
echo "<div id='overlay'></div>
	<div id='lightbox'>
		<div id='lightbox-title'></div>
		<div id='lightbox-content'></div>
		<div id='lightbox-buttons'></div>
		<div id='lightbox-status'></div>
		<div id='lightbox-wait'></div>
	</div>";
echo "<div id='userblock'></div>";

// settings
echo "<div style=\"display: none;\"><input id=\"user_pw_min_length\" type=\"hidden\" value=\"" . $USER_PW_MIN_LENGTH . "\" />
      <input id=\"user_pw_min_strength\" type=\"hidden\" value=\"" . $USER_PW_MIN_STRENGTH . "\" /></div>";

// html footer
echo '<div style="display: none;">Icon-pack: <a href="http://iconeden.com/icon/milky-a-free-vector-iconset.html">Milky</a></div>';
echo "</body></html>";

?>
