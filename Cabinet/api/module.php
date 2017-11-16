<?php namespace pineapple;

class Cabinet extends Module
{

	public function route()
	{
		switch($this->request->action) {
			case 'getDirectoryContents':
				$this->getDirectoryContents();
				break;

			case 'getParentDirectory':
				$this->getParentDirectory();
				break;

			case 'deleteFile':
				$this->deleteFile();
				break;

			case 'editFile':
				$this->editFile();
				break;

			case 'getFileContents':
				$this->getFileContents();
				break;

			case 'createFolder':
				$this->createFolder();
				break;
		}
	}

	protected function getDirectoryContents()
	{
		$dir = $this->request->directory;

		$success = false;
		$contents = array();
		if (file_exists($dir)) {
			foreach (preg_grep('/^([^.])/', scandir($dir)) as $file) {
				$obj = array("name" => $file, "directory" => is_dir($dir . '/' . $file),
				"path" => realpath($dir . '/' . $file), 
				"permissions" => substr(sprintf('%o', fileperms($dir . '/' . $file)), -4),
				"size" => filesize($dir . '/' . $file));
				array_push($contents, $obj);
			}
			$success = true;
		}

		$this->response = array("success" => $success, "contents" => $contents, "directory" => $dir);

	}

	protected function getParentDirectory()
	{
		$dir = $this->request->directory;
		$success = false;
		$parent = "";

		if (file_exists($dir)) {
			$parent = dirname($dir);
			$success = true;
		}

		$this->response = array("success" => $success, "parent" => $parent);

	}

	protected function deleteFile()
	{
		$f = $this->request->file;
		$success = false;

		if (file_exists($f)) {
			if (!is_dir($f)) {
				unlink($f);
			} else {
				foreach (preg_grep('/^([^.])/', scandir($f)) as $file) {
					unlink($f . '/' . $file);
				}
				rmdir($f);
			}
		}

		if (!file_exists($f)) {
			$success = true;
		}

		$this->response = array("success" => $success);

	}

	protected function editFile()
	{
		$f = $this->request->file;
		$data = $this->request->contents;
		$success = false;

		file_put_contents($f, $data);
		if (file_exists($f)) {
			$success = true;
		}

		$this->response = array("success" => $success);
	}

	protected function getFileContents()
	{
		$f = $this->request->file;
		$success = false;
		$content = "";

		if (file_exists($f)) {
			$success = true;
			$content = file_get_contents($f);
		}

		$this->response = array("success" => $success, "content" => $content);

	}

	protected function createFolder()
	{
		$dir = $this->request->directory;
		$name = $this->request->name;
		$success = false;

		if (!is_dir($dir . '/' . $name)) {
			$success = true;
			mkdir($dir . "/" . $name);
		}

		$this->response = array("success" => $success);
	}

}