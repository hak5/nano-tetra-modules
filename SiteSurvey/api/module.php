<?php namespace pineapple;

require_once('/pineapple/modules/SiteSurvey/api/iwlist_parser.php');

class SiteSurvey extends Module
{
    public function __construct($request)
    {
        parent::__construct($request, __CLASS__);
        $this->iwlistparse = new iwlist_parser();
    }

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
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'getMonitors':
                $this->getMonitors();
                break;
            case 'startMonitor':
                $this->startMonitor();
                break;
            case 'stopMonitor':
                $this->stopMonitor();
                break;
            case 'scanForNetworks':
                $this->scanForNetworks();
                break;
            case 'getMACInfo':
                $this->getMACInfo();
                break;
            case 'refreshCapture':
                $this->refreshCapture();
                break;
            case 'viewCapture':
                $this->viewCapture();
                break;
            case 'deleteCapture':
                $this->deleteCapture();
                break;
            case 'downloadCapture':
                $this->downloadCapture();
                break;
            case 'toggleCapture':
                $this->toggleCapture();
                break;
            case 'toggleDeauth':
                $this->toggleDeauth();
                break;
            case 'getProcesses':
                $this->getProcesses();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("sitesurvey.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function checkRunning($processName)
    {
        return exec("ps w | grep {$processName} | grep -v grep") !== '' ? 1 : 0;
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/SiteSurvey/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("mdk3")) {
            $this->execBackground("/pineapple/modules/SiteSurvey/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/SiteSurvey/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/SiteSurvey.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/SiteSurvey.progress')) {
            if (!$this->checkDeps("iwlist")) {
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
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;
        }

        if (file_exists("/tmp/SiteSurvey.log") && (!$this->checkRunning("airodump-ng") || !$this->checkRunning("aireplay-ng"))) {
            exec("rm -rf /tmp/SiteSurvey.log");
        }
        if (file_exists("/tmp/SiteSurvey_deauth.lock") && !$this->checkRunning("aireplay-ng")) {
            exec("rm -rf /tmp/SiteSurvey_deauth.lock");
        }
        if (file_exists("/tmp/SiteSurvey_capture.lock") && !$this->checkRunning("airodump-ng")) {
            exec("rm -rf /tmp/SiteSurvey_capture.lock");
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "processing" => $processing);
    }

    private function toggleCapture()
    {
        $ap = $this->request->ap;

        if (!$this->checkRunning("airodump-ng")) {
            $this->execBackground("/pineapple/modules/SiteSurvey/scripts/capture.sh start ".$this->request->interface." ".$ap->mac." ".$ap->channel);
        } else {
            $this->execBackground("/pineapple/modules/SiteSurvey/scripts/capture.sh stop");
        }
    }

    private function toggleDeauth()
    {
        $ap = $this->request->ap;
        $client = $this->request->client;

        if (!$this->checkRunning("aireplay-ng")) {
            if (!empty($client)) {
                $this->execBackground("/pineapple/modules/SiteSurvey/scripts/deauth.sh start ".$this->request->interface." ".$ap->mac." ".$client);
            } else {
                $this->execBackground("/pineapple/modules/SiteSurvey/scripts/deauth.sh start ".$this->request->interface." ".$ap->mac);
            }
        } else {
            $this->execBackground("/pineapple/modules/SiteSurvey/scripts/deauth.sh stop");
        }
    }

    private function getInterfaces()
    {
        exec("iwconfig 2> /dev/null | grep \"wlan*\" | grep -v \"mon*\" | awk '{print $1}'", $interfaceArray);

        $this->response = array("interfaces" => $interfaceArray);
    }

    private function getMonitors()
    {
        exec("iwconfig 2> /dev/null | grep \"mon*\" | awk '{print $1}'", $monitorArray);

        $this->response = array("monitors" => $monitorArray);
    }

    private function startMonitor()
    {
        exec("airmon-ng start ".$this->request->interface);
    }

    private function stopMonitor()
    {
        exec("airmon-ng stop ".$this->request->monitor);
    }

    private function refreshCapture()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/SiteSurvey/capture/*.cap"));

            echo '[';
            for ($i=0;$i<count($log_list);$i++) {
                $info = explode("_", basename($log_list[$i]));
                $entryDate = gmdate('Y-m-d H-i-s', $info[1]);
                $entryName = basename($log_list[$i]);

                $entryBSSID = exec("awk -F, '/BSSID/ {i=1; next} i {print $1}' /pineapple/modules/SiteSurvey/capture/".basename($log_list[$i], ".cap").".csv | head -1");
                $entryESSID = exec("awk -F, '/BSSID/ {i=1; next} i {print $14}' /pineapple/modules/SiteSurvey/capture/".basename($log_list[$i], ".cap").".csv | head -1");
                $entryIVS = exec("awk -F, '/BSSID/ {i=1; next} i {print $11}' /pineapple/modules/SiteSurvey/capture/".basename($log_list[$i], ".cap").".csv | head -1");

                exec("aircrack-ng -a 2 -w - -b ".$entryBSSID." ".$log_list[$i]." 2>&1", $entryHandshake);
                if (in_array("Passphrase not in dictionary", $entryHandshake)) {
                    $entryHandshake = "Yes";
                } else {
                    $entryHandshake = "No";
                }

                echo json_encode(array($entryDate, $entryName, $entryESSID, $entryBSSID, $entryIVS, $entryHandshake));

                if ($i!=count($log_list)-1) {
                    echo ',';
                }
            }
            echo ']';
        };
    }

    private function downloadCapture()
    {
        $file = basename($this->request->file, ".cap");

        exec("mkdir /tmp/dl/");
        exec("cp /pineapple/modules/SiteSurvey/capture/".$file.".* /tmp/dl/");
        exec("cd /tmp/dl/ && tar -czf /tmp/".$file.".tar.gz *");
        exec("rm -rf /tmp/dl/");

        $this->response = array("download" => $this->downloadFile("/tmp/".$file.".tar.gz"));
    }

    private function viewCapture()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/SiteSurvey/capture/".$this->request->file));
        exec("strings /pineapple/modules/SiteSurvey/capture/".basename($this->request->file, ".cap").".csv", $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty dump...", "date" => $log_date);
        }
    }

    private function deleteCapture()
    {
        $file = basename($this->request->file, ".cap");
        exec("rm -rf /pineapple/modules/SiteSurvey/capture/".$file.".*");
    }

    private function scanForNetworks()
    {
        if (file_exists("/tmp/SiteSurvey_deauth.lock") && !$this->checkRunning("aireplay-ng")) {
            exec("rm -rf /tmp/SiteSurvey_deauth.lock");
        }
        if (file_exists("/tmp/SiteSurvey_capture.lock") && !$this->checkRunning("airodump-ng")) {
            exec("rm -rf /tmp/SiteSurvey_capture.lock");
        }

        $clientArray = array();
        if ($this->request->duration && $this->request->monitor != "" && $this->request->type == 'clientAP') {
            exec("killall -9 airodump-ng && rm -rf /tmp/sitesurvey-*");

            $this->execBackground("airodump-ng --write /tmp/sitesurvey ".$this->request->monitor." &> /dev/null ");
            sleep($this->request->duration);

            exec("cat /tmp/sitesurvey-01.csv | tail -n +$(($(cat /tmp/sitesurvey-01.csv | grep -n \"Station MAC\" | cut -f1 -d:)+1)) | tr '\r' '\n' > /tmp/sitesurvey-01.tmp");
            exec("sed '/^$/d' < /tmp/sitesurvey-01.tmp > /tmp/sitesurvey-01.clients");

            $file_handle = fopen("/tmp/sitesurvey-01.clients", "r");
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                $line = str_replace(" ", "", $line);
                $clientArray[] = explode(",", $line);
            }
            fclose($file_handle);

            exec("killall -9 airodump-ng");
        }

        $p = $this->iwlistparse->parseScanDev($this->request->interface);
        $apArray = $p[$this->request->interface];

        $returnArray = array();
        foreach ($apArray as $apData) {
            $accessPoint = array();
            $accessPoint['mac'] = $apData["Address"];
            $accessPoint['ssid'] = $apData["ESSID"];
            $accessPoint['channel'] = intval($apData["Channel"]);

            $frequencyData = explode(' ', $apData["Frequency"]);
            $accessPoint['frequency'] = $frequencyData[0];

            $accessPoint['signal'] = $apData["Signal level"];
            $accessPoint['quality'] = intval($apData["Quality"]);

            if ($apData["Quality"] <= 25) {
                $accessPoint['qualityLabel'] = "danger";
            } elseif ($apData["Quality"] <= 50) {
                $accessPoint['qualityLabel'] = "warning";
            } elseif ($apData["Quality"] <= 100) {
                $accessPoint['qualityLabel'] = "success";
            }

            if (file_exists("/tmp/SiteSurvey_capture.lock")) {
                if (file_get_contents("cat /tmp/SiteSurvey_capture.lock") == $apData["Address"]) {
                    $accessPoint['captureOnSelected'] = 1;
                } else {
                    $accessPoint['captureOnSelected'] = 0;
                }
            } else {
                $accessPoint['captureOnSelected'] = 0;
            }

            if ($this->checkRunning("airodump-ng")) {
                $accessPoint['captureRunning'] = 1;
            } else {
                $accessPoint['captureRunning'] = 0;
            }

            if (file_exists("/tmp/SiteSurvey_deauth.lock")) {
                $targetArray = explode("\n", file_get_contents("/tmp/SiteSurvey_deauth.lock"));
                if ($targetArray[0] == $apData["Address"]) {
                    $accessPoint['deauthOnSelected'] = 1;
                } else {
                    $accessPoint['deauthOnSelected'] = 0;
                }
            } else {
                $accessPoint['deauthOnSelected'] = 0;
            }

            if ($this->checkRunning("aireplay-ng")) {
                $accessPoint['deauthRunning'] = 1;
            } else {
                $accessPoint['deauthRunning'] = 0;
            }

            if ($apData["Encryption key"] == "on") {
                $WPA = strstr($apData["IE"], "WPA Version 1");
                $WPA2 = strstr($apData["IE"], "802.11i/WPA2 Version 1");

                $auth_type = str_replace("\n", " ", $apData["Authentication Suites (1)"]);
                $auth_type = implode(' ', array_unique(explode(' ', $auth_type)));

                $cipher = $apData["Pairwise Ciphers (2)"] ? $apData["Pairwise Ciphers (2)"] : $apData["Pairwise Ciphers (1)"];
                $cipher = str_replace("\n", " ", $cipher);
                $cipher = implode(', ', array_unique(explode(' ', $cipher)));

                if ($WPA2 != "" && $WPA != "") {
                    $accessPoint['encryption'] = 'Mixed WPA/WPA2';
                } elseif ($WPA2 != "") {
                    $accessPoint['encryption'] = 'WPA2';
                } elseif ($WPA != "") {
                    $accessPoint['encryption'] = 'WPA';
                } else {
                    $accessPoint['encryption'] = 'WEP';
                }

                $accessPoint['cipher'] = $cipher;
                $accessPoint['auth'] = $auth_type;
            } else {
                $accessPoint['encryption'] = 'None';
                $accessPoint['cipher'] = '';
                $accessPoint['auth'] = '';
            }

            $accessPoint['clients'] = array();
            foreach ($clientArray as $clientData) {
                if ($clientData[5] == $accessPoint['mac']) {
                    array_push($accessPoint['clients'], $clientData[0]);
                }
            }

            exec("rm -rf /tmp/sitesurvey-*");

            array_push($returnArray, $accessPoint);
        }

        $this->response = $returnArray;
    }

    private function getMACInfo()
    {
        $content = file_get_contents("https://api.macvendors.com/".$this->request->mac);
        $this->response = array('title' => $this->request->mac, "output" => $content);
    }

    private function getProcesses()
    {
        $returnArray = array();

        $process = array();
        if (file_exists("/tmp/SiteSurvey_deauth.lock") && $this->checkRunning("aireplay-ng")) {
            $targetArray = explode("\n", file_get_contents("/tmp/SiteSurvey_deauth.lock"));

            $process['target'] = $targetArray[0];
            $process['client'] = $targetArray[1];
            $process['name'] = "aireplay-ng";

            array_push($returnArray, $process);
        }

        $process = array();
        if (file_exists("/tmp/SiteSurvey_capture.lock") && $this->checkRunning("airodump-ng")) {
            $process['target'] = exec("cat /tmp/SiteSurvey_capture.lock");
            $process['name'] = "airodump-ng";

            array_push($returnArray, $process);
        }

        $this->response = $returnArray;
    }
}
