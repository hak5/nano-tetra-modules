registerController("EvilPortalController", ['$api', '$scope', function ($api, $scope) {

    // status information about the module
    $scope.evilPortal = {
        "throbber": false,
        "sdAvailable": false,
        "running": false,
        "startOnBoot": false,
        "library": true
    };

    // controls that belong in the Controls pane
    $scope.controls = [
        { "title": "Captive Portal", "visible": true, "throbber": false, "status": "Start"},
        {"title": "Start On Boot", "visible": true, "throbber": false, "status": "Enable"}
    ];

    // messages to be displayed in the Messages pane
    $scope.messages = [];

    $scope.whiteList = {"clients": "", "toManipulate": null};

    $scope.accessList = {"clients": "", "toManipulate": null};

    // all of the portals that could be found
    $scope.portals = [];

    // a model of a new portal to create
    $scope.newPortal = {"type": "basic", "name": ""};

    // deleting portal stuff
    $scope.portalToDelete = null;
    $scope.portalDeleteValidation = null;

    // the portal workshop
    $scope.workshop = {"portal": {}, "dirContents": null, "inRoot": true, "rootDirectory": null, "editFile": {"path": null, "isNewFile": true},
        "onEnable": null, "onDisable": null, "concreteTargetedRules": null, "workingTargetedRules": null, "deleteFile": null
    };

    /**
     * Reset the workshop object to a blank slate with initial values.
     */
    $scope.resetWorkshop = function () {
        $scope.workshop = {"portal": {}, "dirContents": null, "inRoot": true, "rootDirectory": null, "editFile": {"path": null, "isNewFile": true},
            "onEnable": null, "onDisable": null, "concreteTargetedRules": null, "workingTargetedRules": null, "deleteFile": null
        };
    };

    /**
     * Push a message to the Evil Portal Messages Pane
     * @param t: The Title of the message
     * @param m: The message body
     */
    $scope.sendMessage = function (t, m) {
        // Add a new message to the top of the list
        $scope.messages.unshift({title: t, msg: m});

        // if there are 4 items in the list remove the 4th item
        if ($scope.messages.length === 4) {
            $scope.dismissMessage(3);
        }
    };

    /**
     * Remove a message from the Evil Portal Messages pane
     * @param $index: The index of the message in the list to remove
     */
    $scope.dismissMessage = function ($index) {
        $scope.messages.splice($index, 1);
    };

    /**
     * Preform an action for a given control
     * This can be starting the captive portal or toggle on boot.
     * @param control: The control to handle
     */
    $scope.handleControl = function(control) {
        control.throbber = true;
        var actionToPreform = null;
        switch(control.title) {
            case "Captive Portal":
                actionToPreform = "toggleCaptivePortal";
                break;

            case "Start On Boot":
                actionToPreform = "toggleOnBoot";
                break;
        }

        if (actionToPreform !== null) {
            $api.request({
                module: "EvilPortal",
                action: actionToPreform
            }, function(response) {
                if (!response.success) {
                    $scope.sendMessage(control.title, response.message);
                }
                getStatus();
            });
        }
    };

    /**
     * Validates the information in the newPortal model and then makes an API request to create a new portal.
     * @param storage: The storage medium to create the portal on (internal or sd)
     */
    $scope.createNewPortal = function(storage) {
        $api.request({
            module: "EvilPortal",
            action: "createNewPortal",
            name: $scope.newPortal.name,
            type: $scope.newPortal.type,
            storage: storage
        }, function(response) {
            if (!response.success) {
                $scope.sendMessage('Error Creating Portal', response.message);
                return;
            }
            $scope.newPortal = {"type": "basic", "name": ""};
            getPortals();
        });
    };

    /**
     * Move a given portal between storage mediums if an SD card is present.
     * @param portal: The portal to move
     */
    $scope.movePortal = function(portal) {
        if (!$scope.evilPortal.sdAvailable) {
            $scope.sendMessage("No SD Card.", "An SD card must be present to preform this action.");
            return;
        }

        $api.request({
            module: "EvilPortal",
            action: "movePortal",
            name: portal.title,
            storage: portal.storage
        }, function(response) {
            if (response.success) {
                getPortals();
                $scope.sendMessage("Moved Portal", response.message);
            } else {
                $scope.sendMessage("Error Moving " + portal.title, response.message);
            }
        });
    };

    /**
     * Delete a portal from the wifi pineapple
     * @param verified: Has the delete request been verified? If so then make the API request otherwise setup
     * @param portal: The portal to delete
     */
    $scope.deletePortal = function(verified, portal) {
        if (!verified) {  // if the request has not been verified then setup the shits
            $scope.portalToDelete = portal;
            return;
        }

        if ($scope.portalToDelete === null || $scope.portalToDelete.fullPath === null) {
            $scope.sendMessage("Unable To Delete Portal", "No portal was set for deletion.");
            return;
        }
        deleteFileOrDirectory($scope.portalToDelete.fullPath, function (response) {
            if (!response.success) {
                $scope.sendMessage("Error Deleting Portal", response.message);  // push an error if deletion failed
            } else {
                $scope.sendMessage("Deleted Portal", "Successfully deleted " + $scope.portalToDelete.title + ".");
                $scope.portalToDelete = null;
                $scope.portalDeleteValidation = null;
                getPortals();  // refresh the library
            }
        });
    };

    /**
     * Activate a portal
     * @param portal: The portal to activate
     */
    $scope.activatePortal = function(portal) {
        $api.request({
            module: "EvilPortal",
            action: "activatePortal",
            name: portal.title,
            storage: portal.storage
        }, function(response) {
            console.log(response);
            if (response.success) {
                getPortals();
                $scope.sendMessage("Activated Portal", portal.title + " has been activated successfully.");
            } else {
                $scope.sendMessage("Error Activating " + portal.title, response.message);
            }
        });
    };

    /**
     * Deactivate a given portal if its active
     * @param portal: The portal to deactivate
     */
    $scope.deactivatePortal = function(portal) {
        $api.request({
            module: "EvilPortal",
            action: "deactivatePortal",
            name: portal.title,
            storage: portal.storage
        }, function(response) {
            console.log(response);
            if (response.success) {
                getPortals();
                $scope.sendMessage("Deactivated Portal", portal.title + " has been deactivated successfully.");
            } else {
                $scope.sendMessage("Error Deactivating " + portal.title, response.message);
            }
        });
    };

    /**
     * Load portal contents and open it up in the work bench
     * @param portal: The portal to get the contents of
     */
    $scope.loadPortal = function (portal) {
        getFileOrDirectoryContent(portal.fullPath, function(response) {
            if (!response.success) {
                $scope.sendMessage("Error Getting Contents", response.message);
                return;
            }
            $scope.workshop.inRoot = true;
            $scope.workshop.portal = portal;
            $scope.workshop.dirContents = response.content;
            $scope.workshop.rootDirectory = portal.fullPath;
            $scope.evilPortal.library = false;
            console.log(response.content);
        });
    };

    /**
     * Load toggle commands for the current portal in the work bench.
     * These are the commands that are executed when a portal is enabled/disabled.
     */
    $scope.loadToggleCommands = function() {
        [".enable", ".disable"].forEach(getScript);
        function getScript(scriptName) {
            getFileOrDirectoryContent($scope.workshop.rootDirectory + "/" + scriptName, function (response) {
                if (!response.success) {
                    $scope.sendMessage("Error Getting Contents", response.message);
                    return;
                }
                if (scriptName === ".enable")
                    $scope.workshop.onEnable = response.content.fileContent;
                else
                    $scope.workshop.onDisable = response.content.fileContent;
            });
        }
    };

    /**
     * Save toggle commands.
     * @param cmdFile: The commands to save (enable, disable)
     */
    $scope.saveToggleCommands = function(cmdFile) {
        function sendData(f, content) {
            writeToFile($scope.workshop.rootDirectory + "/" + f, content, false, function (response) {
                if (!response.success)
                    $scope.sendMessage("Error write to file " + f, response.message);
            });
        }

        switch(cmdFile) {
            case "disable":
                sendData(".disable", $scope.workshop.onDisable);
                break;

            case "enable":
                sendData(".enable", $scope.workshop.onEnable);
                break;

            default:
                sendData(".enable", $scope.workshop.onEnable);
                sendData(".disable", $scope.workshop.onDisable);
                break;
        }

    };

    /**
     * Load the rules for targeted portals
     */
    $scope.loadTargetedRules = function() {
        $api.request({
            module: "EvilPortal",
            action: "getRules",
            name: $scope.workshop.portal.title,
            storage: $scope.workshop.portal.storage
        }, function(response) {
            if (response.success) {
                $scope.workshop.concreteTargetedRules = response.data;
                $scope.workshop.workingTargetedRules = {"rules": {}};

                // welcome to the realm of loops. I will be your guide
                // We have to turn each rule into a keyed set of rules with a rule index represented by var index
                // this is because we need a constant key for each rule when editing on the web interface
                // the index must be removed later before saving the results to the routes.json file
                // if you have a better way to do this you are my hero. Email me n3rdcav3@gmail.com or fork the repo :)

                // This first loop loops over each rule categories such as "mac", "ssid" and so on
                for (var key in response.data['rules']) {

                    // we then create the a object with that key name in our workingData object
                    $scope.workshop.workingTargetedRules['rules'][key] = {};

                    // Now its time to loop over each category specifier such as "exact" and "regex"
                    for (var specifier in response.data['rules'][key]) {
                        var index = 0;

                        // We then create that specifier in our workingData
                        $scope.workshop.workingTargetedRules['rules'][key][specifier] = {};

                        // finally we loop over the specific rules defined in the specifier
                        for (var r in response.data['rules'][key][specifier]) {
                            var obj = {};
                            obj['key'] = r;
                            obj['destination'] = response.data['rules'][key][specifier][r];
                            $scope.workshop.workingTargetedRules['rules'][key][specifier][index] = obj;
                        }
                        // increment index
                        index++;
                    }
                }
            } else {
                $scope.sendMessage("Error", "There was an issue getting the portal rules.");
            }
        });
    };

    /**
     * Remove a targeted rule
     * @param rule
     * @param specifier
     * @param index
     */
    $scope.removeTargetedRule = function(rule, specifier, index) {
        delete $scope.workshop.workingTargetedRules['rules'][rule][specifier][index];
    };

    /**
     * Create a new targeted rule
     * @param rule
     * @param specifier
     */
    $scope.newTargetedRule = function(rule, specifier) {
        // make sure the specifier is set
        if ($scope.workshop.workingTargetedRules['rules'][rule][specifier] == undefined) {
            $scope.workshop.workingTargetedRules['rules'][rule][specifier] = {};
        }

        var highest = 0;

        // get the highest index
        for (var i in $scope.workshop.workingTargetedRules['rules'][rule][specifier]) {
            if (parseInt(i) >= highest) {
                highest = i + 1;
            }
        }

        $scope.workshop.workingTargetedRules['rules'][rule][specifier][highest] = {"": ""};
    };

    /**
     * Build the targeted rules and sned them to the API for saving
     */
    $scope.saveTargetedRules = function() {
        // build the rules
        for (var key in $scope.workshop.concreteTargetedRules.rules) {
            for (var specifier in $scope.workshop.concreteTargetedRules.rules[key]) {
                var obj = {};
                for (var i in $scope.workshop.workingTargetedRules['rules'][key][specifier]) {
                    obj[$scope.workshop.workingTargetedRules['rules'][key][specifier][i]['key']] = $scope.workshop.workingTargetedRules['rules'][key][specifier][i]['destination'];
                }
                $scope.workshop.concreteTargetedRules['rules'][key][specifier] = obj;
            }
        }

        console.log(JSON.stringify($scope.workshop.concreteTargetedRules));

        $api.request({
            module: "EvilPortal",
            action: "saveRules",
            name: $scope.workshop.portal.title,
            storage: $scope.workshop.portal.storage,
            rules: JSON.stringify($scope.workshop.concreteTargetedRules)
        }, function(response) {
            if (!response.success) {
                $scope.sendMessage("Error", response.message);
            }
        });
    };

    /**
     * check if a given object is empty.
     * @param obj: The object to check
     * @returns {boolean}: true if empty false if not empty
     */
    $scope.isObjectEmpty = function(obj) {
        return (Object.keys(obj).length === 0);
    };

    /**
     * Load the contents of a given file.
     * @param filePath: The path to the file to load
     */
    $scope.loadFileContent = function(filePath) {
        getFileOrDirectoryContent(filePath, function(response) {
            if (!response.success) {
                $scope.sendMessage("Error Getting Contents", response.message);
                return;
            }
            $scope.workshop.editFile = {
                "name": response.content.name,
                "path": response.content.path,
                "size": response.content.size,
                "content": response.content.fileContent
            };
        });
    };

    /**
     * Setup the workshop to create a new empty file.
     */
    $scope.setupNewFile = function() {
        var basePath = ($scope.workshop.portal.storage === "sd") ? "/sd/portals/" : "/root/portals/";
        $scope.workshop.editFile.path = basePath + $scope.workshop.portal.title + "/";
        $scope.workshop.editFile.isNewFile = true;
    };

    /**
     * Write file content to the file system.
     * @param editFile: A portal.editFile object
     */
    $scope.saveFileContent = function(editFile) {
        // new files wont have the filename in the path so make sure to set it here if needed.
        if (!editFile.path.includes(editFile.name))
            editFile.path = editFile.path + editFile.name;

        console.log(editFile.path);
        writeToFile(editFile.path, editFile.content, false, function(response) {
            if (!response.success)
                $scope.sendMessage("Error write to file " + editFile.name, response.message);

            $scope.loadPortal($scope.workshop.portal);  // refresh the portal
        });
    };

    /**
     * Delete a requested file.
     */
    $scope.deleteFile = function() {
        deleteFileOrDirectory($scope.workshop.deleteFile.path, function(response){
            if (!response.success)
                $scope.sendMessage("Error deleting file " + $scope.workshop.deleteFile.name, response.message);

            $scope.loadPortal($scope.workshop.portal);  // refresh the portal
        });
    };

    /**
     * Load either the white list or the authorized clients (access) list
     * @param listName: The name of the list: whiteList or accessList (authorized clients)
     */
    $scope.getList = function (listName) {
        var whiteList = '/pineapple/modules/EvilPortal/data/allowed.txt';
        var authorized = '/tmp/EVILPORTAL_CLIENTS.txt';

        getFileOrDirectoryContent((listName === "whiteList") ? whiteList : authorized, function (response) {
            switch (listName) {
                case 'whiteList':
                    $scope.whiteList.clients = response.content.fileContent;
                    break;
                case 'accessList':
                    $scope.accessList.clients = response.content.fileContent;
                    break;
            }
        })
    };

    /**
     * Remove a client from either the white list (whiteList) the authorized clients list (accessList)
     * @param listName: whiteList or accessList
     */
    $scope.removeClientFromList = function(listName) {
        var clientToRemove = (listName === 'whiteList') ? $scope.whiteList.toManipulate : $scope.accessList.toManipulate;
        console.log(clientToRemove);
        $api.request({
            module: "EvilPortal",
            action: "removeClientFromList",
            clientIP: clientToRemove,
            listName: listName
        }, function(response) {
            if (!response.success) {
                $scope.sendMessage("Error", response.message);
                return;
            }
            $scope.getList(listName);
            switch (listName) {
                case 'whiteList':
                    $scope.whiteList.toManipulate = null;
                    break;
                case 'accessList':
                    $scope.accessList.toManipulate = null;
                    break;
            }
        });
    };

    /**
     * Add a new client to the white list
     */
    $scope.addWhiteListClient = function() {
        writeToFile('/pineapple/modules/EvilPortal/data/allowed.txt', $scope.whiteList.toManipulate + "\n", true, function(response) {
            $scope.getList('whiteList');
        });
        $scope.whiteList.toManipulate = null;
    };

    /**
     * Authorize a new client
     */
    $scope.authorizeClient = function () {
        $api.request({
            module: "EvilPortal",
            action: "authorizeClient",
            clientIP: $scope.accessList.toManipulate
        }, function (response) {
            $scope.getList('accessList');
            $scope.accessList.toManipulate = null;
        });
    };

    /**
     * Get a line clicked in a text area and set that line as the text for a text input
     * @param textareaId: The id of the text area to grab from
     * @param inputname: The name of the input field to write to
     */
    $scope.getClickedClient = function(textareaId, inputname) {
        var textarea = $('#' + textareaId);
        var lineNumber = textarea.val().substr(0, textarea[0].selectionStart).split('\n').length;
        var ssid = textarea.val().split('\n')[lineNumber-1].trim();
        $("input[name='" + inputname + "']").val(ssid).trigger('input');
    };

    /**
     * Write given content to a given file on the file system.
     * @param filePath: The path to the file to write content to
     * @param fileContent: The content to write to the file
     * @param appendFile: Should the content be append to the file (true) or overwrite the file (false)
     * @param callback: A callback function to handle the API response
     */
    function writeToFile(filePath, fileContent, appendFile, callback) {
        $api.request({
            module: "EvilPortal",
            action: "writeFileContent",
            filePath: filePath,
            content: fileContent,
            append: appendFile
        }, function(response) {
            callback(response);
        });
    }

    /**
     * Get the contents of a directory
     * @param pathToObject: The full path to the file or directory to get the contents of
     * @param callback: A function that handles the response from the API.
     */
    function getFileOrDirectoryContent(pathToObject, callback) {
        $api.request({
            module: "EvilPortal",
            action: "getFileContent",
            filePath: pathToObject
        }, function(response) {
            callback(response);
        });
    }

    /**
     * Delete a file or directory from the pineapples filesystem.
     * This is intended to be used for only deleting portals and portal related files but anything can be delete.
     * @param fileOrDirectory: The path to the file to delete
     * @param callback: The callback function to handle the API response
     */
    function deleteFileOrDirectory(fileOrDirectory, callback) {
        $api.request({
            module: "EvilPortal",
            action: "deleteFile",
            filePath: fileOrDirectory
        }, function(response) {
            callback(response);
        });
    }

    /**
     * Update the control models so they reflect the proper information
     */
    function updateControls() {
        $scope.controls = [
            {
                "title": "Captive Portal",
                "status": ($scope.evilPortal.running) ? "Stop" : "Start",
                "visible": true,
                "throbber": false
            },
            {
                "title": "Start On Boot",
                "status": ($scope.evilPortal.startOnBoot) ? "Disable": "Enable",
                "visible": true,
                "throbber": false
            }
        ];
    }

    /**
     * Get the status's for the controls in the Controls pane and other various information
     */
    function getStatus() {
        $scope.evilPortal.throbber = true;
        $api.request({
            module: "EvilPortal",
            action: "status"
        }, function (response) {
            for (var key in response) {
                if (response.hasOwnProperty(key) && $scope.evilPortal.hasOwnProperty(key)) {
                    $scope.evilPortal[key] = response[key];
                }
            }
            $scope.evilPortal.throbber = false;
            updateControls();
        });
    }

    /**
     * Get all of the portals on the Pineapple
     */
    function getPortals() {
        $scope.evilPortal.throbber = true;
        $api.request({
            module: "EvilPortal",
            action: "listAvailablePortals"
        }, function(response) {
            if (!response.success) {
                $scope.sendMessage("Error Listing Portals", "An error occurred while trying to get list of portals.");
                return;
            }
            $scope.portals = [];
            response.portals.forEach(function(item, index) {
                $scope.portals.unshift({
                    title: item.title,
                    storage: item.storage,
                    active: item.active,
                    type: item.portalType,
                    fullPath: item.location
                });
            });
        });
    }

    // The status for the Evil Portal module as well as current portals should be retrieved when the controller loads.
    getStatus();
    getPortals();


}]);