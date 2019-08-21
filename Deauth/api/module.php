<?php namespace pineapple;

//putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');
//putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

class Deauth extends Module
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
            case 'togglemdk3':
                $this->togglemdk3();
                break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
                break;
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'scanForNetworks':
                $this->scanForNetworks();
                break;
            case 'getSettings':
                $this->getSettings();
                break;
            case 'setSettings':
                $this->setSettings();
                break;
            case 'saveAutostartSettings':
                $this->saveAutostartSettings();
                break;
            case 'togglemdk3OnBoot':
                $this->togglemdk3OnBoot();
                break;
            case 'getListsData':
                $this->getListsData();
                break;
            case 'saveListsData':
                $this->saveListsData();
                break;
        }
    }

    protected function checkDep($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("deauth.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/Deauth/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDep("mdk3")) {
            $this->execBackground("/pineapple/modules/Deauth/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/Deauth/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function togglemdk3OnBoot()
    {
        if (exec("cat /etc/rc.local | grep Deauth/scripts/autostart_deauth.sh") == "") {
            exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/Deauth/scripts/autostart_deauth.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");
        } else {
            exec("sed -i '/Deauth\/scripts\/autostart_deauth.sh/d' /etc/rc.local");
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/Deauth.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function togglemdk3()
    {
        if (!$this->checkRunning("mdk3")) {
            $this->uciSet("deauth.run.interface", $this->request->interface);

            $this->execBackground("/pineapple/modules/Deauth/scripts/deauth.sh start");
        } else {
            $this->uciSet("deauth.run.interface", '');

            $this->execBackground("/pineapple/modules/Deauth/scripts/deauth.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/Deauth.progress')) {
            if (!$this->checkDep("mdk3")) {
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

                if ($this->checkRunning("mdk3")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }

                if (exec("cat /etc/rc.local | grep Deauth/scripts/autostart_deauth.sh") == "") {
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

            $status = "Start";
            $statusLabel = "success";

            $bootLabelON = "default";
            $bootLabelOFF = "danger";
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if ($this->checkDependency("mdk3")) {
            if ($this->checkRunning("mdk3")) {
                exec("cat /tmp/deauth.log", $output);
                if (!empty($output)) {
                    $this->response = implode("\n", array_reverse($output));
                } else {
                    $this->response = "Empty log...";
                }
            } else {
                $this->response = "Deauth is not running...";
            }
        } else {
            $this->response = "mdk3 is not installed...";
        }
    }

    private function getInterfaces()
    {
        exec("iwconfig 2> /dev/null | grep \"wlan*\" | awk '{print $1}'", $interfaceArray);

        $this->response = array("interfaces" => $interfaceArray, "selected" => $this->uciGet("deauth.run.interface"));
    }

    private function scanForNetworks()
    {
        $interface = escapeshellarg($this->request->interface);
        if (substr($interface, -4, -1) === "mon") {
            if ($interface == "'wlan1mon'") {
                exec("killall pineap");
                exec("killall pinejector");
            }
            exec("airmon-ng stop {$interface}");
            $interface = substr($interface, 0, -4) . "'";
            exec("iw dev {$interface} scan &> /dev/null");
        }
        exec("iwinfo {$interface} scan", $apScan);

        $apArray = preg_split("/^Cell/m", implode("\n", $apScan));
        $returnArray = array();
        foreach ($apArray as $apData) {
            $apData = explode("\n", $apData);
            $accessPoint = array();
            $accessPoint['mac'] = substr($apData[0], -17);
            $accessPoint['ssid'] = substr(trim($apData[1]), 8, -1);
            if (mb_detect_encoding($accessPoint['ssid'], "auto") === false) {
                continue;
            }

            $accessPoint['channel'] = intval(substr(trim($apData[2]), -2));

            $signalString = explode("  ", trim($apData[3]));
            $accessPoint['signal'] = substr($signalString[0], 8);
            $accessPoint['quality'] = substr($signalString[1], 9);

            $security = substr(trim($apData[4]), 12);
            if ($security === "none") {
                $accessPoint['security'] = "Open";
            } else {
                $accessPoint['security'] = $security;
            }

            if ($accessPoint['mac'] && trim($apData[1]) !== "ESSID: unknown") {
                array_push($returnArray, $accessPoint);
            }
        }
        $this->response = $returnArray;
    }

    private function getSettings()
    {
        $settings = array(
                    'speed' => $this->uciGet("deauth.settings.speed"),
                    'channels' => $this->uciGet("deauth.settings.channels"),
                    'mode' => $this->uciGet("deauth.settings.mode")
                    );
        $this->response = array('settings' => $settings);
    }

    private function setSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("deauth.settings.speed", $settings->speed);
        $this->uciSet("deauth.settings.channels", $settings->channels);
        $this->uciSet("deauth.settings.mode", $settings->mode);
    }

    private function saveAutostartSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("deauth.autostart.interface", $settings->interface);
    }

    private function getListsData()
    {
        $blacklistData = file_get_contents('/pineapple/modules/Deauth/lists/blacklist.lst');
        $whitelistData = file_get_contents('/pineapple/modules/Deauth/lists/whitelist.lst');
        $this->response = array("blacklistData" => $blacklistData, "whitelistData" => $whitelistData );
    }

    private function saveListsData()
    {
        $filename = '/pineapple/modules/Deauth/lists/blacklist.lst';
        file_put_contents($filename, $this->request->blacklistData);

        $filename = '/pineapple/modules/Deauth/lists/whitelist.lst';
        file_put_contents($filename, $this->request->whitelistData);
    }
}
