<?php

/*
 * script/ajax.php v1.7
 * AJAX script, user/group/host administration functions
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2009, 2010, 2011, 2012 Stefan Schaefer, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

require_once('../ldap.php');

// Hinzugefügt nach Erweiterung der config.php (SMB_HOSTNAME) 21.07.2009 -- Stefan
require_once('../config.php');

//--------------------
// AJAX HELPERS
//--------------------

// siwtch GET/POST
// !!REMOVE!!
if (isset($_GET['c'])) {
	$CMD = $_GET['c'];
	if (isset($_GET['u']))
		$USR = $_GET['u'];
	else
		$USR = null;
} else {
	$CMD = $_POST['c'];
	if (isset($_POST['u']))
		$USR = $_POST['u'];
	else
		$USR = null;
}

// calculate password TTLs from current date
function getPasswordTimes() {
		global $USER_PW_EXPIRE;
		$seconds = date("U");			// current date in seconds since 1.1.1970 (UNIX timestamp)
		$days = floor($seconds / 86400);	// current date in days since 1.1.1970
// Passwortgueltigkeit wird jetzt in config.php vorgegeben
		$u_ttl = $USER_PW_EXPIRE; 				// TTL for shadow password (UNIX) in days (1 year)
		$u_expire = $u_ttl;			// expiration date for shadow password (relative days to $days)
		$s_ttl = $USER_PW_EXPIRE * 24 * 60 * 60; 		// TTL for samba password (windows) in seconds (6 month)
		$s_expire = $seconds + $s_ttl;		// expiration date for samba password
		
		return array('shadowlastchange' => $days,
					'shadowmax' => $u_expire,
					'sambapwdlastset' => $seconds,
					'sambapwdmustchange' => $s_expire);
}

//--------------------
// COOKIE STUFF
//--------------------

if (isset($_COOKIE['invis']))
	$cookie_auth = json_decode($_COOKIE['invis'], true);

if (isset($_COOKIE['invis-request']))
	$cookie_data = json_decode($_COOKIE['invis-request'], true);

// unset request cookie
setcookie('invis-request', '', time() - 3600, '/');

//--------------------
// classes
//--------------------

// host class (DHCP, DNS forward / reverse)
// not used atm, change!
class Host {
	private $mac;
	private $dhcpStatement;
	
	private $objectClassDHCP = array('top', 'dhcphost');
	private $objectClassDNS = array('top', 'dnszone');
	
	function __construct() {
		
	}
	
	function getDHCP() {
		
	}
	
	function getForwardDNS() {
		
	}
	
	function getReverseDNS() {
		
	}
}

// group base class
class Group {
	
	private $objectClass = array('top', 'posixGroup', 'sambaGroupMapping');
	private $sambaGroupType = 2;
	
	public $cn;
	public $description;
	public $displayName;
	public $gidNumber;
	public $sambaSID;

	function __construct($cn, $next, $sid, $ridbase) {
		$this -> cn = $cn;
		$this -> gidNumber = $next;
		//$this -> sambaSID = $sid;
		// Daniel, Daniel, Daniel. Bei den Usern hast du die sambaSID doch richtig konstruiert.
		// Warum den nicht hier?
		
		$this -> description = $cn;
		$this -> displayName = $cn;
		// Stefan -- sambaSID richtig konstruieren
		$this -> rid = ($next * 2) + $ridbase + 1;
		$this -> sambaSID = $sid . '-' . $this -> rid;
	}
	
	function getAttributes() {
		return array('objectclass' => $this -> objectClass,
					'cn' => $this -> cn,
					'description' => $this -> description,
					'displayname' => $this -> displayName,
					'gidnumber' => $this -> gidNumber,
					'sambagrouptype' => $this -> sambaGroupType,
					'sambasid' => $this -> sambaSID
					);
	}
}

// user base class
class User {
	public $acc = 0; // 0 = Samba-user, 1 = Samba-admin, 2 = maildummy, 3 = Samba-guest, 4 = Groupware-User
	//public $acc = 3;
	private $objectClass = array('top', 'person', 'organizationalPerson', 'inetOrgPerson', 'posixAccount', 'shadowAccount');
	private $smb = 'sambaSamAccount';
	private $zcp = 'zarafa-user';
	private $groupe = 'groupeUser';
	private $sid;
	private $rid;
	
	// arrays 
	
	private $standard = array();
	private $shadow = array();
	private $samba = array();
	
	// single values

	// standard
	public $uid;
	private $uidNumber;		// aus LDAP
	public $gidNumber = 513;	//513 benutzer, 514 gast
	public $userPassword = '';
	private $loginShell = '/bin/bash';	// /bin/bash für benutzer,admin; /bin/false für email, gast
	private $homeDirectory;		// /home/$uid
	public $mail;			// $uid@domain
	private $cn;			// $uid
	public $givenName;		// $uid
	public $sn;			// $uid
	public $displayName;		// $givenName $sn
	public $gecos; 

	// shadow
	private $shadowExpire = -1;
	private $shadowFlag = 0;
	private $shadowInactive = -1;
	private $shadowLastChange;	// $days // letzte pwd-aenderung, anzahl TAGE seit 1.1.1970
	private $shadowMax;		// $u_expire; // lebensdauer passwort, synchronisieren mit sambaPwdMustChange. Anzahl Tage von $shadowLastChange
	private $shadowMin = -1;
	private $shadowWarning = 7;

	// samba
	private $sambaAcctFlags = "[U]";
	private $sambaHomeDrive = "U:";
	private $sambaHomePath; 	// = "\\\\INVIS4VM\\$uid"; Starre Pfadangabe war falsch Daniel !!
	private $sambaKickoffTime = 0;	// bis wann das konto noch gültig ist (unix timestamp, 0 = unendlich)
	public $sambaLMPassword = '';
	private $sambaLogoffTime = 0;
	private $sambaLogonScript;	//user.cmd
	private $sambaLogonTime = 0;
	public $sambaNTPassword = '';
	private $sambaPrimaryGroupSID;	// $sid-$gidNumber"
	private $sambaProfilePath; 	// = "\\\\INVIS4VM\\profiles\\$uid";	$sambaHomePath\$uid -- falsch! Richtig: \\HOST\profiles\uid 21.07.2009 -- Stefan
	private $sambaPwdCanChange = 0;
	private $sambaPwdLastSet;	// letzte pwd-änderung, anzahl SEKUNDEN seit 1.1.1970 (unix timestamp)
	private $sambaPwdMustChange;	// lebensdauer passwort, unix time stamp
	private $sambaSID;		// $sid-$rid
	
	// Groupe
	private $activGroupe = 1;
	private $permsGroupe = 0;
	public $uidGroupe;
	
	// Zarafa
	private $zarafaAccount = 1;
	private $zarafaAdmin = 0;
	private $zarafaSharedStoreOnly = 0;
	
	// standard contructor taking: uidname, nextfreeuidnumber, sid, ridbase
	function __construct($uid, $next, $sid, $ridbase, $domain) {
		global $SMB_HOSTNAME;
		global $USER_UMASK;
		// standard
		$this -> uid = $uid;
		$this -> uidNumber = $next;
		$this -> homeDirectory = "/home/$uid";
		$this -> cn = $uid;
		$this -> givenName = $uid;
		$this -> sn = $uid;
		$this -> displayName = $this -> givenName . ' ' . $this -> sn;
		$this -> mail = $uid . '@' . $domain;
		$this -> gecos = "System User,$USER_UMASK"; // usmask-Vorgabe aus config.php -- Stefan 24.01.2010
		// shadow
		
		//Group-e
		$this -> uidGroupe = $next;
		
		// samba
		$this -> sambaLogonScript = "user.cmd";
		$this -> sid = $sid;
		$this -> rid = ($next * 2) + $ridbase;
		
		// Falsch -- Stefan 21.07.2009 -- ProfilePath wird oben richtig gesetzt.
		//$this -> sambaProfilePath = $this -> sambaHomePath . '\\' . $uid;.
		$this -> sambaHomePath = "\\\\$SMB_HOSTNAME\\$uid";
		$this -> sambaProfilePath = "\\\\$SMB_HOSTNAME\\profiles\\$uid";
		$this -> sambaSID = $this -> sid . '-' . $this -> rid;
	}
	
	// set 
	private function setPasswordTimes() {
		$foo = getPasswordTimes();
		
		$this -> shadowLastChange = $foo['shadowlastchange'];
		$this -> shadowMax = $foo['shadowmax'];
		
		$this -> sambaPwdLastSet = $foo['sambapwdlastset'];
		$this -> sambaPwdMustChange = $foo['sambapwdmustchange'];
	}
	
	function getAttributes() {
		// if password has been set, set the related attributes
		//if ($this -> userPassword != '') $this -> setPasswordTimes();
		$this -> setPasswordTimes();
		
		
		// samba guests
		if ($this -> acc == 2) {
			$this -> loginShell = '/bin/false';
			$this -> gidNumber = 514;
			$this -> sambaPrimaryGroupSID = $this -> sid . '-' . $this -> gidNumber;
		}
		
		// mail dummies
		if ($this -> acc == 3) {
			$this -> loginShell = '/bin/false';
			$this -> gidNumber = 600;
		}
		
		// build attribute arrays
		$stdAttr = array(
			'uid' => $this -> uid,
			'uidnumber' => $this -> uidNumber,
			'gidnumber' => $this -> gidNumber,
			'userpassword' => $this -> userPassword,
			'loginshell' => $this -> loginShell,
			'homedirectory' => $this -> homeDirectory,
			'mail' => $this -> mail,
			'cn' => $this -> cn,
			'givenname' => $this -> givenName,
			'sn' => $this -> sn,
			'displayname' => $this -> displayName,
			'gecos' => $this -> gecos
		);

		$shadowAttr = array(
			'shadowexpire' => $this -> shadowExpire,
			'shadowflag' => $this -> shadowFlag,
			'shadowinactive' => $this -> shadowInactive,
			'shadowlastChange' => $this -> shadowLastChange,
			'shadowmax' => $this -> shadowMax,
			'shadowmin' => $this -> shadowMin,
			'shadowwarning' => $this -> shadowWarning
		);

		$sambaAttr = array(
			'sambaacctflags' => $this -> sambaAcctFlags,
			'sambahomedrive' => $this -> sambaHomeDrive,
			'sambahomepath'=> $this -> sambaHomePath,
			'sambakickofftime' => $this -> sambaKickoffTime,
			'sambalmpassword' => $this -> sambaLMPassword,
			'sambalogofftime' => $this -> sambaLogoffTime,
			'sambalogonscript' => $this -> sambaLogonScript,
			'sambalogontime' => $this -> sambaLogonTime,
			'sambantpassword'=> $this -> sambaNTPassword,
			'sambaprimarygroupsid' => $this -> sambaPrimaryGroupSID,
			'sambaprofilepath' => $this -> sambaProfilePath,
			'sambapwdcanchange' => $this -> sambaPwdCanChange,
			'sambapwdlastset' => $this -> sambaPwdLastSet,
			'sambapwdmustchange' => $this -> sambaPwdMustChange,
			'sambasid' => $this -> sambaSID
		);
		
		// Group-e Attribute
		$groupeAttr = array(
			'uidgroup-e' => $this -> uidGroupe,
			'permsgroup-e' => $this -> permsGroupe,
			'activgroup-e' => $this -> activGroupe
		);
		
		// Zarafa Attribute
		$zarafaAttr = array(
			'zarafaaccount' => $this -> zarafaAccount,
			'zarafaadmin' => $this -> zarafaAdmin,
			'zarafasharedstoreonly' => $this -> zarafaSharedStoreOnly
		);
		
		// one final array
		
		$attributes = array_merge($stdAttr, $shadowAttr);
		$obj = $this -> objectClass;
		//echo $this -> acc;
		// samba account?
		if ($this -> acc <= 2) {
			array_push($obj, $this -> smb);
			$attributes = array_merge($attributes, $sambaAttr);
		}
		//echo $this -> acc;
		// Groupware account?
		if ($this -> acc == 4) {
		    global $GROUPWARE;
			if ( $GROUPWARE == "zarafa") {
			    // zarafa account?
			    array_push($obj, $this -> smb);
			    $attributes = array_merge($attributes, $sambaAttr);
			    array_push($obj, $this -> zcp);
			    $attributes = array_merge($attributes, $zarafaAttr);
		    	} elseif ($GROUPWARE == "groupe") {
			    // group-e account?
			    array_push($obj, $this -> smb);
			    $attributes = array_merge($attributes, $sambaAttr);
			    array_push($obj, $this -> groupe);
			    $attributes = array_merge($attributes, $groupeAttr);
			} else {
			    // nur roundcube, keine Groupware
			    array_push($obj, $this -> smb);
			    $attributes = array_merge($attributes, $sambaAttr);
			}
		}
		return array_merge(array('objectclass' => $obj), $attributes);
	}
}

//--------------------
// specific functions
//--------------------

//--------------------
// LISTING FUNCTIONS
//--------------------

// user listing (long)
function userList($conn) {
	global $BASE_DN_USER, $BASE_DN_GROUP;
	$admins = ldapAdmins($conn);
	/*
	$result_admins = search($conn, $BASE_DN_GROUP, 'cn=domain admins', array('memberuid'));
	if ($result_admins) {
		$foo = cleanup($result_admins[0]);
		foreach ($foo['memberuid'] as $foo)
			array_push($admins, $foo);
	}
	*/
	
	$result = search($conn, $BASE_DN_USER, 'uidnumber=*', array('uid', 'uidnumber', 'gidnumber', 'loginshell', 'zarafaaccount', 'activgroup-e', 'sambaacctflags'));
	if ($result) {
		// create JSON response
		$json = array();
		for ($i=0; $i < $result["count"]; $i++) {
			$entry = cleanup($result[$i]);
			// Stefan Schaefer -- Hier wird der angezeigte Kontentyp festgelegt. Das hat sich Daniel etwas zu einfach gemacht. Typ3 / Gast wurde nicht beruecksichtigt.
			if (($entry['loginshell'] == '/bin/false') and ($entry['uidnumber'] > 0))
				if ($entry['gidnumber'] == '514' and (strpos($entry['sambaacctflags'], 'U') > 0)) $entry['TYPE'] = 3;
				else $entry['TYPE'] = 2;
			else {
				$isZarafaGroupe = 0;
				if (isset($entry['zarafaaccount'])) {
				    if ($entry['zarafaaccount'] == 1) $isZarafaGroupe = 1;
				}
				if (isset($entry['activegroup-e'])) {
				    if ($entry['activegroup-e'] == 1) $isZarafaGroupe = 1;
				}
				if (in_array($entry['uid'], $admins)) $entry['TYPE'] = 0;
				elseif ($isZarafaGroupe) $entry['TYPE'] = 4;
				else $entry['TYPE'] = 1;
			}
			unset($entry['loginshell']);
			unset($entry['dn']);
			array_push($json, $entry);
		}
		return $json;
	}
}

