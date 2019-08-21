<?php namespace pineapple;

class KeyManager extends Module
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
            case 'handleKey':
                $this->handleKey();
                break;
            case 'handleKeyStatus':
                $this->handleKeyStatus();
                break;
            case 'saveKnownHostsData':
                $this->saveKnownHostsData();
                break;
            case 'getKnownHostsData':
                $this->getKnownHostsData();
                break;
            case 'addToKnownHosts':
                $this->addToKnownHosts();
                break;
            case 'addToKnownHostsStatus':
                $this->addToKnownHostsStatus();
                break;
            case 'copyToRemoteHost':
                $this->copyToRemoteHost();
                break;
            case 'copyToRemoteHostStatus':
                $this->copyToRemoteHostStatus();
                break;
            case 'getSettings':
                $this->getSettings();
                break;
        }
    }

    protected function checkDep($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("keymanager.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/KeyManager/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleKey()
    {
        if (!file_exists("/root/.ssh/id_rsa")) {
            $this->execBackground("/pineapple/modules/KeyManager/scripts/generate_key.sh");
            $this->response = array('success' => true);
        } else {
            exec("rm -rf /root/.ssh/id_rsa*");
            $this->response = array('success' => true);
        }
    }

    private function handleKeyStatus()
    {
        if (!file_exists('/tmp/KeyManager_key.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function handleDependencies()
    {
        if (!$this->checkDep("ssh-keyscan")) {
            $this->execBackground("/pineapple/modules/KeyManager/scripts/dependencies.sh install " . $this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/KeyManager/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/KeyManager.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/KeyManager.progress')) {
            if (!$this->checkDep("ssh-keyscan")) {
                $installed = false;
                $install = "Not installed";
                $installLabel = "danger";
                $processing = false;
            } else {
                $installed = true;
                $install = "Installed";
                $installLabel = "success";
                $processing = false;
            }

            if (!file_exists('/tmp/KeyManager_key.progress')) {
                if (!file_exists("/root/.ssh/id_rsa")) {
                    $key = "Not generated";
                    $keyLabel = "danger";
                    $generated = false;
                    $generating = false;
                } else {
                    $key = "Generated";
                    $keyLabel = "success";
                    $generated = true;
                    $generating = false;
                }
            } else {
                $key = "Generating...";
                $keyLabel = "warning";
                $generated = false;
                $generating = true;
            }
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $key = "Not generated";
            $keyLabel = "danger";
            $generating = false;
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "installed" => $installed, "key" => $key, "keyLabel" => $keyLabel, "generating" => $generating, "generated" => $generated, "install" => $install, "installLabel" => $installLabel, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if (file_exists("/tmp/keymanager.log")) {
            $output = file_get_contents("/tmp/keymanager.log");
            if (!empty($output))
                $this->response = $output;
            else
                $this->response = " ";
        } else {
            $this->response = " ";
        }
    }

    private function clearOutput()
    {
        exec("rm -rf /tmp/keymanager.log");
    }

    private function saveKnownHostsData()
    {
        $filename = '/root/.ssh/known_hosts';
        file_put_contents($filename, $this->request->knownHostsData);
    }

    private function getKnownHostsData()
    {
        $knownHostsData = file_get_contents('/root/.ssh/known_hosts');
        $this->response = array("knownHostsData" => $knownHostsData);
    }

    private function addToKnownHostsStatus()
    {
        if (!file_exists('/tmp/KeyManager.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function addToKnownHosts()
    {
        $this->uciSet("keymanager.settings.host", $this->request->host);
        $this->uciSet("keymanager.settings.port", $this->request->port);

        $this->execBackground("/pineapple/modules/KeyManager/scripts/add_host.sh");
        $this->response = array('success' => true);
    }

    private function copyToRemoteHostStatus()
    {
        if (!file_exists('/tmp/KeyManager.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function copyToRemoteHost()
    {
        $this->uciSet("keymanager.settings.host", $this->request->host);
        $this->uciSet("keymanager.settings.port", $this->request->port);
        $this->uciSet("keymanager.settings.user", $this->request->user);

        $this->execBackground("/pineapple/modules/KeyManager/scripts/copy_key.sh " . $this->request->password);
        $this->response = array('success' => true);
    }

    private function getSettings()
    {
        $settings = array(
            'host' => $this->uciGet("keymanager.settings.host"),
            'port' => $this->uciGet("keymanager.settings.port"),
            'user' => $this->uciGet("keymanager.settings.user")
        );
        $this->response = $settings;
    }

}
