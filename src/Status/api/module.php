<?php namespace pineapple;



class Status extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'refreshInfo':
                $this->refreshInfo();
                break;
            case 'getSystem':
                $this->getSystem();
                break;
            case 'getCPU':
                $this->getCPU();
                break;
            case 'getDHCP':
                $this->getDHCP();
                break;
            case 'getMemory':
                $this->getMemory();
                break;
            case 'getWiFi':
                $this->getWiFi();
                break;
            case 'getSwap':
                $this->getSwap();
                break;
            case 'getStorage':
                $this->getStorage();
                break;
            case 'getInterfaces':
                $this->getInterfaces();
                break;
            case 'getMACInfo':
                $this->getMACInfo();
                break;
            case 'getPingInfo':
                $this->getPingInfo();
                break;
        }
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/Status/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

    private function getSystem()
    {
        $current_time = exec("date");
        $up_time = exec("uptime | awk -F, '{sub(\".*up \",x,$1);print $1}'");
        $hostname = exec("uci get system.@system[0].hostname");
        $machine = $cpu = trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));

        $info = array(
                    'currentTime' =>  $current_time,
                    'uptime' =>  $up_time,
                    'hostname' =>  $hostname,
                    'machine' => $machine
                    );

        $this->response = array('info' => $info);
    }

    private function getCPU()
    {
        $cpu = trim(exec("cat /proc/cpuinfo | grep cpu | awk -F: '{print $2}'"));
        $bogo = trim(exec("cat /proc/cpuinfo | grep Bogo | awk -F: '{print $2}'"));
        $type = trim(exec("cat /proc/cpuinfo | grep type | awk -F: '{print $2}'"));

        $stat1 = $this->getCoreInformation();
        sleep(1);
        $stat2 = $this->getCoreInformation();
        $data = $this->getCpuPercentages($stat1, $stat2);
        $cpu_load_ptg = 100 - $data['cpu0']['idle'];
        $cpu_load_all = exec("uptime | awk -F 'average:' '{ print $2}'");

        $info = array(
                    'cpuModel' =>  $cpu,
                    'bogoMIPS' =>  $bogo,
                    'type' => $type,
                    'loadAveragePourcentage' =>  $cpu_load_ptg,
                    'loadAverageAll' =>  $cpu_load_all
                    );

        $this->response = array('info' => $info);
    }

    private function getDHCP()
    {
        $dhcpClients = explode("\n", trim(shell_exec("cat /tmp/dhcp.leases")));
        $clientsList = array();
        for ($i=0;$i<count($dhcpClients);$i++) {
            if ($dhcpClients[$i] != "") {
                $dhcp_client = explode(" ", $dhcpClients[$i]);
                $mac_address = $dhcp_client[1];
                $ip_address = $dhcp_client[2];
                $hostname = $dhcp_client[3];

                array_push($clientsList, array("hostname" => $hostname, "mac" => $mac_address, "ip" =>$ip_address));
            }
        }

        $info = array(
                    'clientsList' =>  $clientsList
                    );

        $this->response = array('info' => $info);
    }

    private function getMemory()
    {
        $mem_total = exec("free | grep \"Mem:\" | awk '{ print $2 }'");
        $mem_used = exec("free | grep \"Mem:\" | awk '{ print $3 }'");
        $mem_free = exec("free | grep \"Mem:\" | awk '{ print $4 }'");

        $mem_free_ptg = round(($mem_free / $mem_total) * 100);
        $mem_used_ptg = 100 - $mem_free_ptg;

        $mem_total = $this->kbytesToString($mem_total);
        $mem_used = $this->kbytesToString($mem_used);
        $mem_free = $this->kbytesToString($mem_free);

        $info = array(
                    'memoryTotal' =>  $mem_total,
                    'memoryFree' =>  $mem_free,
                    'memoryFreePourcentage' =>  $mem_free_ptg,
                    'memoryUsed' =>  $mem_used,
                    'memoryUsedPourcentage' =>  $mem_used_ptg
                    );

        $this->response = array('info' => $info);
    }

    private function getWiFi()
    {
        $wifiClients = explode("\n", trim(shell_exec("iw dev wlan0 station dump | grep \"Station\"")));
        $wifiClientsList = array();
        for ($i=0;$i<count($wifiClients);$i++) {
            if ($wifiClients[$i] != "") {
                $wifi_client = explode(" ", $wifiClients[$i]);
                $mac_address = $wifi_client[1];
                $ip_address = exec("cat /tmp/dhcp.leases | grep \"".$mac_address."\" | awk '{ print $3}'");
                $hostname = exec("cat /tmp/dhcp.leases | grep \"".$mac_address."\" | awk '{ print $4}'");

                array_push($wifiClientsList, array("hostname" => $hostname, "mac" => $mac_address, "ip" =>$ip_address));
            }
        }

        $info = array(
                    'wifiClientsList' =>  $wifiClientsList
                    );

        $this->response = array('info' => $info);
    }

    private function getSwap()
    {
        $swap_total = exec("free | grep \"Swap:\" | awk '{ print $2 }'");
        $swap_used = exec("free | grep \"Swap:\" | awk '{ print $3 }'");
        $swap_free = exec("free | grep \"Swap:\" | awk '{ print $4 }'");

        if ($swap_total != 0) {
            $swap_available = true;
        } else {
            $swap_available = false;
        }

        if ($swap_available) {
            $swap_free_ptg = round(($swap_free / $swap_total) * 100);
        } else {
            $swap_free_ptg = 0;
        }
        $swap_used_ptg = 100 - $swap_free_ptg;

        $swap_total = $this->kbytesToString($swap_total);
        $swap_used = $this->kbytesToString($swap_used);
        $swap_free = $this->kbytesToString($swap_free);

        $info = array(
                    'swapAvailable' => $swap_available,
                    'swapTotal' =>  $swap_total,
                    'swapFree' =>  $swap_free,
                    'swapFreePourcentage' =>  $swap_free_ptg,
                    'swapUsed' =>  $swap_used,
                    'swapUsedPourcentage' =>  $swap_used_ptg
                    );

        $this->response = array('info' => $info);
    }

    private function getStorage()
    {
        $dfAll = explode("\n", trim(shell_exec("df | grep -v \"Filesystem\"")));
        $dfList = array();
        for ($i=0;$i<count($dfAll);$i++) {
            $df_name = exec("df | grep -v \"Filesystem\" | grep \"".$dfAll[$i]."\" | awk '{ print $1}'");
            $df_mount = exec("df | grep -v \"Filesystem\" | grep \"".$dfAll[$i]."\" | awk '{ print $6}'");
            $df_total = $this->kbytesToString(exec("df | grep -v \"Filesystem\" | grep \"".$dfAll[$i]."\" | awk '{ print $2}'"));
            $df_used = $this->kbytesToString(exec("df | grep -v \"Filesystem\" | grep \"".$dfAll[$i]."\" | awk '{ print $3}'"));
            $df_used_ptg = exec("df | grep -v \"Filesystem\" | grep \"".$dfAll[$i]."\" | awk '{ print $5}'");

            array_push($dfList, array("name" => $df_name, "mount" => $df_mount, "usedPourcentage" =>$df_used_ptg, "used" => $df_used, "total" => $df_total));
        }

        $info = array(
                    'storagesList' => $dfList
                    );

        $this->response = array('info' => $info);
    }

    private function getInterfaces()
    {
        $interfaces = explode("\n", trim(shell_exec("ifconfig | grep  'encap:Ethernet'  | cut -d' ' -f1")));
        $interfacesList = array();
        for ($i=0;$i<count($interfaces);$i++) {
            $interface_name = $interfaces[$i];

            $mac_address = exec("ifconfig ".$interfaces[$i]." | grep 'HWaddr' | awk '{ print $5}'");
            $mac_address = $mac_address != "" ? $mac_address : "-";
            $ip_address = exec("ifconfig ".$interfaces[$i]." | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'");
            $ip_address = $ip_address != "" ? $ip_address : "-";
            $subnet_mask = exec("ifconfig ".$interfaces[$i]." | grep 'inet addr:' | cut -d: -f4 | awk '{ print $1}'");
            $subnet_mask = $subnet_mask != "" ? $subnet_mask : "-";
            $gateway = exec("netstat -r | grep 'default' | grep ".$interfaces[$i]." | awk '{ print $2}'");
            $gateway = $gateway != "" ? $gateway : "-";

            $mode = exec("iwconfig ".$interfaces[$i]." | grep 'Mode:' | cut -d: -f2 | awk '{ print $1}'");
            $tx_power = exec("iwconfig ".$interfaces[$i]." | grep 'Tx-Power=' | cut -d= -f2");

            array_push($interfacesList, array("name" => $interface_name, "mac" => $mac_address, "ip" =>$ip_address, "subnet" => $subnet_mask, "gateway" => $gateway, "mode" => $mode, "txpower" => $tx_power));
        }

        $wan = @file_get_contents("http://cloud.wifipineapple.com/ip.php");
        $wan = $wan != "" ? $wan : "-";
        $gateway = exec("netstat -r | grep 'default' | awk '{ print $2}'");
        $gateway = $gateway != "" ? $gateway : "-";
        $dnsAll = explode("\n", trim(shell_exec("cat /tmp/resolv.conf.auto | grep nameserver | awk '{ print $2}'")));
        $dnsList = array();
        for ($i=0;$i<count($dnsAll);$i++) {
            array_push($dnsList, array("name" => "DNS ".($i+1), "ip" => $dnsAll[$i]));
        }

        $info = array(
                    'wanIpAddress' =>  $wan,
                    'wanGateway' =>  $gateway,
                    'dnsList' =>  $dnsList,
                    'interfacesList' => $interfacesList
                    );

        $this->response = array('info' => $info);
    }

    private function getMACInfo()
    {
        $content = file_get_contents("https://api.macvendors.com/".$this->request->mac);
        $this->response = array('title' => $this->request->mac, "output" => $content);
    }

    private function getPingInfo()
    {
        exec("ping -c4 ".$this->request->ip, $output);
        $this->response = array('title' => $this->request->ip, "output" => implode("\n", array_reverse($output)));
    }

    private function kbytesToString($kb)
    {
        $units = array('TB','GB','MB','KB');
        $scale = 1024*1024*1024;
        $ui = 0;

        while (($kb < $scale) && ($scale > 1)) {
            $ui++;
            $scale = $scale / 1024;
        }
        return sprintf("%0.2f %s", ($kb/$scale), $units[$ui]);
    }

    private function getCoreInformation()
    {
        $data = file('/proc/stat');
        $cores = array();

        foreach ($data as $line) {
            if (preg_match('/^cpu[0-9]/', $line)) {
                $info = explode(' ', $line);
                $cores[] = array(
                    'user' => $info[1],
                    'nice' => $info[2],
                    'sys' => $info[3],
                    'idle' => $info[4]
                );
            }
        }

        return $cores;
    }

    private function getCpuPercentages($stat1, $stat2)
    {
        if (count($stat1) !== count($stat2)) {
            return;
        }

        $cpus = array();

        for ($i = 0, $l = count($stat1); $i < $l; $i++) {
            $dif = array();
            $dif['user'] = $stat2[$i]['user'] - $stat1[$i]['user'];
            $dif['nice'] = $stat2[$i]['nice'] - $stat1[$i]['nice'];
            $dif['sys'] = $stat2[$i]['sys'] - $stat1[$i]['sys'];
            $dif['idle'] = $stat2[$i]['idle'] - $stat1[$i]['idle'];
            $total = array_sum($dif);
            $cpu = array();

            foreach ($dif as $x=>$y) {
                $cpu[$x] = round($y / $total * 100, 1);
            }
            $cpus['cpu' . $i] = $cpu;
        }

        return $cpus;
    }
}
