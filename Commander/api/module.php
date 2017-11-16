<?php namespace pineapple;

class Commander extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'startCommander':
                $this->startCommander();
                break;

            case 'stopCommander':
                $this->stopCommander();
                break;

            case 'getConfiguration':
                $this->getConfiguration();
                break;

            case 'saveConfiguration':
                $this->saveConfiguration();
                break;

            case 'restoreDefaultConfiguration':
                $this->restoreDefaultConfiguration();
                break;
        }
    }

    private function startCommander()
    {
        $this->execBackground('cd /pineapple/modules/Commander/Python && python commander.py');
        $this->response = array("success" => true);
    }

    private function stopCommander()
    {
        exec('kill -9 $(pgrep -f commander)');
        $this->response = array("success" => true);
    }

    private function getConfiguration()
    {
        $config = file_get_contents('/pineapple/modules/Commander/Python/commander.conf');
        $this->response = array("CommanderConfiguration" => $config);
    }

    private function saveConfiguration()
    {
        $config = $this->request->CommanderConfiguration;
        file_put_contents('/pineapple/modules/Commander/Python/commander.conf', $config);
        $this->response = array("success" => true);
    }

    private function restoreDefaultConfiguration()
    {
        $defaultConfig = file_get_contents('/pineapple/modules/Commander/assets/default.conf');
        file_put_contents('/pineapple/modules/Commander/Python/commander.conf', $defaultConfig);
        $this->response = array("success" => true);
    }
}