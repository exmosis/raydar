<?php

require_once('cls_DropboxFile.php');
require_once('cls_DropboxDir.php');
require_once('includes/PHPMailer/PHPMailerAutoload.php');

define('DROPBOX_UPLOADER', './bash/Dropbox-Uploader/dropbox_uploader.sh');
define('DROPBOX_UPLOADER_CMD_LIST', 'list');
define('DROPBOX_UPLOADER_CMD_GET_URL', 'share');

define('CONFIG_FILE_DIRS', '/home/pi/.raydar/dirs');
define('CONFIG_FILE_KNOWN_FILES', '/home/pi/.raydar/.known_files.db');

// Constants for file types
define('DIR_ENTRY_TYPE_FILE', 'F');
define('DIR_ENTRY_TYPE_DIR', 'D');

run();
exit;

function run() {

	echo 'Ray Dar starting up...' . "\n";

	$raydar_dirs = getDirConfig();
	if (! $raydar_dirs) {
		echo '  No directories configured. Exiting.' . "\n";
		exit;
	}

	list($updates, $known_files) = getDropboxUpdates($raydar_dirs);
	if (! $updates) {
		echo '  No updates detected.' . "\n";
	} else {
		sendUpdatesEmail($updates);
	}

	saveKnownFiles($known_files);

}

function sendUpdatesEmail($updates) {

	$mail = new PHPMailer;

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'smtp.mythic-beasts.com';  // Specify main and backup server
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'graham';                            // SMTP username
	$mail->Password = 'panda43greml1n';                           // SMTP password
	$mail->SMTPSecure = 'tls';   

	$mail->From = 'graham@exmosis.net';
	$mail->FromName = 'Update test';
	$mail->addAddress('exmosis@gmail.com');
	$mail->isHTML(true);

	$mail->Subject = 'Dropbox updates [test]';

	$body = '';
	foreach ($updates as $dir_info) {
		print_r($dir_info);
		$body .= $dir_info->toHTML();
	}

	$mail->Body = $body;
	$mail->AltBody = $body;

	$mail->send();
}

function getDropboxUpdates($dirs) {

	$updates = array();
	
	$old_known_files = getKnownFiles();
	$new_known_files = array();

	foreach ($dirs as $dir => $recurse) {
		$dir_entries[] = buildDropboxContents($dir);
	}

	return array($dir_entries, null);

}


function buildDropboxContents($dir) {

	$cmd = DROPBOX_UPLOADER . ' ' . DROPBOX_UPLOADER_CMD_LIST . ' "' . $dir . '"';
	$dir_entries = `$cmd`;
	$dir_entries = explode("\n", $dir_entries);

	$this_dir = new DropboxDir();
	$this_dir->setFullPath($dir);
	$this_dir->setDirName($dir);

	foreach ($dir_entries as $dir_entry) {
		if (preg_match('/
				^\s*
				\[
				  ([^\]]+)	# $1 = entry type
				\]
				\s+
				  (.*)		# $2 = entry name
				$
				/x', $dir_entry, $matches)) {

			$dir_entry_type = $matches[1];
			$dir_entry_name = trim($matches[2]);

			if ($dir_entry_type && $dir_entry_name) {
				if ($dir_entry_type == DIR_ENTRY_TYPE_FILE) {
					
					$dropbox_file = new DropboxFile();
					$dropbox_file->setFileName($dir_entry_name);
					$dropbox_file->setFullPath($dir);
	
					$link_cmd = DROPBOX_UPLOADER . ' ' . 
						    DROPBOX_UPLOADER_CMD_GET_URL . 
						    ' "' . $dir . '/' . $dir_entry_name . '"';
					$link_cmd_result = `$link_cmd`;
					if (preg_match('/\s(
							https?:\/\/.*	# $1 = URL
							)$/x', $link_cmd_result, $matches)) {
						$link = trim($matches[1]);
					}

					if ($link) {
						$dropbox_file->setPublicUrl($link);
					}

					$this_dir->addSubFile($dropbox_file);

				} else if ($dir_entry_type == DIR_ENTRY_TYPE_DIR) {
					$this_dir->addSubDir(buildDropboxContents($dir . '/' . $dir_entry_name));
				}
			}
		}
	}

	return $this_dir;

}


function getDirConfig() {

	if (! file_exists(CONFIG_FILE_DIRS)) {
		echo '  Couldn\'t find config file: ' . CONFIG_FILE_DIRS . '. Exiting.' . "\n";
		exit;
	}

	$raydar_dirs_txt = file_get_contents(CONFIG_FILE_DIRS);
	$raydar_dirs = array();
	foreach (explode("\n", $raydar_dirs_txt) as $dir_config) {

		// skip comment lines
		if (preg_match('/^\s*#/', $dir_config)) {
			continue;
		}
	
		$dir_option_recurse = true;
		$dir = null;

		// check for recursive options - line starting with '= ' or '> '
		if (preg_match('/^=\s/', $dir_config)) {
			$dir_option_recurse = false;
			$dir = preg_replace('/^=\s+/', '', $dir_config);
		} else if (preg_match('/^>\s/', $dir_config)) {
			// use default option of recursing
			$dir = preg_replace('/^>\s+/', '', $dir_config);
		} else {
			// use whole line as directory
			$dir = trim($dir_config);
		}
		
		if ($dir && ! array_key_exists($dir, $raydar_dirs)) {
			$raydar_dirs[$dir] = $dir_option_recurse;
		}
	
	}
	return $raydar_dirs;
}	

function saveKnownFiles($updates) {

	$fh = fopen(CONFIG_FILE_KNOWN_FILES, 'w');
	if ($fh) {
		fwrite($fh, json_encode($updates));
		fclose($fh);
	}

}

function getKnownFiles() {

	$known_files = array();

	if (file_exists(CONFIG_FILE_KNOWN_FILES)) {
		$contents = file_get_contents(CONFIG_FILE_KNOWN_FILES);
		if ($contents) {
			$known_files = json_decode($contents);
		}
	}

	return $known_files;

}



