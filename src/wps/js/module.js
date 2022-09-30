registerController('wps_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'wps',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('wps_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.bootLabelON = "default";
	$scope.bootLabelOFF = "default";

	$scope.processes = [];

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		refreshHistory: false,
		refreshOutput: false,
		refresMonitors: false,
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: 'wps',
			action: "refreshStatus"
		}, function(response) {
			$scope.status = response.status;
			$scope.statusLabel = response.statusLabel;

			$rootScope.status.installed = response.installed;
			$scope.device = response.device;
			$scope.sdAvailable = response.sdAvailable;
			if (response.processing) $scope.processing = true;
			$scope.install = response.install;
			$scope.installLabel = response.installLabel;

			$scope.bootLabelON = response.bootLabelON;
			$scope.bootLabelOFF = response.bootLabelOFF;
		})
	});

	$scope.togglewps = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
			module: 'wps',
			action: 'togglewps',
			command: $rootScope.command
		}, function(response) {
			$timeout(function() {
				$rootScope.status.refreshOutput = true;
				$rootScope.status.refreshHistory = true;

				$scope.getProcesses();

				$scope.starting = false;
				$scope.refreshStatus();

			}, 2000);
		})
	});

	$scope.handleDependencies = (function(param) {
		if (!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
			module: 'wps',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'wps',
						action: 'handleDependenciesStatus'
					}, function(response) {
						if (response.success === true) {
							$scope.processing = false;
							$interval.cancel($scope.handleDependenciesInterval);
							$scope.refreshStatus();
						}
					});
				}, 5000);
			}
		});
	});

	$scope.getProcesses = (function() {
		$api.request({
			module: 'wps',
			action: 'getProcesses'
		}, function(response) {
			$scope.processes = response;
		});
	});

	$scope.refreshStatus();
	$scope.getProcesses();

}]);

registerController('wps_ScanController', ['$api', '$scope', '$rootScope', '$timeout', '$interval', '$filter', function($api, $scope, $rootScope, $timeout, $interval, $filter) {
	$scope.accessPoints = [];
	$scope.interfaces = [];
	$scope.monitors = [];

	$scope.scanLabel = "info";
	$scope.scan = "Scan";
	$scope.scanning = false;

	$scope.startMonLabel = "default";
	$scope.startMon = "Start Monitor";
	$scope.startingMon = false;

	$scope.stopMonLabel = "default";
	$scope.stopMon = "Stop Monitor";
	$scope.stoppingMon = false;

	$scope.sortType = 'ssid';
	$scope.sortReverse = false;
	$scope.search = '';

	$scope.scanDuration = '15';
	var mytimeout = null;
	var counter = 0;

	$scope.clearFilter = (function() {
		$scope.sortType = 'ssid';
		$scope.sortReverse = false;
		$scope.search = '';
	});

	$scope.getMACInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'wps',
			action: 'getMACInfo',
			mac: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.startMonitor = (function() {
		$scope.startMonLabel = "warning";
		$scope.startMon = "Starting...";
		$scope.startingMon = true;

		$api.request({
			module: 'wps',
			action: 'startMonitor',
			interface: $scope.selectedInterface
		}, function(response) {
			$scope.startMonLabel = "success";
			$scope.startMon = "Done";

			$timeout(function() {
				$scope.getInterfaces();
				$scope.getMonitors();

				$scope.startMonLabel = "default";
				$scope.startMon = "Start Monitor";
				$scope.startingMon = false;
			}, 2000);
		});
	});

	$scope.stopMonitor = (function() {
		$scope.stopMonLabel = "warning";
		$scope.stopMon = "Stopping...";
		$scope.stoppingMon = true;

		$api.request({
			module: 'wps',
			action: 'stopMonitor',
			monitor: $scope.selectedMonitor
		}, function(response) {
			$scope.stopMonLabel = "success";
			$scope.stopMon = "Done";

			$timeout(function() {
				$scope.getInterfaces();
				$scope.getMonitors();

				$scope.stopMonLabel = "default";
				$scope.stopMon = "Stop Monitor";
				$scope.stoppingMon = false;
			}, 2000);
		});
	});

	$scope.onTimeout = function() {
		if (counter === 0) {
			$timeout.cancel(mytimeout);
			$scope.scan = "Collecting results...";
			return;
		}
		counter--;
		$scope.scan = "Scanning... " + $filter('date')(new Date(1970, 0, 1).setSeconds(counter), 'mm:ss');
		mytimeout = $timeout($scope.onTimeout, 1000);
	};

	$scope.scanForNetworks = (function() {
		$scope.scanLabel = "warning";
		$scope.scanning = true;

		if ($scope.selectedMonitor) {
			counter = $scope.scanDuration;
			$scope.onTimeout();
		} else {
			$scope.scan = "Scanning...";
		}

		$api.request({
			module: 'wps',
			action: 'scanForNetworks',
			interface: $scope.selectedInterface,
			duration: $scope.scanDuration,
			monitor: $scope.selectedMonitor
		}, function(response) {
			$scope.scanLabel = "success";
			$scope.scan = "Done";
			$scope.accessPoints = response;

			$timeout(function() {
				$scope.scanLabel = "info";
				$scope.scan = "Scan";
				$scope.scanning = false;
			}, 2000);
		});
	});

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'wps',
			action: 'getInterfaces'
		}, function(response) {
			$scope.interfaces = response.interfaces;
			$scope.selectedInterface = $scope.interfaces[0];
		});
	});

	$scope.getMonitors = (function() {
		$rootScope.status.refreshMonitors = false;

		$api.request({
			module: 'wps',
			action: 'getMonitors'
		}, function(response) {
			$scope.monitors = response.monitors;
			$scope.selectedMonitor = $scope.monitors[0];

			$rootScope.status.refreshMonitors = true;
		});
	});

	$scope.target = (function(ap) {
		$rootScope.target = ap;
		$('#Options').collapse('show');
	});

	$scope.getInterfaces();
	$scope.getMonitors();
}]);

