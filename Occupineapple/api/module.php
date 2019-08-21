<?php namespace pineapple;

class Occupineapple extends Module
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
            case 'getLists':
                $this->getLists();
                break;
            case 'showList':
                $this->showList();
                break;
            case 'deleteList':
                $this->deleteList();
                break;
            case 'saveListData':
                $this->saveListData();
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
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("occupineapple.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/Occupineapple/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("mdk3")) {
            $this->execBackground("/pineapple/modules/Occupineapple/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/Occupineapple/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function togglemdk3OnBoot()
    {
        if (exec("cat /etc/rc.local | grep Occupineapple/scripts/autostart_occupineapple.sh") == "") {
            exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/Occupineapple/scripts/autostart_occupineapple.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");
        } else {
            exec("sed -i '/Occupineapple\/scripts\/autostart_occupineapple.sh/d' /etc/rc.local");
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/Occupineapple.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function togglemdk3()
    {
        if (!$this->checkRunning("mdk3")) {
            $this->uciSet("occupineapple.run.interface", $this->request->interface);
            $this->uciSet("occupineapple.run.list", $this->request->list);

            $this->execBackground("/pineapple/modules/Occupineapple/scripts/occupineapple.sh start");
        } else {
            $this->uciSet("occupineapple.run.interface", '');
            $this->uciSet("occupineapple.run.list", '');

            $this->execBackground("/pineapple/modules/Occupineapple/scripts/occupineapple.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/Occupineapple.progress')) {
            if (!$this->checkDeps("mdk3")) {
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

                if (exec("cat /etc/rc.local | grep Occupineapple/scripts/autostart_occupineapple.sh") == "") {
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
        if ($this->checkDeps("mdk3")) {
            if ($this->checkRunning("mdk3")) {
                exec("cat /tmp/occupineapple.log", $output);
                if (!empty($output)) {
                    $this->response = implode("\n", array_reverse($output));
                } else {
                    $this->response = "Empty log...";
                }
            } else {
                $this->response = "Occupineapple is not running...";
            }
        } else {
            $this->response = "mdk3 is not installed...";
        }
    }

    private function getInterfaces()
    {
        exec("iwconfig 2> /dev/null | grep \"wlan*\" | awk '{print $1}'", $interfaceArray);

        $this->response = array("interfaces" => $interfaceArray, "selected" => $this->uciGet("occupineapple.run.interface"));
    }

    private function getLists()
    {
        $listArray = array();
        $listList = array_reverse(glob("/pineapple/modules/Occupineapple/lists/*"));
        array_push($listArray, "--");
        foreach ($listList as $list) {
            array_push($listArray, basename($list));
        }
        $this->response = array("lists" => $listArray, "selected" => $this->uciGet("occupineapple.run.list"));
    }

    private function showList()
    {
        $listData = file_get_contents('/pineapple/modules/Occupineapple/lists/'.$this->request->list);
        $this->response = array("listData" => $listData);
    }

    private function deleteList()
    {
        exec("rm -rf /pineapple/modules/Occupineapple/lists/".$this->request->list);
    }

    private function saveListData()
    {
        $filename = "/pineapple/modules/Occupineapple/lists/".$this->request->list;
        file_put_contents($filename, $this->request->listData);
    }

    private function getSettings()
    {
        $settings = array(
                    'speed' => $this->uciGet("occupineapple.settings.speed"),
                    'channel' => $this->uciGet("occupineapple.settings.channel"),
                    'adHoc' => $this->uciGet("occupineapple.settings.adHoc"),
                    'wepBit' => $this->uciGet("occupineapple.settings.wepBit"),
                    'wpaTKIP' => $this->uciGet("occupineapple.settings.wpaTKIP"),
                    'wpaAES' => $this->uciGet("occupineapple.settings.wpaAES"),
                    'validMAC' => $this->uciGet("occupineapple.settings.validMAC")
                    );
        $this->response = array('settings' => $settings);
    }

    private function setSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("occupineapple.settings.speed", $settings->speed);
        $this->uciSet("occupineapple.settings.channel", $settings->channel);
        if ($settings->adHoc) {
            $this->uciSet("occupineapple.settings.adHoc", 1);
        } else {
            $this->uciSet("occupineapple.settings.adHoc", 0);
        }
        if ($settings->wepBit) {
            $this->uciSet("occupineapple.settings.wepBit", 1);
        } else {
            $this->uciSet("occupineapple.settings.wepBit", 0);
        }
        if ($settings->wpaTKIP) {
            $this->uciSet("occupineapple.settings.wpaTKIP", 1);
        } else {
            $this->uciSet("occupineapple.settings.wpaTKIP", 0);
        }
        if ($settings->wpaAES) {
            $this->uciSet("occupineapple.settings.wpaAES", 1);
        } else {
            $this->uciSet("occupineapple.settings.wpaAES", 0);
        }
        if ($settings->validMAC) {
            $this->uciSet("occupineapple.settings.validMAC", 1);
        } else {
            $this->uciSet("occupineapple.settings.validMAC", 0);
        }
    }

    private function saveAutostartSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("occupineapple.autostart.interface", $settings->interface);
        $this->uciSet("occupineapple.autostart.list", $settings->list);
    }
}
