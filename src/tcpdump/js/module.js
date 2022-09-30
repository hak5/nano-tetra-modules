registerController('tcpdump_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'tcpdump',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('tcpdump_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
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
		refreshOutput: false,
		refreshHistory: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "tcpdump",
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
		})
	});

	$scope.toggletcpdump = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
			module: 'tcpdump',
			action: 'toggletcpdump',
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
			module: 'tcpdump',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'tcpdump',
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

registerController('tcpdump_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "tcpdump",
			action: "refreshOutput"
		}, function(response) {
			$scope.output = response;
		})
	});

	$scope.clearOutput = (function() {
		$api.request({
			module: "tcpdump",
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

registerController('tcpdump_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "tcpdump",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "tcpdump",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "tcpdump",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'tcpdump',
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

registerController('tcpdump_OptionsController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.command = "tcpdump ";

	$scope.interfaces = [];
	$scope.selectedInterface = "--";

	$scope.filter = "";
	$scope.timestamp = "--";
	$scope.resolve = "--";
	$scope.verbose = "--";

	$scope.dumpOptions = {
		option1: false,
		option2: false,
		option3: false,
		option4: false,
		option5: false,
		option6: false
	};

	$scope.update = (function(param) {
		if (updateFilter() != "")
			$scope.command = "tcpdump " + updateInterface() + updateVerbose() + updateRevolve() + updateTimestamp() + updateOptions() + '\'' + updateFilter() + '\'';
		else
			$scope.command = "tcpdump " + updateInterface() + updateVerbose() + updateRevolve() + updateTimestamp() + updateOptions()

		$rootScope.command = $scope.command;
	});

	$scope.appendFilter = (function(param) {
		if ($scope.filter.substr($scope.filter.length - 1) != ' ' && $scope.filter.length != 0)
			$scope.filter = $scope.filter + ' ' + param;
		else
			$scope.filter = $scope.filter + param;

		$scope.update();
	});

	function updateInterface() {
		var return_value = "";

		if ($scope.selectedInterface != "--")
			return_value = "-i " + $scope.selectedInterface + " ";

		return return_value;
	}

	function updateOptions() {
		var return_value = "";

		angular.forEach($scope.dumpOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		return return_value;
	}

	function updateFilter() {
		var return_value = "";

		if ($scope.filter != "")
			return_value = $scope.filter;

		return return_value;
	}

	function updateVerbose() {
		var return_value = "";

		if ($scope.verbose != "--")
			return_value = $scope.verbose + " ";

		return return_value;
	}

	function updateRevolve() {
		var return_value = "";

		if ($scope.resolve != "--")
			return_value = $scope.resolve + " ";

		return return_value;
	}

	function updateTimestamp() {
		var return_value = "";

		if ($scope.timestamp != "--")
			return_value = $scope.timestamp + " ";

		return return_value;
	}

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'tcpdump',
			action: 'getInterfaces'
		}, function(response) {
			$scope.interfaces = response;
			$scope.selectedInterface = "--";
		});
	});

	$scope.getInterfaces();
	$scope.update();
}]);
