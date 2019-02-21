registerController('HandshakeCrack_IsConnected', ['$api', '$scope', '$rootScope', '$interval', function ($api, $scope, $rootScope, $interval) {
    $rootScope.isConnected = false;
    $rootScope.noDependencies = false;

    var isConnected = function () {$api.request({
        module: "HandshakeCrack",
        action: "isConnected"
    }, function (response) {
        if (!response.success) {
            $rootScope.isConnected = true;
        } else {
            $rootScope.isConnected = false;
            $rootScope.noDependencies = false;
        }
    })};

    $api.request({
        module: "HandshakeCrack",
        action: "getStatus"
    }, function (response) {
        if (response.install === 'Install') {
            $rootScope.noDependencies = true;
        }
    });

    isConnected();

    var interval = $interval(function () {
        if (!$rootScope.isConnected) {
            $interval.cancel(interval);
        } else {
            isConnected();
        }
    }, 5000);
}]);

registerController('HandshakeCrack_Dependencies', ['$api', '$scope', '$rootScope', '$interval', function ($api, $scope, $rootScope, $interval) {
    $scope.install = "Loading...";
    $scope.installLabel = "default";
    $scope.processing = false;
    $rootScope.handshakeInfo = false;

    $rootScope.status = {
        installed: false,
        generated: false,
        refreshOutput: false,
        refreshKnownHosts: false
    };

    $scope.refreshStatus = (function () {
        $api.request({
            module: "HandshakeCrack",
            action: "getStatus"
        }, function (response) {
            $scope.status.installed = response.installed;
            $scope.processing = response.processing;
            $scope.install = response.install;
            $scope.installLabel = response.installLabel;

            if ($scope.processing) {
                $scope.statusDependencies();
            }
        })
    });

    $scope.statusDependencies = (function () {
        var statusDependenciesInterval = $interval(function () {
            $api.request({
                module: 'HandshakeCrack',
                action: 'statusDependencies'
            }, function (response) {
                if (response.success === true) {
                    $scope.processing = false;
                    $scope.refreshStatus();
                    $interval.cancel(statusDependenciesInterval);
                }
            });
        }, 2000);
    });

    $scope.managerDependencies = (function () {
        if ($scope.status.installed) {
            $scope.install = "Installing...";
        } else {
            $scope.install = "Removing...";
        }

        $api.request({
            module: 'HandshakeCrack',
            action: 'managerDependencies'
        }, function (response) {
            if (response.success === true) {
                $scope.installLabel = "warning";
                $scope.processing = true;
                $scope.statusDependencies();
            }
        });
    });

    $scope.refreshStatus();
}]);


registerController('HandshakeCrack_Settings', ['$api', '$scope', function ($api, $scope) {
    $scope.settings = {
        email: ""
    };

    $scope.saveSettingsLabel = "success";
    $scope.saveSettings = "Save";
    $scope.saving = false;

    $scope.getSettings = function () {
        $api.request({
            module: 'HandshakeCrack',
            action: 'getSettings'
        }, function (response) {
            $scope.settings = response.settings;
        });
    };

    $scope.setSettings = function () {
        $scope.saveSettingsLabel = "warning";
        $scope.saveSettings = "Saving...";
        $scope.saving = true;

        $api.request({
            module: 'HandshakeCrack',
            action: 'setSettings',
            settings: $scope.settings
        }, function (response) {
            setTimeout(function () {
                $scope.getSettings();
                $scope.saveSettingsLabel = "success";
                $scope.saveSettings = "Save";
                $scope.saving = false;
            }, 1000);
        });
    };

    $scope.getSettings();
}]);

registerController('HandshakeCrack_Files', ['$api', '$scope', '$rootScope', function ($api, $scope, $rootScope) {
    $scope.files = [];

    $scope.getHandshakeFiles = (function () {
        $api.request({
            module: 'HandshakeCrack',
            action: 'getHandshakeFiles'
        }, function (response) {
            $scope.files = response.files;
            $scope.fileHandshake = response.files[0];
            $scope.countHandshake = response.files.length;
            $scope.refreshHandshakeInfo();
        });
    });

    $scope.refreshHandshakeInfo = (function () {
        $api.request({
            module: 'HandshakeCrack',
            action: 'getHandshakeInfo',
            path: $scope.fileHandshake
        }, function (response) {
            if (response.success) {
                $rootScope.handshakeInfo = true;
                $scope.bssid = response.bssid;
                $scope.essid = response.essid;
            }
        });
    });

    $scope.getHandshakeFiles();
}]);

registerController('HandshakeCrack_Crack', ['$api', '$scope', '$controller', function ($api, $scope, $controller) {
    $controller('HandshakeCrack_Files', {$scope: $scope});

    $scope.working = false;
    $scope.btnClass = "default";
    $scope.btnText = "Send";
    $scope.output = 'Nothing here yet...';

    $scope.sendHandhake = (function () {
        $api.request({
            module: 'HandshakeCrack',
            action: 'sendHandshake',
            file: $scope.fileHandshake
        }, function (response) {
            $scope.output = response.output.join("\n");
            $scope.btnClass = "default";
            $scope.btnText = "Send";
            $scope.working = false;
        });
    });

    $scope.send = (function () {
        $scope.btnText = "Loading...";
        $scope.btnClass = "warning";
        $scope.output = 'Nothing here yet...';
        $scope.working = true;
        $scope.sendHandhake();
    });
}]);

registerController('HandshakeCrack_Convert', ['$api', '$scope', '$controller', function ($api, $scope) {
    $scope.btnClass = "default";
    $scope.btnText = "Convert";
    $scope.massages = '';
    $scope.working = false;

    $scope.converter = (function () {
        $api.request({
            module: 'HandshakeCrack',
            action: 'converter',
            file: $scope.fileHandshake
        }, function (response) {
            var data = JSON.parse(response.output);

            $scope.link = data.link;
            $scope.massages = data.uploaded;
            $scope.btnClass = "default";
            $scope.btnText = "Convert";
            $scope.working = false;

            setTimeout(function () {
                $scope.massages = '';
            }, 2000)
        });
    });

    $scope.send = (function () {
        $scope.btnText = "Loading...";
        $scope.btnClass = "warning";
        $scope.working = true;
        $scope.converter();
    });

    $scope.btnDownload = (function () {
        setTimeout(function () {
            $scope.link = '';
        }, 1000);
    });
}]);