/* Main AngularJS openVPNConnectController  */
registerController('openVPNConnectController', ['$api', '$scope', '$timeout', '$window', '$http', function($api, $scope, $timeout, $window, $http) {
    
    
    // Workspace Variables. Each value is populated by the form or displays to the form.
    $scope.workspace = {config: "", 
                        user: "",
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
    $scope.installLabelText = "Checking...";
    $scope.installSDLabelText = "Checking...";
    $scope.selectedFiles = [];
    $scope.hideSDDependency = false;
    $scope.uploading = false;
    $scope.installButtonWidth = "210px";

    // Call a function to install/uninstall dependencies for the module (install to local storage)
    $scope.handleDependencies = function(){

        $scope.workspace.setupcontent = "Handling dependencies (local storage) please wait...";

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
    
        // Call a function to install/uninstall dependencies for the module (install to the SD card)
        $scope.handleDependenciesSDCard = function(){

            $scope.workspace.setupcontent = "Handling dependencies (SD card) please wait...";

            $api.request({
                module: 'OpenVPNConnect', 
                action: 'handleDependenciesSDCard',
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
                $scope.installSDLabelText = response.textSD;
                $scope.installButtonWidth = response.buttonWidth;
                if(response.installed){
                    $scope.hideSDDependency = true;
                }
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }

    // Call the checkDependencies function on page load
    checkDependencies();

    // Function to check if the VPN is currently running or not when re-visiting the module
    var checkVPNStatus = function() {
        $api.request({
            module: 'OpenVPNConnect', 
            action: 'checkVPNStatus'
        }, function(response) {
            if (response.success === true) {
                $scope.workspace.outputcontent = response.content;
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }
    
    // Call checkVPNStatus function on page load
    checkVPNStatus();

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
                  $scope.workspace.user,
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

    /* File upload function. Instaitates a FileReader object the makes a promise call to 
       doUpload once the async reader call is complete on each iteration
    */
    $scope.uploadFile = function(){

        $scope.uploading = true;

		
		for (x = 0; x < $scope.selectedFiles.length; x++) {

            var fileReader = new FileReader();

            var fileName = $scope.selectedFiles[x].name;

            var filesToUpload = $scope.selectedFiles.length;

            readFile($scope.selectedFiles[x], fileName, filesToUpload);

            }
        };


     // Read file function to handle a Promise for multiple file uploads using FilerReader
     function readFile(file, file_name, files_to_upload){
        return new Promise((resolve, reject) => {
          var fr = new FileReader();  
          fr.onload = () => {
            final_file = fr.result.split(',')[1]
            resolve(doUpload(file_name, final_file, files_to_upload - 1));
          };
          fr.readAsDataURL(file);
        });
      }


      /* Actually performs the upload request to the API. Passes the file name and a base64 encoded
         file to be uploaded by the service
      */
     var doUpload = function(file_name, file, files_to_upload){
        
        $api.request({
            module: 'OpenVPNConnect', 
            action: 'uploadFile',
            file: [file_name,
                   file
                  ]
        }, function(response) {
            
            if(response.success){
                $scope.workspace.uploadstatusLabel = "Upload Success!";
                $scope.workspace.uploadstatus = "success";
            }else{
                $scope.workspace.uploadstatusLabel = "One or more files failed to upload!";
                $scope.workspace.uploadstatus = "danger";
            }
        
            if(files_to_upload === 0){
                $scope.selectedFiles = [];
                $scope.uploading = false;
            }

            //console.log(response) //Log the response to the console, this is useful for debugging.
        });

     };
    
}]);
