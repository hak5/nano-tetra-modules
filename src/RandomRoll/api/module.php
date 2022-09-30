<?php namespace pineapple;

class RandomRoll extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'checkStatus':
                $this->checkStatus();
                break;
            case 'startRandomRoll':
                $this->startRandomRoll();
                break;

            case 'stopRandomRoll':
                $this->stopRandomRoll();
                break;

            case 'getRandomRollRolls':
                $this->getRandomRollRolls();
                break;

            case 'getRandomRollLogs':
                $this->getRandomRollLogs();
                break;

            case 'clearRandomRollLogs':
                $this->clearRandomRollLogs();
                break;
        }
    }

    private function checkStatus()
    {
        $running = file_get_contents('/pineapple/modules/RandomRoll/assets/running');
        if($running == 1){
            $this->response = array("running" => true);
        } else {
            $this->response = array("running" => false);
        }
    }

    private function startRandomRoll()
    {
        $date = date("Ymd H:i:s -- ");
        file_put_contents("/pineapple/modules/RandomRoll/assets/logs/randomroll.log", $date . "RandomRoll Started\n", FILE_APPEND);

        foreach($this->request->selected as $roll){
            $title = $roll->randomRollTitle;
            $checked = $roll->randomRollChecked;
            if ($checked){
                exec('iptables -t nat -A PREROUTING -p tcp --dport 80 -j DNAT --to-destination $(uci get network.lan.ipaddr):80');
                exec('iptables -t nat -A POSTROUTING -j MASQUERADE');
                exec('mv /www/index.php /pineapple/modules/RandomRoll/assets/www/index.php');
                symlink('/pineapple/modules/RandomRoll/assets/selector.php', '/www/index.php');
                @mkdir('/www/Rolls');
                symlink("/pineapple/modules/RandomRoll/assets/Rolls/{$title}", "/www/Rolls/{$title}");
            }
        }

        file_put_contents('/pineapple/modules/RandomRoll/assets/running', '1');

        $this->response = array("success" => true);
    }

    private function stopRandomRoll()
    {
        $date = date("Ymd H:i:s -- ");
        file_put_contents("/pineapple/modules/RandomRoll/assets/logs/randomroll.log", $date . "RandomRoll Stopped\n\n", FILE_APPEND);

        exec('iptables -t nat -D PREROUTING -p tcp --dport 80 -j DNAT --to-destination $(uci get network.lan.ipaddr):80');
        unlink('/www/index.php');
        exec('mv /pineapple/modules/RandomRoll/assets/www/index.php /www/index.php');
        exec('rm -rf /www/Rolls/');

        file_put_contents('/pineapple/modules/RandomRoll/assets/running', '0');

        $this->response = array("success" => true);
    }

    private function getRandomRollRolls()
    {
        $rolls = array();
        
        foreach(glob("/pineapple/modules/RandomRoll/assets/Rolls/*") as $roll){
            $rollname = basename($roll);
            array_push($rolls, array("randomRollTitle" => $rollname, "randomRollChecked" => false));
        }

        $this->response = $rolls;
    }

    private function getRandomRollLogs()
    {
        $randomRollLogOutput = file_get_contents('/pineapple/modules/RandomRoll/assets/logs/randomroll.log');
        $this->response = array("randomRollLogOutput" => $randomRollLogOutput);
    }

    private function clearRandomRollLogs()
    {
        file_put_contents("/pineapple/modules/RandomRoll/assets/logs/randomroll.log", "");
    }

}




