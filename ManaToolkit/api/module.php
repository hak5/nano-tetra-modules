<?php namespace pineapple;
putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');
putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

class ManaToolkit extends Module
{
	public function route()
    {
        switch ($this->request->action) {
			case 'refreshInfo':
				$this->refreshInfo();
				break;
			case 'refreshOutput':
                $this->refreshOutput();
                break;
            case 'refreshStatus':
                $this->refreshStatus();
                break;
            case 'toggleManaToolkit':
                $this->toggleManaToolkit();
                break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'handleDependenciesStatus':
                $this->handleDependenciesStatus();
                break;
			case 'toggleManaToolkitOnBoot':
				$this->toggleManaToolkitOnBoot();
				break;
			case 'getInterfaces':
				$this->getInterfaces();
				break;
			case 'saveAutostartSettings':
				$this->saveAutostartSettings();
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
			case 'getVersionInfo':
				$this->getVersionInfo();
				break;
			case 'getWiFi':
				$this->getWiFi();
				break;
			case 'getMACInfo':
				$this->getMACInfo();
				break;
			case 'getPingInfo':
				$this->getPingInfo();
				break;
			case 'getDHCP':
				$this->getDHCP();
				break;
			case 'refreshFilesList':
				$this->refreshFilesList();
				break;
			case 'downloadFilesList':
				$this->downloadFilesList();
				break;
			case 'deleteFilesList':
				$this->deleteFilesList();
				break;
			case 'viewModuleFile':
				$this->viewModuleFile();
				break;
			case 'deleteModuleFile':
				$this->deleteModuleFile();
				break;
			case 'downloadModuleFile':
				$this->downloadModuleFile();
				break;
        }
    }

		protected function checkDependency($dependencyName)
		{
			return ((exec("which hostapd-mana") == '' ? false : true) && ($this->uciGet("ManaToolkit.module.installed")));
		}

		protected function getDevice()
		{
			return trim(exec("cat /proc/cpuinfo | grep machine | awk -F: '{print $2}'"));
		}

		protected function refreshInfo()
		{
			$moduleInfo = @json_decode(file_get_contents("/pineapple/modules/ManaToolkit/module.info"));
			$this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
		}

    private function handleDependencies()
    {
		if(!$this->checkDependency("hostapd-mana"))
		{
			$this->execBackground("chmod +x /pineapple/modules/ManaToolkit/scripts/dependencies.sh");
			$this->execBackground("/pineapple/modules/ManaToolkit/scripts/dependencies.sh install ".$this->request->destination);
		        $this->response = array('success' => true);
		}
		else
		{
		        $this->execBackground("/pineapple/modules/ManaToolkit/scripts/dependencies.sh remove");
		        $this->response = array('success' => true);
		}
	}

    private function handleDependenciesStatus()
    {
        if (!file_exists('/tmp/ManaToolkit.progress'))
		{
            $this->response = array('success' => true);
        }
		else
		{
            $this->response = array('success' => false);
        }
    }

    private function toggleManaToolkitOnBoot()
    {
		if(exec("cat /etc/rc.local | grep ManaToolkit/scripts/autostart_ManaToolkit.sh") == "")
		{
			exec("sed -i '/exit 0/d' /etc/rc.local");
			exec("echo /pineapple/modules/ManaToolkit/scripts/autostart_ManaToolkit.sh >> /etc/rc.local");
			exec("echo exit 0 >> /etc/rc.local");
		}
		else
		{
			exec("sed -i '/ManaToolkit\/scripts\/autostart_ManaToolkit.sh/d' /etc/rc.local");
		}
	}

    private function toggleManaToolkit()
    {
		if(!$this->checkRunning("hostapd-mana"))
		{
			$this->uciSet("ManaToolkit.run.interface", $this->request->interface);
			$this->execBackground("/pineapple/modules/ManaToolkit/scripts/ManaToolkit.sh start");
		}
		else
		{
			$this->uciSet("ManaToolkit.run.interface", '');
			$this->execBackground("/pineapple/modules/ManaToolkit/scripts/ManaToolkit.sh stop");
		}
	}

	private function getInterfaces()
	{
		//exec("ip -o link show | awk '{print $2,$9}' | awk -F':' '{print $1}' | grep wlan | grep -v mon |  awk -F'-' '{print $1}' | uniq", $interfaceArray);
		exec("cat /proc/net/dev | tail -n +3 | cut -f1 -d: | sed 's/ //g' | grep wlan | grep -v mon | awk -F'-' '{print $1}' | uniq", $interfaceArray);
		$this->response = array("interfaces" => $interfaceArray, "selected" => $this->uciGet("ManaToolkit.run.interface"));
	}

