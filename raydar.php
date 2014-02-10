<?php

if (file_exists('raydar-config.php')) {
	include('raydar-config.php');
}

define('CONFIG_PARSE_MODE_SCAN_DIRS', 1);
define('CONFIG_PARSE_MODE_IGNORE_DIRS', 2);
define('CONFIG_PARSE_MODE_IGNORE_FILES', 3);

require_once('config.php');
require_once('cls_DropboxFile.php');
require_once('cls_DropboxDir.php');
require_once('includes/PHPMailer/PHPMailerAutoload.php');

define('DROPBOX_UPLOADER', './bash/Dropbox-Uploader/dropbox_uploader.sh');
define('DROPBOX_UPLOADER_CMD_LIST', 'list');
define('DROPBOX_UPLOADER_CMD_GET_URL', 'share');

// Constants for file types
define('DIR_ENTRY_TYPE_FILE', 'F');
define('DIR_ENTRY_TYPE_DIR', 'D');

run();
exit;

function run() {

	noticeString('Raydar starting up...', 0);

	// Get config entries for SMTP
	loadConfig(CONFIG_FILE_SMTP, true);

	list($raydar_dirs, $ignore_dirs, $ignore_files) = getDirConfig();

	noticeObject('raydar_dirs', $raydar_dirs, 1);
	noticeObject('ignore_dirs', $ignore_dirs, 1);
	noticeObject('ignore_files', $ignore_files, 1);

	if (! $raydar_dirs) {
		noticeString('  No directories configured. Exiting.', 0);
		exit;
	}

	$old_known_files = getKnownFiles();
	noticeObject('old_known_files', $old_known_files, 1);

	list($known_files, $updates) = getDropboxUpdates($raydar_dirs, $old_known_files, $ignore_dirs, $ignore_files);

	noticeObject('known_files', $known_files, 2);
	noticeObject('updates', $updates, 1);

	$empty_updates = true;
	foreach ($updates as $upd) {
		if (! $upd->isEmpty()) {
			$empty_updates = false;
		}
	}

	if ($empty_updates) {
		noticeString('  No updates detected.', 0);
	} else {
		sendUpdatesEmail($updates);
		saveKnownFiles($known_files);
	}

}

function sendUpdatesEmail($updates) {

	noticeString("Sending updates email...", 0);

	$mail = new PHPMailer;

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = SMTP_HOST;  // Specify main and backup server
	$mail->SMTPAuth = SMTP_AUTH;                               // Enable SMTP authentication
	$mail->Username = SMTP_USERNAME;                            // SMTP username
	$mail->Password = SMTP_PASSWORD;                           // SMTP password
	$mail->SMTPSecure = SMTP_SECURE;   

	$mail->From = SMTP_FROM;
	$mail->FromName = SMTP_FROMNAME;
	$mail->addAddress(SMTP_TO);
	$mail->isHTML(true);

	$mail->Subject = fillSubjectTemplate(SMTP_SUBJECT);

	$body = '';
	$body .= DropboxDir::startListToHTML();
	foreach ($updates as $dir_info) {
		$body .= $dir_info->toHTML();
	}
	$body .= DropboxDir::endListToHTML();

	$mail->Body = $body;
	$mail->AltBody = $body;

	$mail->send();
}

/**
 * Helper function to check a string against a bunch of checks. Does a basic match, no regex.
 */
function stringMatchesList($name, $poss_matches) {

	foreach ($poss_matches as $m) {
		if (strpos($name, $m) !== false) {
			return true;
		}
	}

	return false;

}

/**
 * Top-level (non-recursive) function to cycle through directories we want to 
 * check.
 */
function getDropboxUpdates($dirs, $old_known_files = array(), $ignore_dirs = array(), $ignore_files = array()) {

	$dir_entries = array();
	$updates = array();
	
	foreach ($dirs as $dir => $recurse) {

		if (stringMatchesList($dir, $ignore_dirs)) {
			continue; // skip directory
		}

		$old_cache = array();
		foreach ($old_known_files as $cache_dir_info) {
			if ($cache_dir_info->getFullPath() == $dir) {
				$old_cache = $cache_dir_info;
				break;
			}
		}
		$dir_entries[] = buildDropboxContents($dir, $old_cache, $ignore_dirs, $ignore_files);

		$updates[] = calculateUpdatedFiles($old_cache, $dir_entries[count($dir_entries) - 1]);
	}

	noticeObject('dir_entries', $dir_entries, 1);
	noticeObject('updates', $updates, 1);

	return array($dir_entries, $updates);

}

/**
 * Generic function to compare $old_files with $new_files and return only those in latter.
 * Both input params are a DropboxDir object.
 */
