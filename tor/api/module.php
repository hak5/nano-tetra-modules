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
			case 'toggletorOnBoot':
				$this->toggletorOnBoot();
				break;
			case 'toggletor':
				$this->toggletor();
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

	private function checkAutoStart()
	{
		return (exec("cat /etc/rc.local | grep tor/scripts/autostart_tor.sh") != '');
	}

    private function toggletorOnBoot()
    {
        if($this->checkAutoStart())
        {
            exec("sed -i '/tor\/scripts\/autostart_tor.sh/d' /etc/rc.local");
        }
        else
        {
			exec("sed -i '/exit 0/d' /etc/rc.local");
            exec("echo /pineapple/modules/tor/scripts/autostart_tor.sh >> /etc/rc.local");
            exec("echo exit 0 >> /etc/rc.local");

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

        if($this->checkAutoStart())
        {
            $bootLabelON = "success";
            $bootLabelOFF = "default";
        }

        $this->response = array("device" => $device, "sdAvailable" => $sdAvailable, "status" => $status, "statusLabel" => $statusLabel, "installed" => $installed, "install" => $install, "installLabel" => $installLabel, "bootLabelON" => $bootLabelON, "bootLabelOFF" => $bootLabelOFF, "processing" => $processing);
    }
}
