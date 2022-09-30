registerController('CommanderController', ['$api', '$scope', function($api, $scope) {
    $scope.commanderRunning = false;

    $scope.startCommander = (function() {
        $api.request({
            module: 'Commander',
            action: 'startCommander'
        }, function(response) {
            if (response.success === true) {
                $scope.commanderRunning = true;
            }
        });
    });

    $scope.stopCommander = (function() {
        $api.request({
            module: 'Commander',
            action: 'stopCommander'
        }, function(response){
            if (response.success === true) {
                $scope.commanderRunning = false;
            }
        });
    });
}]);

registerController('CommanderManageController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.CommanderConfiguration = "";

    $scope.getConfiguration = (function() {
        $api.request({
            module: 'Commander',
            action: 'getConfiguration'
        }, function(response) {
            console.log(response);
            if (response.error === undefined){
                $scope.CommanderConfiguration = response.CommanderConfiguration;
            }
        });
    });

    $scope.saveConfiguration = (function() {
        $api.request({
            module: 'Commander',
            action: 'saveConfiguration',
            CommanderConfiguration: $scope.CommanderConfiguration
        }, function(response) {
            console.log(response);
            if (response.success === true){
                $scope.getConfiguration();
            }
        });
    });

    $scope.restoreDefaultConfiguration = (function() {
        $api.request({
            module: 'Commander',
            action: 'restoreDefaultConfiguration'
        }, function(response) {
            if (response.success === true) {
                $scope.getConfiguration();
            }
        });
    });

    $scope.getConfiguration();
}]);