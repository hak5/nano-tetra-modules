registerController('nmap_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'nmap',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('nmap_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.device = '';
	$scope.sdAvailable = false;
	$scope.internalAvailable = false;

	$rootScope.status = {
		installed: false,
		refreshOutput: false,
		refreshHistory: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "nmap",
			action: "refreshStatus"
		}, function(response) {
			$scope.status = response.status;
			$scope.statusLabel = response.statusLabel;

			$rootScope.status.installed = response.installed;
			$scope.device = response.device;
			$scope.sdAvailable = response.sdAvailable;
			$scope.internalAvailable = response.internalAvailable;
			if (response.processing) $scope.processing = true;
			$scope.install = response.install;
			$scope.installLabel = response.installLabel;
		})
	});

	$scope.togglenmap = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$api.request({
			module: 'nmap',
			action: 'togglenmap',
			command: $rootScope.command
		}, function(response) {
			$timeout(function() {
				$rootScope.status.refreshOutput = true;
				$rootScope.status.refreshHistory = false;

				$scope.starting = false;
				$scope.refreshStatus();

				$scope.scanInterval = $interval(function() {
					$api.request({
						module: 'nmap',
						action: 'scanStatus'
					}, function(response) {
						if (response.success === true) {
							$interval.cancel($scope.scanInterval);
							$rootScope.status.refreshOutput = false;
							$rootScope.status.refreshHistory = true;
						}
						$scope.refreshStatus();
					});
				}, 5000);

			}, 2000);
		})
	});

	$scope.handleDependencies = (function(param) {
		if (!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
			module: 'nmap',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'nmap',
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

registerController('nmap_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "nmap",
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

registerController('nmap_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "nmap",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "nmap",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "nmap",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'nmap',
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

registerController('nmap_OptionsController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.command = "nmap ";
	$scope.target = "";

	$scope.profile = "--";
	$scope.timing = "--";
	$scope.tcp = "--";
	$scope.nontcp = "--";

	$scope.scanOptions = {
		option1: false,
		option2: false,
		option3: false,
		option4: false,
		option5: false
	};

	$scope.pingOptions = {
		option1: false,
		option2: false,
		option3: false,
		option4: false
	};

	$scope.targetOptions = {
		option1: false
	};

	$scope.otherOptions = {
		option1: false,
		option2: false,
		option3: false,
		option4: false
	};

	$scope.update = (function(param) {
		if (updateProfile() != "")
			$scope.command = "nmap " + updateProfile() + updateTarget();
		else
			$scope.command = "nmap " + updateTiming() + updateTcp() + updateNontcp() + updateOptions() + updateTarget();

		$rootScope.command = $scope.command;
	});

	function updateOptions() {
		var return_value = "";

		angular.forEach($scope.scanOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		angular.forEach($scope.pingOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		angular.forEach($scope.targetOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		angular.forEach($scope.otherOptions, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		return return_value;
	}

	function updateTarget() {
		var return_value = "";

		return_value = $scope.target;

		return return_value;
	}

	function updateProfile() {
		var return_value = "";

		if ($scope.profile != "--")
			return_value = $scope.profile + " ";

		return return_value;
	}

	function updateTiming() {
		var return_value = "";

		if ($scope.timing != "--")
			return_value = $scope.timing + " ";

		return return_value;
	}

	function updateTcp() {
		var return_value = "";

		if ($scope.tcp != "--")
			return_value = $scope.tcp + " ";

		return return_value;
	}

	function updateNontcp() {
		var return_value = "";

		if ($scope.nontcp != "--")
			return_value = $scope.nontcp + " ";

		return return_value;
	}
	
	$scope.update();
}]);