registerController('wps_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "wps",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "wps",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "wps",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'wps',
			action: 'downloadHistory',
			file: param
		}, function(response) {
			if (response.error === undefined) {
				window.location = '/api/?download=' + response.download;
			}
		});
	});

	$scope.refreshHistory();

	$rootScope.$watch('status.refreshHistory', function(param) {
		if (param) {
			$scope.refreshHistory();
		}
	});

}]);

registerController('wps_OptionsController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.command = "";

	$scope.program = "reaver";
	$scope.bssid = '';
	$scope.essid = '';
	$scope.channel = '';
	$scope.monitors = [];

	$scope.reaverAdvancedOptions = {
		option1: {
			check: false,
			val: '1'
		},
		option2: {
			check: false,
			val: '60'
		},
		option3: {
			check: false,
			val: '100'
		},
		option4: {
			check: false,
			val: '0'
		},
		option5: {
			check: false,
			val: '5'
		},
		option6: {
			check: false,
			val: '0.20'
		},
		option7: {
			check: false,
			val: '1:10'
		},
		option8: {
			check: false,
			val: '1'
		}
	};

	$scope.bullyAdvancedOptions = {
		option1: {
			check: false,
			val: '7'
		},
		option2: {
			check: false,
			val: '43'
		},
		option3: {
			check: false,
			val: '12345'
		},
		option4: {
			check: false,
			val: '3'
		},
		option5: {
			check: false,
			val: '2'
		},
		option6: {
			check: false,
			val: '0,1'
		},
		option7: {
			check: false,
			val: '5,1'
		}
	};

	$scope.reaverOptions = {
		option1: false,
		option2: false,
		option3: false,
		option4: false,
		option5: false,
		option6: false,
		option7: false,
		option8: false,
		option9: false,
		option10: false,
		option11: false,
		option12: false,
		option13: false,
		option14: false,
		option15: false,
		option16: false,
		option17: false,
		option18: false
	};

	$scope.bullyOptions = {
		option1: false,
		option2: false,
		option3: false,
		option4: false,
		option5: false,
		option6: false,
		option7: false,
		option8: false,
		option9: false,
		option10: false,
		option11: false,
		option12: false,
		option13: false,
		option14: false,
		option15: false
	};

	$scope.update = (function(param) {
		if ($scope.program == "reaver")
			$scope.command = updateProgram() + updateMonitor() + updateReaverOptions() + updateReaverAdvancedOptions() + updateBSSID() + updateESSID() + updateChannel();
		else if ($scope.program == "bully")
			$scope.command = updateProgram() + updateMonitor() + updateBullyOptions() + updateBullyAdvancedOptions() + updateBSSID() + updateESSID() + updateChannel();
		else
			$scope.command = '';

		$rootScope.command = $scope.command;
	});

	$rootScope.$watch('target', function(param) {
		if (param) {
			$scope.bssid = $rootScope.target.mac;
			$scope.essid = $rootScope.target.ssid;
			$scope.channel = $rootScope.target.channel;
			$scope.update();
		}
	});

	$rootScope.$watch('status.refreshMonitors', function(param) {
		if (param) {
			$scope.getMonitors();
		}
	});

	$scope.getMonitors = (function() {
		$api.request({
			module: 'wps',
			action: 'getMonitors'
		}, function(response) {
			$scope.monitors = response.monitors;
			$scope.selectedMonitor = $scope.monitors[0];
			$scope.update();
		});
	});

	function updateReaverOptions() {
		var return_value = "";

		angular.forEach($scope.reaverOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		return return_value;
	}

	function updateReaverAdvancedOptions() {
		var return_value = "";

		angular.forEach($scope.reaverAdvancedOptions, function(value, key) {
			if (value.check != false)
				return_value += value.check + " " + value.val + " ";
		});

		return return_value;
	}

	function updateBullyOptions() {
		var return_value = "";

		angular.forEach($scope.bullyOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		return return_value;
	}

	function updateBullyAdvancedOptions() {
		var return_value = "";

		angular.forEach($scope.bullyAdvancedOptions, function(value, key) {
			if (value.check != false)
				return_value += value.check + " " + value.val + " ";
		});

		return return_value;
	}

	function updateProgram() {
		var return_value = "";

		return_value = $scope.program + " ";

		return return_value;
	}

	function updateMonitor() {
		var return_value = "";

		if (!angular.isUndefined($scope.selectedMonitor)) {
			if ($scope.program == "reaver")
				return_value = "-i " + $scope.selectedMonitor + " ";
			else if ($scope.program == "bully")
				return_value = $scope.selectedMonitor + " ";
		}

		return return_value;
	}

	function updateBSSID() {
		var return_value = "";

		if ($scope.bssid != "")
			return_value = "-b " + $scope.bssid + " ";

		return return_value;
	}

	function updateESSID() {
		var return_value = "";

		if ($scope.essid != "")
			return_value = "-e \"" + $scope.essid + "\" ";

		return return_value;
	}

	function updateChannel() {
		var return_value = "";

		if ($scope.channel != "")
			return_value = "-c " + $scope.channel;

		return return_value;
	}
	
	$scope.update();
}]);

registerController('wps_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "wps",
			action: "refreshOutput"
		}, function(response) {
			$scope.output = response;
		})
	});

	$scope.toggleAutoRefresh = (function() {
		if ($scope.autoRefreshInterval) {
			$interval.cancel($scope.autoRefreshInterval);
			$scope.autoRefreshInterval = null;
			$scope.refreshLabelON = "default";
			$scope.refreshLabelOFF = "danger";
		} else {
			$scope.refreshLabelON = "success";
			$scope.refreshLabelOFF = "default";

			$scope.autoRefreshInterval = $interval(function() {
				$scope.refreshOutput();
			}, 5000);
		}
	});

	$scope.refreshOutput();

	$rootScope.$watch('status.refreshOutput', function(param) {
		if (param) {
			$scope.refreshOutput();
		}
	});

}]);
