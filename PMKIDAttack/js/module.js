registerController('PMKIDAttack_Dependencies', ['$api', '$scope', '$rootScope', '$interval', function ($api, $scope, $rootScope, $interval) {
    $scope.install = "Loading...";
    $scope.installLabel = "";
    $scope.processing = false;
    $rootScope.installedDependencies = false;
    $rootScope.handshakeInfo = false;
    $rootScope.running = false;
    $rootScope.captureRunning = false;

    $scope.refreshStatus = function () {
        $rootScope.installedDependencies = false;

        $api.request({
            module: "PMKIDAttack",
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
                module: 'PMKIDAttack',
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
            module: 'PMKIDAttack',
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

registerController('PMKIDAttack_ScanSettings', ['$api', '$scope', '$rootScope', '$interval', '$timeout', '$cookies', function ($api, $scope, $rootScope, $interval, $timeout, $cookies) {
    $rootScope.accessPoints = [];
    $rootScope.unassociatedClients = [];
    $rootScope.outOfRangeClients = [];
    $scope.scans = [];
    $scope.selectedScan = "";
    $scope.loadedScan = null;
    $scope.scanType = '0';
    $scope.paused = false;
    $scope.percent = 0;
    $scope.error = false;
    $scope.pineAPDRunning = true;
    $scope.pineAPDStarting = false;
    $scope.percentageInterval = 300;
    $scope.wsAuthToken = "";
    $scope.scanSettings = {
        scanDuration: $cookies.get('scanDuration') !== undefined ? $cookies.get('scanDuration') : '0',
        live: $cookies.get('liveScan') !== undefined ? $cookies.get('liveScan') === 'true' : true
    };

    function checkScanStatus() {
        if ($scope.scanSettings.scanDuration < 1) {
            return;
        }
        if (!$scope.updatePercentageInterval) {
            $scope.updatePercentageInterval = $interval(function () {
                var percentage = $scope.percentageInterval / ($scope.scanSettings.scanDuration * 10);
                if (($scope.percent + percentage) >= 100 && $rootScope.running && !$scope.loading) {
                    $scope.percent = 100;
                    $scope.checkScan();
                } else if ($scope.percent + percentage < 100 && $rootScope.running) {
                    $scope.percent += percentage;
                }
            }, $scope.percentageInterval);
        }
    }

    function parseScanResults(results) {
        annotateMacs();
        var data = results['results'];
        $rootScope.accessPoints = data['ap_list'];
        $rootScope.unassociatedClients = data['unassociated_clients'];
        $rootScope.outOfRangeClients = data['out_of_range_clients'];
    }

    $scope.updateScanSettings = function () {
        $cookies.put('scanDuration', $scope.scanSettings.scanDuration);
        if ($scope.scanSettings.scanDuration === "0") {
            $scope.scanSettings.live = true;
        }
        $cookies.put('liveScan', $scope.scanSettings.live);
        ($cookies.getAll());
    };

    $scope.startScan = function () {
        $scope.percent = 0;
        if ($rootScope.running) {
            return;
        }
        if ($scope.scanSettings.scanDuration === "0") {
            $scope.scanSettings.live = true;
        }
        if ($scope.scanSettings.live === true) {
            $scope.startLiveScan();
        } else {
            $scope.startNormalScan();
        }
        $rootScope.accessPoints = [];
        $rootScope.unassociatedClients = [];
        $rootScope.outOfRangeClients = [];
        checkScanStatus();
    };

    $scope.startLiveScan = function () {
        $scope.loading = true;

        $api.request({
            module: 'Recon',
            action: 'startLiveScan',
            scanType: $scope.scanType,
            scanDuration: $scope.scanSettings.scanDuration
        }, function (response) {
            if (response.success) {
                $scope.loading = false;
                $rootScope.running = true;
                $scope.scanID = response.scanID;
                if ($scope.wsStarted !== true) {
                    $scope.startWS();
                }
            } else {
                if (response.error === "The PineAP Daemon must be running.") {
                    $scope.pineAPDRunning = false;
                }
                $scope.error = response.error;
            }
        });
    };

    $scope.startWS = (function () {
        $scope.wsStarted = true;
        $api.request({
            module: 'Recon',
            action: 'getWSAuthToken'
        }, function (response) {
            if (response.success === true) {
                $scope.wsAuthToken = response.wsAuthToken;
                $scope.doWS();
            } else {
                $scope.wsTimeout = $timeout($scope.startWS, 1500);
            }
        });
    });

    $scope.doWS = (function () {
        if ($scope.ws !== undefined && $scope.ws.readyState !== WebSocket.CLOSED) {
            return;
        }
        $scope.ws = new WebSocket("ws://" + window.location.hostname + ":1337/?authtoken=" + $scope.wsAuthToken);
        $scope.ws.onerror = (function () {
            $scope.wsTimeout = $timeout($scope.startWS, 1000);
        });
        $scope.ws.onopen = (function () {
            $scope.ws.onerror = (function () {
            });
            $rootScope.running = true;

        });
        $scope.ws.onclose = (function () {
            $scope.listening = false;
            $scope.closeWS();
        });

        $scope.ws.onmessage = (function (message) {
            $scope.listening = true;
            if ($scope.paused) {
                return;
            }
            var data = JSON.parse(message.data);
            if (data.scan_complete === true) {
                $scope.checkScan();
                return;
            }
            $rootScope.accessPoints = data.ap_list;
            $rootScope.unassociatedClients = data.unassociated_clients;
            $rootScope.outOfRangeClients = data.out_of_range_clients;
            annotateMacs();
        });
    });

    $scope.startNormalScan = function () {
        if ($rootScope.running) {
            return;
        }

        $scope.loading = true;

        $api.request({
            module: 'Recon',
            action: 'startNormalScan',
            scanType: $scope.scanType,
            scanDuration: $scope.scanSettings.scanDuration
        }, function (response) {
            if (response.success) {
                $scope.loading = false;
                $rootScope.running = true;
                $scope.scanID = response.scanID;
            } else {
                if (response.error === "The PineAP Daemon must be running.") {
                    $scope.pineAPDRunning = false;
                }
                $scope.error = response.error;
            }
        });
    };

    $scope.pauseLiveScan = function () {
        $scope.paused = true;
    };

    $scope.resumeLiveScan = function () {
        $scope.paused = false;
    };

    $scope.stopScan = function () {
        $scope.percent = 0;
        $scope.paused = false;
        $rootScope.running = false;

        $api.request({
            module: 'Recon',
            action: 'stopScan'
        }, function (response) {
            if (response.success === true) {
                $rootScope.running = false;
                $scope.closeWS();
            }
        });
    };

    $scope.checkScan = function () {
        $api.request({
            module: 'Recon',
            action: 'checkScanStatus',
            scanID: $scope.scanID
        }, function (response) {
            $scope.percent = response.scanPercent;
            if (response.error) {
                $scope.error = response.error;
            } else if (response.completed === true) {
                if (!$rootScope.running && !$scope.loading) {
                    $scope.percent = 100;
                }
                if ($rootScope.running) {
                    $scope.stopScan();
                    $scope.scans = $scope.scans || [];
                    
                    // fix missing logic
                    //$scope.selectedScan = $scope.scans[$scope.scans.length - 1];
                    $scope.selectedScan = {};
                    $scope.selectedScan['scan_id'] = $scope.scanID;
                    
                    $scope.displayScan();
                }
            } else if (response.completed === false) {
                if (response.scanID !== null && response.scanID !== undefined) {
                    $scope.scanID = response.scanID;
                }
            }
        });
    };

    $scope.displayScan = function () {
        if ($scope.selectedScan === undefined) {
            return;
        }

        $scope.loadingScan = true;
        $api.request({
            module: 'Recon',
            action: 'getScans'
        }, function (response) {
            if (response.error === undefined) {
                $scope.scans = response.scans;
                $api.request({
                    module: 'Recon',
                    action: 'loadResults',
                    scanID: $scope.selectedScan['scan_id']
                }, function (response) {
                    parseScanResults(response);
                    $scope.loadingScan = false;
                    $scope.loadedScan = $scope.selectedScan;
                    $scope.scanID = $scope.selectedScan['scan_id'];
                });
            } else {
                $scope.error = response.error;
            }
        });
    };

    $scope.cancelIntervals = function () {
        if ($scope.checkScanInterval) {
            $interval.cancel($scope.checkScanInterval);
        }
        if ($scope.updatePercentageInterval) {
            $interval.cancel($scope.updatePercentageInterval);
        }

        if ($scope.wsTimeout) {
            $timeout.cancel($scope.wsTimeout);
        }
        $scope.checkScanInterval = null;
        $scope.updatePercentageInterval = null;
        $scope.wsTimeout = null;
    };

    $scope.closeWS = (function () {
        if ($scope.ws !== undefined) {
            $scope.ws.close();
            $scope.wsStarted = false;
        }
    });

    $scope.displayCurrentScan = function () {
        $api.request({
            module: 'Recon',
            action: 'checkScanStatus'
        }, function (response) {
            if (!response.completed && response.scanID !== null) {
                $scope.scanID = response.scanID;
                $scope.loading = true;
                if (response.continuous) {
                    $scope.scanSettings.scanDuration = "0";
                    $scope.scanSettings.live = true;
                    $scope.percent = response.scanPercent;
                }
                $api.request({
                    module: 'Recon',
                    action: 'startReconPP'
                }, function () {
                    if ($scope.wsStarted !== true) {
                        $scope.startWS();
                    }
                    $rootScope.running = true;
                    checkScanStatus();
                    $scope.loading = false;
                });
            }
        });
    };

    $scope.startPineAP = function () {
        $scope.pineAPDStarting = true;
        $api.request({
            module: 'Recon',
            action: 'startPineAPDaemon'
        }, function (response) {
            $scope.pineAPDStarting = false;
            if (response.error === undefined) {
                $scope.pineAPDRunning = true;
                $scope.startScan();
                $scope.error = null;
            } else {
                $scope.error = response.error;
            }
        });
    };

    $scope.checkScan();

    $scope.$on('$destroy', function () {
        $scope.cancelIntervals();
        $scope.closeWS();
    });

    $api.onDeviceIdentified(function (device) {
        $scope.updateScanSettings();
        $scope.device = device;
        $scope.displayCurrentScan();
    }, $scope);
}]);


registerController('PMKIDAttack_ScanResults', ['$api', '$scope', '$interval', '$rootScope', function ($api, $scope, $interval, $rootScope) {
    $rootScope.ssid = '';
    $rootScope.bssid = '';
    $rootScope.pmkidLog = '';
    $rootScope.pmkids = [];
    $rootScope.pmkidsLoading = false;
    $scope.reverseSort = false;
    $scope.orderByName = 'ssid';

    $scope.getStatusAttack = function () {
        $api.request({
            action: "getStatusAttack",
            module: "PMKIDAttack"
        }, function (response) {
            if (response.success) {
                $rootScope.ssid = response.ssid;
                $rootScope.bssid = response.bssid;
                $scope.checkPMKID();
            }
        });
    };

    $scope.checkPMKID = function() {
        $rootScope.captureRunning = true;
        if (!$rootScope.intervalCheckHash) {
            $rootScope.intervalCheckHash = $interval(function () {
                if ($rootScope.captureRunning) {
                    $rootScope.catchPMKID();
                } else {
                    $rootScope.stopAttack();
                }
            }, 5000);
        }
    };

    $scope.startAttack = function (ssid, bssid) {
        $rootScope.pmkidLog = '';
        $rootScope.ssid = ssid;
        $rootScope.bssid = bssid;

        $api.request({
            action: 'startAttack',
            module: 'PMKIDAttack',
            ssid: ssid,
            bssid: bssid
        }, function (response) {
            if (response.success) {
                $scope.checkPMKID();
            }
        });
    };

    $rootScope.stopAttack = function () {
        $api.request({
            action: 'stopAttack',
            module: 'PMKIDAttack',
            bssid: $rootScope.bssid
        }, function (response) {
            $interval.cancel($rootScope.intervalCheckHash);
            delete $rootScope.intervalCheckHash;
            $rootScope.captureRunning = false;
            $rootScope.getPMKIDFiles();
        });
    };

    $rootScope.viewAttackLog = function (file = '') {
        $rootScope.pmkidLog = '';
        $api.request({
            action: 'viewAttackLog',
            module: 'PMKIDAttack',
            file: file
        }, function (response) {
            $rootScope.pmkidLog = response.pmkidLog;
        });
    };

    $rootScope.catchPMKID = function () {
        $api.request({
            action: 'catchPMKID',
            module: 'PMKIDAttack'
        }, function (response) {
            $rootScope.pmkidLog = response.pmkidLog;
            if (response.success) {
                $rootScope.captureRunning = false;
            }
        });
    };

    $rootScope.getPMKIDFiles = function () {
        $rootScope.pmkids = [];
        $rootScope.pmkidsLoading = true;

        $api.request({
            action: 'getPMKIDFiles',
            module: 'PMKIDAttack',
        }, function (response) {
            $rootScope.pmkids = response.pmkids;
            $rootScope.pmkidsLoading = false;
        });
    };

    $rootScope.downloadPMKID = function (file) {
        $api.request({
            action: 'downloadPMKID',
            module: 'PMKIDAttack',
            file: file
        }, function (response) {
            window.location = '/api/?download=' + response.download;
        });
    };

    $rootScope.deletePMKID = function (file) {
        $api.request({
            action: 'deletePMKID',
            module: 'PMKIDAttack',
            file: file
        }, function (response) {
            $rootScope.getPMKIDFiles();
        });
    };

    $rootScope.getPMKIDFiles();
    $scope.getStatusAttack();

    $scope.$on('$destroy', function() {
        $interval.cancel($scope.intervalCheckHash);
    });
}]);

registerController('PMKIDAttack_Log', ['$api', '$scope', '$rootScope', '$interval', function ($api, $scope, $rootScope, $interval) {
    $scope.moduleLog = '';
    $scope.moduleLogLading = false;

    $scope.refreshLog = function () {
        $scope.moduleLog = '';
        $scope.moduleLogLading = true;

        $api.request({
            module: "PMKIDAttack",
            action: "getLog"
        }, function (response) {
            $scope.moduleLog = response.moduleLog;
            $scope.moduleLogLading = false;
        })
    };

    $scope.clearLog = function () {
        $api.request({
            module: "PMKIDAttack",
            action: "clearLog"
        }, function (response) {
            $scope.moduleLog = '';
        })
    };
}]);
