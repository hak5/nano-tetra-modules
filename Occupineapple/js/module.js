registerController('Occupineapple_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'Occupineapple',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('Occupineapple_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
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

	$scope.lists = [];
	$scope.selectedList = "--";

	$scope.saveSettingsLabel = "default";

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed: false,
		refreshOutput: false,
		refreshLists: false
	};

	$scope.refreshStatus = (function() {
		$api.request({
			module: 'Occupineapple',
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
			module: 'Occupineapple',
			action: 'togglemdk3',
			interface: $scope.selectedInterface,
			list: $scope.selectedList
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
			module: 'Occupineapple',
			action: 'saveAutostartSettings',
			settings: {
				interface: $scope.selectedInterface,
				list: $scope.selectedList
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
			module: 'Occupineapple',
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
			module: 'Occupineapple',
			action: 'handleDependencies',
			destination: param
		}, function(response) {
			if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

				$scope.handleDependenciesInterval = $interval(function() {
					$api.request({
						module: 'Occupineapple',
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

	$scope.getLists = (function(param) {
		$api.request({
			module: 'Occupineapple',
			action: 'getLists'
		}, function(response) {
			$scope.lists = response.lists;
			if (response.selected != "")
				$scope.selectedList = response.selected;
			else
				$scope.selectedList = $scope.lists[0];

			$rootScope.status.refreshLists = false;
		});
	});

	$scope.getInterfaces = (function() {
		$api.request({
			module: 'Occupineapple',
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
	$scope.getLists();

	$rootScope.$watch('status.refreshLists', function(param) {
		if (param) {
			$scope.getLists();
		}
	});

}]);

registerController('Occupineapple_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

	$scope.refreshOutput = (function() {
		$api.request({
			module: 'Occupineapple',
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

registerController('Occupineapple_EditorController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.lists = [];
	$scope.selectedList = "--";

	$scope.listData = '';
	$scope.saveListLabel = "primary";
	$scope.saveList = "New List";
	$scope.saving = false;

	$scope.deleteListLabel = "danger";
	$scope.deleteList = "Delete List";
	$scope.deleting = false;

	$scope.listName = "";

	$scope.getLists = (function(param) {
		$api.request({
			module: 'Occupineapple',
			action: 'getLists'
		}, function(response) {
			$scope.lists = response.lists;
		});
	});

	$scope.showList = (function() {
		$scope.output = "";

		if ($scope.selectedList != "--") {
			$scope.listName = $scope.selectedList;
			$scope.saveList = "Save List";

			$api.request({
				module: 'Occupineapple',
				action: 'showList',
				list: $scope.selectedList
			}, function(response) {
				$scope.listData = response.listData;
			});
		} else {
			$scope.listName = "";
			$scope.listData = "";
			$scope.saveList = "New List";
		}
	});

	$scope.deleteListData = (function() {
		$scope.deleteListLabel = "warning";
		$scope.deleteList = "Deleting...";
		$scope.deleting = true;

		$api.request({
			module: 'Occupineapple',
			action: 'deleteList',
			list: $scope.selectedList
		}, function(response) {
			$scope.deleteListLabel = "success";
			$scope.deleteList = "Deleted";

			$timeout(function() {
				$scope.deleteListLabel = "danger";
				$scope.deleteList = "Delete List";
				$scope.deleting = false;
			}, 2000);

			$scope.getLists();
			$scope.selectedList = '--';
			$scope.listName = "";
			$scope.listData = "";

			$scope.saveList = "New List";

			$rootScope.status.refreshLists = true;
		});
	});

	$scope.saveListData = (function() {
		if ($scope.selectedList != "--" && $scope.listName != "") {
			$scope.saveListLabel = "warning";
			$scope.saveList = "Saving...";
			$scope.saving = true;

			$api.request({
				module: 'Occupineapple',
				action: 'saveListData',
				listData: $scope.listData,
				list: $scope.selectedList
			}, function(response) {
				$scope.saveListLabel = "success";
				$scope.saveList = "Saved";

				$timeout(function() {
					$scope.saveListLabel = "primary";
					$scope.saveList = "Save List";
					$scope.saving = false;
				}, 2000);
			});
		} else if ($scope.selectedList == "--" && $scope.listName != "") {
			$scope.saveListLabel = "warning";
			$scope.saveList = "Saving...";
			$scope.saving = true;

			if ($scope.listName.search(".list") == -1 && $scope.listName.search(".mlist") == -1)
				$scope.listName = $scope.listName + ".list";

			$api.request({
				module: 'Occupineapple',
				action: 'saveListData',
				listData: $scope.listData,
				list: $scope.listName
			}, function(response) {
				$scope.saveListLabel = "success";
				$scope.saveList = "Saved";

				$timeout(function() {
					$scope.saveListLabel = "primary";
					$scope.saveList = "Save List";
					$scope.saving = false;
				}, 2000);

				$scope.getLists();
				$scope.selectedList = $scope.listName;

				$rootScope.status.refreshLists = true;
			});
		}
	});

	$scope.getListData = (function() {
		$api.request({
			module: 'Occupineapple',
			action: 'getListData'
		}, function(response) {
			$scope.listData = response.listData;
		});
	});

	$scope.getLists();

}]);

registerController('Occupineapple_SettingsController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.settings = {
		speed: "",
		channel: "",
		adHoc: false,
		wepBit: false,
		wpaTKIP: false,
		wpaAES: false,
		validMAC: false
	};

	$scope.saveSettingsLabel = "primary";
	$scope.saveSettings = "Save";
	$scope.saving = false;

	$scope.getSettings = function() {
		$api.request({
			module: 'Occupineapple',
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
			module: 'Occupineapple',
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
