<?php namespace pineapple;

putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');
putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

class DNSspoof extends Module
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
            case 'toggleDNSspoof':
                $this->toggleDNSspoof();
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
            case 'toggleDNSspoofOnBoot':
                $this->toggleDNSspoofOnBoot();
                break;
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'saveAutostartSettings':
                $this->saveAutostartSettings();
                break;
            case 'saveLandingPageData':
                $this->saveLandingPageData();
                break;
            case 'getLandingPageData':
                $this->getLandingPageData();
                break;
            case 'saveHostsData':
                $this->saveHostsData();
                break;
            case 'getHostsData':
                $this->getHostsData();
                break;
        }
    }

    protected function checkDependency($dependencyName)
    {
        return ((exec("which {$dependencyName}") == '' ? false : true) && ($this->uciGet("dnsspoof.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/DNSspoof/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDependency("dnsspoof")) {
            $this->execBackground("/pineapple/modules/DNSspoof/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/DNSspoof/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/DNSspoof.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggleDNSspoofOnBoot()
    {
        if (exec("cat /etc/rc.local | grep DNSspoof/scripts/autostart_dnsspoof.sh") == "") {
            exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/DNSspoof/scripts/autostart_dnsspoof.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");
        } else {
            exec("sed -i '/DNSspoof\/scripts\/autostart_dnsspoof.sh/d' /etc/rc.local");
        }
    }

    private function toggleDNSspoof()
    {
        if (!$this->checkRunning("dnsspoof")) {
            $this->uciSet("dnsspoof.run.interface", $this->request->interface);

            $this->execBackground("/pineapple/modules/DNSspoof/scripts/dnsspoof.sh start");
        } else {
            $this->uciSet("dnsspoof.run.interface", '');

            $this->execBackground("/pineapple/modules/DNSspoof/scripts/dnsspoof.sh stop");
        }
    }

    private function getInterfaces()
    {
        exec("cat /proc/net/dev | tail -n +3 | cut -f1 -d: | sed 's/ //g'", $interfaceArray);

        $this->response = array("interfaces" => $interfaceArray, "selected" => $this->uciGet("dnsspoof.run.interface"));
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/DNSspoof.progress')) {
            if (!$this->checkDependency("dnsspoof")) {
                $installed = false;
                $install = "Not installed";
                $installLabel = "danger";
                $processing = false;

                $status = "Start";
                $statusLabel = "success";

                $bootLabelON = "default";
                $bootLabelOFF = "danger";
            } else {
                $installed = true;
                $install = "Installed";
                $installLabel = "success";
                $processing = false;

                if ($this->checkRunning("dnsspoof")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }

                if (exec("cat /etc/rc.local | grep DNSspoof/scripts/autostart_dnsspoof.sh") == "") {
                    $bootLabelON = "default";
                    $bootLabelOFF = "danger";
                } else {
                    $bootLabelON = "success";
                    $bootLabelOFF = "default";
                }
            }
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $status = "Not running";
            $statusLabel = "danger";

            $bootLabelON = "default";
            $bootLabelOFF = "danger";
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if ($this->checkDependency("dnsspoof")) {
            if ($this->checkRunning("dnsspoof")) {
                $path = "/pineapple/modules/DNSspoof/log";

                $latest_ctime = 0;
                $latest_filename = '';

                $d = dir($path);
                while (false !== ($entry = $d->read())) {
                    $filepath = "{$path}/{$entry}";
                    if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
                        $latest_ctime = filectime($filepath);
                        $latest_filename = $entry;
                    }
                }

                if ($latest_filename != "") {
                    $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/DNSspoof/log/".$latest_filename));

                    if ($this->request->filter != "") {
                        $filter = $this->request->filter;

                        $cmd = "cat /pineapple/modules/DNSspoof/log/".$latest_filename." | ".$filter;
                    } else {
                        $cmd = "cat /pineapple/modules/DNSspoof/log/".$latest_filename;
                    }

                    exec($cmd, $output);
                    if (!empty($output)) {
                        $this->response = implode("\n", array_reverse($output));
                    } else {
                        $this->response = "Empty log...";
                    }
                }
            } else {
                $this->response = "DNSspoof is not running...";
            }
        } else {
            $this->response = "DNSspoof is not installed...";
        }
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/DNSspoof/log/*"));

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
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/DNSspoof/log/".$this->request->file));
        exec("cat /pineapple/modules/DNSspoof/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        exec("rm -rf /pineapple/modules/DNSspoof/log/".$this->request->file);
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/DNSspoof/log/".$this->request->file));
    }

    private function saveAutostartSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("dnsspoof.autostart.interface", $settings->interface);
    }

    private function saveLandingPageData()
    {
        $filename = '/www/index.php';
        file_put_contents($filename, $this->request->configurationData);
    }

    private function getLandingPageData()
    {
        $configurationData = file_get_contents('/www/index.php');
        $this->response = array("configurationData" => $configurationData);
    }

    private function saveHostsData()
    {
        $filename = '/etc/pineapple/spoofhost';
        file_put_contents($filename, $this->request->configurationData);
    }

    private function getHostsData()
    {
        $configurationData = file_get_contents('/etc/pineapple/spoofhost');
        $this->response = array("configurationData" => $configurationData);
    }
}
