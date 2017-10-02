raydar
======

Dropbox monitoring tool. I have this running on a Raspberry Pi via a cronjob, which checks a bunch of listed Dropbox folders (which my user has access to) and sends out an email showing any new files in those folders.

SETUP:

* clone into a directory
* cp raydar-config.php.template to raydar-config.php
* edit raydar-config.php to point to your .raydar in your home directory
* create a .raydar directory in your home directory
* add the following files to the directory above:
** dirs - list of directories in Dropbox to check
** smtp - more info to come on how to set up SMTP.
* The script uses Dropbox-Uploader, so you'll need to set up an app in Dropbox, and set up the script as per instructions at  https://github.com/andreafabrizi/Dropbox-Uploader - this will basically mean running ./bash/Dropbox-Uploader/dropbox_uploader.sh from the base directory of the repo. Once you have an access key, the script should work.

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

