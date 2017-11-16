registerController('KeyManager_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

  $scope.refreshInfo = (function() {
		$api.request({
            module: 'KeyManager',
            action: "refreshInfo"
        }, function(response) {
						$scope.title = response.title;
						$scope.version = "v"+response.version;
        })
    });

		$scope.refreshInfo();

}]);

registerController('KeyManager_ControlsController', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.install = "Loading...";
	$scope.installLabel = "default";
	$scope.processing = false;

	$scope.key = "Loading...";
	$scope.keyLabel = "default";
	$scope.generating = false;

	$scope.device = '';
	$scope.sdAvailable = false;

	$rootScope.status = {
		installed : false,
		generated : false,
		refreshOutput : false,
		refreshKnownHosts : false
	};

    $scope.refreshStatus = (function() {
		$api.request({
            module: "KeyManager",
            action: "refreshStatus"
        }, function(response) {
			$rootScope.status.installed = response.installed;
			$scope.device = response.device;
			$scope.sdAvailable = response.sdAvailable;
			if(response.processing) $scope.processing = true;
			$scope.install = response.install;
			$scope.installLabel = response.installLabel;

			$rootScope.status.generated = response.generated;
			$scope.key = response.key;
			if(response.generating) $scope.generating = true;
			$scope.keyLabel = response.keyLabel;

        })
    });

    $scope.handleKey = (function() {
        if($scope.key != "Generated")
			$scope.key = "Generating...";
		else
			$scope.key = "Removing...";

		$api.request({
            module: 'KeyManager',
            action: 'handleKey'
        }, function(response){
            if (response.success === true) {
				$scope.keyLabel = "warning";
				$scope.generating = true;

                $scope.handleKeyInterval = $interval(function(){
                    $api.request({
                        module: 'KeyManager',
                        action: 'handleKeyStatus'
                    }, function(response) {
                        if (response.success === true){
                            $scope.generating = false;
                            $interval.cancel($scope.handleKeyInterval);
                            $scope.refreshStatus();
                        }
                    });
                }, 5000);
            }
        });
    });

    $scope.handleDependencies = (function(param) {
        if(!$rootScope.status.installed)
			$scope.install = "Installing...";
		else
			$scope.install = "Removing...";

		$api.request({
            module: 'KeyManager',
            action: 'handleDependencies',
						destination: param
        }, function(response){
            if (response.success === true) {
				$scope.installLabel = "warning";
				$scope.processing = true;

                $scope.handleDependenciesInterval = $interval(function(){
                    $api.request({
                        module: 'KeyManager',
                        action: 'handleDependenciesStatus'
                    }, function(response) {
                        if (response.success === true){
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

registerController('KeyManager_OutputController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
    $scope.output = 'Loading...';

    $scope.refreshOutput = (function() {
		$api.request({
            module: "KeyManager",
            action: "refreshOutput",
			filter: $scope.filter
        }, function(response) {
            $scope.output = response;
        })
    });

		$scope.clearOutput = (function() {
			$api.request({
	            module: "KeyManager",
	            action: "clearOutput"
	        }, function(response) {
	            $scope.refreshOutput();
	        })
	    });

    $scope.refreshOutput();

		$rootScope.$watch('status.refreshOutput', function(param) {
			if(param) {
				$scope.refreshOutput();
			}
		});

}]);

registerController('KeyManager_RemoteHostController', ['$api', '$scope', '$rootScope', '$interval', function($api, $scope, $rootScope, $interval) {
	$scope.host = '';
	$scope.port = '';
	$scope.user = '';
	$scope.password = '';

	$scope.addHostLabel = "primary";
	$scope.addHost = "Add remote host to local known_hosts";

	$scope.copyKeyLabel = "primary";
	$scope.copyKey = "Copy public key to remote host";

	$scope.working = false;

	$scope.addToKnownHosts = (function() {
		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshKnownHosts = false;

		$api.request({
			module: 'KeyManager',
			action: 'addToKnownHosts',
			host: $scope.host,
			port: $scope.port
		}, function(response) {
						$scope.addHostLabel = "warning";
						$scope.addHost = "Working...";
						$scope.working = true;

						$scope.addToKnownHostsInterval = $interval(function(){
								$api.request({
										module: 'KeyManager',
										action: 'addToKnownHostsStatus'
								}, function(response) {
										if (response.success === true){
												$scope.working = false;
												$interval.cancel($scope.addToKnownHostsInterval);

												$scope.addHostLabel = "primary";
												$scope.addHost = "Add remote host to local known_hosts";

												$rootScope.status.refreshOutput = true;
												$rootScope.status.refreshKnownHosts = true;
										}
								});
						}, 5000);
		});
	});

	$scope.copyToRemoteHost = (function() {
		$rootScope.status.refreshOutput = false;
		$rootScope.status.refreshKnownHosts = false;

		$api.request({
			module: 'KeyManager',
			action: 'copyToRemoteHost',
			host: $scope.host,
			port: $scope.port,
			user: $scope.user,
			password: $scope.password
		}, function(response) {
						$scope.copyKeyLabel = "warning";
						$scope.copyKey = "Working...";
						$scope.working = true;

						$scope.copyToRemoteHostInterval = $interval(function(){
								$api.request({
										module: 'KeyManager',
										action: 'copyToRemoteHostStatus'
								}, function(response) {
										if (response.success === true){
												$scope.working = false;
												$interval.cancel($scope.copyToRemoteHostInterval);

												$scope.copyKeyLabel = "primary";
												$scope.copyKey = "Copy public key to remote host";

												$rootScope.status.refreshOutput = true;
												$rootScope.status.refreshKnownHosts = true;
										}
								});
						}, 5000);
		});
	});

	$scope.getSettings = function() {
			$api.request({
					module: 'KeyManager',
					action: 'getSettings'
			}, function(response) {
					$scope.host = response.host;
					$scope.port = response.port;
					$scope.user = response.user;
			});
	};

	$scope.getSettings();

}]);

registerController('KeyManager_KnownHostsController', ['$api', '$scope', '$timeout', '$rootScope', function($api, $scope, $timeout, $rootScope) {
	$scope.knownHostsData = '';
	$scope.saveKnownHostsLabel = "primary";
	$scope.saveKnownHosts = "Save";
	$scope.saving = false;

	$scope.saveKnownHostsData = (function() {
		$scope.saveKnownHostsLabel = "warning";
		$scope.saveKnownHosts = "Saving...";
		$scope.saving = true;

		$api.request({
			module: 'KeyManager',
			action: 'saveKnownHostsData',
			knownHostsData: $scope.knownHostsData
		}, function(response) {
            $scope.saveKnownHostsLabel = "success";
						$scope.saveKnownHosts = "Saved";

            $timeout(function(){
                $scope.saveKnownHostsLabel = "primary";
								$scope.saveKnownHosts = "Save";
								$scope.saving = false;
            }, 2000);
		});
	});

	$scope.getKnownHostsData = (function() {
		$api.request({
			module: 'KeyManager',
			action: 'getKnownHostsData'
		}, function(response) {
			$scope.knownHostsData = response.knownHostsData;
		});
	});

	$scope.getKnownHostsData();

	$rootScope.$watch('status.refreshKnownHosts', function(param) {
		if(param) {
			$scope.getKnownHostsData();
		}
	});

}]);
