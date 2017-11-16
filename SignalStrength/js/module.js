registerController('SignalStrengthController', ['$api', '$scope', function($api, $scope) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";
	$scope.interfaces = [];
	$scope.interfaceStatus = [];
	$scope.selectedInterface = "";
	$scope.scanLoading = false;
	$scope.continuousScan = false;
	$scope.polarData = [];

	// this function gets info from the module.info file
	$scope.getVersionInfo = (function() {
		$api.request({
			module: 'SignalStrength',
			action: 'getVersionInfo'
		}, function(response) {
			$scope.title = response.title;
			$scope.version = response.version;
		});
	});

	// this function generates a random color for the graph.js graph to use
	function getRandomColor() {
		var letters = '0123456789ABCDEF'.split('');
		var color = '#';
		for (var i = 0; i < 6; i++ ) {
			color += letters[Math.floor(Math.random() * 16)];
		}
		return color;
	}

	// this function gets the cell info for an interface
	$scope.scanInterface = (function() {
		$scope.scanLoading = true;
		$api.request({
			module: 'SignalStrength',
			action: 'getInterfaceScan',
			selectedInterface: $scope.selectedInterface
		}, function (response) {
			$scope.scanLoading = false;
			$scope.interfaceScan = response.interfaceScan;
			polarDataArray = [];
			response.interfaceScan.forEach(function(scannedCell) {
				graphStrength = parseInt(scannedCell['strength'].substring(20, 23));
				graphStrength += 100;
				polarDataElement = {};
				polarDataElement['value'] = graphStrength;
				polarDataElement['color'] = getRandomColor();
				polarDataElement['label'] = scannedCell['essid'];
				polarDataArray.push(polarDataElement);
			});
			$scope.polarData = polarDataArray;
			// call the javascript function to draw the chart
			refreshChart();
			// RECURSION!!!!!!!
			if ($scope.continuousScan == true) {$scope.scanInterface();}
		});
	});

	// this function builds the Scan Settings panel
	$scope.getWirelessInterfaces = (function() {
		$api.request({
			module: 'SignalStrength',
			action: 'getWirelessInterfaces'
		}, function(response) {
			$scope.interfaces = response.interfaces;
			$scope.selectedInterface = response.interfaces[0];
		});
	});

	// this function gets the status for each wireless interface - Up or Down
	$scope.getInterfaceStatus = (function() {
		$api.request({
			module: 'SignalStrength',
			action: 'getInterfaceStatus'
		}, function(response) {
			$scope.interfaceStatus = response.interfaceStatus;
		});
	});

	// this function toggles an interfaces state - Up or Down
	$scope.toggleInterface = (function(interfaceToToggle, interfaceStatus) {
		$api.request({
			module: 'SignalStrength',
			action: 'toggleInterface',
			interface: interfaceToToggle,
			status: interfaceStatus
		}, function(response) {
			$scope.getInterfaceStatus();
		});
	});

	// this function toggles the continuous scan setting
	$scope.toggleContinuous = (function() {
		if ($scope.continuousScan == true) {$scope.continuousScan = false;} else {$scope.continuousScan = true;}
	});

	// initialize the panels
	$scope.getVersionInfo();
	$scope.getWirelessInterfaces();
	$scope.getInterfaceStatus();
}]);
