registerController('Terminal_Dependencies', ['$api', '$scope', '$rootScope', '$interval', function ($api, $scope, $rootScope, $interval) {
    $scope.install = "Loading...";
    $scope.installLabel = "";
    $scope.processing = false;
    $rootScope.installedDependencies = false;

    $scope.refreshStatus = function () {
        $rootScope.installedDependencies = false;

        $api.request({
            module: "Terminal",
            action: "getDependenciesStatus"
        }, function (response) {
            $rootScope.installedDependencies = response.installed;
            $scope.processing = response.processing;
            $scope.install = response.install;
            $scope.installLabel = response.installLabel;

            if ($scope.processing) {
                $scope.getDependenciesInstallStatus();
            }
        })
    };

    $scope.getDependenciesInstallStatus = function () {
        var dependenciesInstallStatusInterval = $interval(function () {
            $api.request({
                module: 'Terminal',
                action: 'getDependenciesInstallStatus'
            }, function (response) {
                if (response.success === true) {
                    $scope.processing = false;
                    $scope.refreshStatus();
                    $interval.cancel(dependenciesInstallStatusInterval);
                }
            });
        }, 2000);
    };

    $scope.managerDependencies = function () {
        $scope.install = $rootScope.installedDependencies ? "Removing..." : "Installing...";
        $api.request({
            module: 'Terminal',
            action: 'managerDependencies'
        }, function (response) {
            if (response.success === true) {
                $scope.installLabel = "warning";
                $scope.processing = true;
                $scope.getDependenciesInstallStatus();
            }
        });
    };

    $scope.refreshStatus();
}]);
