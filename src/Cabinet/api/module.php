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

            case 'download':
                $this->response = $this->download($this->request->filePath);
                break;

		}
	}

	private function getDirectoryContents()
	{
		$dir = $this->request->directory;

		$success = false;
		$contents = array();
		if (file_exists($dir)) {
			foreach (preg_grep('/^([^.])/', scandir($dir)) as $file) {
				$obj = array("name" => $file, "directory" => is_dir($dir . '/' . $file),
				"path" => realpath($dir . '/' . $file), 
				"permissions" => substr(sprintf('%o', fileperms($dir . '/' . $file)), -4),
				"size" => $this->readableFileSize($dir . '/' . $file));
				array_push($contents, $obj);
			}
			$success = true;
		}

		$this->response = array("success" => $success, "contents" => $contents, "directory" => $dir);

	}

	private function getParentDirectory()
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

	private function deleteFile()
	{
		$f = $this->request->file;
		$success = false;

		if (file_exists($f)) {
			exec("rm -rf " . escapeshellarg($f));
		}

		if (!file_exists($f)) {
			$success = true;
		}

		$this->response = array("success" => $success);

	}

	private function editFile()
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

	private function getFileContents()
	{
		$f = $this->request->file;
		$success = false;
		$content = "";
		$size = "0 Bytes";

		if (file_exists($f)) {
			$success = true;
			$content = file_get_contents($f);
			$size = $this->readableFileSize($f);
		}

		$this->response = array("success" => $success, "content" => $content, "size" => $size);

	}

	private function createFolder()
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

    /**
     * Download a file
     * @param: The path to the file to download
     * @return array : array
     */
    private function download($filePath)
    {
        if (file_exists($filePath)) {
            return array("success" => true, "message" => null, "download" => $this->downloadFile($filePath));
        } else {
            return array("success" => false, "message" => "File does not exist", "download" => null);
        }
    }

    /**
     * Get the size of a file and add a unit to the end of it.
     * @param $file: The file to get size of
     * @return string: File size plus unit. Exp: 3.14M
     */
    private function readableFileSize($file) {
        $size = filesize($file);

        if ($size == null)
            return "0 Bytes";

        if ($size < 1024) {
            return "{$size} Bytes";
        } else if ($size >= 1024 && $size < 1024*1024) {
            return round($size / 1024, 2) . "K";
        } else if ($size >= 1024*1024) {
            return round($size / (1024*1024), 2) . "M";
        }
        return "{$size} Bytes";
    }

}