registerController('Deauth_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'Deauth',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('Deauth_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
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
		refreshOutput: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: 'Deauth',
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

	$scope.togglemdk3 = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;

		$api.request({
			module: 'Deauth',
			action: 'togglemdk3',
			interface: $scope.selectedInterface
		}, function(response) {
			$timeout(function() {
				$rootScope.status.refreshOutput = true;

				$scope.starting = false;
				$scope.refreshStatus();
				$scope.getInterfaces();

			}, 3000);
		})
	});

	$scope.saveAutostartSettings = (function() {
		$api.request({
			module: 'Deauth',
			action: 'saveAutostartSettings',
			settings: {
				interface: $scope.selectedInterface
			}
		}, function(response) {
			$scope.saveSettingsLabel = "success";
			$timeout(function() {
				$scope.saveSettingsLabel = "default";
			}, 2000);
		})
	});


	$scope.togglemdk3OnBoot = (function() {
		if ($scope.bootLabelON == "default") {
			$scope.bootLabelON = "success";
			$scope.bootLabelOFF = "default";
		} else {
			$scope.bootLabelON = "default";
			$scope.bootLabelOFF = "danger";
		}

		$api.request({
			module: 'Deauth',
			action: 'togglemdk3OnBoot',
		}, function(response) {
			$scope.refreshStatus();
		})
	});

	$scope.handleDependencies = (function(param) {
		if (!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
			module: 'Deauth',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'Deauth',
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

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'Deauth',
			action: 'getInterfaces'
		}, function(response) {
			$scope.interfaces = response.interfaces;
			if (response.selected != "")
				$scope.selectedInterface = response.selected;
			else
				$scope.selectedInterface = $scope.interfaces[0];
		});
	});

	$scope.refreshStatus();
	$scope.getInterfaces();

}]);

registerController('Deauth_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: 'Deauth',
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

registerController('Deauth_EditorController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.accessPoints = [];
	$scope.selectedAP = {};

	$scope.scanLabel = "default";
	$scope.scan = "Scan";
	$scope.scanning = false;

	$scope.saveListsLabel = "primary";
	$scope.saveLists = "Save";
	$scope.saving = false;

	$scope.blacklistData = '';
	$scope.whitelistData = '';

	$scope.clearWhitelist = (function() {
		$scope.whitelistData = '';
	});

	$scope.clearBlacklist = (function() {
		$scope.blacklistData = '';
	});

	$scope.getListsData = (function() {
		$api.request({
			module: 'Deauth',
			action: 'getListsData'
		}, function(response) {
			$scope.blacklistData = response.blacklistData;
			$scope.whitelistData = response.whitelistData;
		});
	});

	$scope.saveListsData = (function() {
		$scope.saveListsLabel = "warning";
		$scope.saveLists = "Saving...";
		$scope.saving = true;

		$api.request({
			module: 'Deauth',
			action: 'saveListsData',
			blacklistData: $scope.blacklistData,
			whitelistData: $scope.whitelistData
		}, function(response) {
			$scope.saveListsLabel = "success";
			$scope.saveLists = "Saved";

			$timeout(function() {
				$scope.saveListsLabel = "primary";
				$scope.saveLists = "Save";
				$scope.saving = false;
			}, 2000);
		});
	});

	$scope.addWhitelist = (function() {
		if ($scope.whitelistData != "")
			$scope.whitelistData = $scope.whitelistData + '\n' + '# ' + $scope.selectedAP.ssid + '\n' + $scope.selectedAP.mac;
		else
			$scope.whitelistData = '# ' + $scope.selectedAP.ssid + '\n' + $scope.selectedAP.mac;
	});

	$scope.addBlacklist = (function() {
		if ($scope.blacklistData != "")
			$scope.blacklistData = $scope.blacklistData + '\n' + '# ' + $scope.selectedAP.ssid + '\n' + $scope.selectedAP.mac;
		else
			$scope.blacklistData = '# ' + $scope.selectedAP.ssid + '\n' + $scope.selectedAP.mac;
	});

	$scope.scanForNetworks = (function() {
		$scope.scanLabel = "warning";
		$scope.scan = "Scanning...";
		$scope.scanning = true;

		$api.request({
			module: 'Deauth',
			action: 'scanForNetworks',
			interface: $scope.selectedInterface
		}, function(response) {
			$scope.scanLabel = "success";
			$scope.scan = "Done";

			$timeout(function() {
				$scope.scanLabel = "default";
				$scope.scan = "Scan";
				$scope.scanning = false;
			}, 2000);

			$scope.accessPoints = response;
			$scope.selectedAP = $scope.accessPoints[0];
		});
	});

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'Deauth',
			action: 'getInterfaces'
		}, function(response) {
			$scope.interfaces = response.interfaces;
			if (response.selected != "")
				$scope.selectedInterface = response.selected;
			else
				$scope.selectedInterface = $scope.interfaces[0];
		});
	});

	$scope.getInterfaces();
	$scope.getListsData();

}]);

registerController('Deauth_SettingsController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.settings = {
		speed: "",
		channels: "",
		mode: "whitelist"
	};

	$scope.saveSettingsLabel = "primary";
	$scope.saveSettings = "Save";
	$scope.saving = false;

	$scope.getSettings = function() {
		$api.request({
			module: 'Deauth',
			action: 'getSettings'
		}, function(response) {
			$scope.settings = response.settings;
		});
	};

	$scope.setSettings = function() {
		$scope.saveSettingsLabel = "warning";
		$scope.saveSettings = "Saving...";
		$scope.saving = true;

		$api.request({
			module: 'Deauth',
			action: 'setSettings',
			settings: $scope.settings
		}, function(response) {
			$scope.getSettings();

			$scope.saveSettingsLabel = "success";
			$scope.saveSettings = "Saved";

			$timeout(function() {
				$scope.saveSettingsLabel = "primary";
				$scope.saveSettings = "Save";
				$scope.saving = false;
			}, 2000);
		});
	};

	$scope.getSettings();

}]);
