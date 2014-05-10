@echo off 
rem Dieses Script loescht die Ordner documents, videos, downloads, music
rem und pictures aus dem Benutzerverzeichnis des aktuellen Benutzers und
rem erstzt sie durch symbolische links
rem (c) 2010 Stefan Schaefer -- invis-server.org
rem (c) Questions: http://forum.invis-server.org
rem License: GPLv3
echo on 

rmdir /S /Q c:\users\USER\documents
rmdir /S /Q c:\users\USER\downloads
rmdir /S /Q c:\users\USER\pictures
rmdir /S /Q c:\users\USER\videos
rmdir /S /Q c:\users\USER\music

mklink /d c:\users\USER\documents u:\Dokumente
mklink /d c:\users\USER\downloads u:\Downloads
mklink /d c:\users\USER\pictures u:\Bilder
mklink /d c:\users\USER\videos u:\Videos
mklink /d c:\users\USER\music u:\Musik
