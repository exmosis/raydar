<?php

class DropboxDir {

	var $full_path = '';
	var $parent_dir = '';
	var $dir_name = '';
	var $public_url = '';

	var $subfiles = array();
	var $subdirs = array();

	public function __construct() {
		$this->reset();
	}

	public function reset() {
		$this->full_path = '';
		$this->parent_dir = '';
		$this->dir_name = '';
		$this->public_url = '';

		$this->subfiles = array();
		$this->subdirs = array();
	}

	public function setFullPath($path) {
		$this->full_path = $path;
	}

	public function getFullPath() {
		return $this->full_path;
	}

	public function setParentDir($parent_dir) {
		$this->parent_dir = $parent_dir;
	}

	public function getParentDir() {
		return $this->parent_dir;
	}

	public function setDirName($dir_name) {
		$this->dir_name = $dir_name;
	}

	public function getDirName() {
		return $this->dir_name;
	}

	public function setPublicUrl($public_url) {
		$this->public_url = $public_url;
	}

	public function getPublicUrl() {
		return $this->public_url;
	}

	public function addSubFile($file_obj) {
		$this->subfiles[] = $file_obj;
	}

	public function getSubFiles() {
		return $this->subfiles;
	}

	public function addSubDir($dir_obj) {
		$this->subdirs[] = $dir_obj;
	}

	public function getSubDirs() {
		return $this->subdirs;
	}

	public function toHTML() {

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

	public function toJson() {
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
		$str = json_decode($json, true);
		if (! $str) {
			return null;
		}

		$dir_obj = new DropboxDir();
		if ($str['full_path']) {
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

	public function isEmpty() {
		$empty = true;
		if (count($this->getSubFiles()) > 0) {
			$empty = false;
		} else {
			foreach ($this->getSubDirs() as $subdir) {
				$empty = ($empty && $subdir->isEmpty());
			}
		}
		return $empty;
	}

}

