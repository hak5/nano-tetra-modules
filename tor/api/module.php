<?php namespace pineapple;

class tor extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'refreshInfo':
                $this->refreshInfo();
                break;
			case 'refreshStatus':
				$this->refreshStatus();
				break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
                break;
			case 'toggletor':
				$this->toggletor();
				break;
			case 'refreshHiddenServices':
				$this->refreshHiddenServices();
				break;
			case 'addHiddenService':
				$this->addHiddenService();
				break;
			case 'removeHiddenService':
				$this->removeHiddenService();
			    break;
			case 'addServiceForward':
				$this->addServiceForward();
				break;
			case 'removeServiceForward':
				$this->removeServiceForward();
				break;
        }
    }

	private function isValidName($name) {
		return preg_match('/^[a-zA-Z0-9_]+$/', $name) === 1;
	}

	private function isValidPort($port) {
		return preg_match('/^[0-9]+$/', $port) === 1;
	}

	private function isValidRedirectTo($redirect_to) {
		return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+$/', $redirect_to) === 1;
	}

    protected function checkDependency($dependencyName)
    {
        return ((exec("which {$dependencyName}") == '' ? false : true) &&  !file_exists("/tmp/tor.progress"));
    }

    protected function refreshInfo()
    {
        $moduleInfo = @json_decode(file_get_contents("/pineapple/modules/tor/module.info"));
        $this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
    }

	protected function checkRunning($processName)
	{
		return (exec("pgrep {$processName}") != '');
	}

    private function handleDependencies()
    {
		if(isset($this->request->destination) && $this->request->destination != "internal" && $this->request->destination != "sd") {
			$this->response = array('error'=>'Invalid destination');
			return;
		}

        if(!$this->checkDependency("tor"))
        {
            $this->execBackground("/pineapple/modules/tor/scripts/dependencies.sh install " . $this->request->destination);
        }
        else
        {
            $this->execBackground("/pineapple/modules/tor/scripts/dependencies.sh remove");
        }
        $this->response = array('success' => true);
    }

    private function handleDependenciesStatus()
    {
        if (file_exists('/tmp/tor.progress'))
        {
            $this->response = array('success' => false);
        }
        else
        {
            $this->response = array('success' => true);
        }
    }

	private function toggletor()
	{
		if($this->checkRunning("tor"))
		{
			exec("/etc/init.d/tor stop");
		}
		else
		{
			exec("/etc/init.d/tor start");
		}
	}

    private function refreshStatus()
    {

        $device = $this->getDevice();
        $sdAvailable = $this->isSDAvailable();
		$installed = false;
        $bootLabelON = "default";
        $bootLabelOFF = "danger";
        $processing = false;

        if (file_exists('/tmp/tor.progress'))
        {
			// TOR Is installing, please wait.
            $install = "Installing...";
            $installLabel = "warning";
            $processing = true;

            $status = "Not running";
            $statusLabel = "danger";

	        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
			return;
		}

        if (!$this->checkDependency("tor"))
        {
			// TOR is not installed, please install.
            $install = "Not installed";
            $installLabel = "danger";

            $status = "Start";
            $statusLabel = "success";
			
	        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
			return;
        }

		// TOR is installed, please configure.
        $installed = true;
        $install = "Installed";
        $installLabel = "success";

        if($this->checkRunning("tor"))
        {
            $status = "Started";
            $statusLabel = "success";
        }
        else
        {
			$status = "Stopped";
            $statusLabel = "danger";
        }

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
    }

	private function generateConfig() {
		$output = file_get_contents("/etc/config/tor/torrc");
		$output .= "\n";
		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		foreach($hiddenServices as $hiddenService) {
			$output .= "HiddenServiceDir /etc/config/tor/services/{$hiddenService->name}\n";
			$forwards = $hiddenService->forwards;
			foreach($forwards as $forward) {
				$output .= "HiddenServicePort {$forward->port} {$forward->redirect_to}\n";
			}
		}
		file_put_contents("/etc/tor/torrc", $output);
	}

	private function reloadTor() {
		$this->generateConfig(); 
	}

	private function refreshHiddenServices() {
		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		foreach($hiddenServices as $hiddenService) {
			if(file_exists("/etc/config/tor/services/{$hiddenService->name}/hostname")) {
				$hiddenService->hostname = trim(file_get_contents("/etc/config/tor/services/{$hiddenService->name}/hostname"));
			}
		}
		$this->response = array("hiddenServices" => $hiddenServices);
	}

	private function addHiddenService() {
		$name = $this->request->name;
		if(!$this->isValidName($name)) {
			$this->response = array("error" => "Invalid name"); 
			return;
		}

		$hiddenService = array("name" => $name, "forwards" => array() );
		$hiddenServices = array();
		if(file_exists("/etc/config/tor/config")) {
			$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		}
		array_push($hiddenServices, $hiddenService);
		file_put_contents("/etc/config/tor/config", @json_encode($hiddenServices, JSON_PRETTY_PRINT));
		$this->reloadTor();
	}

	private function removeHiddenService() {
		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		foreach($hiddenServices as $key => $hiddenService) {
			if($hiddenService->name == $this->request->name) {
				unset($hiddenServices[$key]);
			}
		}
		file_put_contents("/etc/config/tor/config", @json_encode($hiddenServices, JSON_PRETTY_PRINT));
		$this->reloadTor();
	}


	private function addServiceForward() {
		$name = $this->request->name;
		$port = $this->request->port;
		$redirect_to = $this->request->redirect_to;

		if(!$this->isValidName($name)) {
			$this->response = array("error" => "Invalid name"); 
			return;
		}
		if(!$this->isValidPort($port)) {
			$this->response = array("error" => "Invalid port"); 
			return;
		}
		if(!$this->isValidRedirectTo($redirect_to)) {
			$this->response = array("error" => "Invalid redirect to"); 
			return;
		}

		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		foreach($hiddenServices as $key => $hiddenService) {
			if($hiddenService->name == $name) {
				$forwards = $hiddenService->forwards;
				$forward = array("port" => $port, "redirect_to" => $redirect_to);
				array_push($forwards, $forward);
				$hiddenServices[$key]->forwards = $forwards;
			}
		}
		file_put_contents("/etc/config/tor/config", @json_encode($hiddenServices, JSON_PRETTY_PRINT));

		$this->reloadTor();	
	}

	private function removeServiceForward() {
		$name = $this->request->name;
		$port = $this->request->port;
		$redirect_to = $this->request->redirect_to;

		if(!$this->isValidName($name)) {
			$this->response = array("error" => "Invalid name"); 
			return;
		}
		if(!$this->isValidPort($port)) {
			$this->response = array("error" => "Invalid port"); 
			return;
		}
		if(!$this->isValidRedirectTo($redirect_to)) {
			$this->response = array("error" => "Invalid redirect to"); 
			return;
		}


		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		foreach($hiddenServices as $hiddenService) {
			if($hiddenService->name == $name) {
				$forwards = $hiddenService->forwards;
				foreach($forwards as $key => $forward) {
					if($forward->port == $port && $forward->redirect_to == $redirect_to) {
						unset($forwards[$key]);
					}
				}
				$hiddenService->forwards = $forwards;
			}
		}
		file_put_contents("/etc/config/tor/config", @json_encode($hiddenServices, JSON_PRETTY_PRINT));

		$this->reloadTor();
	}

}
