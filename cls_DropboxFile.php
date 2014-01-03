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


	function toHTML() {

		$html = '';

		$html .= '<li><a href="' . $this->getPublicUrl() . '" target="_blank">' .
			  $this->getFileName() . 
			  '</a></li>';

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
			'file_name'  => $this->getFileName(),
			'public_url' => $this->getPublicUrl(),
		);
		return json_encode($str_base);
	}

	public static function fromJson($json) {
		$str = json_decode($json);
		if (! $str) {
			return null;
		}

		$file_obj = new DropboxFile();
		if (@$str['full_path']) {
			$file_obj->setFullPath($str['full_path']);
		}
		if (@$str['parent_dir']) {
			$file_obj->setParentDir($str['parent_dir']);
		}
		if (@$str['file_name']) {
			$file_obj->setFileName($str['file_name']);
		}
		if (@$str['public_url']) {
			$file_obj->setPublicUrl($str['public_url']);
		}

		return $file_obj;
	}

}

