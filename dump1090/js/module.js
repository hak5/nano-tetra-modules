registerController('dump1090_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'dump1090',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('dump1090_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		running: false,
		refreshOutput: false,
		refreshHistory: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "dump1090",
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
			$rootScope.status.running = response.running;
		})
	});

	$scope.toggledump1090 = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
			module: 'dump1090',
			action: 'toggledump1090',
			command: $rootScope.command
		}, function(response) {
			$timeout(function() {
				$rootScope.status.refreshOutput = true;
				$rootScope.status.refreshHistory = true;

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
			module: 'dump1090',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'dump1090',
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

registerController('dump1090_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "dump1090",
			action: "refreshOutput"
		}, function(response) {
			$scope.output = response;
		})
	});

	$scope.clearOutput = (function() {
		$api.request({
			module: "dump1090",
			action: "clearOutput"
		}, function(response) {
			$scope.refreshOutput();
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

registerController('dump1090_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "dump1090",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "dump1090",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "dump1090",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'dump1090',
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

registerController('dump1090_MapController', ['$api', '$scope', '$rootScope', '$timeout', '$location', '$sce', function($api, $scope, $rootScope, $timeout, $location, $sce) {

	$scope.getMapSrc = (function(param) {
		return $sce.trustAsResourceUrl('http://' + $location.host() + ':9090/');
	});

	$scope.refreshMap = (function(param) {
		document.getElementById('map').src += '';
	});

	$rootScope.$watch('status.refreshOutput', function(param) {
		if (param) {
			$scope.refreshMap();
		}
	});

}]);

registerController('dump1090_ListController', ['$api', '$scope', '$rootScope', '$timeout', '$interval', function($api, $scope, $rootScope, $timeout, $interval) {
	$scope.list = [];

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

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
				$scope.refreshList();
			}, 5000);
		}
	});

	$scope.refreshList = (function() {
		$api.request({
			module: "dump1090",
			action: "refreshList"
		}, function(response) {
			$scope.list = response;
		})
	});

	$scope.refreshList();

	$rootScope.$watch('status.refreshOutput', function(param) {
		if (param) {
			$scope.refreshList();
		}
	});

}]);

registerController('dump1090_SettingsController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.settings = {
		csv: false,
		gain: "",
		frequency: "",
		metrics: false,
		agc: false,
		aggressive: false
	};

	$scope.saveSettingsLabel = "primary";
	$scope.saveSettings = "Save";
	$scope.saving = false;

	$scope.getSettings = function() {
		$api.request({
			module: 'dump1090',
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
			module: 'dump1090',
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