function calculateUpdatedFiles($old_files, $new_files) {

	noticeObject('old_files', $old_files, 1);
	noticeObject('new_files', $new_files, 1);

	// Return all new files if no old files passed in
	if (! $old_files) {
		return $new_files;
	}

	// Return nothing if no new files passed in
	if (! $new_files) {
		return null;
	}

	$updates = new DropboxDir();
	$updates->setFullPath($new_files->getFullPath());
	$updates->setDirName($new_files->getDirName());
	$updates->setPublicUrl($new_files->getPublicUrl());

	// compare subdirs
	foreach ($new_files->getSubDirs() as $subdir) {
		$compare_dir = null;
		// Find same dir in old version
		foreach ($old_files->getSubDirs() as $old_subdir) {
			if ($subdir->getFullPath() == $old_subdir->getFullPath()) {
				$compare_dir = $old_subdir;
				break;
			}
		}
		if ($compare_dir != null) {
			// Match found, add changes only
			$updates->addSubDir(calculateUpdatedFiles($compare_dir, $subdir));
		} else {
			// No match, everything is new
			$updates->addSubDir($subdir);
		}
	}

	noticeObject('updates mid way', $updates, 1);

	// compare files
	foreach ($new_files->getSubFiles() as $subfile) {

		noticeString('Checking subfile: ' . $subfile->getFileName(), 1);

		$compare_file = null;
		// Find same file in old version
		if ($old_files) {
			foreach ($old_files->getSubFiles() as $old_subfile) {
				if ($subfile->getFullPath() == $old_subfile->getFullPath() &&
				    $subfile->getFileName() == $old_subfile->getFileName()) {
					// match exists
					$compare_file = $old_subfile; 
					noticeObject('Found cache version', $compare_file, 1);
				}
			}
		}
		if ($compare_file == null) {
			$updates->addSubFile($subfile);
		}
	}

	noticeObject('updates', $updates, 1);

	return $updates;

}

/**
 * Recursive function to get contents of directory from Dropbox and turn into object tree.
 */
function buildDropboxContents($dir, $dir_cache = null, $ignore_dirs = array(), $ignore_files = array()) {

	noticeString("Processing $dir", 0);

	$cmd = DROPBOX_UPLOADER . ' ' . DROPBOX_UPLOADER_CMD_LIST . ' "' . $dir . '"';
	$dir_entries = `$cmd`;
	$dir_entries = explode("\n", $dir_entries);

	$this_dir = new DropboxDir();
	$this_dir->setFullPath($dir);
	$this_dir->setDirName($dir);

	$testing_stop = 0;

	foreach ($dir_entries as $dir_entry) {

		// if ($testing_stop++ > 3) {
		// 	break;
		// }

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

					if (stringMatchesList($dir_entry_name, $ignore_files)) {
						noticeString('Ignoring file: ' . $dir_entry_name, 1);
						continue;
					}
					
					$dropbox_file = new DropboxFile();
					$dropbox_file->setFileName($dir_entry_name);
					$dropbox_file->setFullPath($dir);

					noticeString("Checking file: " . $dir_entry_name, 0);

					$cache_link = null;
					if ($dir_cache != null && count($dir_cache->getSubFiles()) > 0) {
						foreach ($dir_cache->getSubFiles() as $cache_subfile) {
							if ($cache_subfile->getFullPath() == $dir &&
							    $cache_subfile->getFileName() == $dir_entry_name) {
									noticeString("Found cache_link: " . $cache_subfile->getPublicUrl(), 1);
								$cache_link = $cache_subfile->getPublicUrl();
							}
						}
					}
					// Only fetch Public URL if we don't have it cached.
					if ($cache_link != null) {
						noticeString("Found cached link for " . $dir_entry_name, 0);
						$dropbox_file->setPublicUrl($cache_link);
					} else {

						noticeString("Getting URL for " . $dir_entry_name, 0);
						// TODO: Move this into object as "updatePublicUrl()"
	
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
					}

					// Only add if we have a URL
					if ($dropbox_file->getPublicUrl()) {
						$this_dir->addSubFile($dropbox_file);
					}

				} else if ($dir_entry_type == DIR_ENTRY_TYPE_DIR) {

					if (stringMatchesList($dir_entry_name, $ignore_dirs)) {
						noticeString('Ignoring dir: ' . $dir_entry_name, 1);
						continue;
					}

					// TODO: Move this to a findSubDirByName() method in DropboxDir
					$cache_subdir = null;
					if ($dir_cache != null) {
						foreach ($dir_cache->getSubDirs() as $check_subdir) {
							if ($check_subdir->getFullPath() == $dir . '/' . $dir_entry_name) {
								$cache_subdir = $check_subdir;
								break;
							}
						}
					}
					$this_dir->addSubDir(buildDropboxContents($dir . '/' . $dir_entry_name, $cache_subdir));
				}
			}
		}
	}

	noticeObject('this_dir', $this_dir, 1);

	return $this_dir;

}


