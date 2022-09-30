<?php namespace pineapple;

class OnlineHashCrack extends Module
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
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
                break;
            case 'submitWPAOnline':
                $this->submitWPAOnline();
                break;
            case 'submitWPAOnlineStatus':
                $this->submitWPAOnlineStatus();
                break;
            case 'getSettings':
                $this->getSettings();
                break;
            case 'setSettings':
                $this->setSettings();
                break;
            case 'getCapFiles':
                $this->getCapFiles();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("onlinehashcrack.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/OnlineHashCrack/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if(!$this->checkDeps("curl"))
        {
            $this->execBackground("/pineapple/modules/OnlineHashCrack/scripts/dependencies.sh install " . $this->request->destination);
            $this->response = array('success' => true);
        }
        else
        {
            $this->execBackground("/pineapple/modules/OnlineHashCrack/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/OnlineHashCrack.progress'))
        {
            $this->response = array('success' => true);
        }
        else
        {
            $this->response = array('success' => false);
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/OnlineHashCrack.progress'))
        {
            if(!$this->checkDeps("curl"))
            {
                $installed = false;
                $install = "Not installed";
                $installLabel = "danger";
                $processing = false;
            }
            else
            {
                $installed = true;
                $install = "Installed";
                $installLabel = "success";
                $processing = false;
            }
        }
        else
        {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if (file_exists("/tmp/onlinehashcrack.log"))
        {
            $output = file_get_contents("/tmp/onlinehashcrack.log");
            if(!empty($output))
                $this->response = $output;
            else
                $this->response = " ";
        }
        else
        {
             $this->response = " ";
        }
    }

    private function clearOutput()
    {
        exec("rm -rf /tmp/onlinehashcrack.log");
    }

    private function submitWPAOnlineStatus()
    {
        if (!file_exists('/tmp/OnlineHashCrack.progress'))
        {
            $this->response = array('success' => true);
        }
        else
        {
            $this->response = array('success' => false);
        }
    }

    private function submitWPAOnline()
    {
        $this->execBackground("/pineapple/modules/OnlineHashCrack/scripts/submit_wpa.sh ".$this->request->file);
        $this->response = array('success' => true);
    }

    private function getSettings()
    {
        $settings = array(
                    'email' => $this->uciGet("onlinehashcrack.settings.email")
                    );
        $this->response = array('settings' => $settings);
    }

    private function setSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("onlinehashcrack.settings.email", $settings->email);
    }

    private function getCapFiles()
    {
        exec("find -L /pineapple/modules/ -type f -name \"*.**cap\" -o -name \"*.**pcap\" -o -name \"*.**pcapng\" -o -name \"*.**hccapx\" 2>&1", $filesArray);
        $this->response = array("files" => $filesArray);
    }
}