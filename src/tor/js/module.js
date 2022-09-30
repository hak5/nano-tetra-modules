registerController('tor_DependenciesController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.saveSettingsLabel = "default";

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed : false
	};

  $scope.refreshStatus = (function() {
		$api.request({
            module: "tor",
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

  $scope.toggletor = (function() {
		if($scope.status != "Stop")
			$scope.status = "Starting...";
		else
			$scope.status = "Stopping...";

		$scope.statusLabel = "warning";
		$scope.starting = true;

		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshHistory = false;

		$api.request({
            module: 'tor',
            action: 'toggletor'
        }, function(response) {
            $timeout(function(){
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
            module: 'tor',
            action: 'handleDependencies',
			destination: param
        }, function(response){
            if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

                $scope.handleDependenciesInterval = $interval(function(){
                    $api.request({
                        module: 'tor',
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

registerController('tor_ConfigurationController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {

  $scope.refreshHiddenServices = (function() {
	$api.request({
		module: 'tor',
		action: 'refreshHiddenServices'
	}, function(response) {
		$scope.hiddenServices = response.hiddenServices;
	});
  });

  $scope.addHiddenService = (function() {
	$api.request({
		module: 'tor',
		action: 'addHiddenService',
		name: $scope.name
	}, function(response){
		    $scope.refreshHiddenServices();
		});
    });

  $scope.removeHiddenService = (function(name) {
	$api.request({
		module: 'tor',
		action: 'removeHiddenService',
		name: name
	}, function(response) {
		    $scope.refreshHiddenServices();
		});
	});

  $scope.addServiceForward = (function() {
	$api.request({
		module: 'tor',
		action: 'addServiceForward',
		name: $scope.name,
		port: $scope.port,
		redirect_to: $scope.redirect_to
	}, function(response) {
            $scope.hiddenServicesLoad = '(reloading...)';
            $timeout(function() {
                $scope.hiddenServicesLoad = '';
		        $scope.refreshHiddenServices();
            }, 2000);
		});
	});

  $scope.removeServiceForward = (function(name, port, redirect_to) {
	$api.request({
		module: 'tor',
		action: 'removeServiceForward',
		name: name,
		port: port,
		redirect_to: redirect_to
	}, function(response) {
            $scope.hiddenServicesLoad = '(reloading...)';
            $timeout(function() {
                $scope.hiddenServicesLoad = '';
			    $scope.refreshHiddenServices();
            }, 2000);
		});
	});

	$scope.refreshHiddenServices();
}]);
