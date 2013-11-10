# Freigaben Datei
# Faxdrucker Multi-User

[Faxgate]
	comment = Faxdrucker
	printing = sysv
	path = /var/tmp
	read only = no
	create mask = 0600
	printable = yes
	print command = lpr -r -o ip=%I -P%p %s
	printer name = Faxgate

