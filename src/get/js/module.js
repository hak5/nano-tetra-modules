registerController("getController", ['$api', '$scope', function($api, $scope) {

    getControls();
    getClientProfiles();

    $scope.messages = [];
    $scope.profiles = [];
    $scope.throbber = true;
    $scope.enabled = false;
    $scope.hidden = false;
    $scope.dbonsd = false;
    $scope.comments = "";
    $scope.workshopProfile = {id: "", hostname: "", info: "", mac: "", ip: "", comments: "", date: ""};

    $scope.handleControl = function(control) {
        control.throbber = true;
        switch (control.title) {
            /*
            case "Hidden iFrame":
                $api.request({
                    module: "get",
                    action: "handleIFrame",
                    data: $scope.hidden
                }, function(response) {
                    getControls();
                    control.throbber = false;
                    
                    // based on the response from the module, we need to update the variable in the browser
                    if ( response.hidden_status == "true" )  $scope.hidden = true;
                    if ( response.hidden_status == "false" ) $scope.hidden = false;

                    // write message to messages panel
                    $scope.sendMessage(control.title, response.control_message ); // + " " + response.hidden_status);
                });
                break;
            */
            
            case "Enable Module":
                $api.request({
                    module: "get",
                    action: "handleInfoGetter",
                    data: $scope.enabled
                }, function(response) {
                    getControls();
                    control.throbber = false;
                    
                    // based on the response from the module, we need to update the variable in the browser
                    if ( response.enabled_status == "true" )  $scope.enabled = true;
                    if ( response.enabled_status == "false" ) $scope.enabled = false;
                    
                    // write message to messages panel
                    $scope.sendMessage(control.title, response.control_message ); // + " " + response.enabled_status);
                });
                break;

            case "Database on SD":
                //alert($scope.dbonsd);
                $api.request({
                    module: "get",
                    action: "handleDBLocation",
                    data: $scope.dbonsd
                }, function(response) {
                    getControls();
                    control.throbber = false;
                    
                    // based on the response from the module, we need to update the variable in the browser
                    if ( response.dbonsd_status == "true" )  $scope.dbonsd = true;
                    if ( response.dbonsd_status == "false" ) $scope.dbonsd = false;
                    
                    // write message to messages panel
                    $scope.sendMessage(control.title, response.control_message ); // + " " + response.dbonsd_status);
                });
                break;
        }
    }

    $scope.getComments = function(profile) {
        console.log("Getting comments for: " + profile.mac );
        $scope.workshopProfile = profile;
        
        $api.request({
            module: "get",
            action: "getComments",
            id: profile.id,
            mac: profile.mac
        }, function(response) {
            $scope.sendMessage("Retrieved comments ", response.message);
            console.log( $scope.workshopProfile );
        });
    }

    $scope.saveComments = function(profileid, mac, comments) {
        //console.log("Saving comments for: " + profileid  + " Comments: " + comments);
        $api.request({
            module: "get",
            action: "saveComments",
            id: profileid,
            comments: comments,
            mac: mac
        }, function(response) {
            $scope.sendMessage("Comments Saved ", response.message);
            // we need to refresh the data for all records... This is not a good design, but ok for now..
            getClientProfiles();
        });
    }


    $scope.deleteProfile = function(profile) {
        //console.log( profile.mac );
        $api.request({
            module: "get",
            action: "deleteProfile",
            mac: profile.mac,
            id: profile.id
        }, function(response) {
            $scope.sendMessage("Record Deleted ", response.message);
            getClientProfiles();
        });
    }

    $scope.viewInformation = function(profile) {
        //console.log( profile.mac );
        $api.request({
            module: "get",
            action: "viewInformation",
            mac: profile.mac,
            id: profile.id
        }, function(response) {
            $scope.sendMessage("View information ", response.message);
            $scope.workshopProfile.info = response.info;
        });
    }

    $scope.sendMessage = function(t, m) {
        // Add a new message to the top of the list
        $scope.messages.unshift({title: t, msg: m});

        // if there are 4 items in the list remove the 4th item
        if ($scope.messages.length == 4) {
            $scope.dismissMessage(3);
        }
    }

    $scope.dismissMessage = function($index) {
        //var index = $scope.messages.indexOf(message);
        $scope.messages.splice($index, 1);
    }

    function getControls() {
        $scope.throbber = true;
        $api.request({
            module: "get",
            action: "getControlValues"
        }, function(response) {
            updateControls(response);
        });
    }

    function getClientProfiles() {
        $scope.throbber = true; 
        $api.request({
            module: "get",
            action: "getClientProfiles"
        }, function (response) {
            $scope.profiles = [];
            $scope.throbber = false;
            for (var i = 0; i < response.length; i++) {
                $scope.profiles.unshift({id: response[i].id, mac: response[i].mac, ip: response[i].ip, hostname: response[i].hostname, date: response[i].date, comments: response[i].comments});
                //console.log( {id: response[i].id, mac: response[i].mac, ip: response[i].ip, hostname: response[i].hostname, date: response[i].date, comments: response[i].comments} );
            }
        });
    }

    function updateControls(response) {
        var hidden;
        var enabled;
        var dbonsd;
        
        if (response.hidden == false) {
            hidden = "Install";
            //$scope.sendMessage("iFrame not installed", "The get module requires the hidden frame to be installed");
            $scope.hidden = false;
        } else {
            hidden = "Uninstall";
            $scope.hidden = true;
        }
        
        if (response.enabled == false) {
            enabled = "Enable";
            $scope.enabled = false;
        } else {
            enabled = "Disable";
            $scope.enabled = true;
        }
        
        if (response.dbonsd == false) {
            dbonsd = "Enable";
            //$scope.sendMessage("Database location", "The database will be stored on the SD card");
            $scope.dbonsd = false;
        } else {
            dbonsd = "Disable";
            $scope.dbonsd = true;
        }

        // set parameters that are passed to the html 
        $scope.controls = [
        /*
        {
            title: "Hidden iFrame",
            status: hidden,
            visible: true,
            throbber: false
        },
        */
        {
            title: "Enable Module",
            status: enabled,
            visible: true,
            throbber: false
        },
        {
            title: "Database on SD",
            status: dbonsd,
            visible: true,
            throbber: false
        }];
        $scope.throbber = false;
    }

}]);