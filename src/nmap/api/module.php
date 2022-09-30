<?php namespace pineapple;

//putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');
//putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

class nmap extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'refreshInfo':
                $this->refreshInfo();
                break;
            case 'refreshOutput':
                $this->refreshOutput();
                break;
            case 'refreshStatus':
                $this->refreshStatus();
                break;
            case 'togglenmap':
                $this->togglenmap();
                break;
            case 'scanStatus':
                $this->scanStatus();
                break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
                break;
            case 'refreshHistory':
                $this->refreshHistory();
                break;
            case 'viewHistory':
                $this->viewHistory();
                break;
            case 'deleteHistory':
                $this->deleteHistory();
                break;
            case 'downloadHistory':
                $this->downloadHistory();
                break;
        }
    }

    protected function checkDep($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("nmap.module.installed")));
    }

//    protected function getDevice()
//    {
//        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
//    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/nmap/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        error_log("handleDependencies()");
        if (!$this->checkDependency("nmap")) {
            $this->execBackground("/pineapple/modules/nmap/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/nmap/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/nmap.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function scanStatus()
    {
        if (!$this->checkRunning("nmap")) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function togglenmap()
    {
        if (!$this->checkRunning("nmap")) {
            error_log("nmap not running");
            $full_cmd = $this->request->command . " -oN /tmp/nmap.scan 2>&1";
            shell_exec("echo -e \"{$full_cmd}\" > /tmp/nmap.run");

            error_log("calling run script");
            $this->execBackground("/pineapple/modules/nmap/scripts/nmap.sh start");
        } else {
            $this->execBackground("/pineapple/modules/nmap/scripts/nmap.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/nmap.progress')) {
            if (!$this->checkDependency("nmap")) {
                $installed = false;
                $install = "Not installed";
                $installLabel = "danger";
                $processing = false;

                $status = "Start";
                $statusLabel = "success";
            } else {
                $installed = true;
                $install = "Installed";
                $installLabel = "success";
                $processing = false;

                if ($this->checkRunning("nmap")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }
            }
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $status = "Start";
            $statusLabel = "success";
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        // 2143000 is the installed size of nmap.
        $internalAvailable = (disk_free_space("/") - 64000) > 2143000;

        $this->response = array("device" => $device, "internalAvailable" => $internalAvailable, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if ($this->checkDependency("nmap")) {
            if ($this->checkRunning("nmap") && file_exists("/tmp/nmap.scan")) {
                $output = file_get_contents("/tmp/nmap.scan");
                if (!empty($output)) {
                    $this->response = $output;
                } else {
                    $this->response = "Empty log...";
                }
            } else {
                $this->response = "nmap is not running...";
            }
        } else {
            $this->response = "nmap is not installed...";
        }
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/nmap/scan/*"));

            echo '[';
            for ($i=0;$i<count($log_list);$i++) {
                $info = explode("_", basename($log_list[$i]));
                $entryDate = gmdate('Y-m-d H-i-s', $info[1]);
                $entryName = basename($log_list[$i]);

                echo json_encode(array($entryDate, $entryName));

                if ($i!=count($log_list)-1) {
                    echo ',';
                }
            }
            echo ']';
        };
    }

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/nmap/scan/".$this->request->file));
        exec("cat /pineapple/modules/nmap/scan/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty scan...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        exec("rm -rf /pineapple/modules/nmap/scan/".$this->request->file);
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/nmap/scan/".$this->request->file));
    }
}
