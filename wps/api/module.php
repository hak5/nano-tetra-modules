<?php namespace pineapple;

require_once('/pineapple/modules/wps/api/iwlist_parser.php');

class wps extends Module
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
            case 'refreshOutput':
                $this->refreshOutput();
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
            case 'togglewps':
                $this->togglewps();
                break;
            case 'getProcesses':
                $this->getProcesses();
                break;
            case 'refreshHistory':
                $this->refreshHistory();
                break;
            case 'viewHistory':
                $this->viewHistory();
                break;
            case 'deleteHistory':
                $this->deleteHistory();
                break;
            case 'downloadHistory':
                $this->downloadHistory();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("wps.module.installed")));
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
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/wps/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("reaver")) {
            $this->execBackground("/pineapple/modules/wps/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/wps/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/wps.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/wps.progress')) {
            if (!$this->checkDeps("iwlist")) {
                $installed = false;
                $install = "Not installed";
                $installLabel = "danger";
                $processing = false;

                $status = "Start";
                $statusLabel = "success";
            } else {
                $installed = true;
                $install = "Installed";
                $installLabel = "success";
                $processing = false;

                if ($this->checkRunning("reaver") || $this->checkRunning("bully")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }
            }
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $status = "Start";
            $statusLabel = "success";
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "processing" => $processing);
    }

    private function togglewps()
    {
        if (!($this->checkRunning("reaver") || $this->checkRunning("bully"))) {
            $full_cmd = $this->request->command . " -o /pineapple/modules/wps/log/log_".time().".log";
            shell_exec("echo -e \"{$full_cmd}\" > /tmp/wps.run");

            $this->execBackground("/pineapple/modules/wps/scripts/wps.sh start");
        } else {
            $this->execBackground("/pineapple/modules/wps/scripts/wps.sh stop");
        }
    }

    private function refreshOutput()
    {
        if ($this->checkDeps("reaver") && $this->checkDeps("bully")) {
            if ($this->checkRunning("reaver") || $this->checkRunning("bully")) {
                $path = "/pineapple/modules/wps/log";

                $latest_ctime = 0;
                $latest_filename = '';

                $d = dir($path);
                while (false !== ($entry = $d->read())) {
                    $filepath = "{$path}/{$entry}";
                    if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
                        $latest_ctime = filectime($filepath);
                        $latest_filename = $entry;
                    }
                }

                if ($latest_filename != "") {
                    $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/wps/log/".$latest_filename));

                    $cmd = "cat /pineapple/modules/wps/log/".$latest_filename;

                    exec($cmd, $output);
                    if (!empty($output)) {
                        $this->response = implode("\n", array_reverse($output));
                    } else {
                        $this->response = "Empty log...";
                    }
                }
            } else {
                $this->response = "wps is not running...";
            }
        } else {
            $this->response = "wps is not installed...";
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

    private function scanForNetworks()
    {
        if ($this->request->duration && $this->request->monitor != "") {
            exec("killall -9 airodump-ng && rm -rf /tmp/wps-*");
            $this->execBackground("airodump-ng -a --output-format cap -w /tmp/wps ".$this->request->monitor." &> /dev/null");
            sleep($this->request->duration);
            exec("wash -f /tmp/wps-01.cap -o /tmp/wps-01.wash &> /dev/null");

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

            if (exec("cat /tmp/wps_capture.lock") == $apData["Address"]) {
                $accessPoint['captureOnSelected'] = 1;
            } else {
                $accessPoint['captureOnSelected'] = 0;
            }

            if ($this->checkRunning("airodump-ng")) {
                $accessPoint['captureRunning'] = 1;
            } else {
                $accessPoint['captureRunning'] = 0;
            }

            if (exec("cat /tmp/wps_deauth.lock") == $apData["Address"]) {
                $accessPoint['deauthOnSelected'] = 1;
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

            if ($this->request->duration && $this->request->monitor != "") {
                $wps_enabled = trim(exec("cat /tmp/wps-01.wash | tail -n +3 | grep ".$accessPoint['mac']." | awk '{ print $5; }'"));
                if ($wps_enabled == "No" || $wps_enabled == "Yes") {
                    $accessPoint['wps'] = "Yes";
                    $accessPoint['wpsLabel'] = "success";
                } else {
                    $accessPoint['wps'] = "No";
                    $accessPoint['wpsLabel'] = "";
                }
            } else {
                $accessPoint['wps'] = "--";
            }

            array_push($returnArray, $accessPoint);
        }

        exec("rm -rf /tmp/wps-*");

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
        if (file_exists("/tmp/wps.run") && ($this->checkRunning("reaver") || $this->checkRunning("bully"))) {
            $args = $this->parse_args(file_get_contents("/tmp/wps.run"));

            $process['ssid'] = $args["e"];
            $process['mac'] = $args["b"];
            $process['channel'] = $args["c"];

            if ($args["reaver"]) {
                $process['name'] = "reaver";
            } elseif ($args["bully"]) {
                $process['name'] = "bully";
            }

            array_push($returnArray, $process);
        }

        $this->response = $returnArray;
    }

    private function parse_args($args)
    {
        if (is_string($args)) {
            $args = str_replace(array('=', "\'", '\"'), array('= ', '&#39;', '&#34;'), $args);
            $args = str_getcsv($args, ' ', '"');
            $tmp = array();
            foreach ($args as $arg) {
                if (!empty($arg) && $arg != "&#39;" && $arg != "=" && $arg != " ") {
                    $tmp[] = str_replace(array('= ', '&#39;', '&#34;'), array('=', "'", '"'), trim($arg));
                }
            }
            $args = $tmp;
        }

        $out = array();
        $args_size = count($args);
        for ($i = 0; $i < $args_size; $i++) {
            $value = false;
            if (substr($args[$i], 0, 2) == '--') {
                $key = rtrim(substr($args[$i], 2), '=');
                $out[$key] = true;
            } elseif (substr($args[$i], 0, 1) == '-') {
                $key = rtrim(substr($args[$i], 1), '=');

                $opt = str_split($key);
                $opt_size = count($opt);
                if ($opt_size > 1) {
                    for ($n=0; $n < $opt_size; $n++) {
                        $key = $opt[$n];
                        $out[$key] = true;
                    }
                }
            } else {
                $value = $args[$i];
            }

            if (isset($key)) {
                if (isset($out[$key])) {
                    if (is_bool($out[$key])) {
                        $out[$key] = $value;
                    } else {
                        $out[$key] = trim($out[$key].' '.$value);
                    }
                } else {
                    $out[$key] = $value;
                }
            } elseif ($value) {
                $out[$value] = true;
            }
        }
        return $out;
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/wps/log/*"));

            echo '[';
            for ($i=0;$i<count($log_list);$i++) {
                $info = explode("_", basename($log_list[$i]));
                $entryDate = gmdate('Y-m-d H-i-s', $info[1]);
                $entryName = basename($log_list[$i]);

                echo json_encode(array($entryDate, $entryName));

                if ($i!=count($log_list)-1) {
                    echo ',';
                }
            }
            echo ']';
        };
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/wps/log/".$this->request->file));
    }

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/wps/log/".$this->request->file));
        exec("strings /pineapple/modules/wps/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        exec("rm -rf /pineapple/modules/wps/log/".$this->request->file);
    }
}
