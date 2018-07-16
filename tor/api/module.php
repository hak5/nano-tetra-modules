<?php namespace pineapple;

class Tor extends Module
{
    private $progressFile = '/tmp/tor.progress';
    private $moduleConfigFile = '/etc/config/tor/config';
    private $dependenciesScriptFile = '/pineapple/modules/tor/scripts/dependencies.sh';

    // Error Constants
    const INVALID_NAME = 'Invalid name';
    const INVALID_PORT = 'Invalid port';
    const INVALID_DESTINATION = 'Invalid destination';

    // Display Constants
    const DANGER = 'danger';
    const WARNING = 'warning';
    const SUCCESS = 'success';

    public function route()
    {
        switch ($this->request->action) {
            case 'refreshInfo':
                $this->refreshInfo();
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
            case 'toggletor':
                $this->toggletor();
                break;
            case 'refreshHiddenServices':
                $this->refreshHiddenServices();
                break;
            case 'addHiddenService':
                $this->addHiddenService();
                break;
            case 'removeHiddenService':
                $this->removeHiddenService();
                break;
            case 'addServiceForward':
                $this->addServiceForward();
                break;
            case 'removeServiceForward':
                $this->removeServiceForward();
                break;
            default:
                break;
        }
    }

    private function success($value)
    {
        $this->response = array('success' => $value);
    }

    private function error($message)
    {
        $this->response = array('error' => $message);
    }

