<?php

class DropboxFile {

	var $full_path = '';
	var $parent_dir = '';
	var $file_name = '';
	var $public_url = '';

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

	public function setFileName($file_name) {
		$this->file_name = $file_name;
	}

	public function getFileName() {
		return $this->file_name;
	}

	public function setPublicUrl($public_url) {
		$this->public_url = $public_url;
	}

	public function getPublicUrl() {
		return $this->public_url;
	}


	public function toHTML() {

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

	public function toJson() {
		$str_base = array(
			'full_path'  => $this->getFullPath(),
			'parent_dir' => $this->getParentDir(),
			'file_name'  => $this->getFileName(),
			'public_url' => $this->getPublicUrl(),
		);
		return json_encode($str_base);
	}

	public static function fromJson($json) {
		$str = json_decode($jsoni, true);
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

