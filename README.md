raydar
======

Dropbox monitoring tool. Eventually this will run regularly on a Raspberry Pi and send out emails listing latest uploads.

SETUP:

* clone into a directory
* cp raydar-config.php.template to raydar-config.php
* edit raydar-config.php to point to your .raydar in your home directory
* create a .raydar directory in your home directory
* add the following files to the directory above:
** dirs - list of directories in Dropbox to check
** smtp - more info to come on how to set up SMTP.

TODOs:

* Maybe move to a per-directory cache, rather than a directory-tree based one (would preserve cache if moving between sub/parent folders)
* Add ignore folder patterns, for folders such as .AppleDouble
* Add ignore file patterns, for system files, etc.
* Link recursive option to dirs config

