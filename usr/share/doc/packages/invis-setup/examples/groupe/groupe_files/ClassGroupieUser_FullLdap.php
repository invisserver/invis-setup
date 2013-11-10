<?php
/*
	+-----------------------------------------------------------------------------+
	| GROUP-E collaboration software                                              |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 2002-2005 group-e,                                            |
	|         Endo7 GmbH/Srl                                                      |
	|	  via-Goethe-strasse 34a                                              |
	|	  39100 Bozen/Bolzano                                                 |
	|	  ITALIEN/ITALIA                                                      |
	|         contact@endo7.com                                                   |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        | http://www.fsf.org/                                                         |
	+-----------------------------------------------------------------------------+
*/
class GroupieUser {
	var $_ds;
	var $_error;
	var $_sort;
	var $_myDomain=GE_DOMAIN;
	var $_group_classes=array(
			'posixGroup',
			'sambaGroupMapping',
			'groupeGroup',
	);
	function GroupieUser() {

	}
	function setDomain($val) {
		$this->_myDomain=$val;

	}
	function cmp($a, $b) {
		foreach ($this->_sort as $val) {
			$res=strnatcasecmp($a[$val],$b[$val]);
			if ($res) break;
		}
		return $res;
	}
	function getUserFields() {
		$result=array(
			'g',
			'p',
		);
		if ($this->getFrmCfg('ldap/smbServer')) {
			$result[]='s';
		}
		if ($this->getFrmCfg('ldap/squid')) $result[]='i';
		return $result;
	}
	function getGroupClasses() {
		$result=array(
			'posixGroup',
			'groupeGroup',
		);
		if ($this->getFrmCfg('ldap/smbServer')) {
			$result[]='sambaGroupMapping';
		}
		return $result;
	}
	function getUserClasses() {
		$result=array(
			/*'top',*/
			/*'Person',*/
			'inetOrgPerson',
			'groupeUser',
			'shadowAccount',
		);
		if ($this->getFrmCfg('ldap/squid')) {
			$result[]='groupeSquid';
		}
		if ($this->getFrmCfg('ldap/smbServer')) {
			$result[]='sambaSamAccount';
		}
		if ($this->getFrmCfg('ldap/mailRouting')) {
			$result[]='inetLocalMailRecipient';
		}
		//if ($this->getFrmCfg('ldap/posix')) {
			$result[]='posixAccount';
		//}
		return $result;
	}
	function _getBaseDn($dn='') {
		if (defined('GE_LDAP_DOMAIN_SEP')) {
			$sep=GE_LDAP_DOMAIN_SEP;
		} else {
			$sep='_';
		}
		if (!$dn) {
			return str_replace('{DOMAIN}',str_replace('.',$sep,$this->_myDomain),$this->getFrmCfg('ldap/basedn'));
		} else {
			return str_replace('{DOMAIN}',str_replace('.',$sep,$this->_myDomain),$dn);
		}
	}
	function _connect() {
		$_host=defined('GE_LDAPHost')?GE_LDAPHost:$this->getFrmCfg('ldap/host');
		$_port=intval(defined('GE_LDAPPort')?GE_LDAPPort:$this->getFrmCfg('ldap/port'));

		$this->_ds=ldap_connect($_host,$_port);
		//debug($this->_ds);
		if ($this->_ds) {
			@ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, 3);
   			if ($this->getFrmCfg('ldap/tls')) @ldap_start_tls($this->_ds);
		}
		return $this->_ds;
	}
	function getError() {
		return "LDAP: ".@ldap_error($this->_ds);
	}
	function _bind($binddn = NULL ,$password = NULL) {
		$this->_connect();
		if (!$binddn) {
			$_admin_bnd=defined('GE_LDAPAdminBind')?GE_LDAPAdminBind:$this->getFrmCfg('ldap/admin_bnd');
			$_admin_pwd=defined('GE_LDAPPassword')?GE_LDAPPassword:$this->getFrmCfg('ldap/admin_pwd');
			//debug($this->getFrmCfg('ldap/admin_bnd') .'<br>'. $this->getFrmCfg('ldap/admin_pwd'));
			return @ldap_bind($this->_ds, $_admin_bnd, $_admin_pwd);
		} else {
			return @ldap_bind($this->_ds, $this->utf8encode($binddn), $this->utf8encode($password));
		}
	}
	function _add($dn,$add) {
		//debug($dn);
		//debug($add);
		return ldap_add($this->_ds,$this->utf8encode($dn),$this->utf8encode($add));
	}
	function _rename($dn,$newRdn,$newParent,$del=true) {
		return ldap_rename($this->_ds, $this->utf8encode($dn), $this->utf8encode($newRdn), $this->utf8encode($newParent), $del);
	}
	function _del($dn) {
		return ldap_delete($this->_ds,$this->utf8encode($dn));
	}
	function _mod_add($dn,$add) {
		//debug($dn);
		//debug($add);
		return ldap_mod_add($this->_ds,$this->utf8encode($dn),$this->utf8encode($add));
	}
	function _mod_rep($dn,$add) {
		//debug($dn);
		//debug($add);
		return ldap_mod_replace($this->_ds,$this->utf8encode($dn),$this->utf8encode($add));
	}
	function _mod_del($dn,$del) {
		//debug($dn);
		//debug($del);
		return ldap_mod_del($this->_ds,$this->utf8encode($dn),$this->utf8encode($del));
	}
	function _listUser($search_dn,$search_attr= array(),$sub=0) {
		$result=false;
		//debug($search_dn);
		if ($sub) {
			$sr=ldap_search($this->_ds,$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(), $this->utf8encode($search_dn),$search_attr);
		} else {
			$sr=ldap_list($this->_ds,$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(), $this->utf8encode($search_dn),$search_attr);
		}
		if ($sr) {
			$result = ldap_get_entries($this->_ds, $sr);
		}
		return $this->utf8decode($result);
	}
	function _listGroup($search_dn,$search_attr= array(),$sub=0) {
		$result=false;
		//debug($search_dn);
		if ($sub) {
			$sr=ldap_search($this->_ds,$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $this->utf8encode($search_dn),$search_attr);
		} else {
			$sr=ldap_list($this->_ds,$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $this->utf8encode($search_dn),$search_attr);
		}
		if ($sr) {
			$result = ldap_get_entries($this->_ds, $sr);
		}
		//debug($result);
		return $this->utf8decode($result);
	}
	function listSchema($search_dn='') {
		require_once('lib/class_LdapObjectClass.php');
		$result=array();
		if ($this->_bind()) {
			$sr=ldap_read($this->_ds,'cn=Subschema','(objectClasses=*)',array('objectclasses'));

			//$sr=ldap_search($this->_ds,'cn=schema', $this->utf8encode($search_dn));
			if ($sr) {
				$raw_result = ldap_get_entries($this->_ds, $sr);
			}
			$this->_close();
			if ($raw_result[0]['objectclasses']['count']) {
				$raw_result = $this->utf8decode($raw_result[0]['objectclasses']);
				unset($raw_result['count']);
				foreach ($raw_result as $class_string) {
					if (is_null($class_string) || ! strlen($class_string))	continue;
					$object_class = new LdapObjectClass($class_string);
					$result[strtolower($object_class->getName())] = $object_class;
				}
			}
		}
		return $result;
	}
	function _close() {
		@ldap_close($this->_ds);
	}
	function _getNextID($typ) {
		if ($this->getFrmCfg('ldap/use_idpool') && $this->getFrmCfg('ldap/basedn_nextid')) {
			$nextid=3000;
			if ($this->_bind()) {
				$base_dn=$this->getFrmCfg('ldap/basedn_nextid');
				switch ($typ) {
					case 'gid':
						$field=strtolower('gidNumber');
					break;
					default:
						$field=strtolower('uidNumber');
				}
				$sr=@ldap_read($this->_ds,$this->utf8encode($base_dn),"objectClass=sambaUnixIdPool",array($field));
				if ($sr) {
					$info = ldap_get_entries($this->_ds, $sr);
					if ($info["count"]) {
						$nextid=max($nextid,$info[0][$field][0]);
						if ($this->_mod_rep($base_dn,array($field=>($nextid+1)))) {
							return $nextid;
						}
					}
				}
				$this->_close();
			}
		}
		switch ($typ) {
			case 'gid':
				return $this->db->nextid("groups");
			break;
			default:
				return $this->db->nextid("auth_user_md5");
		}
	}

	function check_user($username,$user_id=0){
		if ($this->_bind()) {
			$search_dn=$this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEsc($username);
			if ($info=$this->_listUser($search_dn,array($this->getFrmCfg('ldap/attr/uid')))) {
				for ($i=0; $i<$info["count"]; $i++) {
					if (!$user_id || $info[$i]["uidnumber"][0]!=$user_id) {
						return $info[$i]["uidnumber"][0];
					}
				}
				return 0;
			}
			$this->_close();
		}
		return false;
	}
	function check_group($groupname,$group_id=0){
		if ($this->_bind()) {
			$search_dn=$this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEsc($groupname);
			if ($info=$this->_listGroup($search_dn,array($this->getFrmCfg('ldap/attr/gid')))) {
				for ($i=0; $i<$info["count"]; $i++) {
					if (!$group_id || $info[$i]["gidnumber"][0]!=$group_id) {
						return $info[$i]["gidnumber"][0];
					}
				}
				return 0;
			}
			$this->_close();
		}
		return false;
	}
	function check_uidNumber($user_id=0){
		if ($this->_bind()) {
			$search_dn='uidNumber='.$this->ldapEsc($user_id);
			if ($info=$this->_listUser($search_dn,array('uidNumber'),1)) {
				return $info["count"];
			}
			$this->_close();
		}
		return false;
	}
	function check_gidNumber($group_id=0){
		if ($this->_bind()) {
			$search_dn='gidNumber='.$this->ldapEsc($group_id);
			if ($info=$this->_listGroup($search_dn,array('gidNumber'),1)) {
				return $info["count"];
			}
			$this->_close();
		}
		return false;
	}
	function _getSambaSid(){
		if ($this->_bind()) {
			$base_dn=$this->getFrmCfg('ldap/basedn_s').','.$this->_getBaseDn();
			$sr=@ldap_read($this->_ds,$this->utf8encode($base_dn),"objectClass=sambaDomain",array('sambaSID'));
			if ($sr) {
				$info = ldap_get_entries($this->_ds, $sr);
				if ($info["count"]) {
					return $this->utf8decode($info[0]['sambasid'][0]);
				}

			}
			$this->_close();
		}
		return false;
	}
	function checkPassword($username,$pwd) {
		if (!$pwd) return false;
		if ($this->_bind()) {
			$search_dn=array();
			$search_dn[]='(!(shadowExpire=1))';
			$search_dn[]='('.$this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEsc($username).')';
			$search_dn[]='(objectClass=groupeUser)';
			$search_dn[]='(activGroup-e=1)';
			//print_r($search_dn);

			if ($info=$this->_listUser('(&'.implode('',$search_dn).')',array($this->getFrmCfg('ldap/attr/uid'),'uidGroup-e'))) {
				//print_r($info);
				for ($i=0; $i<$info["count"]; $i++) {
					if ($this->_bind($info[$i]["dn"],$pwd)) {

						return $info[$i]["uidgroup-e"][0];
					}
				}
			}
			$this->_close();
		} else {
			debug($this->getError(),1);
		}
		return false;
	}
	function searchUser(&$rows,$info,$uid=0,$perms=0,$b=0,$limit=10,$use_limit=false,$only_groupe=true) {
		$min_uidnumber=0;
		$result=array();
		$fields=array(
			'cn',
			'givenName',
			'sn',
			'uid',
			'uidNumber',
			'loginShell',
			'gidNumber',
			'shadowExpire',
			'sambaAcctFlags',
			'uidGroup-e',
			'activGroup-e',
			'accessSquid',
			'permsGroup-e',
		);
		if ($perms==1) {
			$uperms=array('','deleted','system');
		} elseif (is_array($perms)) {
			$uperms=$perms;
		} elseif($perms) {
			$uperms=array($perms);
		} else {
			$uperms=array('');
		}
		$search_dn=array();
		if ($only_groupe) {
			$search_dn[]='(uidGroup-e=*)';
			$search_dn[]='(objectClass=groupeUser)';
			if (!in_array('deleted',$uperms)) {
				$search_dn[]='(activGroup-e=1)';
				$search_dn[]='(!(shadowExpire=1))';
			}
			if (!in_array('system',$uperms)) {
				$search_dn[]='(permsGroup-e=0)';
			}
			if (is_array($uid)) {
				if (empty($uid)) {
					return $result;
				} else {
					$search_dn[]="(|(uidGroup-e=".implode(')(uidGroup-e=',$this->ldapEsc($uid))."))";
				}
			} elseif ($uid) {
				$search_dn[]="(uidGroup-e=".$this->ldapEsc($uid).")";
			}
		} else {
			$min_uidnumber=$GLOBALS['CFG']['LDAP_MIN_UID'];
			if (!in_array('deleted',$uperms)) {
				$search_dn[]='(!(shadowExpire=1))';
			}
			if (is_array($uid)) {
				if (empty($uid)) {
					return $result;
				} else {
					$search_dn[]="(|(uidnumber=".implode(')(uidnumber=',$this->ldapEsc($uid))."))";
				}
			} elseif ($uid) {
				$search_dn[]="(uidnumber=".$this->ldapEsc($uid).")";
			}
		}
		if ($info) {
			$search_dn[]="(|(uid=*".$this->ldapEsc($info)."*)(givenname=*".$this->ldapEsc($info)."*)(sn=*".$this->ldapEsc($info)."*))";
		}
		//debug($search_dn);

		if ($this->_bind()) {
			if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
				for ($i=0;$i<=$info['count'];$i++) {
					if ($min_uidnumber && $info[$i]['uidnumber'][0]<$min_uidnumber) {
						continue;
					}
					if (!isset($info[$i])) break;
					if ($only_groupe) {
						$id=$info[$i]['uidgroup-e'][0];
					} else {
						$id=$info[$i]['uidnumber'][0];
					}
					$result[$id]=array(
						'firstname'=>$info[$i]['givenname'][0],
						'lastname'=>$info[$i]['sn'][0],
						'username'=>$info[$i]['uid'][0],
						'uidGroup-e'=>$info[$i]['group-e-uid'][0],
						'posix'=>$info[$i]['loginshell'][0]=='/bin/bash'?1:0,
						'samba'=>(!isset($info[$i]['sambaacctflags']) || strpos($info[$i]['sambaacctflags'][0],'D'))?0:1,
						'groupe'=>$info[$i]['activgroup-e'][0]?1:0,
						'internet'=>$info[$i]['accesssquid'][0]?1:0,
						'perms'=>$info[$i]['permsgroup-e'][0]?1:0,
						'gidnumber'=>$info[$i]['gidnumber'][0],
					);
				}
				//debug($info);
			}
			$this->_close();
			$rows=count($result);
			$this->_sort=array('lastname','firstname');
			uasort($result,array($this,'cmp'));
			//debug($rows);
			if ($b || ($limit && $rows>$limit)) {
				$i=0;
				foreach (array_keys($result) as $key) {
					if ($i<$b || $i>=($b+$limit)) {
						unset($result[$key]);
					}
					$i++;
				}
			}
		}
		return $result;
	}

	function read_user($uid,$ldap_uid=false) {
		$result=array();
		$fields=array(
			'cn',
			'givenName',
			'sn',
			'uid',
			'uidNumber',
			'loginShell',
			'gidNumber',
			'shadowExpire',
			'sambaAcctFlags',
			'uidGroup-e',
			'activGroup-e',
			'accessSquid',
			'permsGroup-e',
			'mail',
			'title',
			'postalCode',
			'postalAddress',
			'street',
			'telephoneNumber',
			'telexNumber',
			'departmentNumber',
			'sambaPwdLastSet',
			'sambaProfilePath',
			'sambaHomeDrive',
			'sambaHomePath',
			'shadowMax',
			'shadowLastChange',
		);
		if ($this->getFrmCfg('ldap/mailRouting')) {
			$fields[]='mailHost';
			$fields[]='mailLocalAddress';
			$fields[]='mailRoutingAddress';
		}

		if ($this->_bind()) {
			if ($ldap_uid) $search_dn="(uidnumber=".$this->ldapEsc($uid).")"; else $search_dn="(uidGroup-e=".$this->ldapEsc($uid).")";
			if ($info=$this->_listUser($search_dn,$fields)) {
				if ($info['count']) {
					$result=array(
						'uidnumber'=>$info[0]['uidnumber'][0],
						'gidnumber'=>$info[0]['gidnumber'][0],
						'firstname'=>$info[0]['givenname'][0],
						'lastname'=>$info[0]['sn'][0],
						'username'=>$info[0]['uid'][0],
						'title'=>$info[0]['title'][0],
						'uname'=>$info[0]['uid'][0],
						'uidGroup-e'=>$info[0]['uidgroup-e'][0],
						'posix'=>$info[0]['loginshell'][0]=='/bin/bash'?1:0,
						'samba'=>(!isset($info[0]['sambaacctflags']) || strpos($info[0]['sambaacctflags'][0],'D'))?0:1,
						'groupe'=>$info[0]['activgroup-e'][0]?1:0,
						'internet'=>$info[0]['accesssquid'][0]?1:0,
						'perms'=>$info[0]['permsgroup-e'][0]?1:0,
						'email'=>$info[0]['mail'][0],
						'zip'=>$info[0]['postalcode'][0],
						'place'=>$info[0]['postaladdress'][0],
						'adress'=>$info[0]['street'][0],
						'fax'=>$info[0]['telexnumber'][0],
						'tel'=>$info[0]['telephonenumber'][0],
						'company'=>$info[0]['departmentnumber'][0],
						'deleted'=>$info[0]['shadowexpire'][0]==1?1:0,
						'pwd_time'=>$this->getFrmCfg('ldap/smbServer')?$info[0]['sambapwdlastset'][0]:(intval($info[0]['shadowlastchange'][0])*86400),
						'shadowMax'=>$info[0]['shadowmax'][0]>3000?0:$info[0]['shadowmax'][0],
					);
					if ($ldap_uid && $result['samba']) {
						$result['smbServer']=$this->_getMySmbServer($info[0]);
					}
					if ($this->getFrmCfg('ldap/mailRouting')) {
						$result['mailhost']=$info[0]['mailhost'][0];
						$temp=array();
						for ($i=0;$i<$info[0]['maillocaladdress']['count'];$i++){
							$temp[]=$info[0]['maillocaladdress'][$i];
						}
						$result['maillocaladdress']=implode(',',$temp);

						$result['mailroutingaddress']=$info[0]['mailroutingaddress'][0];
					}
				}
			}
			//debug($result);
			$this->_close();

		}
		return $result;
	}


	function insert_user($userData) {
		$oldData=array();
		if ($userData['uidnumber'] && !$this->check_uidNumber($userData['uidnumber'])) {

		} else {
			do {
				$userData['uidnumber']=$this->_getNextID('uid');
			} while ($this->check_uidNumber($userData['uidnumber']));
		}
		if (!$userData['uidGroup-e']) $userData['uidGroup-e']=$userData['uidnumber'];
		if (!$userdata['gidnumber']) $userdata['gidnumber']=$this->getGroupAllLdapID();
		$ldap_add=array();
		$ldap_add['objectClass']=$this->getUserClasses();
		foreach ($ldap_add['objectClass'] as $val) {
			$ldap_add = $ldap_add + $this->_getObjectAdd($val,$userData,$oldData);
		}
		if ($this->_bind() && $this->_add($this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($ldap_add[$this->getFrmCfg('ldap/attr/uname')]).','.$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(), $ldap_add)) {
			$this->_close();
			$this->insertUserLog($userData,array());
			return $userData['uidnumber'];
		} else {
			//$this->deleteGroupieGroup($userData['gidnumber']);
		}
		return false;
	}
	function update_user($uid,$userData,$ldap_uid=false) {
		if (!is_array($userData)) return 0;
		/*
		if (!$this->check_gidNumber($userData['gidnumber'])) {
			$userData['gidnumber']=$this->insert_group(array($this->getFrmCfg('ldap/attr/gname')=>$userData['username'],'activGroup-e'=>0));
			debug('NEW_GROUP:'.$userData['gidnumber']);
		}*/
		if ($this->_bind()) {
			if ($ldap_uid) $search_dn="(uidnumber=".$this->ldapEsc($uid).")"; else $search_dn="(uidGroup-e=".$this->ldapEsc($uid).")";
			if ($info=$this->_listUser($search_dn)) {
				if ($info['count']) {
					$ldap_del=array();
					foreach ($this->getUserClasses() as $val) {
						$ldap_del = $ldap_del + $this->_getObjectDelete($val,$userData,$info[0]);

					}
					$ldap_add=array();
					foreach ($this->getUserClasses() as $val) {
						if (!in_array($val,$info[0]['objectclass']) && !in_array(strtolower($val),$info[0]['objectclass'])) {
							$ldap_add['objectClass'][]=$val;
						}
						$ldap_add = $ldap_add + $this->_getObjectAdd($val,$userData,$info[0]);

					}
					$ldap_rep=array();
					foreach ($this->getUserClasses() as $val) {
						if (in_array($val,$info[0]['objectclass']) || in_array(strtolower($val),$info[0]['objectclass'])) {
							$ldap_rep = $ldap_rep + $this->_getObjectReplace($val,$userData,$info[0]);
						}
					}
					$ldap_ren='';
					if ($ldap_rep[$this->getFrmCfg('ldap/attr/uname')]) {
						if (strcasecmp ($ldap_rep[$this->getFrmCfg('ldap/attr/uname')],$info[0][$this->getFrmCfg('ldap/attr/uname')][0])!=0) {
							$ldap_ren=$ldap_rep[$this->getFrmCfg('ldap/attr/uname')];
							unset($ldap_rep[$this->getFrmCfg('ldap/attr/uname')]);
						} else {
							$ldap_gren=$ldap_rep[$this->getFrmCfg('ldap/attr/uname')];
						}
					}
				}

				$no_error=true;
				//debug($ldap_del);
				if (!empty($ldap_del) && $this->_bind()) {
					$no_error=$this->_mod_del($this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/uname')][0]).','.$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(), $ldap_del);
				}
				//debug($ldap_add);
				if (!empty($ldap_add) && $this->_bind()) {
					$no_error=$this->_mod_add($this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/uname')][0]).','.$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(), $ldap_add);
				}
				//debug($ldap_rep);
				if ($no_error && !empty($ldap_rep) && $this->_bind()) {
					$no_error=$this->_mod_rep($this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/uname')][0]).','.$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(), $ldap_rep);
				}
				if ($no_error && $ldap_ren && $this->_bind()) {
					$no_error=$this->_rename($this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/uname')][0]).','.$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn(),$this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($ldap_ren),$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn());
					if ($no_error) {
						$this->insertUserLog($userData,array('username'=>$info[0][$this->getFrmCfg('ldap/attr/uname')][0],'uidnumber'=>$info[0]['uidnumber'][0]));
					}
					/*if ($this->_bind() && $ginfo=$this->_listGroup($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEsc($info[0][$this->getFrmCfg('ldap/attr/uname')][0]))) {
						if ($ginfo['count'] && $this->_bind()) {
							$no_error=$this->_rename($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/uname')][0]).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(),$this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($ldap_ren),$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn());
						}
					}*/
				}
				if ($no_error && $ldap_gren) {
					/*if ($this->_bind() && $ginfo=$this->_listGroup($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEsc($info[0][$this->getFrmCfg('ldap/attr/uname')][0]))) {
						if ($ginfo['count'] && $this->_bind()) {
							$no_error=$this->_mod_rep($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/uname')][0]).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(),array($this->getFrmCfg('ldap/attr/gname')=>$ldap_gren));
						}
					}*/
				}
				return ($no_error);
			}
			$this->_close();
		}
	}
	function getGroupAllLdapID() {
		if (!$this->_groupAllLdapID) {
			$tmp=$this->getAdminGroups($GLOBALS['CFG']['GROUP_ALL'],true,false);
			if ($tmp) {
				$this->_groupAllLdapID=$tmp['gidnumber'];
			}
		}
		return $this->_groupAllLdapID;

	}
	function getAdminGroups($uid=0,$all=true,$ldap_uid=true,$no_proj=true) {
		$min_gidnumber=$GLOBALS['CFG']['LDAP_MIN_GID'];
		$result=array();
		$fields=array(
			'cn',
			'gidNumber',
			'displayName',
			'gidGroup-e',
			'activGroup-e',
			'memberUid',
		);
		if ($this->_bind()) {
			$search_dn=array('(objectclass=groupeGroup)');
			if (!$all) $search_dn[]='(memberUid=*)';
			if ($uid) {
				if ($ldap_uid) {
					$search_dn[]="(gidNumber=".$this->ldapEsc($uid).")";
				} else {
					$search_dn[]="(gidGroup-e=".$this->ldapEsc($uid).")";
				}
			}
			if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
				for ($i=0;$i<$info['count'];$i++) {
					if (!$uid && $info[$i]['gidnumber'][0]<$min_gidnumber) continue;
					if (!$uid && $info[$i]['activgroup-e'][0]<0) continue;
					if ($no_proj && $info[$i]['activgroup-e'][0]>1) continue;
					if ($ldap_uid) $key=$info[$i]['gidnumber'][0]; else $key=$info[$i]['gidgroup-e'][0];
					$result[$key]=array(
						'cn'=>$info[$i]['cn'][0],
						'displayName'=>$info[$i]['displayname'][0],
						'gidnumber'=>$info[$i]['gidnumber'][0],
						'gidGroup-e'=>$info[$i]['gidgroup-e'][0],
						'activGroup-e'=>$info[$i]['activgroup-e'][0],
						'members'=>$info[$i]['memberuid'],
					);

				}
				//debug($info);
			}
			$this->_close();
		}
		$this->_sort=array('cn');
		uasort($result,array($this,'cmp'));
		if ($uid) {
			return $result[$uid];
		} else {
			return $result;
		}
	}
	function _dnExists($dn){
		if ($this->_bind() && $dn) {
			$base_dn=$this->getFrmCfg('ldap/basedn_s').','.$this->_getBaseDn();
			$sr=@ldap_read($this->_ds,$this->utf8encode($dn),"objectClass=*");
			if ($sr) {
				$info = ldap_get_entries($this->_ds, $sr);
				return $info["count"];
			}
			$this->_close();
		}
		return false;
	}
	function newDomain($domain) {
		if (defined('GE_LDAP_DOMAIN_SEP')) {
			$sep=GE_LDAP_DOMAIN_SEP;
		} else {
			$sep='_';
		}
		$no_error=false;
		$topDomain=str_replace('.',$sep,$domain);
		$newBase=str_replace('{DOMAIN}',$this->ldapEscDN($topDomain),$this->getFrmCfg('ldap/basedn'));
		if (!$this->_dnExists($newBase)) {
			if ($this->_bind()) {
				$ldap_add=array();
				$ldap_add['objectClass']=array('organizationalUnit','top');
				$ldap_add['ou']=$topDomain;
				$no_error=$this->_add($newBase, $ldap_add);
			}
		} else {
			$no_error=true;
		}
		$newOu='Domain';
		if ($no_error && !$this->_dnExists('ou='.$this->ldapEscDN($newOu).','.$newBase)) {
			if ($this->_bind()) {
				$ldap_add=array();
				$ldap_add['objectClass']=array('organizationalUnit','domainRelatedObject','top');
				$ldap_add['ou']=$newOu;
				$ldap_add['associatedDomain']=$topDomain;
				$no_error=$this->_add('ou='.$this->ldapEscDN($newOu).','.$newBase, $ldap_add);
			} else {
				$no_error=false;
			}
		}
		$newOu=explode('=',$this->getFrmCfg('ldap/basedn_u'));
		if ($no_error && !$this->_dnExists($newOu[0].'='.$this->ldapEscDN($newOu[1]).','.$newBase)) {
			if ($this->_bind()) {
				$ldap_add=array();
				$ldap_add['objectClass']=array('organizationalUnit','top');
				$ldap_add[$newOu[0]]=$newOu[1];
				$no_error=$this->_add($newOu[0].'='.$this->ldapEscDN($newOu[1]).','.$newBase, $ldap_add);
			} else {
				$no_error=false;
			}
		}
		$newOu=explode('=',$this->getFrmCfg('ldap/basedn_g'));
		if ($no_error && !$this->_dnExists($newOu[0].'='.$this->ldapEscDN($newOu[1]).','.$newBase)) {
			if ($this->_bind()) {
				$ldap_add=array();
				$ldap_add['objectClass']=array('organizationalUnit','top');
				$ldap_add[$newOu[0]]=$newOu[1];
				$no_error=$this->_add($newOu[0].'='.$this->ldapEscDN($newOu[1]).','.$newBase, $ldap_add);
			} else {
				$no_error=false;
			}
		}
		return $no_error;
	}
	function insert_group($userData) {
		$oldData=array();
		if ($userData['gidnumber'] && !$this->check_gidNumber($userData['gidnumber'])) {
			$userData['gidGroup-e']=$userData['gidnumber'];
		} else {
			do {
				$userData['gidnumber']=$this->_getNextID('gid');
			} while ($this->check_gidNumber($userData['gidnumber']));
		}
		if (!$userData['gidGroup-e']) $userData['gidGroup-e']=$userData['gidnumber'];
		$ldap_add=array();
		$ldap_add['objectClass']=$this->getGroupClasses();
		foreach ($ldap_add['objectClass'] as $val) {
			$ldap_add = $ldap_add + $this->_getObjectAdd($val,$userData,$oldData);
		}
		//debug($ldap_add);

		if ($this->_bind() && $this->_add($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($ldap_add[$this->getFrmCfg('ldap/attr/gname')]).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $ldap_add)) {
			$this->_close();
			return $userData['gidnumber'];
		} else {
			return false;
		}
	}
	function update_group($uid,$userData,$ldap_uid=false) {
		if (!is_array($userData)) return 0;
		if ($this->_bind()) {
			if ($ldap_uid) $search_dn="(gidnumber=".$this->ldapEsc($uid).")"; else $search_dn="(gidGroup-e=".$this->ldapEsc($uid).")";
			if ($info=$this->_listGroup($search_dn)) {
				if ($info['count']) {
					//debug($info);
					$ldap_add=array();
					foreach ($this->getGroupClasses() as $val) {
						if (!in_array($val,$info[0]['objectclass']) && !in_array(strtolower($val),$info[0]['objectclass'])) {
							$ldap_add['objectClass'][]=$val;
						}
						$ldap_add = $ldap_add + $this->_getObjectAdd($val,$userData,$info[0]);

					}
					$ldap_rep=array();
					foreach ($this->getGroupClasses() as $val) {
						if (in_array($val,$info[0]['objectclass']) || in_array(strtolower($val),$info[0]['objectclass'])) {
							$ldap_rep = $ldap_rep + $this->_getObjectReplace($val,$userData,$info[0]);
						}
					}
					$ldap_ren='';
					if ($ldap_rep[$this->getFrmCfg('ldap/attr/gname')] && strcasecmp ($ldap_rep[$this->getFrmCfg('ldap/attr/gname')],$info[0][$this->getFrmCfg('ldap/attr/gname')][0])!=0) {
						$ldap_ren=$ldap_rep[$this->getFrmCfg('ldap/attr/gname')];
						unset($ldap_rep[$this->getFrmCfg('ldap/attr/gname')]);
					}
				}

				$no_error=true;
				//debug($ldap_add);
				if (!empty($ldap_add) && $this->_bind()) {
					$no_error=$this->_mod_add($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/gname')][0]).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $ldap_add);
				}
				//debug($ldap_rep);
				if ($no_error && !empty($ldap_rep) && $this->_bind()) {
					$no_error=$this->_mod_rep($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/gname')][0]).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $ldap_rep);
				}
				if ($no_error && $ldap_ren && $this->_bind()) {
					$no_error=$this->_rename($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($info[0][$this->getFrmCfg('ldap/attr/gname')][0]).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(),$this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($ldap_ren),$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn());
				}
				return ($no_error);
			}
		}
	}

	function insertUserGroups($uid,$user_groups,$ldap_uid=true) {
		foreach ($user_groups as $val) {
			$this->insertGroupUsers($val,array($uid),$ldap_uid);
		}
	}

	function insertGroupUsers($gid,$user_groups,$ldap_uid=true) {
		//debug($gid);
		if ($gid && $groupData=$this->getAdminGroups($gid,true,$ldap_uid,false)) {
			if (!is_array($groupData['members'])) {
				$groupData['members']=array();
			}
			//debug($groupData);
			$ldap_add=array();
			if (is_array($user_groups)) {
				foreach ($user_groups as $val) {
					$userData=$this->read_user($val,$ldap_uid);
					if ($userData['username'] && !in_array($userData['username'],$groupData['members'])) {
						$ldap_add['memberUid'][]=$userData['username'];
					}
				}
			}
			if (!empty($ldap_add) && $this->_bind()) {
				return $this->_mod_add($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($groupData['cn']).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $ldap_add);
			}
		}
	}

	function deleteUserGroups($uid=0,$gid=0,$ldap_uid=true,$no_proj=true) {
		$del_groups=array();
		$del_name=array();
		if ($uid) {
			$userData=$this->read_user($uid,$ldap_uid);
			if ($userData['username']) {
				$del_name['memberUid']=$userData['username'];
			}
		} else {
			$del_name['memberUid']=array();
		}
		if ($gid) {
			$groupData=$this->getAdminGroups($gid,true,$ldap_uid,$no_proj);
			if ($groupData['cn'] && ($groupData['members']['count']>1 || $groupData['activGroup-e']>1 || $groupData['activGroup-e']<0)) {
				$del_groups[]=$groupData['cn'];
			}
		} else {
			if ($userData['username']) {
				$result=array();
				$fields=array(
					'cn',
					'memberUid',
					'activGroup-e',
				);
				if ($this->_bind()) {
					$search_dn=array('(objectclass=groupeGroup)');
					$search_dn[]='(memberUid='.$this->ldapEsc($userData['username']).')';
					if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
						for ($i=0;$i<$info['count'];$i++) {
							if ($no_proj && $info[$i]['activgroup-e'][0]>1) continue;
							if ($info[$i]['memberuid']['count']>1 || $info[$i]['activgroup-e'][0]>1) {
								$del_groups[]=$info[$i]['cn'][0];
							}
						}
					}
					$this->_close();
				}
			}
		}
		if (!empty($del_groups) && !empty($del_name) && $this->_bind()) {
			foreach ($del_groups as $val) {
				$this->_mod_del($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($val).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn(), $del_name);
			}
			return true;
		} else {
			return false;
		}
	}
	function deleteGroupieGroup($gid) {
		$groupData=$this->getAdminGroups($gid,true,true,false);
		if ($groupData['cn'] && $this->_bind()) {
			$fields=array(
				'gidNumber',
			);
			$search_dn=array();
			$search_dn[]='(gidNumber='.$this->ldapEsc($gid).')';
			//debug($groupData);
			if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
				//debug($info);
				if (!$info['count'] && $this->_bind()) {
					return $this->_del($this->getFrmCfg('ldap/attr/gname').'='.$this->ldapEscDN($groupData['cn']).','.$this->getFrmCfg('ldap/basedn_g').','.$this->_getBaseDn());
				}
			}
		}
		return false;
	}


	function deleteGroupieUser($uid) {
		$userData=$this->read_user($uid,true);
		if ($userData['username']) {
			$insert=array(
				'user_id'=>$uid,
				'username'=>$userData['username'],
				'firstname'=>$userData['firstname'],
				'lastname'=>$userData['lastname'],
				'perms'=>'deleted',
			);
			$this->db->insert('auth_user_md5',$insert,'',1);
			if ($this->getFrmCfg('cfg/delall')) {
				if ($this->_bind()) {
					$this->_del($this->getFrmCfg('ldap/attr/uname').'='.$this->ldapEscDN($userData['username']).','.$this->getFrmCfg('ldap/basedn_u').','.$this->_getBaseDn());
					//$this->deleteGroupieGroup($userData['gidnumber']);
				}
			} else {
				$userData['deleted']=1;
				$this->update_user($uid,$userData,true);
			}
		}
	}
	function getAllGroupUsers($group_id) {
		$tmp_result=array();
		$result=array();
		$fields=array(
			'memberUid',
			'gidGroup-e',
		);
		if ($this->_bind()) {
			$search_dn=array();
			if (is_array($group_id)) {
				$search_dn[]="(|(gidNumber=".implode(')(gidNumber=',$this->ldapEsc($group_id))."))";
			} else {
				$search_dn[]="(gidNumber=".$this->ldapEsc($group_id).")";
			}
			if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
				for ($i=0;$i<$info['count'];$i++) {
					if ($info[$i]['memberuid']['count']) {
						for ($j=0;$j<$info[$i]['memberuid']['count'];$j++) {
							$tmp_result[$info[$i]['memberuid'][$j]]=0;
							$result[$info[$i]['gidgroup-e'][0]][]=$info[$i]['memberuid'][$j];
						}
					}
				}

				//debug($result);
			}
			if (!empty($tmp_result)) {
				$fields=array(
					'uid',
					'uidGroup-e',
				);
				$search_dn=array();
				$search_dn[]="(|(uid=".implode(')(uid=',$this->ldapEsc(array_keys($tmp_result)))."))";
				if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
					for ($i=0;$i<$info['count'];$i++) {
						$tmp_result[$info[$i]['uid'][0]]=$info[$i]['uidgroup-e'][0];
					}
					//debug($info);
				}
				foreach ($result as $gkey=>$gval) {
					foreach ($gval as $ukey=>$uval) {
						if ($tmp_result[$uval]) {
							$result[$gkey][$ukey]=$tmp_result[$uval];
						} else {
							unset($result[$gkey][$ukey]);
						}
					}
				}
			}
			$this->_close();
		}
		return $result;
	}

	function getGroupUsers($group_id,$ldap_uid=false) {
		$tmp_result=array();
		$result=array();
		$fields=array(
			'memberUid',
		);
		if ($this->_bind()) {
			$search_dn=array();
			if ($ldap_uid) {
				if (is_array($group_id)) {
					$search_dn[]="(|(gidNumber=".implode(')(gidNumber=',$this->ldapEsc($group_id))."))";
				} else {
					$search_dn[]="(gidNumber=".$this->ldapEsc($group_id).")";
				}
			} else {
				if (is_array($group_id)) {
					$search_dn[]="(|(gidGroup-e=".implode(')(gidGroup-e=',$this->ldapEsc($group_id))."))";
				} else {
					$search_dn[]="(gidGroup-e=".$this->ldapEsc($group_id).")";
				}
			}
			if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
				for ($i=0;$i<$info['count'];$i++) {
					if ($info[$i]['memberuid']['count']) {
						for ($j=0;$j<$info[$i]['memberuid']['count'];$j++) {
							$tmp_result[]=$info[$i]['memberuid'][$j];
						}
					}
				}
				//debug($info);
			}
			if (!empty($tmp_result)) {
				$tmp_result=array_unique($tmp_result);
				$fields=array(
					'uidNumber',
					'uidGroup-e',
				);
				$search_dn=array();
				$search_dn[]="(|(uid=".implode(')(uid=',$this->ldapEsc($tmp_result))."))";
				if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
					for ($i=0;$i<$info['count'];$i++) {
						if ($ldap_uid) {
							$result[$info[$i]['uidnumber'][0]]=$info[$i]['uidnumber'][0];
						} elseif ($info[$i]['uidgroup-e'][0]) {
							$result[$info[$i]['uidgroup-e'][0]]=$info[$i]['uidgroup-e'][0];
						}
					}
					//debug($info);
				}
			}
			$this->_close();
		}
		return $result;
	}
	function getUserGroups($user_id,$ldap_uid=false,$noNonAktivPrj=true) {
		global $P;

		//$result=array();
		if (!is_array($user_id)) $tmp=array($user_id); else $tmp=$user_id;
		foreach ($tmp as $val) {
			$userData=$this->read_user($val,$ldap_uid);
			if ($userData['username']) {
				$unames[]=$userData['username'];
			}
		}

		if (is_array($unames) && $this->_bind()) {
			if ($ldap_uid) {
				$fields=array(
					'gidNumber',
				);
				$idField='gidnumber';
			} else {
				$fields=array(
					'gidGroup-e',
				);
				$idField='gidgroup-e';
			}
			if ($noNonAktivPrj) $fields[]='activGroup-e';
			$checkPrj=array();
			$search_dn=array('(objectclass=groupeGroup)');
			$search_dn[]="(|(memberUid=".implode(')(memberUid=',$this->ldapEsc($unames))."))";
			if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
				for ($i=0;$i<$info['count'];$i++) {
					$result[$info[$i][$idField][0]]=$info[$i][$idField][0];
					if ($noNonAktivPrj && $info[$i]['activgroup-e'][0]>1) {
						$checkPrj[$info[$i]['activgroup-e'][0]][]=$info[$i][$idField][0];
					}
				}
				//debug($info);
			}
			if (!empty($checkPrj) && $P) {
				$deletIds=$P->getNonActivProjects(array_keys($checkPrj));
				foreach ($deletIds as $val) {
					if (is_array($checkPrj[$val])) {
						foreach ($checkPrj[$val] as $delID) {
							unset($result[$delID]);
						}
					}
				}
			}
		}


		return $result;
	}
	function idToSid($id,$mode='u',$ldap_uid=false) {
		$result=0;
		if ($this->_bind()) {
			$search_dn=array();
			switch ($mode) {
				case 'u':
					$fields=array(
						'sambaSID',
						'uidGroup-e',
						'uidNumber',
						'uid',
					);
					if ($ldap_uid) $search_dn[]="(uidnumber=".$this->ldapEsc($id).")"; else $search_dn[]="(uidGroup-e=".$this->ldapEsc($id).")";
					if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
						if ($info[0]['sambasid'][0]) {
							$result=$info[0]['sambasid'][0];
						}
					}
				break;
				case 'g':
					$fields=array(
						'sambaSID',
						'gidGroup-e',
						'gidNumber',
						'cn',
					);
					if ($ldap_uid) $search_dn[]="(gidnumber=".$this->ldapEsc($id).")"; else $search_dn[]="(gidGroup-e=".$this->ldapEsc($id).")";
					if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
						if ($info[0]['sambasid'][0]) {
							$result=$info[0]['sambasid'][0];
						}
				}
				break;
			}
		}
		return $result;
	}
	function sidToId($sid,$mode='u') {
		$result=array();
		if ($this->_bind()) {
			$search_dn=array();
			if (is_array($sid)) {
				$search_dn[]="(|(sambaSID=".implode(')(sambaSID=',$this->ldapEsc($sid))."))";
			} else {
				$search_dn[]='(sambaSID='.$this->ldapEsc($sid).')';
			}
			switch ($mode) {
				case 'u':
					$fields=array(
						'sambaSID',
						'uidGroup-e',
						'uidNumber',
						'uid',
					);
					if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
						for ($i=0;$i<$info['count'];$i++) {
							$result[$info[$i]['sambasid'][0]]=array(
								'uidGroup-e'=>$info[$i]['uidgroup-e'][0],
								'uidNumber'=>$info[$i]['uidnumber'][0],
								'uid'=>$info[$i]['uid'][0],
							);
						}
					}
				break;
				case 'g':
					$fields=array(
						'sambaSID',
						'gidGroup-e',
						'gidNumber',
						'cn',
					);
					if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
						for ($i=0;$i<$info['count'];$i++) {
							$result[$info[$i]['sambasid'][0]]=array(
								'gidGroup-e'=>$info[$i]['gidgroup-e'][0],
								'gidNumber'=>$info[$i]['gidnumber'][0],
								'cn'=>$info[$i]['cn'][0],
							);
						}
					}
				break;
			}
			if (is_array($sid)) {
				return $result;
			} else {
				return reset($result);
			}
		}
		return $result;
	}
	function getAllGroups($uid=0,$perms=0,$info='',$limit=0,$translate=true,$strict_info=false) {
		global $P,$T;
		$result=array();
		$prj_groups=array();
		$fields=array(
			'cn',
			'gidGroup-e',
			'activGroup-e',
		);
		if ($this->_bind()) {
			if ($translate && $this->_myDomain) $preg_domain='/'.preg_quote('_'.str_replace('.','_',$this->_myDomain)).'$/'; else $preg_domain='';
			$search_dn=array();
			$search_dn[]='(gidGroup-e=*)';
			if (is_array($perms)) {
				$search_dn[]="(|(activGroup-e=".implode(')(activGroup-e=',$this->ldapEsc($perms))."))";
			} elseif ($perms!=-1) {
				if ($perms) {
					$search_dn[]='(|(activGroup-e=1)(activGroup-e='.$this->ldapEsc($perms).'))';
				} else {
					$search_dn[]='(activGroup-e=1)';
				}
			}
			if ($info) {
				if ($strict_info) {
					$search_dn[]="(cn=".$this->ldapEsc($info).")";
				} else {
					$search_dn[]="(cn=*".$this->ldapEsc($info)."*)";
				}
			}
			if (is_array($uid)) {
				$search_dn[]="(|(gidGroup-e=".implode(')(gidGroup-e=',$this->ldapEsc($uid))."))";
			} elseif ($uid) {
				$search_dn[]="(gidGroup-e=".$this->ldapEsc($uid).")";
			}
			if ($info=$this->_listGroup('(&'.implode('',$search_dn).')',$fields)) {
				if (!$limit) $limit=$info['count'];
				for ($i=0;$i<$limit;$i++) {
					if (!isset($info[$i])) break;
					if ($perms && $translate && $info[$i]['activgroup-e'][0]!=1 && $P && $T) {
						$prj_groups[$info[$i]['gidgroup-e'][0]]='';
					} else {
						if ($preg_domain) {
							$result[$info[$i]['gidgroup-e'][0]]=preg_replace($preg_domain,'',$info[$i]['cn'][0]);
						} else {
							$result[$info[$i]['gidgroup-e'][0]]=$info[$i]['cn'][0];
						}
					}
				}
			}
			$this->_close();
		}
		natsort($result);
		if (!empty($prj_groups)) {
			natsort($prj_groups);
			foreach ($prj_groups as $key=>$val) {
				$prj_groups[$key]=$T->lang($P->getGroupName($key));
			}
			if (is_array($result)) {
				$result=$prj_groups+$result;
			} else {
				$result=$prj_groups;
			}
		}
		if (!empty($result)) {
			return $result;
		}
	}
	function getFullName($uid=0,$format=0,$perms=0,$info='',$limit=0,$strict_info=false) {
		$users=array();
		$fields=array(
			'uidGroup-e',
			'permsGroup-e',
			'activGroup-e',
			'givenName',
			'sn',
			'shadowExpire',
		);
		switch ($format) {
			case 2:
				$fields[]='title';
			break;
		}


		$search_dn=array();
		$search_dn[]='(uidGroup-e=*)';
		$search_dn[]='(objectClass=groupeUser)';
		if ($perms==1) {
			$uperms=array('','deleted','system');
		} elseif (is_array($perms)) {
			$uperms=$perms;
		} elseif($perms) {
			$uperms=array($perms);
		} else {
			$uperms=array('');
		}

		if (!in_array('deleted',$uperms)) {
			$search_dn[]='(activGroup-e=1)';
			$search_dn[]='(!(shadowExpire=1))';
		}
		if (!in_array('system',$uperms)) {
			$search_dn[]='(permsGroup-e=0)';
		}
		if ($info) {
			if ($strict_info) {
				$search_dn[]="(uid=".$this->ldapEsc($info).")";
			} else {
				$search_dn[]="(|(uid=*".$this->ldapEsc($info)."*)(givenname=*".$this->ldapEsc($info)."*)(sn=*".$this->ldapEsc($info)."*))";
			}
		}
		if ($this->uid!=$GLOBALS['CFG']['SUPERADMIN'] && !$super) {
			if (!$uid || (is_array($uid) && !in_array($GLOBALS['CFG']['SUPERADMIN'],$uid)) || (!is_array($uid) && $GLOBALS['CFG']['SUPERADMIN']!=$uid)) {
				$search_dn[]="(!(uidGroup-e=".$this->ldapEsc($GLOBALS['CFG']['SUPERADMIN'])."))";
			}
		}
		if (is_array($uid)) {
			if (empty($uid)) {
				return $users;
			} else {
				$search_dn[]="(|(uidGroup-e=".implode(')(uidGroup-e=',$this->ldapEsc($uid))."))";
			}
			$numUsers=count(array_unique($uid));
		} elseif ($uid) {
			$search_dn[]="(uidGroup-e=".$this->ldapEsc($uid).")";
			$numUsers=1;
		} else {
			$numUsers=-1;
		}
		//debug($search_dn);
		if ($this->_bind()) {
			if ($info=$this->_listUser('(&'.implode('',$search_dn).')',$fields)) {
				if (!$limit) $limit=$info['count'];
				for ($i=0;$i<$limit;$i++) {
					if (!isset($info[$i])) break;
					switch ($format) {
						case 2:
							$users[$info[$i]['uidgroup-e'][0]]=$info[$i]['sn'][0].' '.$info[$i]['givenname'][0];
							if ($info[$i]['title'][0]) $users[$info[$i]['uidgroup-e'][0]].=' - '.$info[$i]['title'][0];
						break;
						case 1:
							$users[$info[$i]['uidgroup-e'][0]]=$info[$i]['sn'][0].' '.substr($info[$i]['givenname'][0],0,1).'.';
						break;
						default:
							$users[$info[$i]['uidgroup-e'][0]]=$info[$i]['sn'][0].' '.$info[$i]['givenname'][0];
						break;
					}
					if ($info[$i]['shadowexpire'][0]==1) {
						$users[$info[$i]['uidgroup-e'][0]]='#'.$users[$info[$i]['uidgroup-e'][0]].'#';
					}
				}
				//debug($info);
			}
			$this->_close();
		}
		$skip=1;
		if (in_array('deleted',$uperms) && count($users)!=$numUsers) {
			$query="SELECT user_id,firstname,lastname FROM auth_user_md5 WHERE Perms='deleted'";
			if (is_array($uid)) {
				$dUid=array_diff($uid,array_keys($users));
				if (!empty($dUid)) {
					$query.=" AND user_id IN (".implode(',',$this->db->escape($dUid)).")";
					$skip=0;
				}
			} elseif($uid) {
				$query.=" AND user_id=".$this->db->escape($uid);
				$skip=0;
			} elseif (!empty($users)) {
				$query.=" AND user_id IN (".implode(',',$this->db->escape(array_keys($users))).")";
				$skip=0;
			}
			if (!$skip) {
				$this->db->query($query);
				while ($this->db->next_record()) {
					if (!isset($users[$this->db->f("user_id")])) {
						switch ($format) {
							case 1:
								$users[$this->db->f("user_id")]='#'.$this->db->f("lastname").' '.substr($this->db->f("firstname"),0,1).'.'.'#';
							break;
							default:
								$users[$this->db->f("user_id")]='#'.$this->db->f("lastname").' '.$this->db->f("firstname").'#';
							break;
						}
					}
				}

			}
		}
		uasort($users,'strnatcmp');
		return $users;
	}

	function _getObjectDelete($objClass,&$userData,&$oldData) {
		$ldap_del=array();
		switch ($objClass) {
			case 'inetOrgPerson':
				if (isset($userData['zip']) && isset($oldData['postalcode']) && !strlen($userData['zip'])) $ldap_del['postalcode']=$oldData['postalcode'][0];
				if (isset($userData['place']) && isset($oldData['postaladdress']) && !strlen($userData['place'])) $ldap_del['postaladdress']=$oldData['postaladdress'][0];
				if (isset($userData['adress']) && isset($oldData['street']) && !strlen($userData['adress'])) $ldap_del['street']=$oldData['street'][0];
				if (isset($userData['fax']) && isset($oldData['telexnumber']) && !strlen($userData['fax'])) $ldap_del['telexnumber']=$oldData['telexnumber'][0];
				if (isset($userData['tel']) && isset($oldData['telephonenumber']) && !strlen($userData['tel'])) $ldap_del['telephonenumber']=$oldData['telephonenumber'][0];
				if (isset($userData['company']) && isset($oldData['departmentnumber']) && !strlen($userData['company'])) $ldap_del['departmentnumber']=$oldData['departmentnumber'][0];
				if (isset($userData['title']) && isset($oldData['title']) && !strlen($userData['title'])) $ldap_del['title']=$oldData['title'][0];
			break;
			case 'inetLocalMailRecipient':
				if (isset($userData['mailhost']) && isset($oldData['mailhost']) && !strlen($userData['mailhost'])) $ldap_del['mailHost']=$oldData['mailhost'][0];
				if (isset($userData['mailroutingaddress']) && isset($oldData['mailroutingaddress']) && !strlen($userData['mailroutingaddress'])) $ldap_del['mailRoutingAddress']=$oldData['mailroutingaddress'][0];

				if (isset($userData['maillocaladdress']) && isset($oldData['maillocaladdress'])) {
					$temp=explode(',',$userData['maillocaladdress']);
					$temp=array_map('strtolower',$temp);
					$temp=array_map('trim',$temp);
					for ($i=0;$i<$oldData['maillocaladdress']['count'];$i++){
						if (!in_array(trim(strtolower($oldData['maillocaladdress'][$i])),$temp)) {
							$ldap_del['mailLocalAddress'][]=$oldData['maillocaladdress'][$i];
						}
					}
				}

			break;
		}
		return $ldap_del;
	}


	function _getObjectReplace($objClass,&$userData,&$oldData) {
		$ldap_add=array();
		switch ($objClass) {
			case 'inetOrgPerson':
				if (strlen($userData['firstname']) && isset($oldData['givenname']) && $oldData['givenname'][0]!=$userData['firstname']) $ldap_add['givenName']=$userData['firstname'];
				if (strlen($userData['lastname']) && isset($oldData['sn']) && $oldData['sn'][0]!=$userData['lastname']) $ldap_add['sn']=$userData['lastname'];
				if (strlen($userData['lastname']) && isset($oldData['cn']) && $oldData['cn'][0]!=$userData['firstname'].' '.$userData['lastname']) $ldap_add['cn']=$userData['firstname'].' '.$userData['lastname'];
				if (strlen($userData['email']) && isset($oldData['mail']) && $oldData['mail'][0]!=$userData['email']) $ldap_add['mail']=$userData['email'];
				if (strlen($userData['zip']) && isset($oldData['postalcode']) && $oldData['postalcode'][0]!=$userData['zip']) $ldap_add['postalcode']=$userData['zip'];
				if (strlen($userData['place']) && isset($oldData['postaladdress']) && $oldData['postaladdress'][0]!=$userData['place']) $ldap_add['postaladdress']=$userData['place'];
				if (strlen($userData['adress']) && isset($oldData['street']) && $oldData['street'][0]!=$userData['adress']) $ldap_add['street']=$userData['adress'];
				if (strlen($userData['fax']) && isset($oldData['telexnumber']) && $oldData['telexnumber'][0]!=$userData['fax']) $ldap_add['telexnumber']=$userData['fax'];
				if (strlen($userData['tel']) && isset($oldData['telephonenumber']) && $oldData['telephonenumber'][0]!=$userData['tel']) $ldap_add['telephonenumber']=$userData['tel'];
				if (strlen($userData['company']) && isset($oldData['departmentnumber']) && $oldData['departmentnumber'][0]!=$userData['company']) $ldap_add['departmentnumber']=$userData['company'];
				if (strlen($userData['title']) && isset($oldData['title']) && $oldData['title'][0]!=$userData['title']) $ldap_add['title']=$userData['title'];
			break;
			case 'posixAccount':
				if (strlen($userData['posix']) && isset($oldData['loginshell'])) {
					$newShell=$userData['posix']?'/bin/bash':'/bin/false';
					if ($oldData['loginshell'][0]!=$newShell) $ldap_add['loginShell']=$newShell;
				}
				if (strlen($userData['username']) && isset($oldData['uid']) && $oldData['uid'][0]!=$userData['username']) $ldap_add['uid']=$userData['username'];
				if (strlen($userData['username']) && isset($oldData['homedirectory']) && $oldData['homedirectory'][0]!='/home/'.$userData['username']) $ldap_add['homeDirectory']='/home/'.$userData['username'];
				if (isset($userData['gidnumber']) && isset($oldData['gidnumber']) && $oldData['gidnumber'][0]!=$userData['gidnumber']) $ldap_add['gidNumber']=$userData['gidnumber'];
				//if (isset($userData['lastname']) && isset($oldData['gecos']) && $oldData['gecos'][0]!=$userData['firstname'].' '.$userData['lastname']) $ldap_add['gecos']=$userData['firstname'].' '.$userData['lastname'];
			break;
			case 'shadowAccount':
				if ($userData['deleted'] && isset($oldData['shadowexpire']) && $userData['deleted']!=$oldData['shadowexpire'][0]) $ldap_add['shadowExpire']=$userData['deleted'];
				if (strlen($oldData['userpassword']) && $userData['password']) {
					$ldap_add['userPassword']=$this->password_hash( $userData["password"], $GLOBALS['CFG']['LDAP_PWD_HASH'] );
					if (strlen($oldData['shadowlastchange'])) $ldap_add['shadowLastChange']=$this->dateDays();
					/*if ($this->getFrmCfg('cfg/usr/MaxPassDays') && isset($oldData['shadowexpire'])) {
						$ldap_add['shadowExpire']=$this->dateDays() + $this->getFrmCfg('cfg/usr/MaxPassDays');
					}*/
				}
				if (isset($userData['shadowMax']) && strlen($oldData['shadowmax'][0]) && $oldData['shadowmax'][0]!=$userData['shadowMax']) {
					if ($userData['shadowMax']) {
						$ldap_add['shadowMax']=$userData['shadowMax'];
					} else {
						$ldap_add['shadowMax']=999999;
					}
				}
			break;
			case 'groupeUser':
				if (strlen($userData['perms']) && isset($oldData['permsgroup-e']) && $oldData['permsgroup-e'][0]!=intval($userData['perms'])) $ldap_add['permsGroup-e']=intval($userData['perms']);
				if (strlen($userData['groupe']) && isset($oldData['activgroup-e']) && $oldData['activgroup-e'][0]!=intval($userData['groupe'])) $ldap_add['activGroup-e']=intval($userData['groupe']);
			break;
			case 'groupeSquid':
				if (strlen($userData['internet']) && isset($oldData['accesssquid']) && $oldData['accesssquid'][0]!=intval($userData['internet'])) $ldap_add['accessSquid']=intval($userData['internet']);
			break;
			case 'sambaSamAccount':
				$smb_profile=$this->_getSmbProfileData($userData);
				if (isset($userData['samba']) && isset($oldData['sambaacctflags']) && $userData['samba'] && strpos($oldData['sambaacctflags'][0],'D')) $ldap_add['sambaAcctFlags']=str_replace('D','',$oldData['sambaacctflags'][0]);
				if (isset($userData['samba']) && isset($oldData['sambaacctflags']) && !$userData['samba'] && !strpos($oldData['sambaacctflags'][0],'D')) $ldap_add['sambaAcctFlags']=str_replace(']','D]',$oldData['sambaacctflags'][0]);
				// Stefan Schaefer - stefan@invis-server.org - Lasst bitte die Finger von bereits vorhandenen Attributen
				//if (strlen($userData['username']) && isset($oldData['sambalogonscript']) && $oldData['sambalogonscript'][0]!=$userData['username'].'.bat') $ldap_add['sambaLogonScript']=$userData['username'].'.bat';
				if (strlen($userData['username']) && !isset($oldData['sambalogonscript'])) $ldap_add['sambaLogonScript']=$userData['username'].'.bat';
				if (strlen($userData['username']) && isset($oldData['sambaprofilepath']) && $oldData['sambaprofilepath'][0]!=$smb_profile['smbProfilePath']) $ldap_add['sambaProfilePath']=$smb_profile['smbProfilePath'];
				if (strlen($userData['username']) && isset($oldData['sambahomedrive']) && $oldData['sambahomedrive'][0]!=$smb_profile['smbHomeDrive']) $ldap_add['sambaHomeDrive']=$smb_profile['smbHomeDrive'];
				if (strlen($userData['username']) && isset($oldData['sambahomepath']) && $oldData['sambahomepath'][0]!=$smb_profile['smbHomePath']) $ldap_add['sambaHomePath']=$smb_profile['smbHomePath'];

				if (isset($userData['gidnumber']) && isset($oldData['sambaprimarygroupsid'])) {
					$newSid=$this->idToSid($userData['gidnumber'],'g',true);
					if ($oldData['sambaprimarygroupsid'][0]!=$newSid)	$ldap_add['sambaPrimaryGroupSid']=$newSid;
				}
				//$smb_sid=$this->_getSambaSid();
				//if (strlen($userData['uidnumber']) && isset($oldData['sambasid'])) $ldap_add['sambaSID']=$smb_sid.'-'.(2*$userData['uidnumber']+1000);
				if ($userData['password']) {
					$smb_pwd=$this->createSambaPasswords($userData["password"]);
					if (isset($oldData['sambantpassword'])) $ldap_add['sambaNTPassword']=$smb_pwd['sambaNTPassword'];
					if (isset($oldData['sambalmpassword'])) $ldap_add['sambaLMPassword']=$smb_pwd['sambaLMPassword'];
					if (isset($oldData['sambapwdcanchange'])) $ldap_add['sambaPwdCanChange']=time();
					if (isset($oldData['sambapwdlastset'])) $ldap_add['sambaPwdLastSet']=time();
					if (isset($oldData['sambapwdmustchange'])) {
						//debug($oldData['shadowmax']);
						$shadowMax=isset($userData['shadowMax'])?$userData['shadowMax']:$oldData['shadowmax'][0];
						if ($shadowMax) {
							$ldap_add['sambaPwdMustChange']=time()+($shadowMax+21)*3600*24;
						} else {
							$ldap_add['sambaPwdMustChange']=2147483647;
						}
					}
				}
			break;
			case 'posixGroup':
				if (strlen($userData['cn']) && isset($oldData['cn']) && $oldData['cn'][0]!=$userData['cn']) $ldap_add['cn']=$userData['cn'];
			break;
			case 'sambaGroupMapping':
			break;
			case 'groupeGroup':
				if (strlen($userData['activGroup-e']) && isset($oldData['activgroup-e']) && $oldData['activgroup-e'][0]!=intval($userData['activGroup-e'])) $ldap_add['activGroup-e']=intval($userData['activGroup-e']);
			break;
			case 'postfixUser':
				if (strlen($userData['maildrop']) && isset($oldData['maildrop']) && $oldData['maildrop'][0]!=$userData['maildrop']) $ldap_add['maildrop']=$userData['maildrop'];
			break;
			case 'inetLocalMailRecipient':
				if (strlen($userData['mailhost']) && isset($oldData['mailhost']) && $oldData['mailhost'][0]!=$userData['mailhost']) $ldap_add['mailHost']=$userData['mailhost'];
				if (strlen($userData['mailroutingaddress']) && isset($oldData['mailroutingaddress']) && $oldData['mailroutingaddress'][0]!=$userData['mailroutingaddress']) $ldap_add['mailRoutingAddress']=$userData['mailroutingaddress'];
			break;

		}
		return $ldap_add;
	}
	function _getObjectAdd($objClass,&$userData,&$oldData) {
		$ldap_add=array();
		switch ($objClass) {
			case 'inetOrgPerson':
				if (strlen($userData['firstname']) && !isset($oldData['givenname'])) $ldap_add['givenName']=$userData['firstname'];
				if (strlen($userData['lastname']) && !isset($oldData['sn'])) $ldap_add['sn']=$userData['lastname'];
				if (strlen($userData['lastname']) && !isset($oldData['cn'])) $ldap_add['cn']=$userData['firstname'].' '.$userData['lastname'];
				if (strlen($userData['email']) && !isset($oldData['mail'])) $ldap_add['mail']=$userData['email'];
				if (strlen($userData['zip']) && !isset($oldData['postalcode'])) $ldap_add['postalcode']=$userData['zip'];
				if (strlen($userData['place']) && !isset($oldData['postaladdress'])) $ldap_add['postaladdress']=$userData['place'];
				if (strlen($userData['adress']) && !isset($oldData['street'])) $ldap_add['street']=$userData['adress'];
				if (strlen($userData['fax']) && !isset($oldData['telexnumber'])) $ldap_add['telexnumber']=$userData['fax'];
				if (strlen($userData['tel']) && !isset($oldData['telephonenumber'])) $ldap_add['telephonenumber']=$userData['tel'];
				if (strlen($userData['company']) && !isset($oldData['departmentnumber'])) $ldap_add['departmentnumber']=$userData['company'];
				if (strlen($userData['title']) && !isset($oldData['title'])) $ldap_add['title']=$userData['title'];
			break;
			case 'posixAccount':
				if (strlen($userData['posix']) && !isset($oldData['loginshell'])) {
					if ($userData['posix']) $ldap_add['loginShell']='/bin/bash'; else $ldap_add['loginShell']='/bin/false';
				}
				if (strlen($userData['username']) && !isset($oldData['uid'])) $ldap_add['uid']=$userData['username'];
				if (strlen($userData['uidnumber']) && !isset($oldData['uidnumber'])) $ldap_add['uidNumber']=$userData['uidnumber'];
				if (strlen($userData['username']) && !isset($oldData['homedirectory'])) $ldap_add['homeDirectory']='/home/'.$userData['username'];
				if (strlen($userData['gidnumber']) && !isset($oldData['gidnumber'])) $ldap_add['gidNumber']=$userData['gidnumber'];
				//if (isset($userData['lastname']) && !isset($oldData['gecos'])) $ldap_add['gecos']=$userData['firstname'].' '.$userData['lastname'];
			break;
			case 'shadowAccount':
				if (!isset($oldData['shadowexpire'])) {
					$ldap_add['shadowExpire']=-1;
				}
				if (!isset($oldData['shadowflag'])) $ldap_add['shadowFlag']=0;
				if (!isset($oldData['shadowinactive'])) $ldap_add['shadowInactive']=7;
				if (!isset($oldData['shadowmax'])) {
					if ($userData['shadowMax']) {
						$ldap_add['shadowMax']=$userData['shadowMax'];
					} else {
						$ldap_add['shadowMax']=999999;
					}
				}
				if (!isset($oldData['shadowmin'])) $ldap_add['shadowMin']=-1;
				if (!isset($oldData['shadowwarning'])) $ldap_add['shadowWarning']=7;
				if (!isset($oldData['userpassword'])) {
					if (!$userData["password"]) {
						$ldap_add['userPassword']=$this->password_hash( 'password', $GLOBALS['CFG']['LDAP_PWD_HASH']  );
					} else {
						$ldap_add['userPassword']=$this->password_hash( $userData["password"],$GLOBALS['CFG']['LDAP_PWD_HASH']  );
					}
				}
				if (!isset($oldData['shadowlastchange'])) {
					$ldap_add['shadowLastChange']=$this->dateDays();
				}
			break;
			case 'groupeUser':
				if (!isset($oldData['uidgroup-e'])) {
					if (isset($userData['uidGroup-e'])) $ldap_add['uidGroup-e']=$userData['uidGroup-e'];
					elseif (isset($userData['uidnumber'])) $ldap_add['uidGroup-e']=$userData['uidnumber'];
				}
				if (strlen($userData['perms']) && !isset($oldData['permsgroup-e'])) $ldap_add['permsGroup-e']=intval($userData['perms']);
				if (strlen($userData['groupe']) && !isset($oldData['activgroup-e'])) $ldap_add['activGroup-e']=intval($userData['groupe']);
			break;
			case 'groupeSquid':
				if (strlen($userData['internet']) && !isset($oldData['accesssquid'])) $ldap_add['accessSquid']=intval($userData['internet']);
			break;
			case 'sambaSamAccount':
				$smb_sid=$this->_getSambaSid();
				$smb_profile=$this->_getSmbProfileData($userData);
				if (!isset($oldData['sambaacctflags'])){
					if ($userData['samba']) $ldap_add['sambaAcctFlags']='[UX]'; else $ldap_add['sambaAcctFlags']='[UXD]';
				}
				if (!isset($oldData['sambahomedrive'])) $ldap_add['sambaHomeDrive']=$smb_profile['smbHomeDrive'];
				if (!isset($oldData['sambahomepath'])) $ldap_add['sambaHomePath']=$smb_profile['smbHomePath'];
				if (!isset($oldData['sambakickofftime'])) $ldap_add['sambaKickoffTime']=2147483647;
				if (strlen($userData['username']) && !isset($oldData['sambalogonscript'])) $ldap_add['sambaLogonScript']=$userData['username'].'.bat';
				if (strlen($userData["password"])) {
					$smb_pwd=$this->createSambaPasswords($userData["password"]);
					if (!isset($oldData['sambantpassword']) && !empty($smb_pwd['sambaNTPassword'])) $ldap_add['sambaNTPassword']=$smb_pwd['sambaNTPassword'];
					if (!isset($oldData['sambalmpassword']) && !empty($smb_pwd['sambaLMPassword'])) $ldap_add['sambaLMPassword']=$smb_pwd['sambaLMPassword'];
				}
				if (!isset($oldData['sambalogofftime'])) $ldap_add['sambaLogoffTime']=2147483647;
				if (!isset($oldData['sambalogontime'])) $ldap_add['sambaLogonTime']=0;
				//if (!isset($oldData['sambapasswordhistory'])) $ldap_add['sambaPasswordHistory']='0000000000000000000000000000000000000000000000000000000000000000';
				if (isset($userData['gidnumber']) && !isset($oldData['sambaprimarygroupsid'])) $ldap_add['sambaPrimaryGroupSid']=$this->idToSid($userData['gidnumber'],'g',true);
				if (!isset($oldData['sambapwdcanchange'])) $ldap_add['sambaPwdCanChange']=time();
				if (!isset($oldData['sambapwdlastset'])) $ldap_add['sambaPwdLastSet']=time();
				if (!isset($oldData['sambapwdmustchange'])) $ldap_add['sambaPwdMustChange']=$userData['shadowMax']?(time()+($userData['shadowMax']+21)*3600*24):2147483647;
				if (strlen($userData['uidnumber']) && !isset($oldData['sambasid'])) $ldap_add['sambaSID']=$smb_sid.'-'.(2*$userData['uidnumber']+1000);
				if (strlen($userData['username']) && !isset($oldData['sambaprofilepath'])) $ldap_add['sambaProfilePath']=$smb_profile['smbProfilePath'];
			break;
			case 'posixGroup':
				if (strlen($userData['cn']) && !isset($oldData['cn'])) $ldap_add['cn']=$userData['cn'];
				if (strlen($userData['gidnumber']) && !isset($oldData['gidnumber'])) $ldap_add['gidNumber']=$userData['gidnumber'];
			break;
			case 'sambaGroupMapping':
				if (!isset($oldData['sambagrouptype'])) $ldap_add['sambaGroupType']=2;
				if (strlen($userData['gidnumber']) && !isset($oldData['sambasid'])) {
					$smb_sid=$this->_getSambaSid();
					$ldap_add['sambaSID']=$smb_sid.'-'.(2*$userData['gidnumber']+1001);
				}
			break;
			case 'groupeGroup':
				//debug($userData);
				if (!isset($oldData['gidgroup-e'])) {
					if (isset($userData['gidGroup-e'])) $ldap_add['gidGroup-e']=$userData['gidGroup-e'];
					elseif (isset($userData['gidnumber'])) $ldap_add['gidGroup-e']=$userData['gidnumber'];
				}
				if (strlen($userData['activGroup-e']) && !isset($oldData['activgroup-e'])) $ldap_add['activGroup-e']=$userData['activGroup-e'];
			break;
			case 'postfixUser':
				if (strlen($userData['mailacceptinggeneralid']) && (!isset($oldData['mailacceptinggeneralid']) || !in_array(strtolower($userData['mailacceptinggeneralid']),$oldData['mailacceptinggeneralid']))) $ldap_add['mailacceptinggeneralid']=strtolower($userData['mailacceptinggeneralid']);
				if (strlen($userData['maildrop']) && !isset($oldData['maildrop'])) $ldap_add['maildrop']=$userData['maildrop'];
			break;
			case 'inetLocalMailRecipient':
				if (strlen($userData['mailhost']) && !isset($oldData['mailhost'])) $ldap_add['mailHost']=$userData['mailhost'];
				if (strlen($userData['mailroutingaddress']) && !isset($oldData['mailroutingaddress'])) $ldap_add['mailRoutingAddress']=strtolower($userData['mailroutingaddress']);
				if (strlen($userData['maillocaladdress'])) {
					$temp=explode(',',$userData['maillocaladdress']);
					foreach ($temp as $val) {
						if (!isset($oldData['maillocaladdress']) || !in_array(strtolower(trim($val)),$oldData['maillocaladdress'])) {
							$ldap_add['mailLocalAddress'][]=strtolower(trim($val));
						}
					}
				}
			break;
		}
		return $ldap_add;
	}

	function _getSmbProfileData($userData) {
		$server=$this->getSmbServers();
		$key=0;
		if ($userData['smbServer'] && in_array($userData['smbServer'],$server)) {
			$key=intval(array_search($userData['smbServer'],$server));
		}
		$tras_array=array(
			'{USERNAME}'=>$userData['username'],
			'{SERVER}'=>$server[$key],
			'{DOMAIN}'=>str_replace('.','_',$this->_myDomain),
		);


		$drive=trim($this->getFrmCfg('ldap/smbHomeDrive'))?explode(',',$this->getFrmCfg('ldap/smbHomeDrive')):array('H:');
		$home=trim($this->getFrmCfg('ldap/smbHomePath'))?explode(',',$this->getFrmCfg('ldap/smbHomePath')):array('\\\\{SERVER}\\homes');
		$profile=trim($this->getFrmCfg('ldap/smbProfilePath'))?explode(',',$this->getFrmCfg('ldap/smbProfilePath')):array('\\\\{SERVER}\\profiles\\{USERNAME}');
		return array(
			'smbServer'=>$server[$key],
			'smbHomeDrive'=>isset($drive[$key])?strtr($drive[$key],$tras_array):strtr($drive[0],$tras_array),
			'smbHomePath'=>isset($home[$key])?strtr($home[$key],$tras_array):strtr($home[0],$tras_array),
			'smbProfilePath'=>isset($profile[$key])?strtr($profile[$key],$tras_array):strtr($profile[0],$tras_array),
		);
	}
	function getSmbServers() {
		$servers=explode(',',$this->getFrmCfg('ldap/smbServer'));
		return $servers;
	}
	function _getMySmbServer($info) {
		//debug($info);
		$servers=$this->getSmbServers();
		foreach ($servers as $val) {
			$profile=$this->_getSmbProfileData(array('username'=>$info['uid'][0],'smbServer'=>$val));
			//debug($profile);
			if ($profile['smbHomeDrive']==$info['sambahomedrive'][0] &&
				$profile['smbHomePath']==$info['sambahomepath'][0] &&
				$profile['smbProfilePath']==$info['sambaprofilepath'][0]
				) {
				return $val;
			}
		}
		return reset($servers);
	}

	function getUserPwd($uname) {
		$query="SELECT password FROM auth_user_md5 WHERE username=".$this->db->escape($uname);
		$query.=" AND activ=1 and perms!='deleted'";
		$this->db->query($query);
		if ($this->db->next_record()) {
			return $this->db->f("password");
		} else {
			return false;
		}
	}


	function getRule($perms=0,$sadmin=0) {
		$result=array();
		if (is_array($perms)) {
			$where=" Perms IN (".implode(",",$this->db->escape($perms)).")";
		} elseif ($perms) {
			$where=" Perms=".$this->db->escape($perms)."";
		} else $where='';
		if ($sadmin) {
			$query="SELECT Perms FROM Rules ";
			if ($where) $query.=" WHERE $where";
			$this->db->query($query);
		} else {
			$query="SELECT Perms FROM user_rules INNER JOIN Rules ON RuleID=FKRuleID WHERE FKUserID=".$this->db->escape($this->uid);
			if ($where) $query.=" AND $where";
			$this->db->query($query);
		}
		while ($this->db->next_record()) {
			$result[$this->db->f('Perms')]=$this->db->f('Perms');
		}
		return $result;
	}
	function getRuleUser($perms) {
		$this->db->query("SELECT FKUserID FROM user_rules INNER JOIN Rules ON RuleID=FKRuleID WHERE Perms=".$this->db->escape($perms)." AND Activ=1 ORDER BY Priority");
		if ($this->db->next_record()) {
			return $this->db->f('FKUserID');
		} else return 0;
	}
	function getUserRules($user_id) {
		$query="SELECT Perms,Name_".$this->lang." FROM user_rules INNER JOIN Rules ON RuleID=FKRuleID WHERE FKUserID=".$this->db->escape($user_id);
		$this->db->query($query);
		while ($this->db->next_record()) {
			$rules[$this->db->f("Perms")]=$this->db->f("Name_".$this->lang);
		}
		return $rules;
	}
	function insertRuleUser($RuleID,$UserID,$Priority) {
		$insert=array(
			'FKRuleID'=>$RuleID,
			'Priority'=>$Priority,
			'FKUserID'=>$UserID,
			'Activ'=>1,
		);
		$this->db->insert('user_rules',$insert);
	}
	function deleteRuleUser($RuleID=0,$UserID=0,$Priority=-1) {
		$query="DELETE FROM user_rules WHERE ";
		if ($RuleID) {
			$where[]=" FKRuleID=".$this->db->escape($RuleID);
		}
		if ($UserID) {
			$where[]=" FKUserID=".$this->db->escape($UserID);
		}
		if ($Priority!=-1) {
			$where[]=" Priority=".$this->db->escape($Priority);
		}
		if ($where) {
			$this->db->query($query.implode(' AND ',$where));
			//debug($query.implode(' AND ',$where));
		}
	}
	function getAllRules($ruleID=0) {
		$query="SELECT Perms,Name_".$this->lang." FROM Rules";
		if ($ruleID) $query.=" WHERE RuleID=".$this->db->escape($ruleID);
		$query.=" ORDER BY Name_".$this->lang;
		$this->db->query($query);
		while ($this->db->next_record()) {
				$rules[$this->db->f("Perms")]=$this->db->f("Name_".$this->lang);
		}
		return $rules;
	}
	function listAllRules() {
		$query="SELECT RuleID,Perms,Name_".$this->lang." FROM Rules";
		$query.=" ORDER BY Name_".$this->lang;
		$this->db->query($query);
		while ($this->db->next_record()) {
				$rules[$this->db->f("RuleID")]=array(
					'Perms'=>$this->db->f("Perms"),
					'Name'=>$this->db->f("Name_".$this->lang),
				);
		}
		return $rules;
	}
	function getRuleUsers($perms,$all=0) {
		$result=array();
		$query="SELECT Perms,FKUserID,Priority FROM user_rules INNER JOIN Rules ON RuleID=FKRuleID ";
		$query.=" WHERE Perms=".$this->db->escape($perms);
		if (!$all) $query.=" AND Activ=1";
		$query.=" ORDER BY Priority";
		$this->db->query($query);
		while ($this->db->next_record()) {
			$result[$this->db->f('Perms')][$this->db->f('Priority')]=$this->db->f('FKUserID');
		}
		return $result;
	}
	function insertUserLog($userData,$oldData) {
		$insert=array(
			'uid'=>$userData['username'],
			'uidNumber'=>$userData['uidnumber'],
			'old_uid'=>$oldData['username'],
			'old_uidNumber'=>$oldData['uidnumber'],
		);
		$this->db->insert('AdminUserLog',$insert,'');
	}
	function password_hash( $password_clear, $enc_type ){
		$enc_type = strtolower( $enc_type );
		switch( $enc_type )	{
			case 'crypt':
				$new_value = '{CRYPT}' . crypt( $this->utf8encode($password_clear), $this->random_salt(2) );
				break;
			case 'md5crypt':
				$new_value = '{CRYPT}' . crypt( $this->utf8encode($password_clear) , '$1$' . $this->random_salt(9) );
				break;
			case 'blowfish':
				$new_value = '{CRYPT}' . crypt( $this->utf8encode($password_clear) , '$2$' . $this->random_salt(13) );
				break;
			case 'sha':
				$new_value = '{SHA}' . base64_encode( pack( 'H*' , sha1($this->utf8encode($password_clear))));
				break;
			case 'ssha':
				mt_srand( (double) microtime() * 1000000 );
				$salt = substr(pack("H*", sha1(substr( pack( "h*", md5( mt_rand() ) ), 0, 8 ).$this->utf8encode($password_clear))), 0,  4);
				$new_value = "{SSHA}".base64_encode(pack( 'H*' , sha1($this->utf8encode($password_clear).$salt)).$salt);
				break;
			case 'smd5':
				mt_srand( (double) microtime() * 1000000 );
				$salt = substr(pack("H*", md5(substr( pack( "h*", md5(mt_rand() ) ), 0, 8 ) . $this->utf8encode($password_clear))), 0, 4);
				$new_value = "{SMD5}".base64_encode( pack( 'H*' , md5($this->utf8encode($password_clear).$salt)).$salt );
				break;
			case 'clear':
				$new_value = $password_clear;
				break;
			case 'md5':
			default:
				$new_value = '{MD5}' . base64_encode( pack( 'H*' , md5( $this->utf8encode($password_clear)) ) );
				break;
		}
		return $new_value;
	}
	function random_salt($length){
		$possible = '0123456789'.
			'abcdefghijklmnopqrstuvwxyz'.
			'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
			'./';
		$str = "";
		mt_srand((double)microtime() * 1000000);
		while( strlen( $str ) < $length ) {
				$str .= substr( $possible, ( rand() % strlen( $possible ) ), 1 );
		}
		//$str = "\$1\$".$str."\$";
		return $str;
	}

  	function createSambaPasswords($password){
    	//if (file_exists ($this->getFrmCfg('ldap/mkntpwdCommand')) && is_executable ($this->getFrmCfg('ldap/mkntpwdCommand'))) {
			 $sambaPassCommand = $this->getFrmCfg('ldap/mkntpwdCommand')." ".escapeshellarg($password);
			 //debug($sambaPassCommand);
			 if($sambaPassCommandOutput = shell_exec($sambaPassCommand)){
				//debug($sambaPassCommandOutput);
      			$result['sambaLMPassword'] = trim( substr( $sambaPassCommandOutput , 0 , strPos( $sambaPassCommandOutput,':' )));
      			$result['sambaNTPassword'] = trim( substr( $sambaPassCommandOutput, strPos( $sambaPassCommandOutput ,':' ) +1 ));
      			return $result;
    		}
		//}
     	return false;
    }
	function ldapEsc($value) {
		if (is_array($value)) {
			foreach ($value as $key=>$val) {
				$value[$key]=$this->ldapEsc($val);
			}
		} else {
			$tras=array('*'=>'\\2a','('=> '\\28',')'=>'\\29','\\'=>'\\5c','\0'=>'\\00');
			$value=strtr($value,$tras);
		}
		return $value;
	}
	function ldapEscDN($value) {
		if (is_array($value)) {
			foreach ($value as $key=>$val) {
				$value[$key]=$this->ldapEscDN($val);
			}
		} else {
			$tras=array(','=>'\,','+'=>'\+');
			$value=strtr($value,$tras);
		}
		return $value;
	}
	function utf8encode($value) {
		if (is_array($value)) {
			foreach ($value as $key=>$val) {
				$value[$key]=$this->utf8encode($val);
			}
		} else {
			$value=Framework::convertCharset($value,GE_CHARSET,'UTF-8');
		}
		return $value;
	}
	function utf8decode($value) {
		if (is_array($value)) {
			foreach ($value as $key=>$val) {
				$value[$key]=$this->utf8decode($val);
			}
		} else {
			$value=Framework::convertCharset($value,'UTF-8',GE_CHARSET);
		}
		return $value;
	}
	function dateDays($time=0) {
		if (!$time) $time=time();
		return floor($time/86400);

	}
}
?>