// user listing (short)
function userListShort($conn) {
	global $BASE_DN_USER;
	$result = search($conn, $BASE_DN_USER, 'uidnumber=*', array('uid'));
	if ($result) {
		// create JSON response
		$json = array();
		for ($i=0; $i < $result['count']; $i++) {
			$entry = cleanup($result[$i]);
			array_push($json, $entry['uid']);
		}
		return $json;
	}
}

// group listing (long)
function groupList($conn) {
	global $BASE_DN_GROUP;
	$result = search($conn, $BASE_DN_GROUP, 'gidnumber=*', array('cn', 'gidnumber'));
	if ($result) {
		// create JSON response
		$json = array();
		for ($i=0; $i < $result['count']; $i++) {
			$entry = cleanup($result[$i]);
			unset($entry['dn']);
			array_push($json, $entry);
		}
		return $json;
	}
}

// host listing (long)
function hostList($conn) {
	global $BASE_DN_DHCP, $DHCP_RANGE_SERVER, $DHCP_RANGE_PRINTER, $DHCP_RANGE_CLIENT, $DHCP_RANGE_IPDEV, $DHCP_IP_BASE;
// dhcphost-Array um Attribut "dhcpcomments" erweitert.
	$result = search($conn, $BASE_DN_DHCP, "objectclass=dhcphost", array('cn', 'dhcphwaddress', 'dhcpstatements', 'dhcpcomments'));
	if ($result) {
		// create JSON response
		$json = array();
		for ($i=0; $i < $result['count']; $i++) {
			$entry = cleanup($result[$i]);
			unset($entry['dn']);
			$ip = strrchr($entry['dhcpstatements'], '.');
			$ip = intval(substr($ip, 1));
			
			// 0: client, 1: printer, 2: server, 3: ip-device
			switch(true) {
				case ($ip >= $DHCP_RANGE_SERVER[0] && $ip <= $DHCP_RANGE_SERVER[1]): $type = 'Server'; break;
				case ($ip >= $DHCP_RANGE_IPDEV[0] && $ip <= $DHCP_RANGE_IPDEV[1]): $type = 'IP-Gerät'; break;
				case ($ip >= $DHCP_RANGE_PRINTER[0] && $ip <= $DHCP_RANGE_PRINTER[1]): $type = 'Drucker'; break;
				default:
					$type = 'Client';
			}
			$entry['TYPE'] = $type;
			
			array_push($json, $entry);
		}
		return $json;
	}
}

