// Foxtrot (C) 2016 <foxtrotnull@gmail.com>

registerController('ModuleMakerGenerator', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.moduleTitle = "";
    $scope.moduleDesc = "";
    $scope.moduleVersion = "";
    $scope.moduleAuthor = "";
    $scope.generateSuccess = false;
    $scope.generateFailure = "";

    $scope.generateModule = (function() {
        $api.request({
            module: 'ModuleMaker',
            action: 'generateModule',
            moduleTitle: $scope.moduleTitle,
            moduleDesc: $scope.moduleDesc,
            moduleVersion: $scope.moduleVersion,
            moduleAuthor: $scope.moduleAuthor
        }, function(response) {
            if (response.success === true) {
                $scope.generateSuccess = true;
                $scope.moduleTitle = "";
                $scope.moduleDesc = "";
                $scope.moduleVersion = "";
                $scope.moduleAuthor = "";
                $timeout(function(){
                    $scope.generateSuccess = false;
                }, 2000);
            } else {
                $scope.generateFailure = response.error;
                $timeout(function(){
                    $scope.generateFailure = "";
                }, 5000);
            }
        });
    });
}])

registerController('ModuleMakerManager', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.installedModules = "";
    $scope.removedModule = "";

    $scope.getInstalledModules = (function() {
        $api.request({
            module: "ModuleMaker",
            action: "getInstalledModules"
        }, function(response) {
            $scope.installedModules = response.installedModules;
            console.log(response);
        });
    });

    $scope.removeModule = (function(name) {
        $api.request({
            module: 'ModuleManager',
            action: 'removeModule',
            moduleName: name
        }, function(response) {
            if (response.success === true) {
                $scope.getInstalledModules();
                $scope.removedModule = true;
                $api.reloadNavbar();
                $timeout(function(){
                    $scope.removedModule = false;
                }, 2000);
            }
        });
    });

    $scope.getInstalledModules();
}])