	private function check_loud()
	{
		
	}
	
    private function refreshStatus()
    {
        if (!file_exists('/tmp/ManaToolkit.progress'))
		{
			if (!$this->checkDependency("hostapd-mana"))
			{
				$installed = false;
				$install = "Not installed";
				$installLabel = "danger";
				$processing = false;

				$status = "Start";
				$statusLabel = "success";

				$bootLabelON = "default";
				$bootLabelOFF = "danger";
			}
			else
			{
				$installed = true;
				$install = "Installed";
				$installLabel = "success";
				$processing = false;

				if($this->checkRunning("hostapd-mana"))
				{
					$status = "Stop";
					$statusLabel = "danger";
				}
				else
				{
					$status = "Start";
					$statusLabel = "success";
				}

				if(exec("cat /etc/rc.local | grep ManaToolkit/scripts/autostart_ManaToolkit.sh") == "")
				{
					$bootLabelON = "default";
					$bootLabelOFF = "danger";
				}
				else
				{
					$bootLabelON = "success";
					$bootLabelOFF = "default";
				}
			}
        }
		else
		{
			$installed = false;
			$install = "Installing...";
			$installLabel = "warning";
			$processing = true;

			$status = "Not running";
			$statusLabel = "danger";
			$verbose = false;

			$bootLabelON = "default";
			$bootLabelOFF = "danger";
        }

			$device = $this->getDevice();
			$sdAvailable = $this->isSDAvailable();

		$this->response = array(
			"device" => $device,
			"sdAvailable" => $sdAvailable,
			"status" => $status,
			"statusLabel" => $statusLabel,
			"installed" => $installed,
			"install" => $install,
			"installLabel" => $installLabel,
			"bootLabelON" => $bootLabelON,
			"bootLabelOFF" => $bootLabelOFF,
			"processing" => $processing);
	}

    private function refreshOutput()
    {
		if ($this->checkDependency("hostapd-mana"))
		{
			if ($this->checkRunning("hostapd-mana"))
			{
				$filename = '/pineapple/modules/ManaToolkit/log/hostapd-mana_output.log';
				if(file_exists($filename))
				{
					if ($this->request->filter != "")
					{
						$filter = $this->request->filter;
						$cmd = "cat ".$filename." | ".$filter;
					}
					else
					{
						$cmd = "cat ".$filename;
					}

					exec ($cmd, $output);
					if(!empty($output))
						$this->response = implode("\n", array_reverse($output));
					else
						$this->response = "No output to display...";
				}
			}
			else
			{
				 $this->response = "Mana Toolkit is not running...";
			}
		}
		else
		{
			$this->response = "Mana Toolkit is not installed...";
		}
    }

	private function saveAutostartSettings()
	{
			$settings = $this->request->settings;
			$this->uciSet("ManaToolkit.autostart.interface", $settings->interface);
	}

    private function getConfiguration()
    {
		$manaconf = '/mana-toolkit/hostapd-mana.conf';
		if(file_exists($manaconf))
		{
			$config = file_get_contents('/etc/mana-toolkit/hostapd-mana.conf');
			$this->response = array("ManaToolkitConfiguration" => $config);
		}
		else
		{
			$config = file_get_contents('/etc/mana-toolkit/hostapd-mana.conf');
			$this->response = array("ManaToolkitConfiguration" => $config);
		}
    }

    private function saveConfiguration()
    {
        $config = $this->request->ManaToolkitConfiguration;
        file_put_contents('/etc/mana-toolkit/hostapd-mana.conf', $config);
        $this->response = array("success" => true);
    }

    private function restoreDefaultConfiguration()
    {
        $defaultConfig = file_get_contents('/etc/mana-toolkit/hostapd-mana.default.conf');
        file_put_contents('/etc/mana-toolkit/hostapd-mana.conf', $defaultConfig);
        $this->response = array("success" => true);
    }
	
	private function getDHCP()
	{

			$dhcpClients = explode("\n", trim(shell_exec("cat /tmp/dhcp-mana.leases")));
			$clientsList = array();
			for($i=0;$i<count($dhcpClients);$i++)
			{
				if($dhcpClients[$i] != "")
				{
					$dhcp_client = explode(" ", $dhcpClients[$i]);
					$mac_address = $dhcp_client[1];
					$ip_address = $dhcp_client[2];
					$hostname = $dhcp_client[3];

					array_push($clientsList, array("hostname" => $hostname, "mac" => $mac_address, "ip" =>$ip_address));
				}
			}

			$info = array(
						'clientsList' =>  $clientsList
						);

			$this->response = array('info' => $info);
	}
	
