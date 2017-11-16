registerController('ettercap_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

  $scope.refreshInfo = (function() {
		$api.request({
            module: 'ettercap',
            action: "refreshInfo"
        }, function(response) {
						$scope.title = response.title;
						$scope.version = "v"+response.version;
        })
    });

		$scope.refreshInfo();

}]);


registerController('ettercap_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed : false,
		refreshOutput : false,
		refreshHistory : false,
		refreshFilters : false
	};

  $scope.refreshStatus = (function() {
		$api.request({
            module: "ettercap",
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
        })
    });

  $scope.toggleettercap = (function() {
		if($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
		        module: 'ettercap',
		        action: 'toggleettercap',
		        command: $rootScope.command
		    }, function(response) {
		        $timeout(function(){
							$rootScope.status.refreshOutput = true;
							$rootScope.status.refreshHistory = true;

							$scope.starting = false;
							$scope.refreshStatus();

		        }, 2000);
		    })
	});

  $scope.handleDependencies = (function(param) {
    if(!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
            module: 'ettercap',
            action: 'handleDependencies',
						destination: param
        }, function(response){
            if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

                $scope.handleDependenciesInterval = $interval(function(){
                    $api.request({
                        module: 'ettercap',
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

		$scope.refreshStatus();
}]);

registerController('ettercap_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
  $scope.output = 'Loading...';
	$scope.filter = '';

	$scope.refreshLabelON = "default";
	$scope.refreshLabelOFF = "danger";

  $scope.refreshOutput = (function() {
		$api.request({
            module: "ettercap",
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

registerController('ettercap_HistoryController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
	$scope.history = [];
	$scope.historyOutput = 'Loading...';
	$scope.historyDate = 'Loading...';

  $scope.refreshHistory = (function() {
        $api.request({
            module: "ettercap",
            action: "refreshHistory"
        }, function(response) {
                $scope.history = response;
        })
    });

  $scope.viewHistory = (function(param) {
		$api.request({
            module: "ettercap",
            action: "viewHistory",
			file: param
        }, function(response) {
            $scope.historyOutput = response.output;
			$scope.historyDate = response.date;
        })
    });

  $scope.deleteHistory = (function(param) {
		$api.request({
            module: "ettercap",
            action: "deleteHistory",
						file: param
        }, function(response) {
            $scope.refreshHistory();
        })
    });

		$scope.downloadHistory = (function(param) {
					$api.request({
							module: 'ettercap',
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
		if(param) {
			$scope.refreshHistory();
		}
	});

}]);

registerController('ettercap_OptionsController', ['$api', '$scope', '$rootScope', function($api, $scope, $rootScope) {
		$scope.command = "ettercap ";

		$scope.target1 = "";
		$scope.target2 = "";

		$scope.filters = [];
		$scope.selectedFilter = "--";

		$scope.interfaces = [];
		$scope.selectedInterface = "--";

		$scope.mitm = "--";
		$scope.arpParameters = "--";
		$scope.portParameters = "--";
		$scope.visualization = "--";
		$scope.proto = "--";

		$scope.visualizationOptions = {
			option1 : false,
			option2 : false,
			option3 : false
		};

		$scope.protoOptions = {
			option1 : false,
			option2 : false,
			option3 : false,
			option4 : false,
			option5 : false,
			option6 : false
		};

		$scope.update = (function(param) {
				$scope.command = "ettercap " + updateInterface() + updateOptions() + updateProto() + updateVisualization() + updateFilter() + updateMITM() + updateTarget1() + updateTarget2()
				$rootScope.command = $scope.command;
		});

		$scope.getFilters = (function() {
	    $api.request({
	            module: 'ettercap',
	            action: 'getFilters',
							compiled: true
	        }, function(response) {
	        		$scope.filters = response;
							$scope.selectedFilter = "--";
							$rootScope.status.refreshFilters = false;
	        });
	    });

		function updateInterface() {
			var return_value = "";

			if($scope.selectedInterface != "--")
				return_value = "-i " + $scope.selectedInterface + " ";

			return return_value;
		}

		function updateOptions() {
			var return_value = "";

			angular.forEach($scope.visualizationOptions, function(value, key) {
				if(value != false)
					return_value += value + " ";
			});

			angular.forEach($scope.protoOptions, function(value, key) {
				if(value != false)
					return_value += value + " ";
			});

			return return_value;
		}

		function updateTarget1() {
			var return_value = "";

			if($scope.target1 != "")
				return_value = "/" + $scope.target1 + "/ ";

			return return_value;
		}

		function updateTarget2() {
			var return_value = "";

			if($scope.target2 != "")
				return_value = "/" + $scope.target2 + "/ ";

			return return_value;
		}

		function updateMITM() {
				var return_value = "";

			if($scope.mitm != "--")
				if($scope.mitm == "-M arp" && $scope.arpParameters != "--")
					return_value = $scope.mitm + ":" + $scope.arpParameters + " ";
				else if($scope.mitm == "-M port" && $scope.portParameters != "--")
					return_value = $scope.mitm + ":" + $scope.portParameters + " ";
				else
					return_value = $scope.mitm + " ";

			return return_value;
		}

		function updateFilter() {
				var return_value = "";

			if($scope.selectedFilter != "--")
				return_value = "-F /pineapple/modules/ettercap/filters/" + $scope.selectedFilter + " ";

			return return_value;
		}

		function updateVisualization() {
				var return_value = "";

			if($scope.visualization != "--")
				return_value = $scope.visualization + " ";

			return return_value;
		}

		function updateProto() {
		    var return_value = "";

			if($scope.proto != "--")
				return_value = $scope.proto + " ";

			return return_value;
		}

		$scope.getInterfaces = (function() {
	    $api.request({
	            module: 'ettercap',
	            action: 'getInterfaces'
	        }, function(response) {
	        		$scope.interfaces = response;
	        });
	    });

	$scope.getInterfaces();
	$scope.getFilters();

	$rootScope.$watch('status.refreshFilters', function(param) {
		if(param) {
			$scope.getFilters();
		}
	});

}]);

registerController('ettercap_EditorController', ['$api', '$scope', '$rootScope', '$timeout', function($api, $scope, $rootScope, $timeout) {
	$scope.filters = [];
	$scope.selectedFilter = "--";

	$scope.filterData = '';
	$scope.saveFilterLabel = "primary";
	$scope.saveFilter = "New Filter";
	$scope.saving = false;

	$scope.compileFilterLabel = "primary";
	$scope.compileFilter = "Compile Filter";
	$scope.compiling = false;

	$scope.deleteFilterLabel = "danger";
	$scope.deleteFilter = "Delete Filter";
	$scope.deleting = false;

	$scope.filterName = "";

	$scope.getFilters = (function() {
		$api.request({
						module: 'ettercap',
						action: 'getFilters',
						compiled: false
				}, function(response) {
						$scope.filters = response;
				});
		});

	$scope.showFilter = (function() {
		$scope.output = "";

		if($scope.selectedFilter != "--") {
			$scope.filterName = $scope.selectedFilter;
			$scope.saveFilter = "Save Filter";

			$api.request({
					module: 'ettercap',
					action: 'showFilter',
					filter: $scope.selectedFilter
				}, function(response) {
							$scope.filterData = response.filterData;
				});
		}
		else {
			$scope.filterName = "";
			$scope.filterData = "";
			$scope.saveFilter = "New Filter";
		}
	});

	$scope.deleteFilterData = (function() {
		$scope.deleteFilterLabel = "warning";
		$scope.deleteFilter = "Deleting...";
		$scope.deleting = true;

		$api.request({
			module: 'ettercap',
			action: 'deleteFilter',
			filter: $scope.selectedFilter
		}, function(response) {
					$scope.deleteFilterLabel = "success";
					$scope.deleteFilter = "Deleted";

					$timeout(function(){
								$scope.deleteFilterLabel = "danger";
								$scope.deleteFilter = "Delete Filter";
								$scope.deleting = false;
					}, 2000);

					$scope.getFilters();
					$scope.selectedFilter = '--';
					$scope.filterName = "";
					$scope.filterData = "";

					$scope.saveFilter = "New Filter";

					$rootScope.status.refreshFilters = true;
		});
	});

	$scope.compileFilterData = (function() {
		if($scope.selectedFilter != "--" && $scope.filterName != "")
		{
				$scope.compileFilterLabel = "warning";
				$scope.compileFilter = "Compiling...";
				$scope.compiling = true;

				$api.request({
					module: 'ettercap',
					action: 'compileFilterData',
					filterData: $scope.filterData,
					filter: $scope.selectedFilter
				}, function(response) {
							$scope.compileFilterLabel = "success";
							$scope.compileFilter = "Compiled";

							$timeout(function(){
										$scope.compileFilterLabel = "primary";
										$scope.compileFilter = "Compile Filter";
										$scope.compiling = false;
							}, 2000);

							$rootScope.status.refreshFilters = true;

							$scope.output = response;
				});
		}
	});

	$scope.saveFilterData = (function() {
		if($scope.selectedFilter != "--" && $scope.filterName != "")
		{
				$scope.saveFilterLabel = "warning";
				$scope.saveFilter = "Saving...";
				$scope.saving = true;

				$api.request({
					module: 'ettercap',
					action: 'saveFilterData',
					filterData: $scope.filterData,
					filter: $scope.selectedFilter
				}, function(response) {
								$scope.saveFilterLabel = "success";
								$scope.saveFilter = "Saved";

								$timeout(function(){
										$scope.saveFilterLabel = "primary";
										$scope.saveFilter = "Save Filter";
										$scope.saving = false;
								}, 2000);
				});
			}
			else if($scope.selectedFilter == "--" && $scope.filterName != "")
			{
				$scope.saveFilterLabel = "warning";
				$scope.saveFilter = "Saving...";
				$scope.saving = true;

				if($scope.filterName.search(".filter") == -1)
					$scope.filterName = $scope.filterName + ".filter";

				$api.request({
					module: 'ettercap',
					action: 'saveFilterData',
					filterData: $scope.filterData,
					filter: $scope.filterName
				}, function(response) {
								$scope.saveFilterLabel = "success";
								$scope.saveFilter = "Saved";

								$timeout(function(){
										$scope.saveFilterLabel = "primary";
										$scope.saveFilter = "Save Filter";
										$scope.saving = false;
								}, 2000);

								$scope.getFilters();
								$scope.selectedFilter = $scope.filterName;

								$rootScope.status.refreshFIlters = true;
				});
			}
	});

	$scope.getFilters();

}]);
