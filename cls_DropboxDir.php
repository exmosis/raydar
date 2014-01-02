<?php

class DropboxDir {

	var $full_path = '';
	var $parent_dir = '';
	var $dir_name = '';
	var $public_url = '';

	var $subfiles = array();
	var $subdirs = array();

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

	function setDirName($dir_name) {
		$this->dir_name = $dir_name;
	}

	function getDirName() {
		return $this->dir_name;
	}

	function setPublicUrl($public_url) {
		$this->public_url = $public_url;
	}

	function getPublicUrl() {
		return $this->public_url;
	}

	function addSubFile($file_obj) {
		$this->subfiles[] = $file_obj;
	}

	function getSubFiles() {
		return $this->subfiles;
	}

	function addSubDir($dir_obj) {
		$this->subdirs[] = $dir_obj;
	}

	function getSubDirs() {
		return $this->subdirs;
	}

	function toHTML() {

		$html = '';
		$html .= '<li>';
		$html .= '<strong>' . $this->getDirName() . '</strong>';

		$html .= DropboxFile::startListToHTML();
		foreach ($this->getSubFiles() as $file_obj) {
			$html .= $file_obj->toHTML();
		}
		$html .= DropboxFile::endListToHTML();

		if (count($this->getSubDirs()) > 0) {
			$html .= '<ul>';
			foreach ($this->getSubDirs() as $dir_obj) {
				$html .= $dir_obj->toHTML();
			}
			$html .= '</ul>';
		}

		return $html;

	}

	public static function startListToHTML() {
		$html = '';
		$html .= '<ul>';
		return $html;
	}

	public static function endListToHTML() {
		$html = '';
		$html .= '</ul>';
		return $html;
	}

}

