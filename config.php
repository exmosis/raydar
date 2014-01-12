<?php

if (! defined('MAX_LOG_LEVEL')) {
	define('MAX_LOG_LEVEL', 1); // 0 = message, 1 = debug
}

if (! defined('CONFIG_DIR')) {
	define('CONFIG_DIR', '/home/pi/.raydar');
}

// TODO: Work out user's home directory automatically
define('CONFIG_FILE_DIRS', CONFIG_DIR . '/dirs');
define('CONFIG_FILE_KNOWN_FILES', CONFIG_DIR . '/.known_files.db');
define('CONFIG_FILE_SMTP', 'smtp');

// CONSTANT NAMES OF CONFIG FILE SECTIONS
define('CONFIG_SECTION_DIRS_SCAN', 'scan_dirs');
define('CONFIG_SECTION_DIRS_IGNORE', 'ignore_dirs_match');
define('CONFIG_SECTION_FILES_IGNORE', 'ignore_files_match');

function loadConfig($file, $mandatory = false) {
	$file = CONFIG_DIR . '/' . $file;

	if (! file_exists($file)) {
		echo "Config file not found: " . $file . "\n";
		if ($mandatory) {
			echo "Exiting.\n";
			exit;
		}
		return null;
	}

	$file_contents = file_get_contents($file);

	if (! $file_contents) {
		if ($mandatory) {
			echo "Exiting.\n";
			exit;
		}
		return null;
	}

	$file_lines = explode("\n", $file_contents);

	$returns = array();

	foreach ($file_lines as $line) {
		if (preg_match('/^
				  ([^=]+)	# $1 = before equals
				  =
				  (.*)		# $2 = after first equals
				$/x', $line, $matches)) {
		
			$key = trim($matches[1]);
			$value = trim($matches[2]);
			
			// Check if key already defined
			if (defined(strtoupper($key))) {
				echo 'Config key \'' . $key . '\' already defined. SKipping.' . "\n";
			} else {
				// Otherwise define it
				echo 'Setting ' . strtoupper($key) . "\n";
				define(strtoupper($key), $value);

				// And add it to return values
				$returns[$key] = $value;
			}
		}
	}

	return $returns;

}