// links listing for user
function linksList($conn, $uid) {
	global $BASE_DN_USER;
	$result = search($conn, $BASE_DN_USER, "uid=$uid", array('labeledURI'));
	if ($result) {
		$result = cleanup($result[0]);
		unset($result['dn']);
		return $result;
	}
}

//--------------------
// DETAIL FUNCTIONS
//--------------------

// user details
function userDetail($conn, $uid) {
	global $BASE_DN_USER;
	$result = search($conn, $BASE_DN_USER, "uid=$uid");
	if ($result) {
		$entry = cleanup($result[0]);
		unset($entry['dn']);
		return $entry;
	}
}

// group details
function groupDetail($conn, $cn) {
	global $BASE_DN_GROUP;
	$result = search($conn, $BASE_DN_GROUP, "cn=$cn");
	if ($result) {
		$entry = cleanup($result[0]);
		unset($entry['dn']);
		
		if (isset($entry['memberuid'])) {
		    $member = $entry['memberuid'];
		    if (!is_array($member))
			$member = array($member);
		}
		else
		    $member = array();
		
		// build list of non-group users
		$tmp = userListShort($conn);
		$nonmember = array();
		foreach($tmp as $user) {
			$ix = array_search($user, $member);
			if ($ix === false)
				array_push($nonmember, $user);
		}
		
		unset($entry['memberuid']);
		
		return array($entry, $member, $nonmember);
	}
}

