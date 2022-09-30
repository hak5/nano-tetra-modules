<?php namespace pineapple;

class ConnectedClients extends Module
{
	public function route()
	{
		switch ($this->request->action) {
			case 'getVersionInfo':
			$this->getVersionInfo();
			break;
			case 'getDHCPLeases':
			$this->getDHCPLeases();
			break;
			case 'getBlacklist':
			$this->getBlacklist();
			break;
			case 'getConnectedClients':
			$this->getConnectedClients();
			break;
			case 'removeMacAddress':
			$this->removeMacAddress();
			break;
			case 'addMacAddress':
			$this->addMacAddress();
			break;
			case 'disassociateMac':
			$this->disassociateMac();
			break;
			case 'deauthenticateMac':
			$this->deauthenticateMac();
			break;
		}
	}

	protected function getVersionInfo() {
		$moduleInfo = @json_decode(file_get_contents("/pineapple/modules/ConnectedClients/module.info"));
		$this->response = array('title' => $moduleInfo->title, 'version' => $moduleInfo->version);
	}

	private function getDHCPLeases() {
		exec("cat /tmp/dhcp.leases", $dhcpleases);
		$this->response = array('dhcpleases' => $dhcpleases);		

	}

	private function getBlacklist() {
		exec("pineapple karma list_macs", $mac_list);
		$this->response = array('blacklist' => $mac_list);
	}

	private function getConnectedClients() {
		exec("iwconfig 2>/dev/null | grep IEEE | awk '{print $1}'", $wlandev);
		exec("iw dev $wlandev[0] station dump | grep Station | awk '{print $2}'", $wlan0clients);
		exec("iw dev $wlandev[1] station dump | grep Station | awk '{print $2}'", $wlan01clients);
		exec("iw dev $wlandev[2] station dump | grep Station | awk '{print $2}'", $wlan1clients);
		$this->response = array('wlan0clients' => $wlan0clients, 'wlan01clients' => $wlan01clients, 'wlan1clients' => $wlan1clients, 'wlandev' => $wlandev);
	}

	private function removeMacAddress() {
		exec('pineapple karma del_mac "'.$this->request->macAddress.'"', $removeMacResponse);
		$this->response = array('removeMacResponse' => $removeMacResponse);
	}

	private function addMacAddress() {
		exec('pineapple karma add_mac "'.$this->request->macAddress.'"', $addMacResponse);
		$this->response = array('addMacResponse' => $addMacResponse);
	}

	private function disassociateMac() {
		exec('hostapd_cli disassociate "'.$this->request->macAddress.'"', $disassociateResponse);
		$this->response = array('disassociateResponse' => $disassociateResponse);
	}

	private function deauthenticateMac() {
		exec('hostapd_cli deauthenticate "'.$this->request->macAddress.'"', $deauthenticateResponse);
		$this->response = array('deauthSuccess' => 'Successful', 'deauthenticateResponse' => $deauthenticateResponse);
	}
}