	private function getWiFi()
	{
		$wifiClients = explode("\n", trim(shell_exec('iw dev wlan1 station dump | grep "Station"')));
		$wifiClientsList = array();
		for($i=0;$i<count($wifiClients);$i++)
		{
			if($wifiClients[$i] != "")
			{
				$wifi_client = explode(" ", $wifiClients[$i]);
				$mac_address = $wifi_client[1];
				$ip_address = exec("cat /tmp/dhcp-mana.leases | grep \"".$mac_address."\" | awk '{ print $3}'");
				$hostname = exec("cat /tmp/dhcp-mana.leases | grep \"".$mac_address."\" | awk '{ print $4}'");

				array_push($wifiClientsList, array("hostname" => $hostname, "mac" => $mac_address, "ip" =>$ip_address));
			}
	}

		$info = array(
			'wifiClientsList' =>  $wifiClientsList
		);

		$this->response = array('info' => $info);
	}
		
	private function getMACInfo()
	{
		$content = file_get_contents("http://api.macvendors.com/".$this->request->mac);
		$this->response = array('title' => $this->request->mac, "output" => $content);
	}
		
	private function getPingInfo()
	{
		exec ("ping -c4 ".$this->request->ip, $output);
		$this->response = array('title' => $this->request->ip, "output" => implode("\n", array_reverse($output)));
	}

	private function dataSize($path)
	{
	    $blah = exec( "/usr/bin/du -sch $path | tail -1 | awk {'print $1'}" );
	    return $blah;
	}

		private function downloadFilesList()
		{
			$files = $this->request->files;

			exec("mkdir /tmp/dl/");
			foreach($files as $file)
			{
				exec("cp ".$file." /tmp/dl/");
			}
			exec("cd /tmp/dl/ && tar -czf /tmp/files.tar.gz *");
			exec("rm -rf /tmp/dl/");

			$this->response = array("download" => $this->downloadFile("/tmp/files.tar.gz"));
		}

		private function deleteFilesList()
		{
			$files = $this->request->files;

			foreach($files as $file)
			{
				exec("rm -rf ".$file);
			}
		}

		private function refreshFilesList()
		{
			$modules = array();
			foreach(glob('/pineapple/modules/ManaToolkit/log/*.log') as $file)
			{
				$module = array();
				$module['file'] = basename($file);
				$module['path'] = $file;
				$module['size'] = $this->dataSize($file);
				if(basename($file) != 'hostapd-mana_output'){
					$module['title'] = 'Hostapd - Live Output';
				}
				else{
					$module['title'] = explode("/", dirname($file))[3];
				}
				$module['date'] = gmdate("F d Y H:i:s", filemtime($file));
				$module['timestamp'] = filemtime($file);
				$modules[] = $module;
			}

			foreach(glob('/pineapple/modules/ManaToolkit/log/*/*.log') as $file)
			{
				$module = array();
				$module['file'] = basename($file);
				$module['path'] = $file;
				$module['size'] = $this->dataSize($file);
				$module['title'] = explode("/", dirname($file))[5];
				$module['date'] = gmdate("F d Y H:i:s", filemtime($file));
				$module['timestamp'] = filemtime($file);
				$modules[] = $module;
			}

			foreach(glob('/pineapple/modules/ManaToolkit/log/*/*/*.log') as $file)
			{
				$module = array();
				$module['file'] = basename($file);
				$module['path'] = $file;
				$module['size'] = $this->dataSize($file);
				$module['title'] = explode("/", dirname($file))[5];
				$module['date'] = gmdate("F d Y H:i:s", filemtime($file));
				$module['timestamp'] = filemtime($file);
				$modules[] = $module;
			}

			usort($modules, create_function('$a, $b','if($a["timestamp"] == $b["timestamp"]) return 0; return ($a["timestamp"] > $b["timestamp"]) ? -1 : 1;'));

			$this->response = array("files" => $modules);
		}

		private function viewModuleFile()
		{
			$log_date = gmdate("F d Y H:i:s", filemtime($this->request->file));
			exec ("strings ".$this->request->file, $output);

			if(!empty($output))
				$this->response = array("output" => implode("\n", $output), "date" => $log_date, "name" => basename($this->request->file));
			else
				$this->response = array("output" => "Empty file...", "date" => $log_date, "name" => basename($this->request->file));
		}

		private function deleteModuleFile()
		{
			exec("rm -rf ".$this->request->file);
		}

		private function downloadModuleFile()
		{
			$this->response = array("download" => $this->downloadFile($this->request->file));
		}
}
