registerController('SSIDManagerController', ['$api', '$scope', '$timeout',function($api, $scope, $timeout) {
    /* It is good practice to 'initialize' your variables with nothing */
    $scope.currentSSIDs = "";
    $scope.pineAPssidPool = "";
    $scope.ssidPool = "";
    $scope.storeFileName = "";
    $scope.updatedPineAP = "";
    $scope.storedSSIDFile = "";
    $scope.deletedSSIDFile = "";

    $scope.getPool = (function() {
        $api.request({
            module: 'PineAP',
            action: 'getPool'
        }, function(response) {
            $scope.ssidPool = response.ssidPool;
            $scope.pineAPssidPool = response.ssidPool;
        });
    });
    
    $scope.clearPool = (function() {
        $api.request({
            module: 'PineAP',
            action: 'clearPool'
        }, function(response) {
            $scope.ssidPool = '';
             $scope.pineAPssidPool = '';
        });
    });

	$scope.setPool = (function() {
        $api.request({
            module: 'PineAP',
            action: 'clearPool'
        }, function(response) {
			var newPool = $scope.ssidPool.split("\n");
			$api.request({
            	module: 'PineAP',
				action: 'addSSIDs',
				ssids: newPool
        	}, function(response) {
            	if (response.error === undefined) {
					$scope.updatedPineAP = true;
            	} else {
                	$scope.lengthError = true;
            	}
				$timeout(function(){
                	$scope.updatedPineAP = false;
            	}, 2000);
            	$scope.getPool();
				
        	});   
        });
    });

    $scope.archivePool = (function() {
        $api.request({
            module: 'SSIDManager',
            action: 'archivePool',
            storeFileName: $scope.storeFileName,
            ssidPool: $scope.ssidPool
        }, function(response) {
	        $scope.storeFileName = '';
			$scope.getSSIDFilesList();
			$scope.storedSSIDFile = true;
			$timeout(function(){
                	$scope.storedSSIDFile = false;
            	}, 2000);
        });
    });
    
    $scope.deleteSSIDFile = (function() {
        $api.request({
            module: 'SSIDManager',
            action: 'deleteSSIDFile',
            file: $scope.selectedFile
        }, function(response) {
	        $scope.getSSIDFilesList();
	        $scope.deletedSSIDFile = true;
	        $timeout(function(){
            	$scope.deletedSSIDFile = false;
            }, 2000);
        });
    });

    $scope.loadSSIDFile = (function() {
        $api.request({
            module: 'SSIDManager',
            action: 'getSSIDFile',
            file: $scope.selectedFile
        }, function(response) {
	        $scope.ssidPool = response.content;
        });
    });

	$scope.getSSIDFilesList = (function() {
        $api.request({
            module: 'SSIDManager',
            action: 'getSSIDFilesList'
        }, function(response) {
            $scope.ssidFilesList = response.filesList;
        });
    });
    
        $scope.downloadSSIDFile = (function() {
        $api.request({
            module: 'SSIDManager',
            action: 'downloadSSIDFile',
            file: $scope.selectedFile
        }, function(response) {
	        debugger;
            if (response.error === undefined) {
                window.location = '/api/?download=' + response.download;
            }
        });
    });
	

	$scope.getSSIDFilesList();
	$scope.getPool();
	
    /* Use the API to send a request to your module.php */
    
    $api.request({
        module: 'SSIDManager', //Your module name
        action: 'getContents'   //Your action defined in module.php
    }, function(response) {
        if (response.success === true) {           //If the response has an index called "success" that returns the boolean "true", then:
	 		$scope.version = response.version;    
        }
    });
}]);