<?php namespace pineapple;

class HackRF extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'hackrfInfo':
            $this->hackrfInfo();
            break;

            case 'hackrfInstall':
            $this->hackrfInstall();
            break;

            case 'hackrfUninstall':
            $this->hackrfUninstall();
            break;

            case 'hackrfChecker':
            $this->hackrfChecker();
            break;

            case 'hackrfTransfer':
            $this->hackrfTransfer();
            break;

            case 'hackrfStop':
            $this->hackrfStop();
            break;

            case 'hackrfLog':
            $this->hackrfLog();
            break;
        }
    }

    private function hackrfInfo()
    {
        exec('hackrf_info', $message);
        $message = implode("\n", $message);

        if ($message == "No HackRF boards found.") {
            $this->response = array("foundBoard" => false);
        } else if ($this->checkDependency('hackrf_info') == false) {
            $this->response = array("foundBoard" => false);
        } else {
            $this->response = array("foundBoard" => true,
                "availableHackRFs" => $message);
        }
    }

    private function hackrfChecker()
    {
        if ($this->checkDependency('hackrf_info')) {
            $this->response = array("installed" => true);
        } else {
            $this->response = array("installed" => false);
        }
    }

    private function hackrfInstall()
    {
        if ($this->getDevice() == 'tetra') {
            $this->execBackground('opkg update && opkg install hackrf-mini');    
        } else {
            $this->execBackground('opkg update && opkg install hackrf-mini --dest sd');
        }
        exec('echo "Welcome to HackRF!" > /tmp/hackrf_log');

        $this->response = array("installing" => true);
    }

    private function hackrfUninstall()
    {
        exec('opkg remove hackrf-mini');
        unlink('/tmp/hackrf_log');
        $this->response = array("success" => true);
    }

    private function hackrfTransfer()
    {
        $mode         = $this->request->mode;
        $sampleRate   = $this->request->sampleRate;
        $centerFreq   = $this->request->centerFreq;
        $filename     = $this->request->filename;
        $amp          = $this->request->amp;
        $antPower     = $this->request->antpower;
        $txRepeat     = $this->request->txRepeat;
        $txIfCheckbox = $this->request->txIfCheckbox;
        $txIfGain     = $this->request->txIfGain;
        $rxIfCheckbox = $this->request->rxIfCheckbox;
        $rxBbCheckbox = $this->request->rxBbCheckbox;
        $rxIfGain     = $this->request->rxIfGain;
        $rxBbGain     = $this->request->rxBbGain;

        if(!$sampleRate) {
            $this->response = array("success" => false, "error" => "samplerate");
        } else if(!$centerFreq) {
            $this->response = array("success" => false, "error" => "centerfreq");
        } else if(!$filename) {
            $this->response = array("success" => false, "error" => "filename");
        } else {
            if ($mode == "rx") {
                $mode = "-r";
            } else {
                $mode = "-t";
            }

            if(strpos(strtolower($sampleRate), 'k') == true) {
                $sampleRate = str_replace('k', '', $sampleRate);
                $sampleRate = (int)$sampleRate * 1000;
            } else if(strpos(strtolower($sampleRate), 'm') == true) {
                $sampleRate = str_replace('m', '', $sampleRate);
                $sampleRate = (int)$sampleRate * 1000000;
            }

            if(strpos(strtolower($centerFreq), 'khz') == true) {
                $centerFreq = str_replace('khz', '', $centerFreq);
                $centerFreq = floatval($centerFreq);
                $centerFreq = $centerFreq * 1000;
            } else if(strpos(strtolower($centerFreq), 'mhz') == true) {
                $centerFreq = str_replace('mhz', '', $centerFreq);
                $centerFreq = floatval($centerFreq);
                $centerFreq = $centerFreq * 1000000;
            } else if(strpos(strtolower($centerFreq), 'ghz') == true) {
                $centerFreq = str_replace('ghz', '', $centerFreq);
                $centerFreq = floatval($centerFreq);
                $centerFreq = $centerFreq * 1000000000;
            }

            $command = "hackrf_transfer $mode $filename -f $centerFreq -s $sampleRate";

            if ($amp) {
                $command = $command . " -a 1";
            }
            if ($antPower) {
                $command = $command . " -p 1";
            }
            if ($txRepeat) {
                $command = $command . " -R";
            }

            if ($txIfCheckbox == true && $mode == '-t' && empty($txIfGain) == false) {
                $command = $command . " -x $txIfGain";
            }

            if ($rxIfCheckbox == true && $mode == '-r' && empty($rxIfGain) == false) {
                $command = $command . " -l $rxIfGain";
            }

            if ($rxBbCheckbox == true && $mode == '-r' && empty($rxBbGain) == false) {
                $command = $command . " -g $rxBbGain";
            }

            unlink("/tmp/hackrf_log");
            $this->execBackground("$command > /tmp/hackrf_log 2>&1");
            $this->response = array("success" => true);
        }
    }

    private function hackrfStop()
    {
        exec("killall hackrf_transfer");

        $this->response = array("success" => true);
    }

    private function hackrfLog()
    {
        $log  = '/tmp/hackrf_log';
        if(file_exists($log)) {
            $log = file_get_contents($log);
            $this->response = array("success" => true, "log" => $log);
        } else {
            $this->response = array("success" => true, "log" => "Welcome to HackRF!");
        }
    }
}

// 01110100 01101000 01100001 01101110 01101011 00100000 01111001 01101111 01110101 00100000 01110011 01100101 01100010 01100001 
// 01110011 01110100 01101001 01100001 01101110 00101110 0001010 01101001 00100000 01101100 01101111 01110110 01100101 00100000
// 01111001 01101111 01110101 00100000 00111100 00110011

