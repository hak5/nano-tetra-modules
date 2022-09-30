registerController('OnlineHashCrack_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'OnlineHashCrack',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('OnlineHashCrack_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.key = "Loading...";
	$scope.keyLabel = "default";
	$scope.generating = false;

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		generated: false,
		refreshOutput: false,
		refreshKnownHosts: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "OnlineHashCrack",
			action: "refreshStatus"
		}, function(response) {
			$rootScope.status.installed = response.installed;
			$scope.device = response.device;
			$scope.sdAvailable = response.sdAvailable;
			if (response.processing) $scope.processing = true;
			$scope.install = response.install;
			$scope.installLabel = response.installLabel;

		})
	});

	$scope.handleDependencies = (function(param) {
		if (!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
			module: 'OnlineHashCrack',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'OnlineHashCrack',
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

registerController('OnlineHashCrack_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';

	$scope.refreshOutput = (function() {
		$api.request({
			module: "OnlineHashCrack",
			action: "refreshOutput",
			filter: $scope.filter
		}, function(response) {
			$scope.output = response;
		})
	});

	$scope.clearOutput = (function() {
		$api.request({
			module: "OnlineHashCrack",
			action: "clearOutput"
		}, function(response) {
			$scope.refreshOutput();
		})
	});

	$scope.refreshOutput();

	$rootScope.$watch('status.refreshOutput', function(param) {
		if (param) {
			$scope.refreshOutput();
		}
	});

}]);

registerController('OnlineHashCrack_WPAController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.file = '';

	$scope.submitWPALabel = "primary";
	$scope.submitWPA = "Submit";

	$scope.working = false;

	$scope.selectedFile = '--';
	$scope.files = [];

	$scope.getCapFiles = function() {
		$api.request({
			module: 'OnlineHashCrack',
			action: 'getCapFiles'
		}, function(response) {
			$scope.files = response.files;
		});
	};

	$scope.submitWPAOnline = (function() {
		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshKnownHosts = false;

		if ($scope.selectedFile != '')
			$file = $scope.selectedFile;
		else
			$file = $scope.file;

		$api.request({
			module: 'OnlineHashCrack',
			action: 'submitWPAOnline',
			file: $file
		}, function(response) {
			$scope.submitWPALabel = "warning";
			$scope.submitWPA = "Working...";
			$scope.working = true;

			$scope.submitWPAOnlineInterval = $interval(function() {
				$api.request({
					module: 'OnlineHashCrack',
					action: 'submitWPAOnlineStatus'
				}, function(response) {
					if (response.success === true) {
						$scope.working = false;
						$interval.cancel($scope.submitWPAOnlineInterval);

						$scope.submitWPALabel = "primary";
						$scope.submitWPA = "Submit";

						$rootScope.status.refreshOutput = true;
						$rootScope.status.refreshKnownHosts = true;
					}
				});
			}, 5000);
		});
	});

	$scope.getCapFiles();

}]);

registerController('OnlineHashCrack_SettingsController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.settings = {
		email: ""
	};

	$scope.saveSettingsLabel = "primary";
	$scope.saveSettings = "Save";
	$scope.saving = false;

	$scope.getSettings = function() {
		$api.request({
			module: 'OnlineHashCrack',
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
			module: 'OnlineHashCrack',
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
