<?php namespace pineapple;



class LogManager extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'refreshInfo':
                $this->refreshInfo();
                break;
            case 'refreshFilesList':
                $this->refreshFilesList();
                break;
            case 'downloadFilesList':
                $this->downloadFilesList();
                break;
            case 'deleteFilesList':
                $this->deleteFilesList();
                break;
            case 'viewModuleFile':
                $this->viewModuleFile();
                break;
            case 'deleteModuleFile':
                $this->deleteModuleFile();
                break;
            case 'downloadModuleFile':
                $this->downloadModuleFile();
                break;
        }
    }

    private function dataSize($path)
    {
        $blah = exec("/usr/bin/du -sch $path | tail -1 | awk {'print $1'}");
        return $blah;
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/LogManager/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function downloadFilesList()
    {
        $files = $this->request->files;

        exec("mkdir /tmp/dl/");
        foreach ($files as $file) {
            exec("cp ".$file." /tmp/dl/");
        }
        exec("cd /tmp/dl/ && tar -czf /tmp/files.tar.gz *");
        exec("rm -rf /tmp/dl/");

        $this->response = array("download" => $this->downloadFile("/tmp/files.tar.gz"));
    }

    private function deleteFilesList()
    {
        $files = $this->request->files;

        foreach ($files as $file) {
            exec("rm -rf ".$file);
        }
    }

    private function refreshFilesList()
    {
        $modules = array();
        foreach (glob('/pineapple/modules/*/log/*') as $file) {
            $module = array();
            $module['file'] = basename($file);
            $module['path'] = $file;
            $module['size'] = $this->dataSize($file);
            $module['title'] = explode("/", dirname($file))[3];
            $module['date'] = gmdate("F d Y H:i:s", filemtime($file));
            $module['timestamp'] = filemtime($file);
            $modules[] = $module;
        }

        foreach (glob('/pineapple/modules/*/dump/*') as $file) {
            $module = array();
            $module['file'] = basename($file);
            $module['path'] = $file;
            $module['size'] = $this->dataSize($file);
            $module['title'] = explode("/", dirname($file))[3];
            $module['date'] = gmdate("F d Y H:i:s", filemtime($file));
            $module['timestamp'] = filemtime($file);
            $modules[] = $module;
        }

        foreach (glob('/pineapple/modules/*/scan/*') as $file) {
            $module = array();
            $module['file'] = basename($file);
            $module['path'] = $file;
            $module['size'] = $this->dataSize($file);
            $module['title'] = explode("/", dirname($file))[3];
            $module['date'] = gmdate("F d Y H:i:s", filemtime($file));
            $module['timestamp'] = filemtime($file);
            $modules[] = $module;
        }

        foreach (glob('/pineapple/modules/*/capture/*') as $file) {
            $module = array();
            $module['file'] = basename($file);
            $module['path'] = $file;
            $module['size'] = $this->dataSize($file);
            $module['title'] = explode("/", dirname($file))[3];
            $module['date'] = gmdate("F d Y H:i:s", filemtime($file));
            $module['timestamp'] = filemtime($file);
            $modules[] = $module;
        }

        usort($modules, create_function('$a, $b', 'if($a["timestamp"] == $b["timestamp"]) return 0; return ($a["timestamp"] > $b["timestamp"]) ? -1 : 1;'));

        $this->response = array("files" => $modules);
    }

    private function viewModuleFile()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime($this->request->file));
        exec("strings ".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date, "name" => basename($this->request->file));
        } else {
            $this->response = array("output" => "Empty file...", "date" => $log_date, "name" => basename($this->request->file));
        }
    }

    private function deleteModuleFile()
    {
        exec("rm -rf ".$this->request->file);
    }

    private function downloadModuleFile()
    {
        $this->response = array("download" => $this->downloadFile($this->request->file));
    }
}
