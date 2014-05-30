<?php
# Klassendefinitionen für CorNAz

# Klasse Mailprovider

class mailprovider {
    
    # Attribute
    public $mpvendor;
    public $mpdescription;
    public $mpusername;
    public $mppopserver;
    public $mpimapserver;
    public $mpsmtpserver;
    public $mppopssl;
    public $mpimapssl;
    public $mpsmtpport;
    public $mpsmtptls;
	
	public function __construct($mpvendor = "", $mpdescription = "", $mpusername = "", $mppopserver = "", $mpimapserver = "", $mpsmtpserver = "", $mppopssl = "", $mpimapssl = "", $mpsmtpport = "", $mpsmtptls = "") {
	$this->mpvendor = $mpvendor;
        $this->mpdescription = $mpdescription;
    	$this->mpusername = $mpusername;
    	$this->mppopserver = $mppopserver;
    	$this->mpimapserver = $mpimapserver;
    	$this->mpsmtpserver = $mpsmtpserver;
    	$this->mppopssl = $mppopssl;
    	$this->mpimapssl = $mpimapssl;
    	$this->mpsmtport = $mpsmtpport;
    	$this->mpsmtptls = $mpsmtptls;
	}

	# Einen vorhandenen Schneeball auslesen	
	function readmailprovider($mpvendor,$ldapbinddn,$password,$LDAP_SUFFIX,$LDAP_SERVER) {
	# Am LDAP per SimpleBind anmelden
	# Verbindung zum LDAP Server aufbauen
		$ditcon=ldap_connect("$LDAP_SERVER");
		# LDAP Protokoll auf Version 3 setzen
		if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    			echo "Kann das Protokoll nicht auf Version 3 setzen";
		// bind mit passendem dn für aktulisierenden Zugriff
	if ($ditcon) {
   		$r=ldap_bind($ditcon,$ldapbinddn,$password);
		$filter="(fspMailProviderVendor=$mpvendor)";
		$justthese = array("fspMailProviderVendor", "fspMailProviderDescription", "fspMailProviderUserName", "fspMailProviderPOP", "fspMailProviderIMAP", "fspMailProviderPOPSSL", "fspMailProviderIMAPSSL" );
		$sr=ldap_search($ditcon, $LDAP_SUFFIX, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
		//print $entries["count"]." Einträge gefunden<p>";
		ldap_close($ditcon);
		if ( $mpvendor == "*" ) {
			return $entries;
		} else {
			if (isset($entries[0])) {
			# Zuordnung der Ergebniswerte zu den Objekteigenschaften
			$this->mpvendor = $mpvendor;
		        $this->mpdescription = $entries[0]["fspmailproviderdescription"][0];
    			$this->mpusername = $entries[0]["fspmailproviderusername"][0];
    			$this->mppopserver = $entries[0]["fspmailproviderpop"][0];
    			$this->mpimapserver = $entries[0]["fspmailproviderimap"][0];
		    	$this->mppopssl = $entries[0]["fspmailproviderpopssl"][0];
		    	$this->mpimapssl = $entries[0]["fspmailproviderimapssl"][0];
		}}
	} else {
    		echo "Verbindung zum LDAP Server nicht möglich!";
	}}
	
}
?>