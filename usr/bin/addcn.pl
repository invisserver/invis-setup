#!/usr/bin/perl -w
#
# This Script creates for every system-user a turba-contact
# entry in an LDAP-based addressbook.
# Existing entries are leaved untouched.

# Script based on a Script from tarjei@nu.no.
# mail: stefan@invis-server.org
# Copyright (c) 2008 Stefan Schaefer -- FSP Computer & Netzwerke
# (C) 2008 Stefan Schaefer -- invis-server.org
# (C) 2012 Ingo Goeppert -- invis-server.org
# Questions: http://forum.invis-server.org
# License GPLv3

# Last Changes: 11.03.2012 

# Get Conf-Params
$cfile = "/etc/invis/invis.conf";
$pwfile = "/etc/invis/invis-pws.conf";

# Funktion zum Auslesen der invis Konfigurationsdateien
sub myGETPARAM {
    #print $_[1];
    open(CONF, $_[0]);
    @lines = <CONF>;
    close(CONF);

    foreach $line (@lines) {
	@splitline = split(/:/, $line);
	if ($splitline[0] eq "$_[1]") {
	    chomp($splitline[1]);
	    return $splitline[1];
	}
    }
}

# Konfigurationen ermitteln
$basedn = &myGETPARAM("$cfile", "baseDN");
$ldaphost = &myGETPARAM("$cfile", "ldapHost");
$passwd = &myGETPARAM("$pwfile", "AdminPW");

use Net::Domain qw(hostname hostfqdn hostdomain);

# Settings that should be changed for your setup:
$kontaktdn = "ou=internes_adressbuch,ou=kontakt";
$userdbdn = "ou=users,ou=benutzerverwaltung";
$binddn = "uid=Admin,ou=Kontakt,$basedn";
$hostname = hostname();
$domain = hostdomain();
$options = ""; # Keine Optionen fuer ldapadd benoetigt.

# End of configuration section - don't edit below here.

use Getopt::Std;
my %Options;
$user = $ARGV[0];

# Vorgabewerte aus der Benutzerdatenbank ermitteln.
use Net::LDAP;
$ldap_con = Net::LDAP->new("$ldaphost");

$resattrs = $ldap_con->search( filter=>"(uid=$user)",base=>"$userdbdn,$basedn",attrs=> ['cn', 'givenname', 'sn'] );
@entries = $resattrs->entries;
foreach $entry (@entries) {
    #@attrs = $entry->attributes();
    $cn = $entry->get_value('cn');
    $gn = $entry->get_value('givenname');
    $sn = $entry->get_value('sn');
}

$ldap_con->unbind;

print "Adding ou: cn=$cn,$kontaktdn,$basedn";

$FILE = "|/usr/bin/ldapadd -x $options -D '$binddn' -w $passwd";

open FILE or die;

print FILE <<EOF;
dn: cn=$cn,$kontaktdn,$basedn
objectClass: top
objectClass: person
objectClass: organizationalPerson
objectClass: inetOrgPerson
objectClass: mozillaOrgPerson
objectClass: calEntry
cn: $cn
givenname: $gn
sn: $sn
mail: $user\@$domain
calFBURL: http://$hostname.$domain/horde/kronolith/fb.php\?u=$user
description: System Benutzer

EOF
close FILE;
exit 0;