function getDirConfig() {

	if (! file_exists(CONFIG_FILE_DIRS)) {
		noticeString('  Couldn\'t find config file: ' . CONFIG_FILE_DIRS . '. Exiting.', 0);
		exit;
	}

	$raydar_dirs_txt = file_get_contents(CONFIG_FILE_DIRS);

	$raydar_dirs = array();
	$ignore_dir_patterns = array();
	$ignore_file_patterns = array();

	$config_mode = CONFIG_PARSE_MODE_SCAN_DIRS;
	foreach (explode("\n", $raydar_dirs_txt) as $dir_config) {

		// skip comment lines
		if (preg_match('/^\s*#/', $dir_config)) {
			continue;
		}

		// skip blank lines
		if (preg_match('/^\s*$/', $dir_config)) {
			continue;
		}

		// check if mode is set
		if (preg_match('/^\[([^\]]+)\]$/', trim($dir_config), $matches)) {
			$attempted_config_mode = $matches[1];
			switch ($attempted_config_mode) {
				case CONFIG_SECTION_DIRS_SCAN:
					$config_mode = CONFIG_PARSE_MODE_SCAN_DIRS;
					break;
				case CONFIG_SECTION_DIRS_IGNORE:
					$config_mode = CONFIG_PARSE_MODE_IGNORE_DIRS;
					break;
				case CONFIG_SECTION_FILES_IGNORE:
					$config_mode = CONFIG_PARSE_MODE_IGNORE_FILES;
					break;
				default:
					noticeString('Didn\'t recognise config section ' . $attempted_config_mode . ', exiting.', 0);
					exit;
			}
			continue;
		}


		if ($config_mode == CONFIG_PARSE_MODE_SCAN_DIRS) {	
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

		} else 
		if ($config_mode == CONFIG_PARSE_MODE_IGNORE_DIRS) {
			$dir = trim($dir_config);
			if (! in_array($dir, $ignore_dir_patterns)) {
				$ignore_dir_patterns[] = $dir;
			}
		} else
		if ($config_mode == CONFIG_PARSE_MODE_IGNORE_FILES) {
			$dir = trim($dir_config);
			if (! in_array($dir, $ignore_file_patterns)) {
				$ignore_file_patterns[] = $dir;
			}
		}
	
	}
	return array($raydar_dirs, $ignore_dir_patterns, $ignore_file_patterns);
}	

function saveKnownFiles($known_files) {

	noticeString("Writing known files to disk.", 0);

	$fh = fopen(CONFIG_FILE_KNOWN_FILES, 'w');
	if ($fh) {
		$json_base = array();
		foreach ($known_files as $kf) {
			$json_base[] = $kf->toJson();
		}
		fwrite($fh, implode("\n", $json_base));
		fclose($fh);
	}

}

function getKnownFiles() {

	$known_objs = array();

	if (file_exists(CONFIG_FILE_KNOWN_FILES)) {
		$contents = file_get_contents(CONFIG_FILE_KNOWN_FILES);
		if ($contents) {
			$known_files = explode("\n", $contents);

			if ($known_files) {
				foreach ($known_files as $decode_dir) {
					$known_objs[] = DropboxDir::fromJson($decode_dir);
				}
			} else {
				noticeString("Couldn't decode previous file cache.", 0);
			}
		}
	} else {
		noticeString("Didn't detect previous file cache.", 0);
	}

	return $known_objs;

}

// Vars map to functions. Vars in template should start with $
function fillSubjectTemplate($subject) {
	
	$vars = array(
		'DATE' => 'fillSubjectDate',
	);

	foreach ($vars as $var => $func) {

		$var = '\[\[' . $var . '\]\]';

		if (preg_match('/' . $var . '/', $subject) &&
		    function_exists($func)) {

			$val = call_user_func($func);
			$subject = preg_replace('/' . $var . '/', $val, $subject);

		}

	}

	return $subject;
}

function fillSubjectDate() {
	return date('j M Y');
}

function noticeObject($obj_name, $obj, $level = 1) {
	if ($level > MAX_LOG_LEVEL) {
		return;
	}
	echo "\n" . $obj_name . ':' . "\n";
	print_r($obj);
}

function noticeString($msg, $level = 0) {
	if ($level > MAX_LOG_LEVEL) {
		return;
	}
	echo $msg . "\n";
}

