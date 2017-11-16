<?php namespace pineapple;

class autossh extends Module
{
  public function route()
  {

    switch ($this->request->action) {
      case 'status':
      $this->status();
      break;

      case 'getInfo':
      $this->getInfo();
      break;

      case 'stopAutossh':
      $this->stopAutossh();
      break;

      case 'startAutossh':
      $this->startAutossh();
      break;

      case 'enableAutossh':
      $this->enableAutossh();
      break;

      case 'disableAutossh':
      $this->disableAutossh();
      break;

      case 'readConf':
      $this->readConf();
      break;

      case 'writeConf':
      $this->writeConf();
      break;

      case 'resetConf':
      $this->resetConf();
      break;

      case 'createSshKey':
      $this->createSshKey();
      break;

      case 'deleteKey':
      $this->deleteKey();
      break;

    }

  }

// Initial Setup
  private function createSshKey()
  {
    $path = "/root/.ssh/id_rsa.autossh";
    exec("ssh-keygen -f $path -t rsa -N ''");
    if (file_exists($path)) {
      $this->response = array("success" => true);
    }
  }

  private function deleteKey()
  {
    exec('rm /root/.ssh/id_rsa.autossh*');
    $this->response = array("success" => true);
  }

  private function ensureKnownHosts($args)
  {
    $cmd = "ssh -o StrictHostKeyChecking=no -o PasswordAuthentication=no -p $args->port $args->user@$args->host exit";
    $this->execBackground($cmd);
  }

  private function getInfo()
  {
    $this->response = array(
      "success" => true,
      "pubKey" => $this->safeRead('/root/.ssh/id_rsa.autossh.pub'),
      "knownHosts" => shell_exec("awk '{print $1}' /root/.ssh/known_hosts")
    );
  }

  private function safeRead($file)
  {
    return file_exists($file) ? file_get_contents($file) : "";
  }



// Configuration
  private function readConf()
  {
    $conf = $this->parsedConfig() + array("success" => true);
    $this->response = $conf;
  }

  private function resetConf()
  {
    exec("cp /rom/etc/config/autossh /etc/config/autossh");
    return $this->response = $this->parsedConfig() + array("success" => true);
  }

  private function parsedConfig()
  {
    $uciString = "autossh.@autossh[0].ssh";
    $contents = $this->uciGet($uciString);
    $args = preg_split("/\s|\t|:|@|'/", $contents);
    return $this->parseArguments(array_filter($args));
  }

  private function writeConf()
  {
    $args = $this->request->data;
    $uciString = "autossh.@autossh[0].ssh";
    $option = $this->buildOptionString($args);
    $this->ensureKnownHosts($args);
    $this->uciSet($uciString, $option);
    $this->response = array("success" => true);
  }

  private function buildOptionString($args)
  {
    return "-i /root/.ssh/id_rsa.autossh -N -T -R $args->rport:localhost:$args->lport $args->user@$args->host -p $args->port";
  }

  private function parseArguments($args)
  {
    return array(
      "user" => $args[8],
      "host" => $args[9],
      "port" => (!$args[11]) ? "22" : $args[11],
      "rport" => $args[5],
      "lport" => $args[7],
    );
  }


  // Management

  private function status()
  {
    $this->response = array(
      "success" => true,
      "isRunning" => $this->isRunning(),
      "isEnabled" => $this->isEnabled()
    );
  }

  private function isRunning()
  {
    return $this->checkRunning("autossh");
  }

  private function isEnabled()
  {
    $rcFile = "/etc/rc.d/S80autossh";
    return file_exists($rcFile);
  }

  private function startAutossh()
  {
    exec("/etc/init.d/autossh start");
    $this->response = array("success" => true);
  }

  private function stopAutossh()
  {
    exec("/etc/init.d/autossh stop");
    $this->response = array("success" => true);
  }

  private function enableAutossh()
  {
    exec("/etc/init.d/autossh enable");
    $this->response = array("success" => true);
  }

  private function disableAutossh()
  {
    exec("/etc/init.d/autossh disable");
    $this->response = array("success" => true);
  }

}
