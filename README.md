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

Example:

.raydar/dirs:

 [scan_dirs]
 /Photos/mine
 /Photos/Bob
 
 [ignore_dirs_match]
 .AppleDouble
 
 [ignore_files_match]
 .picasa.ini

.raydar/smtp:

 smtp_host=host.example.com
 smtp_auth=true
 smtp_username=my-user
 smtp_password=my-password
 smtp_secure=tls
  
 smtp_from=sender@example.com
 smtp_fromname=Raydar Alerts
 smtp_to=address1@example.com,address2@example.com,address3@example.com
 
 smtp_subject=Dropbox updates [[DATE]]

TODOs:

* Maybe move to a per-directory cache, rather than a directory-tree based one (would preserve cache if moving between sub/parent folders)
* Link recursive option to dirs config

