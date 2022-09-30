registerController('ngrep_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'ngrep',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('ngrep_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
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
		refreshHistory: false,
		refreshProfiles: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: "ngrep",
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

	$scope.togglengrep = (function() {
		if ($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
			module: 'ngrep',
			action: 'togglengrep',
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
			module: 'ngrep',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'ngrep',
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

registerController('ngrep_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: "ngrep",
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

registerController('ngrep_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

	$scope.refreshHistory = (function() {
		$api.request({
			module: "ngrep",
			action: "refreshHistory"
		}, function(response) {
			$scope.history = response;
		})
	});

	$scope.viewHistory = (function(param) {
		$api.request({
			module: "ngrep",
			action: "viewHistory",
			file: param
		}, function(response) {
			$scope.historyOutput = response.output;
			$scope.historyDate = response.date;
		})
	});

	$scope.deleteHistory = (function(param) {
		$api.request({
			module: "ngrep",
			action: "deleteHistory",
			file: param
		}, function(response) {
			$scope.refreshHistory();
		})
	});

	$scope.downloadHistory = (function(param) {
		$api.request({
			module: 'ngrep',
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

registerController('ngrep_OptionsController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.command = "ngrep ";

	$scope.interfaces = [];
	$scope.selectedInterface = "--";

	$scope.profiles = [];
	$scope.selectedProfile = "--";

	$scope.filter = "";
	$scope.expression = "";
	$scope.format = "--";

	$scope.options = {
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
		option13: false
	};

	$scope.update = (function(param) {
		if (updateProfile() != "")
			$scope.command = "ngrep " + updateInterface() + updateOptions() + updateFormat() + updateProfile();
		else
			$scope.command = "ngrep " + updateInterface() + updateOptions() + updateFormat() + updateExpression() + updateFilter();

		$rootScope.command = $scope.command;
	});

	function updateInterface() {
		var return_value = "";

		if ($scope.selectedInterface != "--")
			return_value = "-d " + $scope.selectedInterface + " ";

		return return_value;
	}

	function updateProfile() {
		var return_value = "";

		if ($scope.selectedProfile != "--")
			return_value = $scope.selectedProfile + " ";

		return return_value;
	}

	function updateOptions() {
		var return_value = "";

		angular.forEach($scope.options, function(value, key) {
			if (value != false)
				return_value += value + " ";
		});

		return return_value;
	}

	function updateFormat() {
		var return_value = "";

		if ($scope.format != "--")
			return_value = $scope.format + " ";

		return return_value;
	}

	function updateExpression() {
		var return_value = "";

		if ($scope.expression != "")
			return_value = "'" + $scope.expression + "' ";

		return return_value;
	}

	function updateFilter() {
		var return_value = "";

		if ($scope.filter != "")
			return_value = "'" + $scope.filter + "' ";

		return return_value;
	}

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'ngrep',
			action: 'getInterfaces'
		}, function(response) {
			$scope.interfaces = response;
		});
	});

	$scope.getProfiles = (function() {
		$api.request({
			module: 'ngrep',
			action: 'getProfiles'
		}, function(response) {
			$scope.profiles = response;
			$scope.selectedProfile = "--";
			$rootScope.status.refreshProfiles = false;
		});
	});

	$scope.getInterfaces();
	$scope.getProfiles();
	$scope.update();

	$rootScope.$watch('status.refreshProfiles', function(param) {
		if (param) {
			$scope.getProfiles();
		}
	});

}]);

registerController('ngrep_EditorController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.profiles = [];
	$scope.selectedProfile = "--";

	$scope.profileData = '';
	$scope.saveProfileLabel = "primary";
	$scope.saveProfile = "New Profile";
	$scope.saving = false;

	$scope.deleteProfileLabel = "danger";
	$scope.deleteProfile = "Delete Profile";
	$scope.deleting = false;

	$scope.profileName = "";

	$scope.getProfiles = (function(param) {
		$api.request({
			module: 'ngrep',
			action: 'getProfiles'
		}, function(response) {
			$scope.profiles = response;
		});
	});

	$scope.showProfile = (function() {
		$scope.output = "";

		if ($scope.selectedProfile != "--") {
			$scope.profileName = $scope.selectedProfile;
			$scope.saveProfile = "Save Profile";

			$api.request({
				module: 'ngrep',
				action: 'showProfile',
				profile: $scope.selectedProfile
			}, function(response) {
				$scope.profileData = response.profileData;
			});
		} else {
			$scope.profileName = "";
			$scope.profileData = "";
			$scope.saveProfile = "New Profile";
		}
	});

	$scope.deleteProfileData = (function() {
		$scope.deleteProfileLabel = "warning";
		$scope.deleteProfile = "Deleting...";
		$scope.deleting = true;

		$api.request({
			module: 'ngrep',
			action: 'deleteProfile',
			profile: $scope.selectedProfile
		}, function(response) {
			$scope.deleteProfileLabel = "success";
			$scope.deleteProfile = "Deleted";

			$timeout(function() {
				$scope.deleteProfileLabel = "danger";
				$scope.deleteProfile = "Delete Profile";
				$scope.deleting = false;
			}, 2000);

			$scope.getProfiles();
			$scope.selectedProfile = '--';
			$scope.profileName = "";
			$scope.profileData = "";

			$scope.saveProfile = "New Profile";

			$rootScope.status.refreshProfiles = true;
		});
	});

	$scope.saveProfileData = (function() {
		if ($scope.selectedProfile != "--" && $scope.profileName != "") {
			$scope.saveProfileLabel = "warning";
			$scope.saveProfile = "Saving...";
			$scope.saving = true;

			$api.request({
				module: 'ngrep',
				action: 'saveProfileData',
				profileData: $scope.profileData,
				profile: $scope.selectedProfile
			}, function(response) {
				$scope.saveProfileLabel = "success";
				$scope.saveProfile = "Saved";

				$timeout(function() {
					$scope.saveProfileLabel = "primary";
					$scope.saveProfile = "Save Profile";
					$scope.saving = false;
				}, 2000);
			});
		} else if ($scope.selectedProfile == "--" && $scope.profileName != "") {
			$scope.saveProfileLabel = "warning";
			$scope.saveProfile = "Saving...";
			$scope.saving = true;

			$api.request({
				module: 'ngrep',
				action: 'saveProfileData',
				profileData: $scope.profileData,
				profile: $scope.profileName
			}, function(response) {
				$scope.saveProfileLabel = "success";
				$scope.saveProfile = "Saved";

				$timeout(function() {
					$scope.saveProfileLabel = "primary";
					$scope.saveProfile = "Save Profile";
					$scope.saving = false;
				}, 2000);

				$scope.getProfiles();
				$scope.selectedProfile = $scope.profileName;

				$rootScope.status.refreshProfiles = true;
			});
		}
	});

	$scope.getProfileData = (function() {
		$api.request({
			module: 'ngrep',
			action: 'getProfileData'
		}, function(response) {
			$scope.profileData = response.profileData;
		});
	});

	$scope.getProfiles();

}]);
