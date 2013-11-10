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
$CFG['MA_LANG']=explode('|',$GLOBALS['frm']->getFrmCfg('cfg/malang'));

$CFG['MinPassLenght']=$GLOBALS['frm']->getFrmCfg('cfg/usr/MinPassLenght');
$CFG['MinUserLenght']=$GLOBALS['frm']->getFrmCfg('cfg/usr/MinUserLenght');
$CFG['FixPassword']=$GLOBALS['frm']->getFrmCfg('cfg/usr/FixPassword');
$CFG['NoPassword']=$GLOBALS['frm']->getFrmCfg('cfg/usr/NoPassword');

$CFG['NoMailAdmin']=$GLOBALS['frm']->getFrmCfg('cfg/usr/NoMailAdmin');

$CFG['VIRUS_PROG']=$GLOBALS['frm']->getFrmCfg('cfg/virus/prog');
$CFG['VIRUS_EXIT_CODE']=array(
	$GLOBALS['frm']->getFrmCfg('cfg/virus/ex_infected')=>'infected',
	$GLOBALS['frm']->getFrmCfg('cfg/virus/ex_sospicous')=>'sospicous',
);

if (!isset($CFG['SUPERADMIN'])) $CFG['SUPERADMIN']=3000;
if (!isset($CFG['GROUP_ALL'])) $CFG['GROUP_ALL']=3000;
if (!isset($CFG['LDAP_MIN_UID'])) $CFG['LDAP_MIN_UID']=1000;
if (!isset($CFG['LDAP_MIN_GID'])) $CFG['LDAP_MIN_GID']=1000;
if (!isset($CFG['LDAP_PWD_HASH'])) $CFG['LDAP_PWD_HASH']='smd5';

$CFG['AV_LANG']=array("de","it","en","fr","es");
$CFG['IF_LANG']=array("de","it","en");

$CFG['DEF_SITE']='portal.php';

$CFG['calwidth']=350;
$CFG['calheight']=175;

$CFG['USER_LIST']=true;
$CFG['userwidth']=225;
$CFG['userheight']=350;

$CFG['NL_MAIL']="\n";

if ($CFG['NoMailEdit'] && $auth->auth['uid']==$CFG['SUPERADMIN']) $CFG['NoMailEdit']=false;
if ($CFG['NoUserEdit'] && $auth->auth['uid']==$CFG['SUPERADMIN']) $CFG['NoUserEdit']=false;
?>