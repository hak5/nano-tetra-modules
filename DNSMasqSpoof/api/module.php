<?php namespace pineapple;

class DNSMasqSpoof extends Module
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
            case 'toggleDNSMasqSpoof':
                $this->toggleDNSMasqSpoof();
                break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
                break;
            case 'toggleDNSMasqSpoofOnBoot':
                $this->toggleDNSMasqSpoofOnBoot();
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

    protected function checkDep($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("dnsmasqspoof.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function checkRunning($processName)
    {
        return exec("ps w | grep {$processName} | grep -v grep") !== '' && exec("grep addn-hosts /etc/dnsmasq.conf") !== '' ? 1 : 0;
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/DNSMasqSpoof/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDep("dnsmasq")) {
            $this->execBackground("/pineapple/modules/DNSMasqSpoof/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/DNSMasqSpoof/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/DNSMasqSpoof.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggleDNSMasqSpoofOnBoot()
    {
        if (exec("cat /etc/rc.local | grep DNSMasqSpoof/scripts/autostart_dnsmasqspoof.sh") == "") {
            exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/DNSMasqSpoof/scripts/autostart_dnsmasqspoof.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");
        } else {
            exec("sed -i '/DNSMasqSpoof\/scripts\/autostart_dnsmasqspoof.sh/d' /etc/rc.local");
        }
    }

    private function toggleDNSMasqSpoof()
    {
        if (!$this->checkRunning("dnsmasq")) {
            $this->execBackground("/pineapple/modules/DNSMasqSpoof/scripts/dnsmasqspoof.sh start");
        } else {
            $this->execBackground("/pineapple/modules/DNSMasqSpoof/scripts/dnsmasqspoof.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/DNSMasqSpoof.progress')) {
            if (!$this->checkDependency("dnsmasq")) {
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

                if ($this->checkRunning("dnsmasq")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }

                if (exec("cat /etc/rc.local | grep DNSMasqSpoof/scripts/autostart_dnsmasqspoof.sh") == "") {
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
        if ($this->checkDependency("dnsmasq")) {
            if ($this->checkRunning("dnsmasq")) {
                $this->response = "DNSMasq Spoof is running...";
            } else {
                $this->response = "DNSMasq Spoof is not running...";
            }
        } else {
            $this->response = "DNSMasq Spoof is not installed...";
        }
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
        $filename = '/pineapple/modules/DNSMasqSpoof/hosts/dnsmasq.hosts';
        file_put_contents($filename, $this->request->configurationData);
    }

    private function getHostsData()
    {
        $configurationData = file_get_contents('/pineapple/modules/DNSMasqSpoof/hosts/dnsmasq.hosts');
        $this->response = array("configurationData" => $configurationData);
    }
}
