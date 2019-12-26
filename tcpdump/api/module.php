<?php namespace pineapple;



class tcpdump extends Module
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
            case 'clearOutput':
                $this->clearOutput();
                break;
            case 'refreshStatus':
                $this->refreshStatus();
                break;
            case 'toggletcpdump':
                $this->toggletcpdump();
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
            case 'getInterfaces':
                $this->getInterfaces();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("tcpdump.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/tcpdump/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("tcpdump")) {
            $this->execBackground("/pineapple/modules/tcpdump/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/tcpdump/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/tcpdump.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggletcpdump()
    {
        if (!$this->checkRunning("tcpdump")) {
            $full_cmd = $this->request->command . " -w /pineapple/modules/tcpdump/dump/dump_".time().".pcap 2> /tmp/tcpdump_capture.log";
            shell_exec("echo -e \"{$full_cmd}\" > /tmp/tcpdump.run");

            $this->execBackground("/pineapple/modules/tcpdump/scripts/tcpdump.sh start");
        } else {
            $this->execBackground("/pineapple/modules/tcpdump/scripts/tcpdump.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/tcpdump.progress')) {
            if (!$this->checkDeps("tcpdump")) {
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

                if ($this->checkRunning("tcpdump")) {
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

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if ($this->checkDeps("tcpdump")) {
            if (file_exists("/tmp/tcpdump_capture.log")) {
                $output = file_get_contents("/tmp/tcpdump_capture.log");
                if (!empty($output)) {
                    $this->response = $output;
                } else {
                    $this->response = "tcpdump is running...";
                }
            } else {
                $this->response = "tcpdump is not running...";
            }
        } else {
            $this->response = "tcpdump is not installed...";
        }
    }

    private function clearOutput()
    {
        exec("rm -rf /tmp/tcpdump_capture.log");
    }

    private function getInterfaces()
    {
        $this->response = array();
        exec("cat /proc/net/dev | tail -n +3 | cut -f1 -d: | sed 's/ //g'", $interfaceArray);

        foreach ($interfaceArray as $interface) {
            array_push($this->response, $interface);
        }
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/tcpdump/dump/*.pcap"));

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

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/tcpdump/dump/".$this->request->file));
    }

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/tcpdump/dump/".$this->request->file));
        exec("strings /pineapple/modules/tcpdump/dump/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty dump...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        exec("rm -rf /pineapple/modules/tcpdump/dump/".$this->request->file);
    }
}
