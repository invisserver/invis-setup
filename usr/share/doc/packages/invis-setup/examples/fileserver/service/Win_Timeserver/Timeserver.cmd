net stop w32time
regedit.exe /s %~dp0Timeserver.reg
net start w32time