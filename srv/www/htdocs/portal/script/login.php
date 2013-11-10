<?php

/* script/login.php v1.1
 * AJAX login-script, checking given password against ldap-stored ssha hash
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

	// include important ldap and stuff
	require_once('../ldap.php');
	
	// message to be sent if authorization fails
	function unauthorized() {
		header("HTTP/1.0 401 Unauthorized");
	}
	
	// no cookie, no competition
	if (!isset($_COOKIE['invis-login'])) {
		unauthorized();
		error_log("Unauthorized access: User \"" . $data['uid'] . "\" has no cookie set (1, login.php).");
	} else {
		// pull JSON object from cookie
		$data = json_decode($_COOKIE['invis-login'], true);
		
		// connect and bind to server
		$conn = connect();
		$bind = bind($conn);
		
		// get uidnumber and password for entered uid
		$response = search($conn, $BASE_DN_USER, "uid=". $data['uid'], array('uid', 'uidnumber', 'userpassword', 'displayname', 'cn', 'shadowlastchange', 'shadowmax'));
		// Ermitteln, ob der User Mitglied der Gruppe mobiluser ist und sich somit auch von extern anmelden darf.
		// check if request comes from internal address
		$EXTERNAL_ACCESS = (substr($_SERVER['REMOTE_ADDR'], 0, strripos($_SERVER['REMOTE_ADDR'], '.')) != $DHCP_IP_BASE);
		$USER_IS_ALLOWED = true;
		//$EXTERNAL_ACCESS = true;
		if ($EXTERNAL_ACCESS == true) {
			$USER_IS_ALLOWED = array_search($data['uid'], mobilUsers($conn));
			if ($USER_IS_ALLOWED === false)
			{
			    error_log("Unauthorized access: User \"" . $data['uid'] . "\" is not a mobiluser (2, login.php).");
			}
		}
		
		if ($response != false && $USER_IS_ALLOWED !== false) {
			$result = cleanup($response[0]);
			$test_pw = $data['pwd'];
			
			// password hash
			$hash = $result['userpassword'];
			// decode and remove '{SSHA}' prefix
			$hash = base64_decode(substr($hash, 6));
			
			// split in actual password hash and salt
			$salt = substr($hash, -4);
			$challenge = substr($hash, 0, 20);
			
			// test given password against
			if ($challenge == sha1($test_pw . $salt, true)) {
				$result['PWD_EXPIRE'] = ($result['shadowlastchange'] + $result['shadowmax']) - floor(date('U') / 86400);	// days until password will expire
				unset($result['userpassword']);
				unset($result['shadowlastchange']);
				unset($result['shadowmax']);
				echo json_encode($result);
				error_log("Authorized access: User \"" . $data['uid'] . "\" login successful (3, login.php).");
			}
			else
			{
			    unauthorized();
			    error_log("Unauthorized access: User \"" . $data['uid'] . "\" password check failed (4, login.php).");
			}
		} else {
			// no entry found OR general connection problems 
			echo ldap_error($conn);
			unauthorized();
			error_log("Unauthorized access: User \"" . $data['uid'] . "\" general error. LDAP error: \"" . ldap_error($conn) . "\" (5, login.php)");
		}/*} else {
			// no entry found OR general connection problems 
			echo ldap_error($conn);
			unauthorized();
		}*/
		
		// byebye server
		unbind($conn);
	}
?>
