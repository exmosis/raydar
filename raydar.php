<?php

define('DROPBOX_UPLOADER', './bash/Dropbox-Uploader/dropbox_uploader.sh');
define('DROPBOX_UPLOADER_CMD_LIST', 'list');

define('CONFIG_FILE_DIRS', '/home/pi/.raydar/dirs');
define('CONFIG_FILE_KNOWN_FILES', '/home/pi/.raydar/.known_files.db');

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
	}

	saveKnownFiles($known_files);

}


function getDropboxUpdates($dirs) {

	$updates = array();
	
	$old_known_files = getKnownFiles();
	$new_known_files = array();

	foreach ($dirs as $dir => $recurse) {
		$cmd = DROPBOX_UPLOADER . ' ' . DROPBOX_UPLOADER_CMD_LIST . ' ' . $dir;
		echo `$cmd`;
	}

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



