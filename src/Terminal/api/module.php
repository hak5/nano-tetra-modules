<?php

namespace pineapple;

class Terminal extends Module
{
    const TTYD = "/sd/usr/bin/ttyd";

    public function route()
    {
        switch ($this->request->action) {
            case "getDependenciesStatus":
                $this->getDependenciesStatus();
                break;
            case "managerDependencies":
                $this->managerDependencies();
                break;
            case "getDependenciesInstallStatus":
                $this->getDependenciesInstallStatus();
                break;
            case "startTerminal":
                $this->startTerminal();
                break;
            case "stopTerminal":
                $this->stopTerminal();
                break;
            case "getStatus":
                $this->getStatus();
                break;
        }
    }

    protected function getDependenciesStatus()
    {
        $response = [
            "installed" => false,
            "install" => "Install",
            "installLabel" => "success",
            "processing" => false
        ];

        if (file_exists("/tmp/terminal.progress")) {
            $response["install"] = "Installing...";
            $response["installLabel"] = "warning";
            $response["processing"] = true;
        } else if (!$this->checkPanelVersion()) {
            $response["install"] = "Upgrade Pineapple version first!";
            $response["installLabel"] = "warning";
        } else if ($this->checkDependencyInstalled()) {
            $response["install"] = "Remove";
            $response["installLabel"] = "danger";
            $response["installed"] = true;
        }

        $this->response = $response;
    }

    protected function checkPanelVersion()
    {
        $version = file_get_contents("/etc/pineapple/pineapple_version");
        $version = str_replace("+", "", trim($version));
        if (version_compare($version, "2.8.0") >= 0) {
            return true;
        }

        return false;
    }

    protected function checkDependencyInstalled()
    {
        if ($this->uciGet("ttyd.@ttyd[0].port")) {
            return true;
        }

        if ($this->checkDependency("ttyd")) {
            $this->uciSet("ttyd.@ttyd[0].port", "1477");
            //$this->uciSet("ttyd.@ttyd[0].index", "/pineapple/modules/Terminal/ttyd/iframe.html");
            exec("/etc/init.d/ttyd disable");
            return true;
        }

        return false;
    }

    protected function managerDependencies()
    {
        if (!$this->checkPanelVersion()) {
            $this->response = ["success" => true];
            return true;    
        }

        $action = $this->checkDependencyInstalled() ? "remove" : "install";
        $this->execBackground("/pineapple/modules/Terminal/scripts/dependencies.sh {$action}");
        $this->response = ["success" => true];
    }

    protected function getDependenciesInstallStatus()
    {
        $this->response = ["success" => !file_exists("/tmp/terminal.progress")];
    }

    protected function startTerminal()
    {
        /*
        exec("/etc/init.d/ttyd start", $info);
        $status = implode("\n", $info);
        $this->response = [
            "success" => empty(trim($status)),
            "message" => $status,
        ];
        */
        if (!\helper\checkRunning(self::TTYD)) {
            $this->execBackground(self::TTYD . ' -p 1477 -i br-lan /bin/login');
        }

        $this->response = ["success" => \helper\checkRunning(self::TTYD)];
    }

    protected function stopTerminal()
    {
        /*
        exec("/etc/init.d/ttyd stop", $info);
        $status = implode("\n", $info);
        $this->response = [
            "success" => empty(trim($status)),
            "message" => $status,
        ];
        */
        exec("/usr/bin/pkill ttyd");
        $this->response = ["success" => !\helper\checkRunning(self::TTYD)];
    }

    protected function getStatus()
    {
        $this->response = ["status" => \helper\checkRunning(self::TTYD)];
    }
}
