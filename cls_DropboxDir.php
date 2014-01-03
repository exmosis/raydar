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

	function toJson() {
		$str_base = array(
			'full_path'  => $this->getFullPath(),
			'parent_dir' => $this->getParentDir(),
			'dir_name'  => $this->getDirName(),
			'public_url' => $this->getPublicUrl(),
			'subdirs_json' => array(),
			'subfiles_json' => array(),
		);
		// Add directories
		if (count($this->getSubDirs()) > 0) {
			foreach ($this->getSubDirs() as $subdir) {
				$str_base['subdirs_json'][] = $subdir->toJson();
			}
		}
		// Addfiles
		if (count($this->getSubFiles()) > 0) {
			foreach ($this->getSubFiles() as $subfile) {
				$str_base['subfiles_json'][] = $subfile->toJson();
			}
		}

		return json_encode($str_base);
	}

	public static function fromJson($json) {
		$str = json_decode($json);
		if (! $str) {
			return null;
		}

		$dir_obj = new DropboxDir();
		if (@$str['full_path']) {
			$dir_obj->setFullPath($str['full_path']);
		}
		if (@$str['parent_dir']) {
			$dir_obj->setParentDir($str['parent_dir']);
		}
		if (@$str['dir_name']) {
			$dir_obj->setDirName($str['dir_name']);
		}
		if (@$str['public_url']) {
			$dir_obj->setPublicUrl($str['public_url']);
		}

		if (@$str['subfiles_json']) {
			foreach ($str['subfiles_json'] as $subfile_json) {
				$subfile = DropboxFile::fromJson($subfile_json);
				if ($subfile != null) {
					$dir_obj->addSubFile($subfile);
				}
			}
		}

		if (@$str['subdirs_json']) {
			foreach ($str['subdirs_json'] as $subdir_json) {
				$subdir = DropboxDir::fromJson($subdir_json);
				if ($subdir != null) {
					$dir_obj->addSubDir($subdir);
				}
			}
		}

		return $dir_obj;
	}

}

