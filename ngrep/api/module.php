<?php namespace pineapple;

class ngrep extends Module
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
            case 'togglengrep':
                $this->togglengrep();
                break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
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
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'getProfiles':
                $this->getProfiles();
                break;
            case 'showProfile':
                $this->showProfile();
                break;
            case 'deleteProfile':
                $this->deleteProfile();
                break;
            case 'saveProfileData':
                $this->saveProfileData();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("ngrep.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/ngrep/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("ngrep")) {
            $this->execBackground("/pineapple/modules/ngrep/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/ngrep/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/ngrep.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function togglengrep()
    {
        if (!$this->checkRunning("ngrep")) {
            $full_cmd = $this->request->command . " -O /pineapple/modules/ngrep/log/log_".time().".pcap >> /pineapple/modules/ngrep/log/log_".time().".log";
            shell_exec("echo -e \"{$full_cmd}\" > /tmp/ngrep.run");

            $this->execBackground("/pineapple/modules/ngrep/scripts/ngrep.sh start");
        } else {
            $this->execBackground("/pineapple/modules/ngrep/scripts/ngrep.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/ngrep.progress')) {
            if (!$this->checkDeps("ngrep")) {
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

                if ($this->checkRunning("ngrep")) {
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

    private function refreshOutput()
    {
        if ($this->checkDeps("ngrep")) {
            if ($this->checkRunning("ngrep")) {
                $path = "/pineapple/modules/ngrep/log";

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
                    $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/ngrep/log/".$latest_filename));

                    if ($this->request->filter != "") {
                        $filter = $this->request->filter;

                        $cmd = "cat /pineapple/modules/ngrep/log/".$latest_filename." | ".$filter;
                    } else {
                        $cmd = "cat /pineapple/modules/ngrep/log/".$latest_filename;
                    }

                    exec($cmd, $output);
                    if (!empty($output)) {
                        $this->response = implode("\n", array_reverse($output));
                    } else {
                        $this->response = "Empty log...";
                    }
                }
            } else {
                $this->response = "ngrep is not running...";
            }
        } else {
            $this->response = "ngrep is not installed...";
        }
    }

    private function getInterfaces()
    {
        $this->response = array();
        exec("cat /proc/net/dev | tail -n +3 | cut -f1 -d: | sed 's/ //g'", $interfaceArray);

        foreach ($interfaceArray as $interface) {
            array_push($this->response, $interface);
        }
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/ngrep/log/*.pcap"));

            echo '[';
            for ($i=0;$i<count($log_list);$i++) {
                $info = explode("_", basename($log_list[$i]));
                $entryDate = gmdate('Y-m-d H-i-s', $info[1]);
                $entryName = basename($log_list[$i], ".pcap");

                echo json_encode(array($entryDate, $entryName.".log", $entryName.".pcap"));

                if ($i!=count($log_list)-1) {
                    echo ',';
                }
            }
            echo ']';
        };
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/ngrep/log/".$this->request->file));
    }

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/ngrep/log/".$this->request->file));
        exec("strings /pineapple/modules/ngrep/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        $file = basename($this->request->file, ".pcap");
        exec("rm -rf /pineapple/modules/ngrep/log/".$file.".*");
    }

    private function getProfiles()
    {
        $this->response = array();
        $profileList = array_reverse(glob("/pineapple/modules/ngrep/profiles/*"));
        array_push($this->response, array("text" => "--", "value" => "--"));
        foreach ($profileList as $profile) {
            $profileData = file_get_contents('/pineapple/modules/ngrep/profiles/'.basename($profile));
            array_push($this->response, array("text" => basename($profile), "value" => $profileData));
        }
    }

    private function showProfile()
    {
        $profileData = file_get_contents('/pineapple/modules/ngrep/profiles/'.$this->request->profile);
        $this->response = array("profileData" => $profileData);
    }

    private function deleteProfile()
    {
        exec("rm -rf /pineapple/modules/ngrep/profiles/".$this->request->profile);
    }

    private function saveProfileData()
    {
        $filename = "/pineapple/modules/ngrep/profiles/".$this->request->profile;
        file_put_contents($filename, $this->request->profileData);
    }
}
