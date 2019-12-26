registerController('SiteSurvey_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'SiteSurvey',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('SiteSurvey_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.bootLabelON = "default";
	$scope.bootLabelOFF = "default";

	$scope.interfaces = [];
	$scope.selectedInterface = "--";

	$scope.saveSettingsLabel = "default";

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		refreshCapture: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: 'SiteSurvey',
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

	$scope.handleDependencies = (function(param) {
		if (!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
			module: 'SiteSurvey',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'SiteSurvey',
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

	$scope.refreshStatus();

}]);

registerController('SiteSurvey_ScanController', ['$api', '$scope', '$rootScope', '$timeout', '$interval', '$filter', function($api, $scope, $rootScope, $timeout, $interval, $filter) {
	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.accessPoints = [];
	$scope.processes = [];
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

	$scope.captureRunning = false;
	$scope.deauthRunning = false;

	$scope.scanDuration = '15';
	$scope.scanType = 'apOnly';
	var mytimeout = null;
	var counter = 0;

	$scope.autoRefreshInterval = false;

	$scope.sortType = 'ssid';
	$scope.sortReverse = false;
	$scope.search = '';

	$scope.clearFilter = (function() {
		$scope.sortType = 'ssid';
		$scope.sortReverse = false;
		$scope.search = '';
	});

	$scope.getMACInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'SiteSurvey',
			action: 'getMACInfo',
			mac: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.toggleAutoRefresh = (function() {
		if ($scope.autoRefreshInterval) {
			$scope.autoRefreshInterval = false;
			$scope.refreshLabelON = "default";
			$scope.refreshLabelOFF = "danger";
		} else {
			$scope.autoRefreshInterval = true;
			$scope.refreshLabelON = "success";
			$scope.refreshLabelOFF = "default";

			$scope.scanForNetworks();
		}
	});

	$scope.startMonitor = (function() {
		$scope.startMonLabel = "warning";
		$scope.startMon = "Starting...";
		$scope.startingMon = true;

		$api.request({
			module: 'SiteSurvey',
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
			module: 'SiteSurvey',
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

	$scope.isCaptureRunning = (function() {
		for (var i = 0, len = $scope.accessPoints.length; i < len; i++) {
			if ($scope.accessPoints[i]['captureRunning'] == 1) {
				$scope.captureRunning = true;
				break;
			}
			$scope.captureRunning = false;
		}
	});

	$scope.isDeauthRunning = (function() {
		for (var i = 0, len = $scope.accessPoints.length; i < len; i++) {
			if ($scope.accessPoints[i]['deauthRunning'] == 1) {
				$scope.deauthRunning = true;
				break;
			}
			$scope.deauthRunning = false;
		}
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

		if ($scope.scanType == 'clientAP') {
			counter = $scope.scanDuration;
			$scope.onTimeout();
		} else {
			$scope.scan = "Scanning...";
		}

		$api.request({
			module: 'SiteSurvey',
			action: 'scanForNetworks',
			interface: $scope.selectedInterface,
			monitor: $scope.selectedMonitor,
			type: $scope.scanType,
			duration: $scope.scanDuration
		}, function(response) {
			$scope.scanLabel = "success";
			$scope.scan = "Done";

			$scope.accessPoints = response;
			$scope.isCaptureRunning();
			$scope.isDeauthRunning();

			$timeout(function() {
				$scope.scanLabel = "info";
				$scope.scan = "Scan";
				$scope.scanning = false;

				if ($scope.autoRefreshInterval)
					$scope.scanForNetworks();
			}, 2000);

			$timeout(function() {
				$scope.getProcesses();
			}, 2000);
		});
	});

	$scope.stopCapture = (function() {

		angular.forEach($scope.accessPoints, function(apData, id) {
			$scope.accessPoints[id]['captureRunning'] = 0;
			$scope.accessPoints[id]['captureOnSelected'] = 0;
		});

		$rootScope.status.refreshCapture = false;

		$api.request({
			module: 'SiteSurvey',
			action: 'toggleCapture',
		}, function(response) {
			$rootScope.status.refreshCapture = true;
			$scope.isCaptureRunning();

			$timeout(function() {
				$scope.getProcesses();
			}, 1000);
		});
	});

	$scope.stopDeauth = (function() {

		angular.forEach($scope.accessPoints, function(apData, id) {
			$scope.accessPoints[id]['deauthRunning'] = 0;
			$scope.accessPoints[id]['deauthOnSelected'] = 0;
		});

		$rootScope.status.refreshCapture = false;

		$api.request({
			module: 'SiteSurvey',
			action: 'toggleDeauth',
		}, function(response) {
			$rootScope.status.refreshCapture = true;
			$scope.isDeauthRunning();

			$timeout(function() {
				$scope.getProcesses();
			}, 1000);
		});
	});

	$scope.toggleCapture = (function(ap) {

		angular.forEach($scope.accessPoints, function(apData, id) {
			if ($scope.accessPoints[id]['captureRunning'] == 1)
				$scope.accessPoints[id]['captureRunning'] = 0;
			else
				$scope.accessPoints[id]['captureRunning'] = 1

			if (apData['mac'] == ap['mac'])
				if ($scope.accessPoints[id]['captureOnSelected'] == 1)
					$scope.accessPoints[id]['captureOnSelected'] = 0;
				else
					$scope.accessPoints[id]['captureOnSelected'] = 1;
			else
				$scope.accessPoints[id]['captureOnSelected'] = 0;
		});

		$rootScope.status.refreshCapture = false;

		$api.request({
			module: 'SiteSurvey',
			action: 'toggleCapture',
			interface: $scope.selectedMonitor,
			ap: ap
		}, function(response) {
			$rootScope.status.refreshCapture = true;
			$scope.isCaptureRunning();

			$timeout(function() {
				$scope.getProcesses();
			}, 2000);
		});
	});

	$scope.toggleDeauth = (function(ap, client) {

		angular.forEach($scope.accessPoints, function(apData, id) {
			if ($scope.accessPoints[id]['deauthRunning'] == 1)
				$scope.accessPoints[id]['deauthRunning'] = 0;
			else
				$scope.accessPoints[id]['deauthRunning'] = 1

			if (apData['mac'] == ap['mac'])
				if ($scope.accessPoints[id]['deauthOnSelected'] == 1)
					$scope.accessPoints[id]['deauthOnSelected'] = 0;
				else
					$scope.accessPoints[id]['deauthOnSelected'] = 1;
			else
				$scope.accessPoints[id]['deauthOnSelected'] = 0;
		});

		$rootScope.status.refreshCapture = false;

		$api.request({
			module: 'SiteSurvey',
			action: 'toggleDeauth',
			interface: $scope.selectedMonitor,
			ap: ap,
			client: client
		}, function(response) {
			$rootScope.status.refreshCapture = true;
			$scope.isDeauthRunning();

			$timeout(function() {
				$scope.getProcesses();
			}, 2000);
		});
	});

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'SiteSurvey',
			action: 'getInterfaces'
		}, function(response) {
			$scope.interfaces = response.interfaces;
			$scope.selectedInterface = $scope.interfaces[0];
		});
	});

	$scope.getMonitors = (function() {
		$api.request({
			module: 'SiteSurvey',
			action: 'getMonitors'
		}, function(response) {
			$scope.monitors = response.monitors;
			$scope.selectedMonitor = $scope.monitors[0];
		});
	});

	$scope.getProcesses = (function() {
		$api.request({
			module: 'SiteSurvey',
			action: 'getProcesses'
		}, function(response) {
			$scope.processes = response;
		});
	});

	$scope.getInterfaces();
	$scope.getMonitors();
	$scope.getProcesses();

}]);

registerController('SiteSurvey_CaptureController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.capture = [];
	$scope.captureOutput = 'Loading...';
	$scope.captureDate = 'Loading...';
	$scope.throbber = false;

	$scope.refreshCapture = (function() {
		$api.request({
			module: "SiteSurvey",
			action: "refreshCapture"
		}, function(response) {
			$scope.capture = response;
		})
	});

	$scope.viewCapture = (function(param) {
		$api.request({
			module: "SiteSurvey",
			action: "viewCapture",
			file: param
		}, function(response) {
			$scope.captureOutput = response.output;
			$scope.captureDate = response.date;
		})
	});

	$scope.deleteCapture = (function(param) {
		$api.request({
			module: "SiteSurvey",
			action: "deleteCapture",
			file: param
		}, function(response) {
			$scope.refreshCapture();
		})
	});

	$scope.downloadCapture = (function(param) {
		$scope.throbber = true;
		$api.request({
			module: 'SiteSurvey',
			action: 'downloadCapture',
			file: param
		}, function(response) {
			$scope.throbber = false;
			if (response.error === undefined) {
				window.location = '/api/?download=' + response.download;
			}
		});
	});

	$scope.refreshCapture();

	$rootScope.$watch('status.refreshCapture', function(param) {
		if (param) {
			$scope.refreshCapture();
		}
	});

}]);
