<?
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
define('PREPEND_FILE','/srv/www/htdocs/group-e/etc/phplib/prepend.php');
$THEME='ge';
$USE_DOMAIN=false;
$CFG['SUPERADMIN']=3000;
$CFG['GROUP_ALL']=3000;
$CFG['LDAP_MIN_UID']=1000;
$CFG['LDAP_MIN_GID']=1000;
$CFG['LDAP_PWD_HASH']='smd5';
if (file_exists('../dyn') && !file_exists('dyn')) {
	define('GE_DYN_PATH',realpath('../dyn'));
	define('GE_DYN_PATH_OK',1);
} else {
	define('GE_DYN_PATH',realpath('dyn'));
	define('GE_DYN_PATH_OK',0);
}
if ($USE_DOMAIN) {
	$DOMAIN=$_SERVER['SERVER_NAME'];
	define('GE_LDAP_DOMAIN_SEP','_');
	define('GE_DB_PREPEND','groupe_');
	if (file_exists(GE_DYN_PATH.'/domain_config/'.$DOMAIN)) {
		$myDomainIni=parse_ini_file(GE_DYN_PATH.'/domain_config/'.$DOMAIN);
		foreach ($myDomainIni as $key=>$val) {
			define('GE_'.$key,$val);
		}
	}
	if (!defined('GE_GROUPEInstanz')) define('GE_GROUPEInstanz',1);
	if (defined('GE_myDomain') && !defined('GE_DOMAIN')) define('GE_DOMAIN',GE_myDomain); // for Kompatibility
	if (!defined('GE_DOMAIN')) define('GE_DOMAIN',str_replace('www.','',$DOMAIN));
	define('GE_DOMAIN_PATH',str_replace('.','_',GE_DOMAIN).'/');
	define('GE_DB',GE_DB_PREPEND.str_replace('.','_',GE_DOMAIN));
} else {
	define('GE_GROUPEInstanz',1);
	define('GE_DOMAIN','');
	define('GE_DOMAIN_PATH','');
	define('GE_DB','groupe'); // SET NAME OF DATABASE
}
$DEBUG=1;
$CFG['MPASSWD']=false;
$CFG['USER_CLASS']='ldap_full';  // For LDAP use 'ldap_full' for Mysql 'mysql'

if ($CFG['USER_CLASS']=='mysql') {
	$CFG['MPASSWD']=true;
} elseif (!extension_loaded('ldap')) {
	die ('LDAP Extension not avaible: Please install LDAP Extension OR change $CFG[\'USER_CLASS\'] to \'mysql\' in www/cfg/global.inc.php ');
}
//define('ADDR_MODE','ldap');
//define('NO_MB_ENTITIES',1);
//define('USE_MAIL_FROM_DATE',1);
//define('GE_addrArchivCat',0);
//define('FILE_LOG','/tmp/fileLog.txt');
//define('AJAX_LOG','/tmp/ajaxLog.txt');

if (!ini_get('register_globals')) {
	extract($_POST,EXTR_SKIP);
	extract($_GET,EXTR_SKIP);
}
?>
