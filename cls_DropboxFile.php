<?php

class DropboxFile {

	var $full_path = '';
	var $parent_dir = '';
	var $file_name = '';
	var $public_url = '';

	function setFullPath($path) {
		$this->full_path = $path;
	}

	function getFullPath() {
		return $this->full_path;
	}

	function setParentDir($parent_dir) {
		$this->parent_dir = $parent_dir;
	}

	function getParentDir() {
		return $this->parent_dir;
	}

	function setFileName($file_name) {
		$this->file_name = $file_name;
	}

	function getFileName() {
		return $this->file_name;
	}

	function setPublicUrl($public_url) {
		$this->public_url = $public_url;
	}

	function getPublicUrl() {
		return $this->public_url;
	}

}

