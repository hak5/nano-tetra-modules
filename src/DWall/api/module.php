<?php namespace pineapple;

class DWall extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'enable':
                $this->enable();
                break;
            case 'disable':
                $this->disable();
                break;
            case 'getStatus':
                $this->getStatus();
                break;
        }
    }

    private function enable()
    {
        $this->disable();

        if (!file_exists("/usr/lib/libpcap.so.1.3") && file_exists("/usr/lib/libpcap.so")) {
            symlink("/usr/lib/libpcap.so", "/usr/lib/libpcap.so.1.3");
        }
        
        $this->execBackground("/usr/bin/python /pineapple/modules/DWall/assets/DWall.py");
        $this->execBackground("/pineapple/modules/DWall/assets/http_sniffer br-lan");
        $this->response = array("success" => true);
    }

    private function disable()
    {
        exec("killall http_sniffer");
        exec("kill \$(ps -aux | grep DWall | head -n1 | awk '{print $2}')");
        $this->response = array("success" => true);
    }

    private function getStatus()
    {
        if (trim(exec("ps -aux | grep [D]Wall.py")) != "" && trim(exec("ps -aux| grep [h]ttp_sniffer")) != "") {
            $this->response = array("running" => true);
        } else {
            $this->response = array("running" => false);
        }
    }
}
