<?php namespace pineapple;

define('__MODULE_LOCATION__', "/pineapple/modules/SSIDManager/");
define('__SSID_FILES__', __MODULE_LOCATION__ . "SSID_Files/");
define('__MODULE_INFO__', __MODULE_LOCATION__ . "module.info");

/* The class name must be the name of your module, without spaces. */
/* It must also extend the "Module" class. This gives your module access to API functions */
class SSIDManager extends Module
{

    public function route()
    {
        switch ($this->request->action) {
            case 'getContents':
                $this->getContents();
                break;

            case 'getSSIDFilesList':
                $this->getSSIDFilesList();
                break;

            case 'deleteSSIDFile':
                $this->deleteSSIDFile();
                break;

            case 'archivePool':
                $this->saveSSIDFile();
                break;

            case 'getSSIDFile':
                $this->loadSSIDFile();
                break;

            case 'downloadSSIDFile':
                $this->downloadSSIDFile();
                break;
        }
    }

    private function SSIDDirectoryPath()
    {
        if (!file_exists(__SSID_FILES__)) {
            mkdir(__SSID_FILES__, 0755, true);
        }
        return __SSID_FILES__;
    }

    private function getContents()
    {
        $moduleInfo = @json_decode(file_get_contents(__MODULE_INFO__));

        $this->response = array("success" => true,
                    "version" => "version " . $moduleInfo->version,
                    "content" => "");
    }


    private function getSSIDFilesList()
    {
        $SSIDFilesPath = $this->SSIDDirectoryPath();
        $files_list =  glob($SSIDFilesPath . '*')  ;

        for ($i=0; $i<count($files_list); $i++) {
            $files_list[$i] = basename($files_list[$i]);
        }
        $this->response = array("success"=>true, "filesList"=>$files_list);
    }

    private function saveSSIDFile()
    {
        $filename = $this->SSIDDirectoryPath().$this->request->storeFileName;
        file_put_contents($filename, $this->request->ssidPool);
        $this->response = array("success" => true);
    }

    private function downloadSSIDFile()
    {
        $filename = $this->SSIDDirectoryPath().$this->request->file;
        $this->response = array("download" => $this->downloadFile($filename));
    }

    private function deleteSSIDFile()
    {
        unlink($this->SSIDDirectoryPath() . $this->request->file);
        $this->response = array("success" => true);
    }

    private function loadSSIDFile()
    {
        $filename = $this->SSIDDirectoryPath() . $this->request->file;
        $fileContent = file_get_contents($filename);
        $this->response = array("success" => true,"content"=>$fileContent,"fileName"=>$filename);
    }
}
