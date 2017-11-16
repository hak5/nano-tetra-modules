registerController('LEDController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.device = '';
    $scope.resetConfig = false;

    $api.request({
        module: 'LEDController',
        action: 'getDeviceType'
    }, function(response) {
        $scope.device = response;
    });

    $scope.resetLEDs = (function() {
        $api.request({
            module: 'LEDController',
            action: 'resetLEDs'
        }, function(response) {
            $scope.resetConfig = response.success;
            $timeout(function(){
                $scope.resetConfig = false;
            }, 2000);
        });
    });
}]);

registerController('TetraYellow', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.enabled = false;
    $scope.trigger = 'link tx rx';
    $scope.mode = '';
    $scope.delayOn = '';
    $scope.delayOff = '';
    $scope.interface = '';
    $scope.savedConfig = false;

    $scope.getTetraYellow = (function() {
        $api.request({
            module: 'LEDController',
            action: 'getTetraYellow'
        }, function(response) {
            $scope.enabled = response.enabled;
            $scope.trigger = response.trigger;
            $scope.mode = response.mode;
            $scope.delayOn = response.delayOn;
            $scope.delayOff = response.delayOff;
            $scope.interface = response.interface;
        });
    });

    $scope.setTetraYellow = (function() {
        $api.request({
            module: 'LEDController',
            action: 'setTetraYellow',
            enabled: $scope.enabled,
            trigger: $scope.trigger,
            mode: $scope.mode,
            delayOn: $scope.delayOn,
            delayOff: $scope.delayOff,
            interface: $scope.interface
        }, function(response) {
            if (response.success == true) {
                $scope.savedConfig = true;
                $timeout(function(){
                    $scope.savedConfig = false;
                }, 2000);
            }
            $scope.getTetraYellow();
        });
    });

    $scope.getTetraYellow();
}]);

registerController('TetraBlue', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.enabled = false;
    $scope.trigger = '';
    $scope.mode = 'link tx rx';
    $scope.delayOn = '';
    $scope.delayOff = '';
    $scope.interface = '';
    $scope.savedConfig = false;

    $scope.getTetraBlue = (function() {
        $api.request({
            module: 'LEDController',
            action: 'getTetraBlue'
        }, function(response) {
            $scope.enabled = response.enabled;
            $scope.trigger = response.trigger;
            $scope.mode = response.mode;
            $scope.delayOn = response.delayOn;
            $scope.delayOff = response.delayOff;
            $scope.interface = response.interface;
        });
    });

    $scope.setTetraBlue = (function() {
        $api.request({
            module: 'LEDController',
            action: 'setTetraBlue',
            enabled: $scope.enabled,
            trigger: $scope.trigger,
            mode: $scope.mode,
            delayOn: $scope.delayOn,
            delayOff: $scope.delayOff,
            interface: $scope.interface
        }, function(response) {
            if (response.success == true) {
                $scope.savedConfig = true;
                $timeout(function(){
                    $scope.savedConfig = false;
                }, 2000);
            }
            $scope.getTetraBlue();
        });
    });
    $scope.getTetraBlue();
}]);

registerController('TetraRed', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.enabled = false;
    $scope.trigger = '';
    $scope.mode = 'link tx rx';
    $scope.delayOn = '';
    $scope.delayOff = '';
    $scope.interface = '';
    $scope.savedConfig = false;

    $scope.getTetraRed = (function() {
        $api.request({
            module: 'LEDController',
            action: 'getTetraRed'
        }, function(response) {
            $scope.enabled = response.enabled;
            $scope.trigger = response.trigger;
            $scope.mode = response.mode;
            $scope.delayOn = response.delayOn;
            $scope.delayOff = response.delayOff;
            $scope.interface = response.interface;
        });
    });

    $scope.setTetraRed = (function() {
        $api.request({
            module: 'LEDController',
            action: 'setTetraRed',
            enabled: $scope.enabled,
            trigger: $scope.trigger,
            mode: $scope.mode,
            delayOn: $scope.delayOn,
            delayOff: $scope.delayOff,
            interface: $scope.interface
        }, function(response) {
            if (response.success == true) {
                $scope.savedConfig = true;
                $timeout(function(){
                    $scope.savedConfig = false;
                }, 2000);
            }
            $scope.getTetraRed();
        });
    });

    $scope.getTetraRed();
}]);

registerController('NanoBlue', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.enabled = false;
    $scope.trigger = '';
    $scope.mode = 'link tx rx';
    $scope.delayOn = '';
    $scope.delayOff = '';
    $scope.interface = '';
    $scope.savedConfig = false;

    $scope.getNanoBlue = (function() {
        $api.request({
            module: 'LEDController',
            action: 'getNanoBlue'
        }, function(response) {
            $scope.enabled = response.enabled;
            $scope.trigger = response.trigger;
            $scope.mode = response.mode;
            $scope.delayOn = response.delayOn;
            $scope.delayOff = response.delayOff;
            $scope.interface = response.interface;
        });
    });

    $scope.setNanoBlue = (function() {
        $api.request({
            module: 'LEDController',
            action: 'setNanoBlue',
            enabled: $scope.enabled,
            trigger: $scope.trigger,
            mode: $scope.mode,
            delayOn: $scope.delayOn,
            delayOff: $scope.delayOff,
            interface: $scope.interface
        }, function(response) {
            if (response.success == true) {
                $scope.savedConfig = true;
                $timeout(function(){
                    $scope.savedConfig = false;
                }, 2000);
            }
            $scope.getNanoBlue();
        });
    });

    $scope.getNanoBlue();
}]);
