<?php

define('CONFIG_DIR', '/home/pi/.raydar/');

function loadConfig($file, $mandatory = false) {

	$file = CONFIG_DIR . $file;

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

