registerController('ConnectedClientsController', ['$api', '$scope', function($api, $scope) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";
	$scope.clientslength = 0;
	$scope.wlan0clients = [];
	$scope.wlan01clients = [];
	$scope.wlan1clients = [];
	$scope.wlandev = [];
	$scope.dhcplength = 0;
	$scope.dhcpleases = [];
	$scope.blacklistlength = 0;
	$scope.blacklist = [];

	// this function gets info from the module.info file
	$scope.getVersionInfo = (function() {
		$api.request({
			module: 'ConnectedClients',
			action: 'getVersionInfo'
		}, function(response) {
			$scope.title = response.title;
			$scope.version = response.version;
		});
	});

	// this function gets the connected clients information and fills in the panel
	$scope.getConnectedClients = (function() {
		$api.request({
			module: 'ConnectedClients',
			action: 'getConnectedClients'
		}, function(response) {
			$scope.clientslength = response.wlan0clients.length + response.wlan01clients.length + response.wlan1clients.length;
			$scope.wlan0clients = response.wlan0clients;
			$scope.wlan01clients = response.wlan01clients;
			$scope.wlan1clients = response.wlan1clients;
			$scope.wlandev = response.wlandev;
		});
	});

	// this function adds a mac address to the blacklist
	$scope.addMacAddress = (function(macAddress) {
		$api.request({
			module: 'ConnectedClients',
			action: 'addMacAddress',
			macAddress: macAddress
		}, function(response) {
			$scope.getBlacklist();
		});
	});

	// this function gets the DHCP leases from the file system and fills in the panel
	$scope.getDHCPLeases = (function() {
		$api.request({
			module: 'ConnectedClients',
			action: 'getDHCPLeases'
		}, function(response) {
			$scope.dhcplength = response.dhcpleases.length;
			$dhcp = response.dhcpleases;
			for (var i = $scope.dhcplength - 1; i >= 0; i--) {
				$dhcp[i] = $dhcp[i].split(' ');
			}
			$scope.dhcpleases = $dhcp;
		});
	});

	// this function removes a MAC address from the blacklist
	$scope.removeMacAddress = (function(macAddress) {
		$api.request({
			module: 'ConnectedClients',
			action: 'removeMacAddress',
			macAddress: macAddress
		}, function(response) {
			$scope.getBlacklist();
		});
	});

	// this function retrieves the blacklist and fills it in on the panel
	$scope.getBlacklist = (function() {
		$api.request({
			module: 'ConnectedClients',
			action: 'getBlacklist'
		}, function(response) {
			$scope.blacklistlength = response.blacklist.length;
			$scope.blacklist = response.blacklist;
		});
	});

	// this function disassociates a MAC address
	$scope.disassociateMac = (function(macAddress) {
		$api.request({
			module: 'ConnectedClients',
			action: 'disassociateMac',
			macAddress: macAddress
		}, function(response) {
			$scope.getConnectedClients();
		});
	});

	// this function deauthenticates a MAC address
	$scope.deauthenticateMac = (function(macAddress) {
		$api.request({
			module: 'ConnectedClients',
			action: 'deauthenticateMac',
			macAddress: macAddress
		}, function(response) {
			$scope.getConnectedClients();
		});
	});

	// initialize the panels
	$scope.getVersionInfo();
	$scope.getBlacklist();
	$scope.getConnectedClients();
	$scope.getDHCPLeases();
}]);
