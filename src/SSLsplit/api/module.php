<?php namespace pineapple;



class SSLsplit extends Module
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
            case 'toggleSSLsplit':
                $this->toggleSSLsplit();
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
            case 'toggleSSLsplitOnBoot':
                $this->toggleSSLsplitOnBoot();
                break;
            case 'handleCertificate':
                $this->handleCertificate();
                break;
            case 'handleCertificateStatus':
                $this->handleCertificateStatus();
                break;
            case 'saveConfigurationData':
                $this->saveConfigurationData();
                break;
            case 'getConfigurationData':
                $this->getConfigurationData();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("sslsplit.module.installed")));
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/SSLsplit/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleCertificate()
    {
        if (!file_exists("/pineapple/modules/SSLsplit/cert/certificate.crt")) {
            $this->execBackground("/pineapple/modules/SSLsplit/scripts/generate_certificate.sh");
            $this->response = array('success' => true);
        } else {
            exec("rm -rf /pineapple/modules/SSLsplit/cert/certificate.*");
            $this->response = array('success' => true);
        }
    }

    private function handleCertificateStatus()
    {
        if (!file_exists('/tmp/SSLsplit_certificate.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("sslsplit")) {
            $this->execBackground("/pineapple/modules/SSLsplit/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/SSLsplit/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/SSLsplit.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggleSSLsplitOnBoot()
    {
        if (exec("cat /etc/rc.local | grep SSLsplit/scripts/autostart_sslsplit.sh") == "") {
            exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/SSLsplit/scripts/autostart_sslsplit.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");
        } else {
            exec("sed -i '/SSLsplit\/scripts\/autostart_sslsplit.sh/d' /etc/rc.local");
        }
    }

    private function toggleSSLsplit()
    {
        if (!$this->checkRunning("sslsplit")) {
            $this->execBackground("/pineapple/modules/SSLsplit/scripts/sslsplit.sh start");
        } else {
            $this->execBackground("/pineapple/modules/SSLsplit/scripts/sslsplit.sh stop");
        }
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/SSLsplit.progress')) {
            if (!$this->checkDeps("sslsplit")) {
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

                if ($this->checkRunning("sslsplit")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }

                if (exec("cat /etc/rc.local | grep SSLsplit/scripts/autostart_sslsplit.sh") == "") {
                    $bootLabelON = "default";
                    $bootLabelOFF = "danger";
                } else {
                    $bootLabelON = "success";
                    $bootLabelOFF = "default";
                }
            }

            if (!file_exists('/tmp/SSLsplit_certificate.progress')) {
                if (!file_exists("/pineapple/modules/SSLsplit/cert/certificate.crt")) {
                    $certificate = "Not generated";
                    $certificateLabel = "danger";
                    $generated = false;
                    $generating = false;
                } else {
                    $certificate = "Generated";
                    $certificateLabel = "success";
                    $generated = true;
                    $generating = false;
                }
            } else {
                $certificate = "Generating...";
                $certificateLabel = "warning";
                $generated = false;
                $generating = true;
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

            $certificate = "Not generated";
            $certificateLabel = "danger";
            $generating = false;
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed,
                                "certificate" => $certificate, "certificateLabel" => $certificateLabel, "generating" => $generating, "generated" => $generated,
                                "install" => $install, "installLabel" => $installLabel,
                                "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if ($this->checkDeps("sslsplit")) {
            if ($this->checkRunning("sslsplit")) {
                if (file_exists("/pineapple/modules/SSLsplit/connections.log")) {
                    if ($this->request->filter != "") {
                        $filter = $this->request->filter;

                        $cmd = "cat /pineapple/modules/SSLsplit/connections.log"." | ".$filter;
                    } else {
                        $cmd = "cat /pineapple/modules/SSLsplit/connections.log";
                    }

                    exec($cmd, $output);
                    if (!empty($output)) {
                        $this->response = implode("\n", array_reverse($output));
                    } else {
                        $this->response = "Empty connections log...";
                    }
                } else {
                    $this->response =  "No connections log...";
                }
            } else {
                $this->response = "SSLsplit is not running...";
            }
        } else {
            $this->response = "SSLsplit is not installed...";
        }
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/SSLsplit/log/*"));

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

    private function viewHistory()
    {
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/SSLsplit/log/".$this->request->file));
        exec("cat /pineapple/modules/SSLsplit/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        exec("rm -rf /pineapple/modules/SSLsplit/log/".$this->request->file);
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/SSLsplit/log/".$this->request->file));
    }

    private function saveConfigurationData()
    {
        $filename = '/pineapple/modules/SSLsplit/rules/iptables';
        file_put_contents($filename, $this->request->configurationData);
    }

    private function getConfigurationData()
    {
        $configurationData = file_get_contents('/pineapple/modules/SSLsplit/rules/iptables');
        $this->response = array("configurationData" => $configurationData);
    }
}
