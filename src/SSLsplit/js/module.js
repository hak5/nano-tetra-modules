registerController('SSLsplit_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'SSLsplit',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('SSLsplit_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.verbose = false;
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.certificate = "Loading...";
	$scope.certificateLabel = "default";
	$scope.generating = false;

	$scope.bootLabelON = "default";
	$scope.bootLabelOFF = "default";

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		generated: false,
		refreshOutput: false,
		refreshHistory: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "SSLsplit",
			action: "refreshStatus"
		}, function(response) {
			$scope.status = response.status;
			$scope.statusLabel = response.statusLabel;
			$scope.verbose = response.verbose;

			$rootScope.status.installed = response.installed;
			$scope.device = response.device;
			$scope.sdAvailable = response.sdAvailable;
			if (response.processing) $scope.processing = true;
			$scope.install = response.install;
			$scope.installLabel = response.installLabel;

			$rootScope.status.generated = response.generated;
			$scope.certificate = response.certificate;
			if (response.generating) $scope.generating = true;
			$scope.certificateLabel = response.certificateLabel;

			$scope.bootLabelON = response.bootLabelON;
			$scope.bootLabelOFF = response.bootLabelOFF;
		})
	});

	$scope.handleCertificate = (function() {
		if ($scope.certificate != "Generated")
			$scope.certificate = "Generating...";
		else
			$scope.certificate = "Removing...";

		$api.request({
			module: 'SSLsplit',
			action: 'handleCertificate'
		}, function(response) {
			if (response.success === true) {
				$scope.certificateLabel = "warning";
				$scope.generating = true;

				$scope.handleCertificateInterval = $interval(function() {
					$api.request({
						module: 'SSLsplit',
						action: 'handleCertificateStatus'
					}, function(response) {
						if (response.success === true) {
							$scope.generating = false;
							$interval.cancel($scope.handleCertificateInterval);
							$scope.refreshStatus();
						}
					});
				}, 5000);
			}
		});
	});

	$scope.toggleSSLsplit = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
			module: 'SSLsplit',
			action: 'toggleSSLsplit',
			verbose: $scope.verbose
		}, function(response) {
			$timeout(function() {
				$rootScope.status.refreshOutput = true;
				$rootScope.status.refreshHistory = true;

				$scope.starting = false;
				$scope.refreshStatus();
			}, 2000);
		})
	});

	$scope.toggleSSLsplitOnBoot = (function() {
		if ($scope.bootLabelON == "default") {
			$scope.bootLabelON = "success";
			$scope.bootLabelOFF = "default";
		} else {
			$scope.bootLabelON = "default";
			$scope.bootLabelOFF = "danger";
		}

		$api.request({
			module: 'SSLsplit',
			action: 'toggleSSLsplitOnBoot',
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
			module: 'SSLsplit',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'SSLsplit',
						action: 'handleDependenciesStatus'
					}, function(response) {
						if (response.success === true) {
							$scope.processing = false;
							$scope.refreshStatus();
							$interval.cancel($scope.handleDependenciesInterval);
						}
					});
				}, 5000);
			}
		});
	});

	$scope.refreshStatus();

}]);

registerController('SSLsplit_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "SSLsplit",
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

registerController('SSLsplit_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "SSLsplit",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "SSLsplit",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "SSLsplit",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'SSLsplit',
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

registerController('SSLsplit_ConfigurationController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
	$scope.configurationData = '';
	$scope.saveConfigurationLabel = "primary";
	$scope.saveConfiguration = "Save";
	$scope.saving = false;

	$scope.saveConfigurationData = (function() {
		$scope.saveConfigurationLabel = "warning";
		$scope.saveConfiguration = "Saving...";
		$scope.saving = true;

		$api.request({
			module: 'SSLsplit',
			action: 'saveConfigurationData',
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
			module: 'SSLsplit',
			action: 'getConfigurationData'
		}, function(response) {
			$scope.configurationData = response.configurationData;
		});
	});

	$scope.getConfigurationData();
}]);
