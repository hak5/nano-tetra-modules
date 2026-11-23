/* PineappleTAK module for the WiFi Pineapple Tetra - page controller. */
registerController('PineappleTAKController', ['$api', '$scope', '$interval', function ($api, $scope, $interval) {
    $scope.status = null;
    $scope.busy = false;
    $scope.message = '';
    $scope.messageOk = true;
    $scope.pw = { one: '', two: '' };
    $scope.access = { listen: '' };

    function call(action, extra, done) {
        $api.request(angular.extend({ module: 'PineappleTAK', action: action }, extra || {}), function (resp) {
            if (done) { done(resp || {}); }
        });
    }

    $scope.refresh = function () {
        call('status', null, function (resp) {
            if (resp.success) {
                $scope.status = resp;
                if (!$scope.access.listen) { $scope.access.listen = resp.listen || 'all'; }
            }
        });
    };

    function act(action, extra) {
        $scope.busy = true;
        $scope.message = '';
        call(action, extra, function (resp) {
            $scope.busy = false;
            $scope.messageOk = !!resp.success;
            $scope.message = resp.message || (resp.success ? 'Done' : 'Failed');
            $scope.refresh();
        });
    }

    $scope.install = function () { act('install'); };
    $scope.service = function (op) { act('service', { op: op }); };
    $scope.toggleBoot = function () { $scope.service($scope.status.enabled ? 'disable' : 'enable'); };

    $scope.setPassword = function () {
        if ($scope.pw.one.length < 8) { $scope.messageOk = false; $scope.message = 'Use at least 8 characters'; return; }
        if ($scope.pw.one !== $scope.pw.two) { $scope.messageOk = false; $scope.message = 'The two passwords do not match'; return; }
        act('setPassword', { password: $scope.pw.one });
        $scope.pw = { one: '', two: '' };
    };

    $scope.setListen = function () { act('setListen', { mode: $scope.access.listen }); };

    // Opens the console on the same host name or IP as this Pineapple page, so it works however you reached the Pineapple
    $scope.openUrl = function () {
        var s = $scope.status;
        if (!s) { return ''; }
        if (s.listen === 'all' && window.location.hostname) {
            return s.scheme + '://' + window.location.hostname + ':' + s.port;
        }
        return s.urls[0];
    };

    $scope.uninstall = function () {
        if (confirm('Remove PineappleTAK? Your settings, certificates and loot are kept for a reinstall.')) {
            act('uninstall', { keepData: true });
        }
    };

    $scope.updateAvailable = function () {
        var s = $scope.status;
        return !!s && s.installed && !!s.moduleVersion && s.moduleVersion !== s.serviceVersion;
    };

    $scope.linkText = function () {
        var s = $scope.status;
        if (!s || !s.running) { return 'Stopped'; }
        if (s.setupMode) { return 'Waiting for setup in the console'; }
        return s.link === 'connected' ? 'Connected to the TAK Server' :
            s.link === 'connecting' ? 'Connecting to the TAK Server' :
            s.link === 'disconnected' ? 'Not connected to the TAK Server' : 'Starting';
    };

    $scope.linkClass = function () {
        var s = $scope.status;
        if (!s || !s.running) { return 'text-muted'; }
        return s.link === 'connected' ? 'text-success' : s.link === 'disconnected' ? 'text-danger' : 'text-warning';
    };

    $scope.refresh();
    var timer = $interval(function () { if (!$scope.busy) { $scope.refresh(); } }, 5000);
    $scope.$on('$destroy', function () { $interval.cancel(timer); });
}]);