// host details
function hostDetail($conn, $cn) {
	global $BASE_DN_DHCP;
	$result = search($conn, $BASE_DN_DHCP, "cn=$cn");
	if ($result) {
		$entry = cleanup($result[0]);
		unset($entry['dn']);
		return $entry;
	}
}

//--------------------
// MOD FUNCTIONS
//--------------------

// user mod
function userModify($conn, $uid) {
	global $BASE_DN_USER, $cookie_data;
	// read user data from cookie
	//$attributes = json_decode($_COOKIE['invis-request'], true);
	$attributes = $cookie_data;
	
	//Typ des Benutzerkontos ermitteln
/*	$filter = "uid=$uid";
	$result = search($conn, $BASE_DN_USER, $filter, array('gidnumber', 'loginshell'));
	if ($result) {
		for ($i=0; $i < $result["count"]; $i++) {
		    $entry = cleanup($result[$i]);
		    // Stefan Scaefer -- Hier wird der angezeigte Kontentyp festgelegt. Das hat sich Daniel etwas zu einfach gemacht. Typ3 / Gast wurde nicht beruecksichtigt.
		    //echo $entry['loginshell'];
		    if ($entry['loginshell'] == '/bin/false') {
			if ($entry['gidnumber'] == '514') $entry['TYPE'] = 3;
			else $entry['TYPE'] = 2;
		    } else {
			if (in_array($entry['uid'], $admins)) $entry['TYPE'] = 0;
			else $entry['TYPE'] = 1;
		    }
		}
	}*/

	// if password changed: set related attributes
	if (isset($attributes['userpassword'])) {
		$attributes = array_merge($attributes, getPasswordTimes());
		//echo $entry['TYPE'];
		// Wenn eine Passwortaenderung erfolgen soll und es sich um einen Mailaccount handelt, alle samba-Passwortattribute entfernen.
		if ($entry['TYPE'] == 2) {
		    unset($attributes['sambantpassword']);
		    unset($attributes['sambalmpassword']);
		    unset($attributes['sambapwdlastset']);
		    unset($attributes['sambapwdlastchange']);
		    unset($attributes['sambapwdmustchange']);
		}
		//echo($attributes['userpassword']);
	};
	
	$ok = modify($conn, "uid=$uid,$BASE_DN_USER", $attributes);
	return ($ok)?0:array(ldap_errno($conn) => ldap_error($conn), $attributes);
}

