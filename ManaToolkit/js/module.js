registerController('ManaToolkit_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

  $scope.refreshInfo = (function() {
		$api.request({
            module: 'ManaToolkit',
            action: "refreshInfo"
        }, function(response) {
						$scope.title = response.title;
						$scope.version = "v"+response.version;
        })
    });

		$scope.refreshInfo();

}]);

registerController('ManaToolkit_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
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
		installed : false,
		refreshOutput : false
	};

  $scope.refreshStatus = (function() {
		$api.request({
            module: "ManaToolkit",
            action: "refreshStatus"
        }, function(response) {
            $scope.status = response.status;
						$scope.statusLabel = response.statusLabel;

						$rootScope.status.installed = response.installed;
						$scope.device = response.device;
						$scope.sdAvailable = response.sdAvailable;
						if(response.processing) $scope.processing = true;
						$scope.install = response.install;
						$scope.installLabel = response.installLabel;

						$scope.bootLabelON = response.bootLabelON;
						$scope.bootLabelOFF = response.bootLabelOFF;
        })
    });

  $scope.toggleManaToolkit = (function() {
		if($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;

		$api.request({
            module: 'ManaToolkit',
            action: 'toggleManaToolkit',
			interface: $scope.selectedInterface
        }, function(response) {
            $timeout(function(){
				$rootScope.status.refreshOutput = true;
	            $scope.starting = false;
				$scope.refreshStatus();
            }, 2000);
        })
    });

	$scope.saveAutostartSettings = (function() {
		$api.request({
						module: 'ManaToolkit',
						action: 'saveAutostartSettings',
						settings: { interface : $scope.selectedInterface }
				}, function(response) {
					$scope.saveSettingsLabel = "success";
					$timeout(function(){
						$scope.saveSettingsLabel = "default";
					}, 2000);
				})
		});

  $scope.toggleManaToolkitOnBoot = (function() {
    if($scope.bootLabelON == "default")
		{
			$scope.bootLabelON = "success";
			$scope.bootLabelOFF = "default";
		}
		else
		{
			$scope.bootLabelON = "default";
			$scope.bootLabelOFF = "danger";
		}

		$api.request({
            module: 'ManaToolkit',
            action: 'toggleManaToolkitOnBoot',
        }, function(response) {
			$scope.refreshStatus();
        })
    });

  $scope.handleDependencies = (function(param) {
    if(!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
            module: 'ManaToolkit',
            action: 'handleDependencies',
						destination: param
        }, function(response){
            if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

                $scope.handleDependenciesInterval = $interval(function(){
                    $api.request({
                        module: 'ManaToolkit',
                        action: 'handleDependenciesStatus'
                    }, function(response) {
                        if (response.success === true){
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
						module: 'ManaToolkit',
						action: 'getInterfaces'
				}, function(response) {
						$scope.interfaces = response.interfaces;
						if(response.selected != "")
							$scope.selectedInterface = response.selected;
						else
							$scope.selectedInterface = $scope.interfaces[0];
				});
		});

	$scope.refreshStatus();
	$scope.getInterfaces();
}]);

registerController('ManaToolkit_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope,$interval) {
    $scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

    $scope.refreshOutput = (function() {
		$api.request({
            module: "ManaToolkit",
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
        if($scope.autoRefreshInterval)
		{
			$interval.cancel($scope.autoRefreshInterval);
			$scope.autoRefreshInterval = null;
			$scope.refreshLabelON = "default";
			$scope.refreshLabelOFF = "danger";
		}
		else
		{
			$scope.refreshLabelON = "success";
			$scope.refreshLabelOFF = "default";

			$scope.autoRefreshInterval = $interval(function(){
				$scope.refreshOutput();
	        }, 5000);
		}
    });

    $scope.refreshOutput();

		$rootScope.$watch('status.refreshOutput', function(param) {
			if(param) {
				$scope.refreshOutput();
			}
		});

}]);

registerController('ManaToolkit_LogController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.files = [];
	$scope.selectedFiles = {};
	$scope.selectedFilesArray = [];
	$scope.selectedAll = false;
	$scope.fileOutput = 'Loading...';
	$scope.fileDate = 'Loading...';
	$scope.fileName = 'Loading...';

	$scope.updateSelectedFiles = (function() {
		$scope.selectedFilesArray = [];
		angular.forEach($scope.selectedFiles, function(key,value) { if(key) { $scope.selectedFilesArray.push(value); } });
	});

	$scope.updateAllSelectedFiles = (function() {
		$scope.selectedFilesArray = [];
		if($scope.selectedAll)
		{
			angular.forEach($scope.files, function(key,value) { $scope.selectedFilesArray.push(key.path); $scope.selectedFiles[key.path] = true; });
			$scope.selectedAll = true;
		}
		else
		{
			$scope.selectedAll = false;
			$scope.selectedFiles = {};
		}
	});

  $scope.refreshFilesList = (function() {
      $api.request({
          module: "ManaToolkit",
          action: "refreshFilesList"
      }, function(response) {
			$scope.files = response.files;
      })
  });

	$scope.downloadFilesList = (function() {
		$api.request({
        module: "ManaToolkit",
        action: "downloadFilesList",
		files: $scope.selectedFilesArray
    }, function(response) {
			if (response.error === undefined) {
				window.location = '/api/?download=' + response.download;
			}
    })
  });

	$scope.deleteFilesList = (function() {
		$api.request({
        module: "ManaToolkit",
        action: "deleteFilesList",
		files: $scope.selectedFilesArray
    }, function(response) {
			$scope.refreshFilesList();
			$scope.selectedFiles = {};
			$scope.updateSelectedFiles();
    })
  });

  $scope.viewFile = (function(param) {
	$api.request({
        module: "ManaToolkit",
        action: "viewModuleFile",
		file: param
      }, function(response) {
        	$scope.fileOutput = response.output;
			$scope.fileDate = response.date;
			$scope.fileName = response.name;
      })
  });

  $scope.deleteFile = (function(param) {
	$api.request({
        	module: "ManaToolkit",
        	action: "deleteModuleFile",
			file: param
      }, function(response) {
        	$scope.refreshFilesList();
      })
  });

	$scope.downloadFile = (function(param) {
			$api.request({
            	module: 'ManaToolkit',
            	action: 'downloadModuleFile',
				file: param
        }, function(response) {
            if (response.error === undefined) {
                window.location = '/api/?download=' + response.download;
            }
        });
    });

	$scope.refreshFilesList();

}]);

