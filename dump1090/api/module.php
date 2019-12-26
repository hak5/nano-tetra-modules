<?php namespace pineapple;

class dump1090 extends Module
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
            case 'toggledump1090':
                $this->toggledump1090();
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
            case 'deleteHistory':
                $this->deleteHistory();
                break;
            case 'downloadHistory':
                $this->downloadHistory();
                break;
            case 'viewHistory':
                $this->viewHistory();
                break;
            case 'getSettings':
                $this->getSettings();
                break;
            case 'setSettings':
                $this->setSettings();
                break;
            case 'refreshList':
                $this->refreshList();
                break;
        }
    }

    protected function checkDep($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("dump1090.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/dump1090/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDep("dump1090")) {
            $this->execBackground("/pineapple/modules/dump1090/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/dump1090/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/dump1090.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggledump1090()
    {
        if (!$this->checkRunning("dump1090")) {
            $this->execBackground("/pineapple/modules/dump1090/scripts/dump1090.sh start");
        } else {
            $this->execBackground("/pineapple/modules/dump1090/scripts/dump1090.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/dump1090.progress')) {
            if (!$this->checkDependency("dump1090")) {
                $installed = false;
                $install = "Not installed";
                $installLabel = "danger";
                $processing = false;

                $status = "Start";
                $statusLabel = "success";
                $running = false;
            } else {
                $installed = true;
                $install = "Installed";
                $installLabel = "success";
                $processing = false;

                if ($this->checkRunning("dump1090")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                    $running = true;
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                    $running = false;
                }
            }
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $status = "Start";
            $statusLabel = "success";
            $running = false;
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "processing" => $processing, "running" => $running);
    }

    private function refreshOutput()
    {
        if ($this->checkDependency("dump1090")) {
            if (file_exists("/tmp/dump1090_capture.log")) {
                $output = file_get_contents("/tmp/dump1090_capture.log");
                if (!empty($output)) {
                    $this->response = $output;
                } else {
                    $this->response = "dump1090 is running...";
                }
            } else {
                $this->response = "dump1090 is not running...";
            }
        } else {
            $this->response = "dump1090 is not installed...";
        }
    }

    private function clearOutput()
    {
        exec("rm -rf /tmp/dump1090_capture.log");
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/dump1090/log/*.log"));

            echo '[';
            for ($i=0;$i<count($log_list);$i++) {
                $info = explode("_", basename($log_list[$i]));
                $entryDate = gmdate('Y-m-d H-i-s', $info[1]);
                $entryName = basename($log_list[$i], ".log");

                if (file_exists("/pineapple/modules/dump1090/log/".$entryName.".csv")) {
                    echo json_encode(array($entryDate, $entryName.".log", $entryName.".csv"));
                } else {
                    echo json_encode(array($entryDate, $entryName.".log", ''));
                }

                if ($i!=count($log_list)-1) {
                    echo ',';
                }
            }
            echo ']';
        };
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/dump1090/log/".$this->request->file));
    }

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/dump1090/log/".$this->request->file));
        exec("strings /pineapple/modules/dump1090/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        $file = basename($this->request->file, ".log");
        exec("rm -rf /pineapple/modules/dump1090/log/".$file.".*");
    }

    private function getSettings()
    {
        $settings = array(
                    'csv' => $this->uciGet("dump1090.settings.csv"),
                    'gain' => $this->uciGet("dump1090.settings.gain"),
                    'frequency' => $this->uciGet("dump1090.settings.frequency"),
                    'metrics' => $this->uciGet("dump1090.settings.metrics"),
                    'agc' => $this->uciGet("dump1090.settings.agc"),
                    'aggressive' => $this->uciGet("dump1090.settings.aggressive")
                    );
        $this->response = array('settings' => $settings);
    }

    private function setSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("dump1090.settings.gain", $settings->gain);
        $this->uciSet("dump1090.settings.frequency", $settings->frequency);
        if ($settings->csv) {
            $this->uciSet("dump1090.settings.csv", 1);
        } else {
            $this->uciSet("dump1090.settings.csv", 0);
        }
        if ($settings->metrics) {
            $this->uciSet("dump1090.settings.metrics", 1);
        } else {
            $this->uciSet("dump1090.settings.metrics", 0);
        }
        if ($settings->agc) {
            $this->uciSet("dump1090.settings.agc", 1);
        } else {
            $this->uciSet("dump1090.settings.agc", 0);
        }
        if ($settings->aggressive) {
            $this->uciSet("dump1090.settings.aggressive", 1);
        } else {
            $this->uciSet("dump1090.settings.aggressive", 0);
        }
    }

    private function refreshList()
    {
        $this->streamFunction = function () {
            echo file_get_contents("http://127.0.0.1:9090/data.json");
        };
    }
}
