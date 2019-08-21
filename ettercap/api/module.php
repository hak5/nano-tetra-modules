<?php namespace pineapple;

putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');
putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

class ettercap extends Module
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
            case 'toggleettercap':
                $this->toggleettercap();
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
            case 'getFilters':
                $this->getFilters();
                break;
            case 'showFilter':
                $this->showFilter();
                break;
            case 'deleteFilter':
                $this->deleteFilter();
                break;
            case 'saveFilterData':
                $this->saveFilterData();
                break;
            case 'compileFilterData':
                $this->compileFilterData();
                break;
        }
    }

    protected function checkDependency($dependencyName)
    {
        return ((exec("which {$dependencyName}") == '' ? false : true) && ($this->uciGet("ettercap.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/ettercap/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDependency("ettercap")) {
            $this->execBackground("/pineapple/modules/ettercap/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/ettercap/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/ettercap.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggleettercap()
    {
        if (!$this->checkRunning("ettercap")) {
            $full_cmd = $this->request->command . " -T -w /pineapple/modules/ettercap/log/log_".time().".pcap -m /pineapple/modules/ettercap/log/log_".time().".log";
            shell_exec("echo -e \"{$full_cmd}\" > /tmp/ettercap.run");

            $this->execBackground("/pineapple/modules/ettercap/scripts/ettercap.sh start");
        } else {
            $this->execBackground("/pineapple/modules/ettercap/scripts/ettercap.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/ettercap.progress')) {
            if (!$this->checkDependency("ettercap")) {
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

                if ($this->checkRunning("ettercap")) {
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
        if ($this->checkDependency("ettercap")) {
            if ($this->checkRunning("ettercap")) {
                $path = "/pineapple/modules/ettercap/log";

                $latest_ctime = 0;
                $latest_filename = '';

                $d = dir($path);
                while (false !== ($entry = $d->read())) {
                    $filepath = "{$path}/{$entry}";
                    if (is_file($filepath) && filectime($filepath) > $latest_ctime && substr_compare($filepath, ".log", -4, 4) == 0) {
                        $latest_ctime = filectime($filepath);
                        $latest_filename = $entry;
                    }
                }

                if ($latest_filename != "") {
                    $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/ettercap/log/".$latest_filename));

                    if ($this->request->filter != "") {
                        $filter = $this->request->filter;

                        $cmd = "cat /pineapple/modules/ettercap/log/".$latest_filename." | ".$filter;
                    } else {
                        $cmd = "cat /pineapple/modules/ettercap/log/".$latest_filename;
                    }

                    exec($cmd, $output);
                    if (!empty($output)) {
                        $this->response = implode("\n", array_reverse($output));
                    } else {
                        $this->response = "Empty log...";
                    }
                }
            } else {
                $this->response = "ettercap is not running...";
            }
        } else {
            $this->response = "ettercap is not installed...";
        }
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
            $log_list = array_reverse(glob("/pineapple/modules/ettercap/log/*.pcap"));

            echo '[';
            for ($i=0;$i<count($log_list);$i++) {
                $info = explode("_", basename($log_list[$i]));
                $entryDate = gmdate('Y-m-d H-i-s', $info[1]);
                $entryName = basename($log_list[$i], ".pcap");

                echo json_encode(array($entryDate, $entryName.".log", $entryName.".pcap"));

                if ($i!=count($log_list)-1) {
                    echo ',';
                }
            }
            echo ']';
        };
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/ettercap/log/".$this->request->file));
    }

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/ettercap/log/".$this->request->file));
        exec("cat /pineapple/modules/ettercap/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        $file = basename($this->request->file, ".pcap");
        exec("rm -rf /pineapple/modules/ettercap/log/".$file.".*");
    }

    private function getFilters()
    {
        $this->response = array();
        if ($this->request->compiled) {
            $filterList = array_reverse(glob("/pineapple/modules/ettercap/filters/*.ef"));
            array_push($this->response, "--");
            foreach ($filterList as $filter) {
                array_push($this->response, basename($filter));
            }
        } else {
            $filterList = array_reverse(glob("/pineapple/modules/ettercap/filters/*.filter"));
            array_push($this->response, "--");
            foreach ($filterList as $filter) {
                array_push($this->response, basename($filter));
            }
        }
    }

    private function showFilter()
    {
        $filterData = file_get_contents('/pineapple/modules/ettercap/filters/'.$this->request->filter);
        $this->response = array("filterData" => $filterData);
    }

    private function deleteFilter()
    {
        exec("rm -rf /pineapple/modules/ettercap/filters/".basename($this->request->filter, '.filter').".*");
    }

    private function compileFilterData()
    {
        $filename = "/pineapple/modules/ettercap/filters/".$this->request->filter;
        $filename_ef = "/pineapple/modules/ettercap/filters/".basename($this->request->filter, '.filter').".ef";

        $cmd = "etterfilter -o ".$filename_ef." ".$filename." 2>&1";

        exec($cmd, $output);
        if (!empty($output)) {
            $this->response = implode("\n", $output);
        } else {
            $this->response = "Empty log...";
        }
    }

    private function saveFilterData()
    {
        $filename = "/pineapple/modules/ettercap/filters/".$this->request->filter;
        file_put_contents($filename, $this->request->filterData);
    }
}