// group mod
function groupModify($conn, $cn) {
	global $BASE_DN_GROUP, $cookie_data;
	// read group data from cookie
	//$attributes = json_decode($_COOKIE['invis-request'], true);
	$attributes = $cookie_data;
	
	$ok = modify($conn, "cn=$cn,$BASE_DN_GROUP", $attributes);
	return ($ok)?0:array(ldap_errno($conn) => ldap_error($conn));
}

// host mod
function hostModify($conn, $cn) {
	global $BASE_DN_DHCP, $BASE_DN_DNS_FOR, $BASE_DN_DNS_REV, $cookie_data, $DOMAIN;
	$attributes = $cookie_data;
	
	// if cn has changed, rename DHCP&DNS-forward, change DNS-reverse
	if (isset($attributes['cn'])) {
		$newcn = $attributes['cn'];
		unset($attributes['cn']);
		
		// Test fuer neues Atttribut
		echo $attributes['location'];
		
		// rename DHCP
		rename_ldap($conn, "cn=$cn,$BASE_DN_DHCP", "cn=$newcn");
		
		// Ab hier wird es komplizierter als frueher:
		// Es muessen jeweils zwei Eintraege geaendert werden: Host-Knoten und dlzRecordID-Objekt(e)
		// Dabei ist zu beachten, dass es mehrere dlzRecordID-Objekte pro Host geben kann.
		// Vorgehensweise:
		// 1.) neuen Hostknoten erstellen
		// 2.) Alle Subknoten des alten Hostknotens mit ldap_mod_replace oder ldap_rename "verschieben"
		// 3.) alten Hostknoten loeschen.
		
		// rename DNS-forward -- kann nicht funktionieren, da Untereintraege vorhanden sind
		//rename_ldap($conn, "dlzhostname=$cn,$BASE_DN_DNS_FOR", "dlzhostname=$newcn");
		
		// Host-Knoten erzeugen
		$attributesdlz = array(
			'dlzhostname' => "$newcn",
			'objectclass' => array('dlzhost')
		);
		$ok1 = add($conn, "dlzhostname=$newcn,$BASE_DN_DNS_FOR", $attributesdlz);
		
		//Unterknoten verschieben
		//$rdnreplace['dlzHostname'] = array("$newcn");
		$ok2 = move_subtree($conn, "$BASE_DN_DNS_FOR", "$cn", "$newcn", 'dlzhostname');
		// Alten Hostknoten loeschen
		//$ok3 = delete($conn, "dlzhostname=$cn,$BASE_DN_DNS_FOR");;
		//echo $ok3;

		// find DNS-reverse entry
		$result = search($conn, $BASE_DN_DHCP, "cn=$newcn", array('dhcpstatements'));
		$result = cleanup($result[0]);
		$ip = intval(substr(strrchr($result['dhcpstatements'], '.'), 1));
		
		$result = search($conn, $BASE_DN_DNS_REV, "(&(dlzrecordid=*)(dlzhostname=$ip)(dlzdata=$cn.$DOMAIN.))", array('dlzrecordid'));
		$result = cleanup($result[0]);
		$dlzrid = $result['dlzrecordid'];

		// change DNS-reverse entry
		$ok3 = modify($conn, "dlzrecordid=$dlzrid,dlzhostname=$ip,$BASE_DN_DNS_REV", array('dlzdata' => "$newcn.$DOMAIN."));
		
		// set new cn
		$cn = $newcn;
	} else {
		// Kontrollen faken
		$ok1 = true;
		$ok2 = true;
		$ok3 = true;
	}
	
	// modify DHCP
	$ok4 = modify($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
	
	return ($ok1 && $ok2 && $ok3 && $ok4)?0:array(ldap_errno($conn) => ldap_error($conn));
}

//--------------------
// CREATE FUNCTIONS
//--------------------

// user create
function userCreate($conn, $uid) {
	global $BASE_DN_ACCOUNT, $BASE_DN_USER, $SMB_DOMAIN, $DOMAIN, $cookie_data, $USER_ADD_MAIL_SUB, $USER_ADD_MAIL_TXT;
	// get availavle uidNumber, SID, ridBase
	$result = search($conn, $BASE_DN_ACCOUNT, "sambaDomainName=$SMB_DOMAIN", array('uidNumber', 'sambaAlgorithmicRidBase', 'sambaSID'));
	$data = cleanup($result[0]);
	//echo $data['sambasid'];
	$next = intval($data['uidnumber']);
	//echo $next;
	$SID = $data['sambasid'];
	$ridbase = $data['sambaalgorithmicridbase'];
	
	// create new user object
	$u = new User($uid, $next, $SID, $ridbase, $DOMAIN);

	// account type, fetch from POST var
	// (default 0) 0: user, 1: admin, 2: guest, 3: mail, 4: zarafa-user
	if (isset($_POST['t'])) {
		//echo $_POST['t'];
		$u -> acc = intval($_POST['t']); }
	//echo $u -> acc;
	$attributes = $u -> getAttributes();

	// overwrite default attributes with given ones
	$given = $cookie_data;
	foreach($given as $k => $v) {
		//echo $k."&nbsp;";
		//echo $v."<p>";
		// Stefan Schaefer -- Hier liegt der Fehler mit smbntpassword, das Attribut wird auch dann erzeugt, wenn der Account ein Mailaccount ist.
		    $attributes[$k] = $v;
	}
	// Stefan Schaefer, wenn ich nicht verhindern kann, dass sambantpassword ins array wandert, schmeisse ichs bei Bedarf halt wieder raus.
	if ( $u -> acc == 3 ) {
	    //echo $attributes['sambantpasswd'];
	    unset($attributes['sambantpassword']);
	}
	
	$ok = add($conn, "uid=$uid,$BASE_DN_USER", $attributes);
	if ($u -> acc == 1) {
		makeAdmin($conn, $u);
	}
	
	// increase available uidNumber if successfull
	if ($ok) {
		modify($conn, "sambaDomainName=$SMB_DOMAIN,$BASE_DN_ACCOUNT", array('uidNumber' => ($next + 1)));
		$val = shell_exec("sudo /usr/bin/createhome $uid;");
		mail("$uid@$DOMAIN", $USER_ADD_MAIL_SUB, $USER_ADD_MAIL_TXT);
	}
	return ($ok)?0:array(ldap_errno($conn) => ldap_error($conn));
}

// set admin
function makeAdmin($conn, $u) {
	global $BASE_DN_GROUP;
	$list = ldapAdmins($conn);
	
	// Stefan Schaefer -- neues Gruppenmitglied hinzufuegen
	// Mann Daniel, erst richtig getestet, dann nur halb uebernommen!
	array_push($list, $u -> uid);
	// write admin list back
	$ok = modify($conn, "cn=Domain Admins,$BASE_DN_GROUP", array('memberuid' => $list));
}

// unset admin
function removeAdmin($conn, $u) {
	global $BASE_DN_GROUP;
	$result = search($conn, $BASE_DN_GROUP, 'cn=Domain Admins', array('memberUid'));
	if ($result)
		$result = cleanup($result[0]);
		$list = $result['memberuid'];
		$i = array_search($u, $list);
		if ($i === true) {
			unset($list[$i]);
			modify($conn, "cn=Domain Admins,$BASE_DN_GROUP", array('memberUid' => $list));
		}
}

// group create
function groupCreate($conn, $cn) {
	global $BASE_DN_ACCOUNT, $BASE_DN_GROUP, $SMB_DOMAIN, $cookie_data;
	// get available gidNumber, SID, ridBase
	$result = search($conn, $BASE_DN_ACCOUNT, "sambaDomainName=$SMB_DOMAIN", array('gidNumber', 'sambaAlgorithmicRidBase', 'sambaSID'));
	$data = cleanup($result[0]);
	$next = intval($data['gidnumber']);
	$SID = $data['sambasid'];
	$ridbase = $data['sambaalgorithmicridbase'];
	
	// create new group object
	$g = new Group($cn, $next, $SID, $ridbase);
	$attributes = $g -> getAttributes();
	// overwrite default attributes with given ones
	$given = $cookie_data;
	foreach($given as $k => $v) {
		$attributes[$k] = $v;
	}
	unset($attributes['cn']);
	
	$ok = add($conn, "cn=$cn,$BASE_DN_GROUP", $attributes);
	
	// increase available gidNumber if successfull
	if ($ok) {
		modify($conn, "sambaDomainName=$SMB_DOMAIN,$BASE_DN_ACCOUNT", array('gidNumber' => ($next + 1)));
		shell_exec("sudo /usr/bin/creategroupshare $cn;");
	}
	
	return ($ok)?0:array(ldap_errno($conn) => ldap_error($conn));
}

// host create
function hostCreate($conn, $cn) {
	global $DOMAIN, $DHCP_IP_BASE, $DHCP_IP_REV, $BASE_DN_DHCP, $BASE_DN_DNS_FOR, $BASE_DN_DNS_REV, $cookie_data, $DHCP_RANGE_SERVER, $DHCP_RANGE_PRINTER, $DHCP_RANGE_CLIENT,  $DHCP_RANGE_IPDEV;
	
	// 0: client, 1: printer, 2: server, 3: ipdevice
	if (isset($_POST['t']))
		$type = intval($_POST['t']);
	else $type = 0;
	
	$free_server = range($DHCP_RANGE_SERVER[0], $DHCP_RANGE_SERVER[1], 1);
	$free_printer = range($DHCP_RANGE_PRINTER[0], $DHCP_RANGE_PRINTER[1], 1);
	$free_ipdev = range($DHCP_RANGE_IPDEV[0], $DHCP_RANGE_IPDEV[1], 1);
	$free_client = range($DHCP_RANGE_CLIENT[0], $DHCP_RANGE_CLIENT[1], 1);
	
	// list all current hosts
	$result = search($conn, $BASE_DN_DHCP, 'objectclass=dhcphost', array('dhcpstatements'));
	if ($result) {
		$occ_client = array();
		$occ_printer = array();
		$occ_ipdev = array();
		$occ_server = array();
		
		// build list with all used IPs
		for ($i=0; $i < $result["count"]; $i++) {
			$entry = cleanup($result[$i]);
			$ip = strrchr($entry['dhcpstatements'], '.');
			$ip = intval(substr($ip, 1));
			switch(true) {
				case ($ip >= $DHCP_RANGE_SERVER[0] && $ip <= $DHCP_RANGE_SERVER[1]):
					array_push($occ_server, $ip); break;
				case ($ip >= $DHCP_RANGE_PRINTER[0] && $ip <= $DHCP_RANGE_PRINTER[1]):
					array_push($occ_printer, $ip); break;
				case ($ip >= $DHCP_RANGE_IPDEV[0] && $ip <= $DHCP_RANGE_IPDEV[1]):
					array_push($occ_ipdev, $ip); break;
				default:
					array_push($occ_client, $ip); break;
			}
		}

		// remove used client IPs
		foreach ($occ_client as $k => $v) {
			unset($free_client[$v - $DHCP_RANGE_CLIENT[0]]);
		}
		// remove used printer IPs
		foreach ($occ_printer as $k => $v) {
			unset($free_printer[$v - $DHCP_RANGE_PRINTER[0]]);
		}
		// remove used ipdev IPs
		foreach ($occ_ipdev as $k => $v) {
			unset($free_ipdev[$v - $DHCP_RANGE_IPDEV[0]]);
		}
		// remove used server IPs
		foreach ($occ_server as $k => $v) {
			unset($free_server[$v - $DHCP_RANGE_SERVER[0]]);
		}
		
		// next free ip
		switch($type) {
			case 1:
				$free = array_values($free_printer); break;
			case 2:
				$free = array_values($free_server); break;
			case 3:
				$free = array_values($free_ipdev); break;
			default:
				$free = array_values($free_client); break;
		}
		
		$next = $free[0];
		$mac = $cookie_data['dhcphwaddress'];
		// Location uebernehmen
		$location = $cookie_data['location'];
		
		// create DHCP entry
		$attributes = array(
			'dhcphwaddress' => $mac,
			'dhcpstatements' => "fixed-address $DHCP_IP_BASE.$next",
			'dhcpcomments' => "$location",
			'objectclass' => array('top', 'dhcphost')
		);
		$ok1 = add($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
		
		// Achtung: Alles Folgende muss noch gegen doppelte Eintraege gesichert werden!
		// Evtl. vom DHCP Eintrag abhaengig machen
		
		// create DNS forward
		// Es muessen fuer das DLZ-Schema zwei LDAP Eintraege erzeugt werden:
		// 1: Host-Knoten: dlzHostName=....,dlzDomainName=...,$BASE_DN_DNS_FOR
		// 2: dlzRecordID-Knoten: dlzRecordID=XXX,dlzHostName=...
		// Zu verwendende RecordID wird aus infoIdentifier=dlzRecordID,dlzDomainName=...,$BASE_DN_DNS_FOR + 5 errechnet
		// Dieser Wert muss dann im obengenannten Knoten aktualisiert werden.
		// Variable $next = IP-Addresse (Hostanteil)
		
		//dlzRecordID bilden
		$result = search($conn, $BASE_DN_DNS_FOR, 'objectclass=infoentry', array('infoText'));
		$data = cleanup($result[0]);
		$dlzridbase = intval($data['infotext']);
		$dlzrid = $dlzridbase + 5;
		// Host-Knoten erzeugen
		$attributes = array(
			'dlzhostname' => "$cn",
			'objectclass' => array('dlzhost')
		);
		$ok2 = add($conn, "dlzhostname=$cn,$BASE_DN_DNS_FOR", $attributes);
		
		// Host-Objekt erzeugen
		$attributes = array(
			'dlzrecordid' => "$dlzrid",
			'dlzhostname' => $cn,
			'dlzttl' => 86400,
			'dlztype' => 'a',
			'dlzipaddr' => "$DHCP_IP_BASE.$next",
			'objectclass' => array('dlzarecord')
		);
		$ok3 = add($conn, "dlzrecordid=$dlzrid,dlzhostname=$cn,$BASE_DN_DNS_FOR", $attributes);
		
		//dlzRecordID zurueckschreiben
		modify($conn, "infoIdentifier=dlzRecordID,$BASE_DN_DNS_FOR", array('infoText' => $dlzrid ));

		//dlzRecordID bilden
		$result = search($conn, $BASE_DN_DNS_REV, 'objectclass=infoentry', array('infoText'));
		$data = cleanup($result[0]);
		$dlzridbase = intval($data['infotext']);
		$dlzrid = $dlzridbase + 5;

		// Hostknoten erzeugen
		$attributes = array(
			'dlzhostname' => "$next",
			'objectclass' => array('dlzhost')
		);
		$ok4 = add($conn, "dlzhostname=$next,$BASE_DN_DNS_REV", $attributes);

		// create DNS reverse
		$attributes = array(
			'dlzrecordid' => "$dlzrid",
			'dlzhostname' => "$next",
			'dlztype' => "ptr",
			'dlzdata' => "$cn.$DOMAIN.",
			'dlzttl' => 86400,
			'objectclass' => array('dlzptrrecord')
		);
		$ok5 = add($conn, "dlzrecordid=$dlzrid,dlzhostname=$next,$BASE_DN_DNS_REV", $attributes);

		//dlzRecordID zurueckschreiben
		modify($conn, "infoIdentifier=dlzRecordID,$BASE_DN_DNS_REV", array('infoText' => $dlzrid ));

		return ($ok1 && $ok2 && $ok3 && $ok4 && $ok5)?0:array(ldap_errno($conn) => ldap_error($conn));
	} else {
		echo "oops";
	}
}

//--------------------
// DELETE FUNCTIONS
//--------------------

// user delete
function userDelete($conn, $uid) {
	global $BASE_DN_USER;
	$flag = intval($_POST['t']);
	$ok = delete($conn, "uid=$uid,$BASE_DN_USER");
	if ($ok) {
		if ($flag == 1) shell_exec("sudo /usr/bin/deletehome $uid;");
		removeAdmin($uid);
	}
	return ($ok)?0:array(ldap_errno($conn) => ldap_error($conn));
}

// group delete
function groupDelete($conn, $cn) {
	global $BASE_DN_GROUP;
	$ok = delete($conn, "cn=$cn,$BASE_DN_GROUP");
	return ($ok)?0:array(ldap_errno($conn) => ldap_error($conn));
}

// host delete
function hostDelete($conn, $cn) {
	global $BASE_DN_DHCP, $BASE_DN_DNS_FOR, $BASE_DN_DNS_REV;
	// fetch associated IP for cn
	$result = search($conn, $BASE_DN_DHCP, "cn=$cn", array('dhcpstatements'));
	if ($result) {
		$result = cleanup($result[0]);
		$ip = strrchr($result['dhcpstatements'], '.');
		$ip = substr($ip, 1);
		
		// DHCP entry
		$ok1 = delete($conn, "cn=$cn,$BASE_DN_DHCP");
		
		// DNS forward
		$ok2 = delete_subtree($conn, "dlzhostname=$cn,$BASE_DN_DNS_FOR");
		
		// DNS reverse
		$ok3 = delete_subtree($conn, "dlzhostname=$ip,$BASE_DN_DNS_REV");
		
		return ($ok1 && $ok2 && $ok3)?0:array(ldap_errno($conn) => ldap_error($conn));
	}
}

//--------------------
// helpers
//--------------------

function supportMail($conn, $from) {
	global $cookie_data, $PORTAL_SUPPORT_MAIL, $DOMAIN;
	include('Mail.php');
	
	$recipients = $PORTAL_SUPPORT_MAIL;
	
	$headers['From']    = "$from@$DOMAIN";
	$headers['To']      = $PORTAL_SUPPORT_MAIL;
	$headers['Subject'] = $cookie_data['subject'];
	
	$body = $cookie_data['msg'];
	
	$params['sendmail_path'] = '/usr/sbin/sendmail';
	
	// Create the mail object using the Mail::factory method
	$mail_object =& Mail::factory('sendmail', $params);
	$flag = $mail_object -> send($recipients, $headers, $body);
	return ($flag === true)?0:'Ihre Email konnte nicht gesendet werden!';
}

function fileUploadProgress($conn, $id) {
	$status = apc_fetch(ini_get('apc.rfc1867_prefix') . $id);
	//return array('id' => $id, 'current' => $status['current'], 'total' => $status['total']);
	return array($id, $status);
}

function hostDiscover($conn) {
	
}

//--------------------
// main functionality
//--------------------

$conn = connect();
$bind = bind($conn);

//--------------------
// commands allowed for users
$ALLOWED_CMDS = array('user_detail', 'user_mod', 'links_list', 'support_mail', 'upload_progress', 'download');

if (($cookie_auth['uid'] == $USR && (array_search($CMD, $ALLOWED_CMDS) !== false)) || (array_search($cookie_auth['uid'], ldapAdmins($conn)) !== false)) {
//if (true) {
	if ($CMD == 'user_list') {
		echo json_encode(userList($conn));
	}
	elseif ($CMD == 'user_list_short') {
		echo json_encode(userListShort($conn));
	}
	elseif ($CMD == 'group_list') {
		echo json_encode(groupList($conn));
	}
	elseif ($CMD == 'host_list') {
		echo json_encode(hostList($conn));
	}
	elseif($CMD == 'links_list') {
		echo json_encode(linksList($conn, $USR));
	}
	elseif ($CMD == 'user_detail') {
		echo json_encode(userDetail($conn, $USR));
	}
	elseif ($CMD == 'group_detail') {
		echo json_encode(groupDetail($conn, $USR));
	}
	elseif ($CMD == 'host_detail') {
		echo json_encode(hostDetail($conn, $USR));
	}
	elseif ($CMD == 'user_delete') {
		echo json_encode(userDelete($conn, $USR));
	}
	elseif ($CMD == 'group_delete') {
		echo json_encode(groupDelete($conn, $USR));
	}
	elseif ($CMD == 'host_delete') {
		echo json_encode(hostDelete($conn, $USR));
	}
	elseif ($CMD == 'hostDiscover') {
		echo json_encode(hostDiscover($conn, $USR));
	}
	elseif ($CMD == 'user_create') {
		echo json_encode(userCreate($conn, $USR));
	}
	elseif ($CMD == 'group_create') {
		echo json_encode(groupCreate($conn, $USR));
	}
	elseif ($CMD == 'host_create') {
		echo json_encode(hostCreate($conn, $USR));
	}
	elseif ($CMD == 'user_mod') {
		echo json_encode(userModify($conn, $USR));
	}
	elseif ($CMD == 'group_mod') {
		echo json_encode(groupModify($conn, $USR));
	}
	elseif ($CMD == 'host_mod') {
		echo json_encode(hostModify($conn, $USR));
	}
	elseif ($CMD == 'support_mail') {
		echo json_encode(supportMail($conn, $USR));
	}
	elseif ($CMD == 'upload_progress') {
		echo json_encode(fileUploadProgress($conn, $_POST['id']));
	}
} else {
	header("HTTP/1.0 401 Unauthorized");
	die();
}

//--------------------

unbind($conn);
?>
