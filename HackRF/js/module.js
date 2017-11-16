registerController('HackRFController', ['$api', '$scope', '$interval', function($api, $scope, $interval) {
    $scope.foundBoard       = false;
    $scope.availableHackRFs = "";
    $scope.running          = false;
    $scope.installed        = false;
    $scope.installling      = false;

    $scope.hackrfInfo = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfInfo'
        }, function(response) {
            $scope.foundBoard = response.foundBoard;

            if (response.foundBoard === true) {
                $scope.availableHackRFs = response.availableHackRFs;
            }
        });
    });

    $scope.hackrfChecker = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfChecker'
        }, function(response) {
            if(response.installed === true) {
                $scope.installed = true;
                $scope.installing = false;
                $scope.hackrfInfo();
                $interval.cancel($scope.install_interval);
            } else {
                $scope.installed = false;
            }
        });
    });

    $scope.hackrfInstall = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfInstall'
        }, function(response) {
            if(response.installing === true) {
                $scope.installing = true;
                $scope.install_interval = $interval(function(){
                    $scope.hackrfChecker();
                }, 1000);
            }
        });
    });

    $scope.hackrfUninstall = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfUninstall'
        }, function(response) {
            if(response.success === true) {
                $scope.hackrfChecker();
                $scope.hackrfInfo();
            }
        });
    });

    $scope.hackrfChecker();
    $scope.hackrfInfo();

    $scope.$on('$destroy', function() {
        $interval.cancel($scope.install_interval);
    });
}]);

registerController('HackRFSettingsController', ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
    $scope.mode            = "rx";
    $scope.sampleRate      = "";
    $scope.centerFreq      = "";
    $scope.filename        = "";
    $scope.amp             = false;
    $scope.antpower        = false;
    $scope.txRepeat        = false;
    $scope.txIfCheckbox    = false;
    $scope.rxIfCheckbox    = false;
    $scope.rxBbCheckbox    = false;
    $scope.txIfGain        = 0;
    $scope.rxIfGain        = 0;
    $scope.rxBbGain        = 0;
    $scope.sampleRateError = false;
    $scope.filenameError   = false;
    $scope.centerFreqError = false;

    $scope.hackrfTransfer = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfTransfer',
            mode:         $scope.mode,
            sampleRate:   $scope.sampleRate,
            centerFreq:   $scope.centerFreq,
            filename:     $scope.filename,
            amp:          $scope.amp,
            antpower:     $scope.antpower,
            txRepeat:     $scope.txRepeat,
            txIfCheckbox: $scope.txIfCheckbox,
            txIfGain:     $scope.txIfGain,
            rxIfCheckbox: $scope.rxIfCheckbox,
            rxBbCheckbox: $scope.rxBbCheckbox,
            rxIfGain:     $scope.rxIfGain,
            rxBbGain:     $scope.rxBbGain
        }, function(response) {
            if(response.success === true) {
                $scope.running = true;
            } else if(response.success === false) {
                if(response.error == "samplerate") {
                    $scope.sampleRateError = true;
                    $timeout(function() {
                        $scope.sampleRateError = false;
                    }, 3000);
                } else if(response.error == "filename") {
                    $scope.filenameError = true;
                    $timeout(function() {
                        $scope.filenameError = false;
                    }, 3000);
                } else if(response.error == "centerfreq") {
                    $scope.centerFreqError = true;
                    $timeout(function() {
                        $scope.centerFreqError = false;
                    }, 3000);
                }
            }
        });
    });

    
    $scope.hackrfStop = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfStop'
        }, function(response) {
            if (response.success === true) {
                $scope.running = false;
            }
        });
    });
}]);

registerController('HackRFLoggingController', ['$api', '$scope', '$interval', function($api, $scope, $interval) {
    $scope.log = "";
    $scope.autoRefresh = false;

    $scope.hackrfLog = (function() {
        $api.request({
            module: 'HackRF',
            action: 'hackrfLog'
        }, function(response) {
            if (response.success === true) {
                $scope.log = response.log;
            }
        });
    });

    $scope.enableAutoRefresh = (function() {
        $scope.autoRefresh = true;
        $scope.refresh_interval = $interval(function(){
            $scope.hackrfLog();
        }, 1000);
    });

    $scope.disableAutoRefresh = (function() {
        $scope.autoRefresh = false;
        $interval.cancel($scope.refresh_interval);
    });

    $scope.hackrfLog();
    $scope.$on('$destroy', function() {
        $interval.cancel($scope.refresh_interval);
    });

}]);