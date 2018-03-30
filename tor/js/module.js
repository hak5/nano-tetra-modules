registerController('tor_DependenciesController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.status = "Loading...";
	$scope.statusLabel = "default";
	$scope.starting = false;

	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.bootLabelON = "default";
	$scope.bootLabelOFF = "default";

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

			$scope.bootLabelON = response.bootLabelON;
			$scope.bootLabelOFF = response.bootLabelOFF;
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
            action: 'toggletor',
						interface: $scope.selectedInterface
        }, function(response) {
            $timeout(function(){
							$rootScope.status.refreshOutput = true;
							$rootScope.status.refreshHistory = true;

	            $scope.starting = false;
							$scope.refreshStatus();
            }, 2000);
        })
    });

  $scope.toggletorOnBoot = (function() {
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
            module: 'tor',
            action: 'toggletorOnBoot',
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
