registerController("EvilPortalController", ['$api', '$scope', function ($api, $scope) {

    getControls();
    getPortals();

    $scope.portals = [];
    $scope.portalToDelete = null;
    $scope.portalDeleteValidation = '';
    $scope.messages = [];
    $scope.newPortalName = '';
    $scope.throbber = true;
    $scope.running = false;
    $scope.library = true;
    $scope.whiteList = '';
    $scope.whiteListInput = '';
    $scope.accessList = '';
    $scope.accessListInput = '';
    $scope.workshopPortal = {name: "", files: [], storage: "internal"};
    $scope.editPortalFile = {portalName: "", storage: "", file: "", code: ""};

    $scope.handleControl = function (control) {
        control.throbber = true;
        switch (control.title) {

            case "CaptivePortal":
                $api.request({
                    module: "EvilPortal",
                    action: "startStop"
                }, function (response) {
                    getControls();
                    control.throbber = false;
                    if (!response.control_success) {
                        $scope.sendMessage(control.title, response.control_message);
                    }
                    $scope.refreshLivePreview()
                });
                break;

            case "Auto Start":
                $api.request({
                    module: "EvilPortal",
                    action: "enableDisable"
                }, function (response) {
                    getControls();
                    control.throbber = false;
                    if (!response.control_success) {
                        $scope.sendMessage(control.title, response.control_message);
                    }
                });
                break;
        }
    };

    $scope.sendMessage = function (t, m) {
        // Add a new message to the top of the list
        $scope.messages.unshift({title: t, msg: m});

        // if there are 4 items in the list remove the 4th item
        if ($scope.messages.length == 4) {
            $scope.dismissMessage(3);
        }
    };

    $scope.dismissMessage = function ($index) {
        //var index = $scope.messages.indexOf(message);
        $scope.messages.splice($index, 1);
    };

    function getControls() {
        $scope.throbber = true;
        $api.request({
            module: "EvilPortal",
            action: "getControlValues"
        }, function (response) {
            updateControls(response);
        });
    }

    function updateControls(response) {
        var running;
        var autostart;
        if (response.running == false) {
            running = "Start";
            $scope.running = false;
        } else {
            running = "Stop";
            $scope.running = true;
        }
        if (response.autostart == false) {
            autostart = "Enable";
        } else {
            autostart = "Disable";
        }
        $scope.controls = [
            {
                title: "CaptivePortal",
                status: running,
                visible: true,
                throbber: false
            },
             {
             title: "Auto Start",
             status: autostart,
             visible: true,
             throbber: false
             }];
        $scope.throbber = false;
    }

    $scope.createNewPortal = function () {
        $api.request({
            module: "EvilPortal",
            action: "createNewPortal",
            portalName: $scope.newPortalName
        }, function (response) {
            if (response.create_success) {
                getPortals();
                $scope.newPortalName = '';
            } else {
                $scope.sendMessage("Error Creating Portal", response.create_message);
            }
        });
    };

    $scope.deletePortalRequest = function(portal) {
        $scope.portalToDelete = portal;
        console.log(portal);
    };

    $scope.deletePortal = function (portal) {
        console.log(portal.storage);
        console.log(portal.title);
        $scope.portalToDelete = null;
        $scope.portalDeleteValidation = null
        $api.request({
            module: "EvilPortal",
            action: "deletePortal",
            storage: portal.storage,
            name: portal.title
        }, function (response) {
            $scope.sendMessage("Delete Portal", response.message);
            getPortals();
        });
    };

    $scope.activatePortal = function (portal) {
        $api.request({
            module: "EvilPortal",
            action: "activatePortal",
            storage: portal.storage,
            name: portal.title
        }, function (response) {
            //$scope.sendMessage("Activate Portal", response.message);
            getPortals();
        });
    };

    $scope.deactivatePortal = function (portal) {
        $api.request({
            module: "EvilPortal",
            action: "deactivatePortal",
            storage: portal.storage,
            name: portal.title
        }, function (response) {
            //$scope.sendMessage("Deactivate Portal", response.message);
            getPortals();
        });
    };

    $scope.editPortal = function (portal, file) {
        $api.request({
            module: "EvilPortal",
            action: "getPortalCode",
            storage: portal.storage,
            name: portal.name,
            portalFile: file
        }, function (response) {
            //$scope.sendMessage("Edit Portal", response.message);
            $scope.editPortalFile.code = response.code;
            $scope.editPortalFile.file = file;
            $scope.editPortalFile.portalName = portal.name;
            $scope.editPortalFile.storage = portal.storage;
        });
    };

    $scope.savePortalCode = function (editFile) {
        $api.request({
            module: "EvilPortal",
            action: "submitPortalCode",
            storage: editFile.storage,
            portalCode: editFile.code,
            name: editFile.portalName,
            fileName: editFile.file
        }, function (response) {
            $scope.sendMessage("Edit File", response.message);
        });
    };

    $scope.getPortalFiles = function (portal) {
        $api.request({
            module: "EvilPortal",
            action: "portalFiles",
            storage: portal.storage,
            name: portal.title
        }, function (response) {
            $scope.workshopPortal.name = portal.title;
            $scope.workshopPortal.storage = portal.storage;
            $scope.workshopPortal.files = response.portalFiles;
            $scope.library = false;
        });
    };

    function getPortals() {
        $api.request({
            module: "EvilPortal",
            action: "portalList"
        }, function (response) {
            $scope.portals = [];
            for (var i = 0; i < response.length; i++) {
                $scope.portals.unshift({
                    title: response[i].title,
                    storage: response[i].location,
                    active: response[i].active
                });
                //console.log({title: response[i].title, storage: response[i].location, active: response[i].active});
            }
        });
    }

    $scope.refreshLivePreview = function () {
        window.frames['livePreviewIframe'].src = "http://172.16.42.1";
    };

    $scope.getList = function (listToGet) {
        $api.request({
            module: "EvilPortal",
            action: "getList",
            listName: listToGet
        }, function (response) {
            if (response.list_success) {
                if (listToGet == "whiteList") {
                    $scope.whiteList = response.list_contents;
                } else if (listToGet == "accessList") {
                    $scope.accessList = response.list_contents;
                }
            } else {
                $scope.sendMessage("List Data Error", response.list_message);
                console.log(response);
            }
        });
    };

    $scope.addWhiteListClient = function () {
        $api.request({
            module: "EvilPortal",
            action: "addToList",
            listName: "whiteList",
            clientIP: $scope.whiteListInput
        }, function (response) {
            if (response.add_success) {
                $scope.whiteListInput = '';
                $scope.getList("whiteList");
            } else {
                $scope.sendMessage("White List", response.add_message);
                console.log(response);
            }
        });
    };

    $scope.removeWhiteListClient = function () {
        $api.request({
            module: "EvilPortal",
            action: "removeFromList",
            listName: "whiteList",
            clientIP: $scope.whiteListInput
        }, function (response) {
            if (response.remove_success) {
                $scope.whiteListInput = '';
                $scope.getList("whiteList");
            } else {
                $scope.sendMessage("White List", response.remove_message);
                console.log(response);
            }
        });
    };

    $scope.authorizeClient = function () {
        $api.request({
            module: "EvilPortal",
            action: "addToList",
            listName: "accessList",
            clientIP: $scope.accessListInput
        }, function (response) {
            if (response.add_success) {
                $scope.accessListInput = '';
                $scope.getList("accessList");
            } else {
                $scope.sendMessage("Access List", response.add_message);
                console.log(response);
            }
        });
    };

    $scope.revokeClient = function () {
        $api.request({
            module: "EvilPortal",
            action: "removeFromList",
            listName: "accessList",
            clientIP: $scope.accessListInput
        }, function (response) {
            if (response.remove_success) {
                $scope.accessListInput = '';
                $scope.getList("accessList");
            } else {
                $scope.sendMessage("Access List", response.remove_message);
                console.log(response);
            }
        });
    }


}]);