registerController('DNSspoof_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'DNSspoof',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('DNSspoof_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.bootLabelON = "default";
	$scope.bootLabelOFF = "default";

	$scope.interfaces = [];
	$scope.selectedInterface = "";

	$scope.saveSettingsLabel = "default";

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		refreshOutput: false,
		refreshHistory: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "DNSspoof",
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

	$scope.toggleDNSspoof = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
			module: 'DNSspoof',
			action: 'toggleDNSspoof',
			interface: $scope.selectedInterface
		}, function(response) {
			$timeout(function() {
				$rootScope.status.refreshOutput = true;
				$rootScope.status.refreshHistory = true;

				$scope.starting = false;
				$scope.refreshStatus();
			}, 2000);
		})
	});

	$scope.saveAutostartSettings = (function() {
		$api.request({
			module: 'DNSspoof',
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


	$scope.toggleDNSspoofOnBoot = (function() {
		if ($scope.bootLabelON == "default") {
			$scope.bootLabelON = "success";
			$scope.bootLabelOFF = "default";
		} else {
			$scope.bootLabelON = "default";
			$scope.bootLabelOFF = "danger";
		}

		$api.request({
			module: 'DNSspoof',
			action: 'toggleDNSspoofOnBoot',
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
			module: 'DNSspoof',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'DNSspoof',
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
			module: 'DNSspoof',
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

registerController('DNSspoof_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "DNSspoof",
			action: "refreshOutput",
			filter: $scope.filter
		}, function(response) {
			$scope.output = response;
		})
	});

	$scope.clearFilter = (function() {
		$scope.filter = '';
		$scope.refreshOutput();
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

registerController('DNSspoof_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "DNSspoof",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "DNSspoof",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "DNSspoof",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'DNSspoof',
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

registerController('DNSspoof_HostsController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
	$scope.configurationData = '';
	$scope.saveConfigurationLabel = "primary";
	$scope.saveConfiguration = "Save";
	$scope.saving = false;

	$scope.saveConfigurationData = (function() {
		$scope.saveConfigurationLabel = "warning";
		$scope.saveConfiguration = "Saving...";
		$scope.saving = true;

		$api.request({
			module: 'DNSspoof',
			action: 'saveHostsData',
			configurationData: $scope.configurationData
		}, function(response) {
			$scope.saveConfigurationLabel = "success";
			$scope.saveConfiguration = "Saved";

			$timeout(function() {
				$scope.saveConfigurationLabel = "primary";
				$scope.saveConfiguration = "Save";
				$scope.saving = false;
			}, 2000);
		});
	});

	$scope.getConfigurationData = (function() {
		$api.request({
			module: 'DNSspoof',
			action: 'getHostsData'
		}, function(response) {
			$scope.configurationData = response.configurationData;
		});
	});

	$scope.getConfigurationData();
}]);

registerController('DNSspoof_LandingPageController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
	$scope.configurationData = '';
	$scope.saveConfigurationLabel = "primary";
	$scope.saveConfiguration = "Save";
	$scope.saving = false;

	$scope.saveConfigurationData = (function() {
		$scope.saveConfigurationLabel = "warning";
		$scope.saveConfiguration = "Saving...";
		$scope.saving = true;

		$api.request({
			module: 'DNSspoof',
			action: 'saveLandingPageData',
			configurationData: $scope.configurationData
		}, function(response) {
			$scope.saveConfigurationLabel = "success";
			$scope.saveConfiguration = "Saved";

			$timeout(function() {
				$scope.saveConfigurationLabel = "primary";
				$scope.saveConfiguration = "Save";
				$scope.saving = false;
			}, 2000);
		});
	});

	$scope.getConfigurationData = (function() {
		$api.request({
			module: 'DNSspoof',
			action: 'getLandingPageData'
		}, function(response) {
			$scope.configurationData = response.configurationData;
		});
	});

	$scope.getConfigurationData();
}]);
