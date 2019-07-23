<?php namespace pineapple;

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
        $path = '/pineapple/modules/SSIDManager/SSID_Files/';
        if (!file_exists($path)) {
            exec("mkdir ".$path);
        }
        return '/pineapple/modules/SSIDManager/SSID_Files/';
    }

    private function getContents()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/SSIDManager/module.info"));

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
        exec("rm -rf " . $this->SSIDDirectoryPath() ."'" . $this->request->file . "'");
        $this->response = array("success" => true);
    }

    private function loadSSIDFile()
    {
        $filename = $this->SSIDDirectoryPath() . $this->request->file;
        $fileContent = file_get_contents($filename);
        $this->response = array("success" => true,"content"=>$fileContent,"fileName"=>$filename);
    }
}
