<?php namespace pineapple;

class LEDController extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'getDeviceType':
                $this->getDeviceType();
                break;
            case 'resetLEDs':
                $this->resetLEDs();
                break;
            case 'getTetraYellow':
                $this->getTetraYellow();
                break;
            case 'setTetraYellow':
                $this->setTetraYellow();
                break;
            case 'getTetraBlue':
                $this->getTetraBlue();
                break;
            case 'setTetraBlue':
                $this->setTetraBlue();
                break;
            case 'getTetraRed':
                $this->getTetraRed();
                break;
            case 'setTetraRed':
                $this->setTetraRed();
                break;
            case 'getNanoBlue':
                $this->getNanoBlue();
                break;
            case 'setNanoBlue':
                $this->setNanoBlue();
                break;
        }
    }

    private function restartLEDs()
    {
        exec('/etc/init.d/led restart');
    }

    private function getDeviceType()
    {
        $device = $this->getDevice();

        if ($device == 'tetra') {
            $this->response = 'tetra';
        } else {
            $this->response = 'nano';
        }
    }

    private function getTetraYellow()
    {
        $trigger = $this->uciGet('system.led_eth0.trigger');

        if ($trigger == 'none') {
            $default = $this->uciGet('system.led_eth0.default');
            if ($default == 0) {
                $this->response = array('enabled' => false, 'trigger' => $trigger);
            } elseif ($default == 1) {
                $this->response = array('enabled' => true, 'trigger' => $trigger);
            }
        } elseif ($trigger == 'netdev') {
            $mode = $this->uciGet('system.led_eth0.mode');
            $interface = $this->uciGet('system.led_eth0.dev');
            if ($mode == 'link tx rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx rx', 'interface' => $interface);
            } elseif ($mode == 'link tx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx', 'interface' => $interface);
            } elseif ($mode == 'link rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link rx', 'interface' => $interface);
            }
        } elseif ($trigger == 'timer') {
            $delayOn = $this->uciGet('system.led_eth0.delayon');
            $delayOff = $this->uciGet('system.led_eth0.delayoff');
            $this->response = array('enabled' => true, 'trigger' => $trigger,
                                    'delayOn' => $delayOn, 'delayOff' => $delayOff);
        } else {
            $this->response = array('enabled' => true, 'trigger' => $trigger);
        }
    }

    private function setTetraYellow()
    {
        $enabled = $this->request->enabled;
        $trigger = $this->request->trigger;
        $mode = $this->request->mode;
        $delayOn = $this->request->delayOn;
        $delayOff = $this->request->delayOff;
        $interface = $this->request->interface;

        if ($enabled == true) {
            if ($trigger == 'none') {
                $this->uciSet('system.led_eth0.trigger', 'none');
                $this->uciSet('system.led_eth0.default', '1');
                $this->restartLEDs();
            } elseif ($trigger == 'netdev') {
                $this->uciSet('system.led_eth0.trigger', 'netdev');
                $this->uciSet('system.led_eth0.mode', "$mode");
                $this->uciSet('system.led_eth0.dev', "$interface");
                $this->restartLEDs();
            } elseif ($trigger == 'timer') {
                $this->uciSet('system.led_eth0.trigger', 'timer');
                $this->uciSet('system.led_eth0.delayon', "$delayOn");
                $this->uciSet('system.led_eth0.delayoff', "$delayOff");
                $this->restartLEDs();
            }
        } elseif ($enabled == false) {
            $this->uciSet('system.led_eth0.trigger', 'none');
            $this->uciSet('system.led_eth0.default', '0');
            $this->restartLEDs();
        }

        $this->response = array('enabled' => $enabled, 'trigger' => $trigger,
        'mode' => $mode, 'delayOn' => $delayOn,
        'delayOff' => $delayOff, 'interface' => $interface, 'success' => true);
    }

    private function getTetraBlue()
    {
        $trigger = $this->uciGet('system.led_wlan0.trigger');

        if ($trigger == 'none') {
            $default = $this->uciGet('system.led_wlan0.default');
            if ($default == 0) {
                $this->response = array('enabled' => false, 'trigger' => $trigger);
            } elseif ($default == 1) {
                $this->response = array('enabled' => true, 'trigger' => $trigger);
            }
        } elseif ($trigger == 'netdev') {
            $mode = $this->uciGet('system.led_wlan0.mode');
            $interface = $this->uciGet('system.led_wlan0.dev');
            if ($mode == 'link tx rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx rx', 'interface' => $interface);
            } elseif ($mode == 'link tx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx', 'interface' => $interface);
            } elseif ($mode == 'link rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link rx', 'interface' => $interface);
            }
        } elseif ($trigger == 'timer') {
            $delayOn = $this->uciGet('system.led_wlan0.delayon');
            $delayOff = $this->uciGet('system.led_wlan0.delayoff');
            $this->response = array('enabled' => true, 'trigger' => $trigger,
                                    'delayOn' => $delayOn, 'delayOff' => $delayOff);
        } else {
            $this->response = array('enabled' => true, 'trigger' => $trigger);
        }
    }

    private function setTetraBlue()
    {
        $enabled = $this->request->enabled;
        $trigger = $this->request->trigger;
        $mode = $this->request->mode;
        $delayOn = $this->request->delayOn;
        $delayOff = $this->request->delayOff;
        $interface = $this->request->interface;

        if ($enabled == true) {
            if ($trigger == 'none') {
                $this->uciSet('system.led_wlan0.trigger', 'none');
                $this->uciSet('system.led_wlan0.default', '1');
                $this->restartLEDs();
            } elseif ($trigger == 'netdev') {
                $this->uciSet('system.led_wlan0.trigger', 'netdev');
                $this->uciSet('system.led_wlan0.mode', "$mode");
                $this->uciSet('system.led_wlan0.dev', "$interface");
                $this->restartLEDs();
            } elseif ($trigger == 'timer') {
                $this->uciSet('system.led_wlan0.trigger', 'timer');
                $this->uciSet('system.led_wlan0.delayon', "$delayOn");
                $this->uciSet('system.led_wlan0.delayoff', "$delayOff");
                $this->restartLEDs();
            }
        } elseif ($enabled == false) {
            $this->uciSet('system.led_wlan0.trigger', 'none');
            $this->uciSet('system.led_wlan0.default', '0');
            $this->restartLEDs();
        }

        $this->response = array('enabled' => $enabled, 'trigger' => $trigger,
        'mode' => $mode, 'delayOn' => $delayOn,
        'delayOff' => $delayOff, 'interface' => $interface, 'success' => true);
    }

    private function getTetraRed()
    {
        $trigger = $this->uciGet('system.led_wlan1mon.trigger');

        if ($trigger == 'none') {
            $default = $this->uciGet('system.led_wlan1mon.default');
            if ($default == 0) {
                $this->response = array('enabled' => false, 'trigger' => $trigger);
            } elseif ($default == 1) {
                $this->response = array('enabled' => true, 'trigger' => $trigger);
            }
        } elseif ($trigger == 'netdev') {
            $mode = $this->uciGet('system.led_wlan1mon.mode');
            $interface = $this->uciGet('system.led_wlan1mon.dev');
            if ($mode == 'link tx rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx rx', 'interface' => $interface);
            } elseif ($mode == 'link tx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx', 'interface' => $interface);
            } elseif ($mode == 'link rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link rx', 'interface' => $interface);
            }
        } elseif ($trigger == 'timer') {
            $delayOn = $this->uciGet('system.led_wlan1mon.delayon');
            $delayOff = $this->uciGet('system.led_wlan1mon.delayoff');
            $this->response = array('enabled' => true, 'trigger' => $trigger,
                                    'delayOn' => $delayOn, 'delayOff' => $delayOff);
        } else {
            $this->response = array('enabled' => true, 'trigger' => $trigger);
        }
    }

    private function setTetraRed()
    {
        $enabled = $this->request->enabled;
        $trigger = $this->request->trigger;
        $mode = $this->request->mode;
        $delayOn = $this->request->delayOn;
        $delayOff = $this->request->delayOff;
        $interface = $this->request->interface;

        if ($enabled == true) {
            if ($trigger == 'none') {
                $this->uciSet('system.led_wlan1mon.trigger', 'none');
                $this->uciSet('system.led_wlan1mon.default', '1');
                $this->restartLEDs();
            } elseif ($trigger == 'netdev') {
                $this->uciSet('system.led_wlan1mon.trigger', 'netdev');
                $this->uciSet('system.led_wlan1mon.mode', "$mode");
                $this->uciSet('system.led_wlan1mon.dev', "$interface");
                $this->restartLEDs();
            } elseif ($trigger == 'timer') {
                $this->uciSet('system.led_wlan1mon.trigger', 'timer');
                $this->uciSet('system.led_wlan1mon.delayon', "$delayOn");
                $this->uciSet('system.led_wlan1mon.delayoff', "$delayOff");
                $this->restartLEDs();
            }
        } elseif ($enabled == false) {
            $this->uciSet('system.led_wlan1mon.trigger', 'none');
            $this->uciSet('system.led_wlan1mon.default', '0');
            $this->restartLEDs();
        }

        $this->response = array('enabled' => $enabled, 'trigger' => $trigger,
        'mode' => $mode, 'delayOn' => $delayOn,
        'delayOff' => $delayOff, 'interface' => $interface, 'success' => true);
    }

    private function getNanoBlue()
    {
        $trigger = $this->uciGet('system.led_wlan0.trigger');

        if ($trigger == 'none') {
            $default = $this->uciGet('system.led_wlan0.default');
            if ($default == 0) {
                $this->response = array('enabled' => false, 'trigger' => $trigger);
            } elseif ($default == 1) {
                $this->response = array('enabled' => true, 'trigger' => $trigger);
            }
        } elseif ($trigger == 'netdev') {
            $mode = $this->uciGet('system.led_wlan0.mode');
            $interface = $this->uciGet('system.led_wlan0.dev');
            if ($mode == 'link tx rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx rx', 'interface' => $interface);
            } elseif ($mode == 'link tx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link tx', 'interface' => $interface);
            } elseif ($mode == 'link rx') {
                $this->response = array('enabled' => true, 'trigger' => $trigger,
                                        'mode' => 'link rx', 'interface' => $interface);
            }
        } elseif ($trigger == 'timer') {
            $delayOn = $this->uciGet('system.led_wlan0.delayon');
            $delayOff = $this->uciGet('system.led_wlan0.delayoff');
            $this->response = array('enabled' => true, 'trigger' => $trigger,
                                    'delayOn' => $delayOn, 'delayOff' => $delayOff);
        } else {
            $this->response = array('enabled' => true, 'trigger' => $trigger);
        }
    }

    private function setNanoBlue()
    {
        $enabled = $this->request->enabled;
        $trigger = $this->request->trigger;
        $mode = $this->request->mode;
        $delayOn = $this->request->delayOn;
        $delayOff = $this->request->delayOff;
        $interface = $this->request->interface;

        if ($enabled == true) {
            if ($trigger == 'none') {
                $this->uciSet('system.led_wlan0.trigger', 'none');
                $this->uciSet('system.led_wlan0.default', '1');
                $this->restartLEDs();
            } elseif ($trigger == 'netdev') {
                $this->uciSet('system.led_wlan0.trigger', 'netdev');
                $this->uciSet('system.led_wlan0.mode', "$mode");
                $this->uciSet('system.led_wlan0.dev', "$interface");
                $this->restartLEDs();
            } elseif ($trigger == 'timer') {
                $this->uciSet('system.led_wlan0.trigger', 'timer');
                $this->uciSet('system.led_wlan0.delayon', "$delayOn");
                $this->uciSet('system.led_wlan0.delayoff', "$delayOff");
                $this->restartLEDs();
            }
        } elseif ($enabled == false) {
            $this->uciSet('system.led_wlan0.trigger', 'none');
            $this->uciSet('system.led_wlan0.default', '0');
            $this->restartLEDs();
        }

        $this->response = array('enabled' => $enabled, 'trigger' => $trigger,
        'mode' => $mode, 'delayOn' => $delayOn,
        'delayOff' => $delayOff, 'interface' => $interface, 'success' => true);
    }

    private function resetLEDs()
    {
        $device = $this->getDevice();

        if ($device == 'tetra') {
            $this->uciSet('system.led_wlan0.trigger', 'netdev');
            $this->uciSet('system.led_wlan0.mode', 'link tx rx');
            $this->uciSet('system.led_wlan0.dev', 'wlan0');
            $this->uciSet('system.led_wlan1mon.trigger', 'netdev');
            $this->uciSet('system.led_wlan1mon.mode', 'link tx rx');
            $this->uciSet('system.led_wlan1mon.dev', 'wlan1mon');
            $this->uciSet('system.led_eth0.trigger', 'netdev');
            $this->uciSet('system.led_eth0.mode', 'link tx rx');
            $this->uciSet('system.led_eth0.dev', 'eth0');
            $this->restartLEDs();
            $this->response = array('success' => true);
        } else {
            $this->uciSet('system.led_wlan0.trigger', 'netdev');
            $this->uciSet('system.led_wlan0.mode', 'link tx rx');
            $this->uciSet('system.led_wlan0.dev', 'wlan0');
            $this->restartLEDs();
            $this->response = array('success' => true);
        }
    }
}
