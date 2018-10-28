<?php namespace pineapple;

/*
 * Author: trashbo4t (github.com/trashbo4t)
 */

class Locate extends Module
{
	// CONSTANTS
	private $IP_DIR = '/pineapple/modules/Locate/ips';
	private $ALL_IP_FILE = '/pineapple/modules/Locate/ips/all';
	
	public function route()
	{
		switch ($this->request->action) {
			case 'lookupIP':
				$this->lookupIP();
				break;
			case 'getIPFromFile':
				$this->getIPFromFile();
				break;
			case 'getIPs':
				$this->getIPs();
				break;
		}
	}
	public function getJson($link, $file)
	{
		$cmd = "wget -q $link -O $file";
		exec($cmd);
	
		return $this->getIPFile($file);	
	}
	public function makeLink($ip)
	{
		return "https://ipapi.co/{$ip}/json"; 
	}
	public function addToIPFile($ip)
	{
		exec("echo {$ip} >> {$this->ALL_IP_FILE}");
	}
	public function touchIPFile($ip)
	{
		if ($ip == null) 
		{
			exec("touch {$this->ALL_IP_FILE}");
			return "{$this->ALL_IP_FILE}";
		}
		else 
		{
			exec("touch {$this->IP_DIR}/{$ip}");
			return "{$this->IP_DIR}/{$ip}";
		}
	}
	public function getIPFile($file)
	{
		return file_get_contents($file);	
	}
	public function makeIPDir()
	{
		exec("mkdir {$this->IP_DIR}");
	}
	/*
	*  getIP
	*  return the json blob associated with an IP via file contents
	*/
	public function getIPFromFile()
	{
		$this->makeIPDir();
		$file = $this->touchIPFile($this->request->ip);
		$json = $this->getIPFile($file);
		$this->response = $json;
	}
	/*
	*  lookupIP
	*  return the json blob associated with an IP via wget
	*/
	public function lookupIP()
	{
		$this->makeIPDir();

		$file = $this->touchIPFile($this->request->ip);
		$link = $this->makeLink($this->request->ip);

		$json = $this->getJson($link, $file);
	
		$this->addToIpFile($this->request->ip);
		$this->response = $json;
	}
	/*
	*  getIPs
	*  return the ips file as an array
	*/
	public function getIPs()
	{
		$this->makeIPDir();
		$this->touchIPFile();

		$this->response = array();
		
		$lines = file($this->ALL_IP_FILE);
		foreach ($lines as $line) 
		{
			array_push($this->response, $line);
		}  
	}
}
