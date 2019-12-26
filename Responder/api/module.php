<?php namespace pineapple;

class Responder extends Module
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
            case 'toggleResponder':
                $this->toggleResponder();
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
            case 'toggleResponderOnBoot':
                $this->toggleResponderOnBoot();
                break;
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'saveAutostartSettings':
                $this->saveAutostartSettings();
                break;
            case 'getSettings':
                $this->getSettings();
                break;
            case 'setSettings':
                $this->setSettings();
                break;
        }
    }

    protected function checkDeps($dependencyName)
    {
        return ($this->checkDependency($dependencyName) && ($this->uciGet("responder.module.installed")));
    }

    protected function checkRunning($processName)
    {
        return exec("ps w | grep {$processName} | grep -v grep") !== '' ? 1 : 0;
    }

    protected function getDevice()
    {
        return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/Responder/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function handleDependencies()
    {
        if (!$this->checkDeps("python")) {
            $this->execBackground("/pineapple/modules/Responder/scripts/dependencies.sh install ".$this->request->destination);
            $this->response = array('success' => true);
        } else {
            $this->execBackground("/pineapple/modules/Responder/scripts/dependencies.sh remove");
            $this->response = array('success' => true);
        }
    }

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/Responder.progress')) {
            $this->response = array('success' => true);
        } else {
            $this->response = array('success' => false);
        }
    }

    private function toggleResponderOnBoot()
    {
        if (exec("cat /etc/rc.local | grep Responder/scripts/autostart_responder.sh") == "") {
            exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/Responder/scripts/autostart_responder.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");
        } else {
            exec("sed -i '/Responder\/scripts\/autostart_responder.sh/d' /etc/rc.local");
        }
    }

    private function toggleResponder()
    {
        if (!$this->checkRunning("Responder.py")) {
            $this->uciSet("responder.run.interface", $this->request->interface);

            $this->execBackground("/pineapple/modules/Responder/scripts/responder.sh start");
        } else {
            $this->uciSet("responder.run.interface", '');

            $this->execBackground("/pineapple/modules/Responder/scripts/responder.sh stop");
        }
    }

    private function getInterfaces()
    {
        exec("cat /proc/net/dev | tail -n +3 | cut -f1 -d: | sed 's/ //g'", $interfaceArray);

        $this->response = array("interfaces" => $interfaceArray, "selected" => $this->uciGet("responder.run.interface"));
    }

    private function refreshStatus()
    {
        if (!file_exists('/tmp/Responder.progress')) {
            if (!$this->checkDeps("python")) {
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

                if ($this->checkRunning("Responder.py")) {
                    $status = "Stop";
                    $statusLabel = "danger";
                } else {
                    $status = "Start";
                    $statusLabel = "success";
                }

                if (exec("cat /etc/rc.local | grep Responder/scripts/autostart_responder.sh") == "") {
                    $bootLabelON = "default";
                    $bootLabelOFF = "danger";
                } else {
                    $bootLabelON = "success";
                    $bootLabelOFF = "default";
                }
            }
        } else {
            $installed = false;
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $status = "Not running";
            $statusLabel = "danger";
            $verbose = false;

            $bootLabelON = "default";
            $bootLabelOFF = "danger";
        }

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
    }

    private function refreshOutput()
    {
        if ($this->checkDeps("python")) {
            if ($this->checkRunning("Responder.py")) {
                if (file_exists("/pineapple/modules/Responder/dep/responder/logs/Responder-Session.log")) {
                    if ($this->request->filter != "") {
                        $filter = $this->request->filter;

                        $cmd = "strings /pineapple/modules/Responder/dep/responder/logs/Responder-Session.log | ".$filter;
                    } else {
                        $cmd = "strings /pineapple/modules/Responder/dep/responder/logs/Responder-Session.log";
                    }

                    exec($cmd, $output);
                    if (!empty($output)) {
                        $this->response = implode("\n", array_reverse($output));
                    } else {
                        $this->response = "Empty log...";
                    }
                } else {
                    $this->response = "Empty log...";
                }
            } else {
                $this->response = "Responder is not running...";
            }
        } else {
            $this->response = "Responder is not installed...";
        }
    }

    private function refreshHistory()
    {
        $this->streamFunction = function () {
            $log_list = array_reverse(glob("/pineapple/modules/Responder/log/*"));

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
        $log_date = gmdate("F d Y H:i:s", filemtime("/pineapple/modules/Responder/log/".$this->request->file));
        exec("strings /pineapple/modules/Responder/log/".$this->request->file, $output);

        if (!empty($output)) {
            $this->response = array("output" => implode("\n", $output), "date" => $log_date);
        } else {
            $this->response = array("output" => "Empty log...", "date" => $log_date);
        }
    }

    private function deleteHistory()
    {
        exec("rm -rf /pineapple/modules/Responder/log/".$this->request->file);
    }

    private function downloadHistory()
    {
        $this->response = array("download" => $this->downloadFile("/pineapple/modules/Responder/log/".$this->request->file));
    }

    private function saveAutostartSettings()
    {
        $settings = $this->request->settings;
        $this->uciSet("responder.autostart.interface", $settings->interface);
    }

    private function getSettings()
    {
        $settings = array(
                    'SQL' => $this->uciGet("responder.settings.SQL"),
                    'SMB' => $this->uciGet("responder.settings.SMB"),
                    'Kerberos' => $this->uciGet("responder.settings.Kerberos"),
                    'FTP' => $this->uciGet("responder.settings.FTP"),
                    'POP' => $this->uciGet("responder.settings.POP"),
                    'SMTP' => $this->uciGet("responder.settings.SMTP"),
                    'IMAP' => $this->uciGet("responder.settings.IMAP"),
                    'HTTP' => $this->uciGet("responder.settings.HTTP"),
                    'HTTPS' => $this->uciGet("responder.settings.HTTPS"),
                    'DNS' => $this->uciGet("responder.settings.DNS"),
                    'LDAP' => $this->uciGet("responder.settings.LDAP"),
                    'basic' => $this->uciGet("responder.settings.basic"),
                    'wredir' => $this->uciGet("responder.settings.wredir"),
                    'NBTNS' => $this->uciGet("responder.settings.NBTNS"),
                    'fingerprint' => $this->uciGet("responder.settings.fingerprint"),
                    'wpad' => $this->uciGet("responder.settings.wpad"),
                    'forceWpadAuth' => $this->uciGet("responder.settings.forceWpadAuth"),
                    'proxyAuth' => $this->uciGet("responder.settings.proxyAuth"),
                    'forceLmDowngrade' => $this->uciGet("responder.settings.forceLmDowngrade"),
                    'verbose' => $this->uciGet("responder.settings.verbose"),
                    'analyse' => $this->uciGet("responder.settings.analyse")
                    );
        $this->response = array('settings' => $settings);
    }

    private function setSettings()
    {
        $settings = $this->request->settings;
        if ($settings->SQL) {
            $this->updateSetting("SQL", 1);
        } else {
            $this->updateSetting("SQL", 0);
        }
        if ($settings->SMB) {
            $this->updateSetting("SMB", 1);
        } else {
            $this->updateSetting("SMB", 0);
        }
        if ($settings->Kerberos) {
            $this->updateSetting("Kerberos", 1);
        } else {
            $this->updateSetting("Kerberos", 0);
        }
        if ($settings->FTP) {
            $this->updateSetting("FTP", 1);
        } else {
            $this->updateSetting("FTP", 0);
        }
        if ($settings->POP) {
            $this->updateSetting("POP", 1);
        } else {
            $this->updateSetting("POP", 0);
        }
        if ($settings->SMTP) {
            $this->updateSetting("SMTP", 1);
        } else {
            $this->updateSetting("SMTP", 0);
        }
        if ($settings->IMAP) {
            $this->updateSetting("IMAP", 1);
        } else {
            $this->updateSetting("IMAP", 0);
        }
        if ($settings->HTTP) {
            $this->updateSetting("HTTP", 1);
        } else {
            $this->updateSetting("HTTP", 0);
        }
        if ($settings->HTTPS) {
            $this->updateSetting("HTTPS", 1);
        } else {
            $this->updateSetting("HTTPS", 0);
        }
        if ($settings->DNS) {
            $this->updateSetting("DNS", 1);
        } else {
            $this->updateSetting("DNS", 0);
        }
        if ($settings->LDAP) {
            $this->updateSetting("LDAP", 1);
        } else {
            $this->updateSetting("LDAP", 0);
        }

        if ($settings->basic) {
            $this->uciSet("responder.settings.basic", 1);
        } else {
            $this->uciSet("responder.settings.basic", 0);
        }
        if ($settings->wredir) {
            $this->uciSet("responder.settings.wredir", 1);
        } else {
            $this->uciSet("responder.settings.wredir", 0);
        }
        if ($settings->NBTNS) {
            $this->uciSet("responder.settings.NBTNS", 1);
        } else {
            $this->uciSet("responder.settings.NBTNS", 0);
        }
        if ($settings->fingerprint) {
            $this->uciSet("responder.settings.fingerprint", 1);
        } else {
            $this->uciSet("responder.settings.fingerprint", 0);
        }
        if ($settings->wpad) {
            $this->uciSet("responder.settings.wpad", 1);
        } else {
            $this->uciSet("responder.settings.wpad", 0);
        }
        if ($settings->forceWpadAuth) {
            $this->uciSet("responder.settings.forceWpadAuth", 1);
        } else {
            $this->uciSet("responder.settings.forceWpadAuth", 0);
        }
        if ($settings->proxyAuth) {
            $this->uciSet("responder.settings.proxyAuth", 1);
        } else {
            $this->uciSet("responder.settings.proxyAuth", 0);
        }
        if ($settings->forceLmDowngrade) {
            $this->uciSet("responder.settings.forceLmDowngrade", 1);
        } else {
            $this->uciSet("responder.settings.forceLmDowngrade", 0);
        }
        if ($settings->verbose) {
            $this->uciSet("responder.settings.verbose", 1);
        } else {
            $this->uciSet("responder.settings.verbose", 0);
        }
        if ($settings->analyse) {
            $this->uciSet("responder.settings.analyse", 1);
        } else {
            $this->uciSet("responder.settings.analyse", 0);
        }
    }

    private function updateSetting($setting, $value)
    {
        if ($value) {
            $this->uciSet("responder.settings.".$setting, 1);
            exec("/bin/sed -i 's/^".$setting." .*/".$setting." = On/g' /pineapple/modules/Responder/dep/responder/Responder.conf");
        } else {
            $this->uciSet("responder.settings.".$setting, 0);
            exec("/bin/sed -i 's/^".$setting." .*/".$setting." = Off/g' /pineapple/modules/Responder/dep/responder/Responder.conf");
        }
    }
}
