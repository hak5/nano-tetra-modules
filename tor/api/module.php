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
        }
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

	private function refreshHiddenServices() {
		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		$this->response = array("hiddenServices" => $hiddenServices);
	}
	private function addHiddenService() {
		//Perform gate checks here...
		$hiddenService = array("name" => $this->request->name, "forwards" => array() );
		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
		array_push($hiddenServices, $hiddenService);
		file_put_contents("/etc/config/tor/config", json_encode($hiddenServices, JSON_PRETTY_PRINT));
	}
	private function removeHiddenService() {
		$hiddenServices = @json_decode(file_get_contents("/etc/config/tor/config"));
	}
}