registerController('ManaToolkit_ConfigController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.ManaToolkitConfiguration = "";

    $scope.getConfiguration = (function() {
        $api.request({
            module: 'ManaToolkit',
            action: 'getConfiguration'
        }, function(response) {
            console.log(response);
            if (response.error === undefined){
                $scope.ManaToolkitConfiguration = response.ManaToolkitConfiguration;
            }
        });
    });

    $scope.saveConfiguration = (function() {
        $api.request({
            module: 'ManaToolkit',
            action: 'saveConfiguration',
            ManaToolkitConfiguration: $scope.ManaToolkitConfiguration
        }, function(response) {
            console.log(response);
            if (response.success === true){
                $scope.getConfiguration();
            }
        });
    });

    $scope.restoreDefaultConfiguration = (function() {
        $api.request({
            module: 'ManaToolkit',
            action: 'restoreDefaultConfiguration'
        }, function(response) {
            if (response.success === true) {
                $scope.getConfiguration();
            }
        });
    });

    $scope.getConfiguration();
}]);

registerController('ManaToolkit_WiFiController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		wifiClientsList : []
	};
	$scope.title = "Loading...";
	$scope.output = "Loading...";
	$scope.loading = false;

	$scope.getInfo = function() {
			$scope.loading = true;

			$api.request({
					module: 'ManaToolkit',
					action: 'getWiFi'
			}, function(response) {
					$scope.info = response.info;
					$scope.loading = false;
			});
	};

	$scope.getMACInfo = function(param) {
			$scope.title = "Loading...";
			$scope.output = "Loading...";

			$api.request({
					module: 'ManaToolkit',
					action: 'getMACInfo',
					mac: param
			}, function(response) {
					$scope.title = response.title;
					$scope.output = response.output;
			});
	};

	$scope.getPingInfo = function(param) {
			$scope.title = "Loading...";
			$scope.output = "Loading...";

			$api.request({
					module: 'ManaToolkit',
					action: 'getPingInfo',
					ip: param
			}, function(response) {
					$scope.title = response.title;
					$scope.output = response.output;
			});
	};

	$scope.getInfo();

}]);

registerController('ManaToolkit_DHCPController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		clientsList : []
	};

	$scope.title = "Loading...";
	$scope.output = "Loading...";

	$scope.loading = false;

	$scope.getInfo = function() {
			$scope.loading = true;

			$api.request({
					module: 'ManaToolkit',
					action: 'getDHCP'
			}, function(response) {
					$scope.info = response.info;
					$scope.loading = false;
			});
	};

	$scope.getMACInfo = function(param) {
			$scope.title = "Loading...";
			$scope.output = "Loading...";

			$api.request({
					module: 'ManaToolkit',
					action: 'getMACInfo',
					mac: param
			}, function(response) {
					$scope.title = response.title;
					$scope.output = response.output;
			});
	};

	$scope.getPingInfo = function(param) {
			$scope.title = "Loading...";
			$scope.output = "Loading...";

			$api.request({
					module: 'ManaToolkit',
					action: 'getPingInfo',
					ip: param
			}, function(response) {
					$scope.title = response.title;
					$scope.output = response.output;
			});
	};

	$scope.getInfo();

}]);