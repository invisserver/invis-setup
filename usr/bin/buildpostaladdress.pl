#!/usr/bin/perl -w
#
# LDAP build postaladdress entrys
# This script adds postaladdress entries from single address components.
#
# mail: stefan@invis-net.org
# Copyright (c) 2008 Stefan Schaefer -- FSP Computer & Netzwerke
# (C) 2008 Stefan Schaefer -- invis-server.org
# (C) 2012 Ingo Goeppert -- invis-server.org
# Questions: http://forum.invis-server.org
# License GPLv3

# Get Conf-Params
$cfile = "/etc/invis/invis.conf";
$pwfile = "/etc/invis/invis-pws.conf";

# Funktion zum Auslesen der invis Konfigurationen
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

# Konfigurationen ermitteln.
$basedn = &myGETPARAM("$cfile", "baseDN");
$ldaphost = &myGETPARAM("$cfile", "ldapHost");
$passwd = &myGETPARAM("$pwfile", "AdminPW");

# Settings that should be changed for your setup:
$kontaktdn = "ou=externes_adressbuch,ou=kontakt";
$binddn = "uid=Admin,ou=Kontakt,$basedn";
# End of configuration section - don't edit below here.

# Einzelwerte aus der Kotaktdatenbank ermitteln.
use Net::LDAP;
$ldap_con = Net::LDAP->new("$ldaphost");
$ldap_con->bind("$binddn", password=>"$passwd");
# Workaddress
$resattrs = $ldap_con->search( filter=>"(cn=*)",base=>"$kontaktdn,$basedn",attrs=> ['street', 'postalcode', 'l'] );
@entries = $resattrs->entries;
foreach $entry (@entries) {
    #@attrs = $entry->attributes();
    $street = $entry->get_value('street');
    $pc = $entry->get_value('postalcode');
    $l = $entry->get_value('l');
    $dn = $entry->dn();

if ( defined($pc) ) {
    $addr = "$street \n$pc $l";
    print $dn;
    print "\n";
    print $addr;
    print "\n";
    $ldap_con->modify($dn, replace => { "postaladdress" => "$addr" } );
}}

# Homeaddress
$resattrs = $ldap_con->search( filter=>"(cn=*)",base=>"$kontaktdn,$basedn",attrs=> ['mozillahomestreet', 'mozillahomepostalcode', 'mozillahomelocalityname'] );
@entries = $resattrs->entries;
foreach $entry (@entries) {
    #@attrs = $entry->attributes();
    $street = $entry->get_value('mozillahomestreet');
    $pc = $entry->get_value('mozillahomepostalcode');
    $l = $entry->get_value('mozillahomelocalityname');
    $dn = $entry->dn();

if ( defined($street) && defined($pc) && defined($l) ) {
    $addr = "$street \n$pc $l";
    print $dn;
    print "\n";
    print $addr;
    print "\n";
    $ldap_con->modify($dn, replace => { "homepostaladdress" => "$addr" } );
}}


$ldap_con->unbind;
