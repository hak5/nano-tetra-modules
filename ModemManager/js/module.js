registerController('USBController', ['$api', '$scope', '$interval', function($api, $scope, $interval) {
    $scope.lsusb         = "";
    $scope.availableTTYs = "";
    $scope.installed     = false;
    $scope.installling   = false;


    $scope.checkDepends = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'checkDepends'
        }, function(response) {
            console.log(response);
            if(response.installed === true) {
                $scope.installed = true;
                $scope.installing = false;
                $interval.cancel($scope.install_interval);
            } else {
                $scope.installed = false;
            }
        });
    });

    $scope.installDepends = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'installDepends'
        }, function(response) {
            console.log('INSTALL');
            console.log(response);
            if(response.installing === true) {
                $scope.installing = true;
                $scope.install_interval = $interval(function(){
                    $scope.checkDepends();
                }, 1000);
            }
        });
    });

    $scope.removeDepends = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'removeDepends'
        }, function(response) {
            console.log('UNINSTALL');
            console.log(response);
            if(response.success === true) {
                $scope.checkDepends();
            }
        });
    });

    $scope.checkDepends();

    $scope.$on('$destroy', function() {
        $interval.cancel($scope.install_interval);
    });

    $scope.getUSB = (function(){
        $api.request({
            module: 'ModemManager',
            action: 'getUSB'
        }, function(response) {
            if (response.lsusb) {
                $scope.lsusb = response.lsusb;
            }
        });
    });

    $scope.getTTYs = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'getTTYs'
        }, function(response) {
            if (response.success) {
                $scope.availableTTYs = response.availableTTYs;
            } else {
                $scope.availableTTYs = "No TTYs Found.";
            }
        });
    });

    $scope.refresh = (function(){
        $scope.lsusb = "";
        $scope.availableTTYs = "";
        $scope.getUSB();
        $scope.getTTYs();
    });

    $scope.getUSB();
    $scope.getTTYs();
}]);

registerController('ConnectionController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.connected    = false;
    $scope.connecting   = false;
    $scope.disconnected = true;

    $scope.checkConnection = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'checkConnection'
        }, function(response) {
            if (response.status == 'connected') {
                $scope.connected    = true;
                $scope.connecting   = false;
                $scope.disconnected = false;
            }
        });
    });

    $scope.setConnection = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'setConnection'
        }, function(response) {
            if(response.status == 'connecting') {
                $scope.connected    = false;
                $scope.connecting   = true;
                $scope.disconnected = false;
            };
        });
    });

    $scope.unsetConnection = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'unsetConnection'
        }, function(response) {
            if(response.status == 'disconnected') {
                $scope.connecting   = false;
                $scope.connected    = false;
                $scope.disconnected = true;
            };
        });
    });

    $scope.checkConnection();

    setInterval(function() {
       $scope.checkConnection();
   }, 10000);
}]);

registerController('ModemController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.savedConfiguration        = false;
    $scope.resetConfigurationSuccess = false;
    $scope.saveConfigurationError    = "";
    $scope.loadConfigurationError    = "";
    $scope.resetConfigurationError   = "";

    $scope.loadConfiguration = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'loadConfiguration'
        }, function(response) {
            if (response.success === true) {
                $scope.interface    = response.interface;
                $scope.protocol     = response.protocol;
                $scope.service      = response.service;
                $scope.vendorid     = response.vendorid;
                $scope.productid    = response.productid;
                $scope.device       = response.device;
                $scope.apn          = response.apn;
                $scope.username     = response.username;
                $scope.password     = response.password;
                $scope.dns          = response.dns;
                $scope.peerdns      = response.peerdns;
                $scope.pppredial    = response.pppredial;
                $scope.defaultroute = response.defaultroute;
                $scope.keepalive    = response.keepalive;
                $scope.pppdoptions  = response.pppdoptions;
            } else {
                $scope.loadConfigurationError = "Failed to load configuration.";
            }
        });
    });

    $scope.saveConfiguration = (function() {
        $api.request({
            module:       'ModemManager',
            action:       'saveConfiguration',
            interface:    $scope.interface,
            protocol:     $scope.protocol,
            service:      $scope.service,
            vendorid:     $scope.vendorid,
            productid:    $scope.productid,
            device:       $scope.device,
            apn:          $scope.apn,
            username:     $scope.username,
            password:     $scope.password,
            dns:          $scope.dns,
            peerdns:      $scope.peerdns,
            pppredial:    $scope.pppredial,
            defaultroute: $scope.defaultroute,
            keepalive:    $scope.keepalive,
            pppdoptions:  $scope.pppdoptions
        }, function(response) {
            if (response.success === true) {
                $scope.savedConfiguration = true;
                $timeout(function(){
                    $scope.savedConfiguration = false;
                }, 2000);
            } else {
                $scope.saveConfigurationError = "Failed to save configuration.";
            }
        });
    });

    $scope.resetConfiguration = (function() {
        $api.request({
            module: 'ModemManager',
            action: 'resetConfiguration'
        }, function(response) {
            if (response.success === true) {
                $scope.resetConfigurationSuccess = true;
                $scope.loadConfiguration();
                $timeout(function(){
                    $scope.resetConfigurationSuccess = false;
                }, 2000);
            } else {
                $scope.resetConfigurationError = "Failed to open configuration.";
            }
        });
    });

    $scope.loadConfiguration();
}]);