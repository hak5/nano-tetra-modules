/* Main AngularJS openVPNConnectController  */
registerController('openVPNConnectController', ['$api', '$scope', '$timeout', '$window', '$http', function($api, $scope, $timeout, $window, $http) {
    
    
    // Workspace Variables. Each value is populated by the form or displays to the form.
    $scope.workspace = {config: "", 
                        pass: "", 
                        flags: "", 
                        sharedconnection: false, 
                        setupcontent: "", 
                        outputcontent: "", 
                        availablecerts: [],
                        uploadstatusLabel: "",
                        uploadstatus: ""};

    /* Other variables used to display content or assist the dependency installation and file upload
       functions
    */
    $scope.content = "";
    $scope.installLabel = "default";
    $scope.installLabelText = "Checking..."
    $scope.selectedFiles = [];
    $scope.uploading = false;

    // Call a function to install/uninstall dependencies for the module
    $scope.handleDependencies = function(){

        $scope.workspace.setupcontent = "Handling dependencies please wait...";

        $api.request({
            module: 'OpenVPNConnect', 
            action: 'handleDependencies',
        }, function(response) {
            if (response.success === true) {

                $scope.workspace.setupcontent = response.content;

                checkDependencies();

                $timeout(function() {$window.location.reload();}, 5000);
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });

    }
    
    /* Checks the current status of the dependencies for the module and displays 
       it via the dependency install/uninstall button to the user. 
       This is checked each time the app is loaded or when the user 
       installs/uninstalls dependencies manually
    */
    var checkDependencies = function(){
        $api.request({
            module: 'OpenVPNConnect', 
            action: 'checkDependencies',
        }, function(response) {
            if (response.success === true) {
                $scope.installLabel = response.label;
                $scope.installLabelText = response.text;
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }

    // Call the checkDependencies function on page load
    checkDependencies();

    /* Initializes module by creating necessary folder structures and scanning for uploaded certs
       this function is called each time the app is loaded to make sure the module is set up correctly
    */
    var initializeModule = function(){
        $api.request({
            module: 'OpenVPNConnect', 
            action: 'initializeModule',
        }, function(response) {
            if (response.success === true) {
                $scope.workspace.setupcontent = response.content;
                
                for(var i = 0; i <= response.certs.length - 1; i++){
                    $scope.workspace.availablecerts.push(response.certs[i].name);
                }
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }

    // Call the initializeModule function on page load
    initializeModule();

    /* Just calls the initializeModule function to refresh the cert list when the 
        user clicks the drop down menu button
    */
    $scope.refreshCertList = function(){
        $scope.workspace.availablecerts = [];
        initializeModule();
    }

    // Sets the current config to use for the VPN connection
    $scope.setConfig = function(cert){
        $scope.workspace.config = cert;
    }
    

    /* Calls the startVPN function, passes all the form params to the API to run the OpenVPN command
       Users can pass a config, an option password, and optional command line flags to run with the
       openvpn command line utility. Also the shared connection open lets the user share the 
       VPN connection with its clients
    */
    $scope.startVPN = function() {
        $api.request({
            module: 'OpenVPNConnect', 
            action: 'startVPN',
            data: [$scope.workspace.config,
                  $scope.workspace.pass,
                  $scope.workspace.flags,
                  $scope.workspace.sharedconnection]
        }, function(response) {
            if (response.success === true) {
                $scope.workspace.outputcontent = response.content;
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }

    // This function calls the API to kill the openvpn process and stop the VPN
    $scope.stopVPN = function() {
        $api.request({
            module: 'OpenVPNConnect', 
            action: 'stopVPN'
        }, function(response) {
            if (response.success === true) {
                $scope.workspace.outputcontent = response.content;
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }
    
    //File Upload Code, the first two functions prep the files for upload in the modal

    $scope.setSelectedFiles = function(){
		files = document.getElementById("selectedFiles").files;
		for (var x = 0; x < files.length; x++) {
			$scope.selectedFiles.push(files[x]);
		}
    };

    $scope.removeSelectedFile = function(file){
		var x = $scope.selectedFiles.length;
		while (x--) {
			if ($scope.selectedFiles[x] === file) {
				$scope.selectedFiles.splice(x,1);
			}
		}
    };

    // Actual file upload function to upload the .ovpn certs
    $scope.uploadFile = function(){
		$scope.uploading = true;
		
		var fd = new FormData();
		for (x = 0; x < $scope.selectedFiles.length; x++) {
			fd.append($scope.selectedFiles[x].name, $scope.selectedFiles[x]);
		}
		$http.post("/modules/OpenVPNConnect/api/module.php", fd, {
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined}
		}).then(function(response) {
            var failCount = 0;
			for (var key in response) {
				if (response.hasOwnProperty(key)) {
					if (response.key == "Failed") {
                        failCount += 1;
						alert("Failed to upload " + key);
					}
				}
            }
            if(failCount > 0){
                $scope.workspace.uploadstatusLabel = "One or more files failed to upload!";
                $scope.workspace.uploadstatus = "danger";
            }else{
                $scope.workspace.uploadstatusLabel = "Upload Success!";
                $scope.workspace.uploadstatus = "success";
            }
			$scope.selectedFiles = [];
			$scope.uploading = false;
		});
     };
    
}]);