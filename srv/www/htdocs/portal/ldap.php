<?php

/* ldap.php v1.1
 * LDAP utility functions and ldap_xxx wrapper
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2011 Stefan Schaefer, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

require_once('config.php');

//--------------------
// LDAP FUNCTIONS
//--------------------

// connect to LDAP server
function connect() {
	global $LDAP_SERVER;
	$conn = ldap_connect($LDAP_SERVER);
	ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	return $conn;
}

// bind to LDAP server
function bind($conn) {
	global $LDAP_BIND_DN, $LDAP_BIND_PW;
	return ldap_bind($conn, $LDAP_BIND_DN, $LDAP_BIND_PW);
}

// unbind from LDAP server
function unbind($conn) {
	ldap_unbind($conn);
}

// search LDAP server
function search($conn, $basedn, $filter, $justthese = array("*")) {
	if ($search = ldap_search($conn, $basedn, $filter, $justthese)) {
		return ldap_get_entries($conn, $search);
	} else
		return false;
}

// modify an entry in the LDAP server
function modify($conn, $basedn, $entry_array) {
	return ldap_modify($conn, $basedn, $entry_array);
}

// add an entry to the LDAP server
function add($conn, $dn, $attr) {
	return ldap_add($conn, $dn, $attr);
}

// delete an entry from the LDAP server
function delete($conn, $dn) {
	return ldap_delete($conn, $dn);
}

// delete a subtree from the LDAP server
function delete_subtree($conn, $dn){
        //searching for sub entries
        $sr=ldap_list($conn, $dn,"ObjectClass=*",array(""));
        $info = ldap_get_entries($conn, $sr);
        for($i=0;$i<$info['count'];$i++){
            //deleting recursively sub entries
            $result=delete_subtree($conn, $info[$i]['dn']);
            if(!$result){
                //return result code, if delete fails
                return($result);
            }
        }
        return(ldap_delete($conn, $dn));
}

// rename an entry in the LDAP server
function rename_ldap($conn, $olddn, $newrdn) {
	return ldap_rename($conn, $olddn, $newrdn, null, true);
}

// move a subtree in the LDAP Server
// $rdnreplace ist ein Array z.b. rdnreplace['dlzHostname'] = array("pc1", "pc2");
// Alle Objekte unterhalb eines Knotens lassen sich nicht verschieben.
// Statt dessen muss der Vorgang als Abfolge von Einzelschritten realisiert werden:
// 1. Neuen Zielknoten anlegen (in ajax.php).
// 2. Unterobjekte kopieren.
// 3. Attribute der kopierten Knoten anpassen, soweit diese Teil des DNs sind.
// 4. Alte Objekte loeschen.
// 5. Alten Knoten loeschen (in ajax.php).

function move_subtree($conn, $basedn, $oldvalue, $newvalue, $attr ) {
	// DNs erzeugen
        $dn = "$attr=$oldvalue,$basedn";
        $newdn = "$attr=$newvalue,$basedn";
        //searching for sub entries
        $sr0 = ldap_list($conn, $dn,"ObjectClass=*",array(""));
        $object = ldap_get_entries($conn, $sr0);
        //$sr1 = ldap_search($conn, $dn,"ObjectClass=*");
        //$result_entries = ldap_get_entries($conn, $sr1);
        for($i=0;$i<$object['count'];$i++){
            // rdn ermitteln
            $rdn1=ldap_explode_dn($object[$i]['dn'],0);
            $result = search($conn, $dn, ($rdn1[0]));
            $data = cleanup($result[0]);
            //echo $rdn1[0];
	    // 1. Wert des LDAP-Objekts aendern (geht bisher nur fuer "SINGLE VALUES") 
	    $data[$attr] = $newvalue;
	    // 2. Objekt unter neuem Knoten anlegen
	    //echo "$rdn1[0],$newdn";
	    unset($data['dn']);
	    add($conn, "$rdn1[0],$newdn", $data);
        }
	// 3 Altes Objekt loeschen
	return(delete_subtree($conn, $dn));
}

//--------------------
// HELPER FUNCTIONS
//--------------------

// clean up the result entry for better usage
// remove 'count' and number -> key entries from ldap search result
function cleanup($result_entry) {
	// remove 'count' entry
	$n = $result_entry['count'];
	unset($result_entry['count']);
	
	// remove {number} -> key entries
	for ($i = 0; $i <= $n; $i++) {
		unset($result_entry[$i]);
	}
	
	// make value-arrays a single value if single entry
	foreach($result_entry as $k => $v) {
		if (is_array($v)) {
			if ($result_entry[$k]['count'] == 1)
				$result_entry[$k] = $result_entry[$k][0];
			else
				// remove 'count' entry
				unset($result_entry[$k]['count']);
		}
	}
	
	return $result_entry;
}

// cleanup2 -> bereinigt ausgelesene Objekt-Arrays so, dass Sie wieder per ldap_add geschrieben werden koennen.
// (c) Thorsten "d3rb" -- entnommen dem selfphp-Forum
function cleanup2($result_entry){
	$keys = array_keys($result_entry);
	$new = array();
	for($key_count=0; $key_count < count($keys); $key_count++) {
		if($keys[$key_count] != "count" && ! is_int($keys[$key_count]) && $keys[$key_count] != 'dn') {
			if(is_array($result_entry[$keys[$key_count]])) {
				$new[$keys[$key_count]] = array();
				for($attr_count=0; $attr_count< count($result_entry[$keys[$key_count]])-1; $attr_count++) {
					$new[$keys[$key_count]][$attr_count] = $result_entry[$keys[$key_count]][$attr_count];
				}
			} else {
				$new[$keys[$key_count]] = $result_entry[$keys[$key_count]];
			}
		}
	}
	return $new;
}

// list all admin entries
function ldapAdmins($conn) {
	global $BASE_DN_GROUP;
	$admins = array();
	$result_admins = search($conn, $BASE_DN_GROUP, 'cn=domain admins', array('memberuid'));
	if ($result_admins) {
		$foo = cleanup($result_admins[0]);
		foreach ($foo['memberuid'] as $foo)
			array_push($admins, $foo);
	}
	return $admins;
}

// list all mobiluser entries
function mobilUsers($conn) {
	global $BASE_DN_GROUP;
	$mobilusers = array();
	$result_mobilusers = ldap_search($conn, $BASE_DN_GROUP, 'cn=mobilusers');
	$group_mobilusers = ldap_first_entry($conn, $result_mobilusers);
	if ($result_mobilusers) {
		$mobilusers = ldap_get_values($conn, $group_mobilusers, 'memberuid');
	}
	return $mobilusers;
}

?>
