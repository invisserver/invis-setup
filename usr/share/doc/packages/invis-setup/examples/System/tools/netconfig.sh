#!/bin/bash
# Kleine Notfallhilfe um openSUSE ein paar autoconfig Flausen auszutreiben.

# Konfigurationsparameter tauschen
changevalues() {
    # Arg1 = Pfad, Arg2 = Datei, Arg3 = sed String
    cat $1/$2|sed "s%$3%g" > $1/$2.new
    mv $1/$2.new $1/$2
}

file="config"
path="/etc/sysconfig/network"
strings="NETCONFIG_DNS_POLICY=\"auto\"%NETCONFIG_DNS_POLICY=\"\""
changevalues $path $file "$strings"
strings="NETCONFIG_NTP_POLICY=\"auto\"%NETCONFIG_NTP_POLICY=\"\""
changevalues $path $file "$strings"
cp ../sysconfig/network/servers-netconfig /var/run/ntp

