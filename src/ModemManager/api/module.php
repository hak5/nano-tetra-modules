<?php namespace pineapple;

/***
Modem Manager <api/module.php>
Written by Foxtrot <foxtrot@realloc.me>
Distributed under the MIT Licence <https://opensource.org/licenses/MIT>
***/

class ModemManager extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'checkDepends':
                $this->checkDepends();
                break;

            case 'installDepends':
                $this->installDepends();
                break;

            case 'removeDepends':
                $this->removeDepends();
                break;

            case 'getUSB':
                $this->getUSB();
                break;

            case 'getTTYs':
                $this->getTTYs();
                break;

            case 'checkConnection':
                $this->checkConnection();
                break;

            case 'setConnection':
                $this->setConnection();
                break;

            case 'unsetConnection':
                $this->unsetConnection();
                break;

            case 'loadConfiguration':
                $this->loadConfiguration();
                break;

            case 'saveConfiguration':
                $this->saveConfiguration();
                break;

            case 'resetConfiguration':
                $this->resetConfiguration();
                break;
        }
    }

    private function checkDepends()
    {
        /* Check dependencies */
        if(empty($this->checkDependency('comgt'))) {
            $this->response = array('installed' => false);
        } else {
            $this->response = array('installed' => true);
        }
    }

    private function installDepends()
    {
        /* Install dependencies */
        $this->execBackground('opkg update && opkg install comgt wwan uqmi');    
        $this->response = array("installing" => true);

    }

    private function removeDepends()
    {
        /* Remove dependencies */
        $this->execBackground('opkg remove comgt wwan uqmi');
        $this->response = array('success' => true);
    }

    private function getUSB()
    {
        /* Execute 'lsusb' and capture its output in the $lsusb variable.
           Then split the output by its newlines. */
        exec('lsusb', $lsusb);
        $lsusb = implode("\n", $lsusb);

        $this->response = array('lsusb' => $lsusb);
    }

    private function getTTYs()
    {
        exec('ls /dev/ttyUSB* && ls /dev/cdc-wdm* && ls /dev/ttyACM*', $TTYs);

        if (empty($TTYs)) {
            $this->response = array('success' => false,
                                    'availableTTYs' => false);
        } else {
            $TTYs = implode("\n", $TTYs);
            $this->response = array('success' => true,
                                    'availableTTYs' => $TTYs);
        }
    }

    private function checkConnection()
    {
        /* Check the connection of the wan2 interface. */
        if(file_exists('/sys/class/net/3g-wan2/carrier')) {
            $this->response = array('status' => 'connected');
            exec('iptables -t nat -A POSTROUTING -s 172.16.42.0/24 -o 3g-wan2 -j MASQUERADE');
            exec('iptables -A FORWARD -s 172.16.42.0/24 -o 3g-wan2 -j ACCEPT');
            exec('iptables -A FORWARD -d 172.16.42.0/24 -m state --state ESTABLISHED,RELATED -i 3g-wan2 -j ACCEPT');
        } else {
            $this->response = array('status' => 'disconnected');
        }

    }

    private function setConnection()
    {
        /* Set the connection of the wan2 interface. */
        $this->execBackground('ifup wan2');
        $this->response = array('status' => 'connecting');
    }

    private function unsetConnection()
    {
        /* Unset the connection of the wan2 interface. */
        $this->execBackground('ifdown wan2');
        $this->response = array('status' => 'disconnected');
    }

    private function loadConfiguration()
    {
        /* For easier code reading, assign a variable for each bit of information we require from the system.
           Read more about UCI at https://wiki.openwrt.org/doc/uci.
           For more information about the WiFi Pineapple API, visit https://wiki.wifipineapple.com. */
        $interface     = $this->uciGet('network.wan2.ifname');
        $protocol      = $this->uciGet('network.wan2.proto');
        $service       = $this->uciGet('network.wan2.service');
        $vendorid      = $this->uciGet('network.wan2.currentVID');
        $productid     = $this->uciGet('network.wan2.currentPID');
        $device        = $this->uciGet('network.wan2.device');
        $apn           = $this->uciGet('network.wan2.apn');
        $username      = $this->uciGet('network.wan2.username');
        $password      = $this->uciGet('network.wan2.password');
        $dns           = $this->uciGet('network.wan2.dns');
        $peerdns       = $this->uciGet('network.wan2.peerdns');
        $pppredial     = $this->uciGet('network.wan2.ppp_redial');
        $defaultroute  = $this->uciGet('network.wan2.defaultroute');
        $keepalive     = $this->uciGet('network.wan2.keepalive');
        $pppdoptions   = $this->uciGet('network.wan2.pppd_options');

        /* Now send a response inside of an array, with keys being 'interface', 'protocol' etc
           and their values being those we obtained from uciGet(). */
        $this->response = array('success'      => true,
                                'interface'    => $interface,
                                'protocol'     => $protocol,
                                'service'      => $service,
                                'vendorid'     => $vendorid,
                                'productid'    => $productid,
                                'device'       => $device,
                                'apn'          => $apn,
                                'username'     => $username,
                                'password'     => $password,
                                'dns'          => $dns,
                                'peerdns'      => $peerdns,
                                'pppredial'    => $pppredial,
                                'defaultroute' => $defaultroute,
                                'keepalive'    => $keepalive,
                                'pppdoptions'  => $pppdoptions);
    }

    private function saveConfiguration()
    {
        /* In the same way as loadConfiguration(), get the desired information and assign it to a variable.
           However this time get the data that was sent with the request from the JS. */
        $interface     = $this->request->interface;
        $protocol      = $this->request->protocol;
        $service       = $this->request->service;
        $vendorid      = $this->request->vendorid;
        $productid     = $this->request->productid;
        $device        = $this->request->device;
        $apn           = $this->request->apn;
        $username      = $this->request->username;
        $password      = $this->request->password;
        $dns           = $this->request->dns;
        $peerdns       = $this->request->peerdns;
        $pppredial     = $this->request->pppredial;
        $defaultroute  = $this->request->defaultroute;
        $keepalive     = $this->request->keepalive;
        $pppdoptions   = $this->request->pppdoptions;

        /* Using the APIs uciSet() function, set the UCI properties to
           what the JS request gave us. */
        $this->uciSet('network.wan2',              'interface');
        $this->uciSet('network.wan2.ifname',       $interface);
        $this->uciSet('network.wan2.proto',        $protocol);
        $this->uciSet('network.wan2.service',      $service);
        $this->uciSet('network.wan2.currentVID',   $vendorid);
        $this->uciSet('network.wan2.currentPID',   $productid);
        $this->uciSet('network.wan2.device',       $device);
        $this->uciSet('network.wan2.apn',          $apn);
        $this->uciSet('network.wan2.peerdns',      $peerdns);
        $this->uciSet('network.wan2.ppp_redial',   $pppredial);
        $this->uciSet('network.wan2.defaultroute', $defaultroute);
        $this->uciSet('network.wan2.keepalive',    $keepalive);
        $this->uciSet('network.wan2.pppd_options', $pppdoptions);

        if(!empty($username)) {
            $this->uciSet('network.wan2.username', $username);
        }
        if (!empty($password)) {
            $this->uciSet('network.wan2.password', $password);
        }
        if(!empty($dns)) {
            $this->uciSet('network.wan2.dns', $dns);
        }

        unlink("/etc/modules.d/60-usb-serial");
        exec("echo 'usbserial vendor=0x$vendorid product=0x$productid maxSize=4096' > /etc/modules.d/60-usb-serial");

        $this->response = array('success' => true);
    }

    private function resetConfiguration()
    {
        /* Delete the network.wan2 section */
        exec('uci del network.wan2');
        exec('uci commit');
        unlink('/etc/modules.d/60-usb-serial');

        $this->response = array('success' => true);
    }
}