    private function isValidName($name)
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $name) === 1;
    }

    private function isValidPort($port)
    {
        return preg_match('/^[0-9]+$/', $port) === 1;
    }

    private function isValidRedirectTo($redirect_to)
    {
        return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+$/', $redirect_to) === 1;
    }

    protected function checkDependency($dependencyName)
    {
        return (exec("which {$dependencyName}") != '' &&  !file_exists($this->progressFile));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/tor/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    protected function checkRunning($processName)
    {
        return (exec("pgrep '{$processName}$'") != '');
    }

    private function handleDependencies()
    {
        $destination = "";
        if (isset($this->request->destination)) {
            $destination = $this->request->destination;
            if ($destination != "internal" && $destination != "sd") {
                $this->error(self::INVALID_DESTINATION);
                return;
            }
        }

        if (!$this->checkDependency("tor")) {
            $this->execBackground($this->dependenciesScriptFile . " install " . $destination);
        } else {
            $this->execBackground($this->dependenciesScriptFile . " remove");
        }
        $this->success(true);
    }

    private function handleDependenciesStatus()
    {
        if (file_exists($this->progressFile)) {
            $this->success(false);
        } else {
            $this->success(true);
        }
    }

    private function toggletor()
    {
        if ($this->checkRunning("tor")) {
            exec("/etc/init.d/tor stop");
        } else {
            exec("/etc/init.d/tor start");
        }
    }

    private function refreshStatus()
    {

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();
        $installed = false;
        $install = "Not Installed";
        $processing = false;

        if (file_exists($this->progressFile)) {
            // TOR Is installing, please wait.
            $install = "Installing...";
            $installLabel = self::WARNING;
            $processing = true;

            $status = "Not running";
            $statusLabel = self::DANGER;
        } elseif (!$this->checkDependency("tor")) {
            // TOR is not installed, please install.
            $installLabel = self::DANGER;

            $status = "Start";
            $statusLabel = self::DANGER;
        } else {
            // TOR is installed, please configure.
            $installed = true;
            $install = "Installed";
            $installLabel = self::SUCCESS;

            if ($this->checkRunning("tor")) {
                $status = "Started";
                $statusLabel = self::SUCCESS;
            } else {
                $status = "Stopped";
                $statusLabel = self::DANGER;
            }
        }

        $this->response = array("device" => $device,
                                "sdAvailable" => $sdAvailable,
                                "status" => $status,
                                "statusLabel" => $statusLabel,
                                "installed" => $installed,
                                "install" => $install,
                                "installLabel" => $installLabel,
                                "processing" => $processing);
    }

    private function generateConfig()
    {
        $output = file_get_contents("/etc/config/tor/torrc");
        $output .= "\n";
        $hiddenServices = @json_decode(file_get_contents($this->moduleConfigFile));
        foreach ($hiddenServices as $hiddenService) {
            $output .= "HiddenServiceDir /etc/config/tor/services/{$hiddenService->name}\n";
            $forwards = $hiddenService->forwards;
            foreach ($forwards as $forward) {
                $output .= "HiddenServicePort {$forward->port} {$forward->redirect_to}\n";
            }
        }
        file_put_contents("/etc/tor/torrc", $output);
    }

    private function reloadTor()
    {
        $this->generateConfig();
        //Sending SIGHUP to tor process cause config reload.
        exec("pkill -sighup tor$");
    }

    private function refreshHiddenServices()
    {
        $hiddenServices = @json_decode(file_get_contents($this->moduleConfigFile));
        foreach ($hiddenServices as $hiddenService) {
            if (file_exists("/etc/config/tor/services/{$hiddenService->name}/hostname")) {
                $hostname = file_get_contents("/etc/config/tor/services/{$hiddenService->name}/hostname");
                $hiddenService->hostname = trim($hostname);
            }
        }
        $this->response = array("hiddenServices" => $hiddenServices);
    }

    private function addHiddenService()
    {
        $name = $this->request->name;
        if (!$this->isValidName($name)) {
            $this->error(self::INVALID_NAME);
            return;
        }

        $hiddenService = array("name" => $name, "forwards" => array() );
        $hiddenServices = array();
        if (file_exists($this->moduleConfigFile)) {
            $hiddenServices = @json_decode(file_get_contents($this->moduleConfigFile));
        }
        array_push($hiddenServices, $hiddenService);
        file_put_contents($this->moduleConfigFile, @json_encode($hiddenServices, JSON_PRETTY_PRINT));
        $this->reloadTor();
    }

    private function removeHiddenService()
    {
        $hiddenServices = @json_decode(file_get_contents($this->moduleConfigFile));
        foreach ($hiddenServices as $key => $hiddenService) {
            if ($hiddenService->name == $this->request->name) {
                unset($hiddenServices[$key]);
            }
        }
        file_put_contents($this->moduleConfigFile, @json_encode($hiddenServices, JSON_PRETTY_PRINT));
        $this->reloadTor();
    }

    private function addServiceForward()
    {
        $name = $this->request->name;
        $port = $this->request->port;
        $redirect_to = $this->request->redirect_to;

        if (!$this->isValidName($name)) {
            $this->error(self::INVALID_NAME);
            return;
        }
        if (!$this->isValidPort($port)) {
            $this->error(self::INVALID_PORT);
            return;
        }
        if (!$this->isValidRedirectTo($redirect_to)) {
            $this->error(self::INVALID_DESTINATION);
            return;
        }

        $hiddenServices = @json_decode(file_get_contents($this->moduleConfigFile));
        foreach ($hiddenServices as $key => $hiddenService) {
            if ($hiddenService->name == $name) {
                $forwards = $hiddenService->forwards;
                $forward = array("port" => $port, "redirect_to" => $redirect_to);
                array_push($forwards, $forward);
                $hiddenServices[$key]->forwards = $forwards;
            }
        }
        file_put_contents($this->moduleConfigFile, @json_encode($hiddenServices, JSON_PRETTY_PRINT));

        $this->reloadTor();
    }

    private function removeServiceForward()
    {
        $name = $this->request->name;
        $port = $this->request->port;
        $redirect_to = $this->request->redirect_to;

        $hiddenServices = @json_decode(file_get_contents($this->moduleConfigFile));
        foreach ($hiddenServices as $hiddenService) {
            if ($hiddenService->name == $name) {
                $forwards = $hiddenService->forwards;
                foreach ($forwards as $key => $forward) {
                    if ($forward->port == $port && $forward->redirect_to == $redirect_to) {
                        unset($forwards[$key]);
                    }
                }
                $hiddenService->forwards = $forwards;
            }
        }
        file_put_contents($this->moduleConfigFile, @json_encode($hiddenServices, JSON_PRETTY_PRINT));

        $this->reloadTor();
    }
}
