registerController("ThemesController", ['$api', '$scope','$window','$route', '$http', function ($api, $scope, $window, $route, $http) {

    /*
     * Author: trashbo4t (github.com/trashbo4t)
     */
    getThemes();
    getCurrentTheme();
    backupFiles();

    $scope.debug                 = false;
    $scope.themes                = [];
    $scope.themeToDelete         = null;
    $scope.themeDeleteValidation = '';
    $scope.messages              = [];
    $scope.newThemeName          = '';
    $scope.throbber              = true;
    $scope.running               = false;
    $scope.current               = '';
    $scope.library               = true;
    $scope.editor                = true;
    $scope.workshopTheme         = {themeName: "", file: "", code: "", title: ""};
    $scope.editThemeFile         = {themeName: "", file: "", code: ""};
    $scope.colors                = ['dark', 'light', 'red', 'blue', 'green', 'purple', 'orange', 'yellow', 'pink'];
    $scope.brightness            = ['light', 'normal', 'dark'];
    $scope.working               = false;
    $scope.autoRefresh           = true;
    // Dark and White
    $scope.throbbercontrast     = true; // true == light -> false == dark
    $scope.logocontrast         = true;
    $scope.faviconcontrast      = true;
    $scope.reconcontrast        = true;
    $scope.logocontrastText     = 'light';
    $scope.faviconcontrastText  = 'light';
    $scope.throbbercontrastText = 'light';
    // Color and brightness
    $scope.allcontrastText                 = 'light';
    $scope.allcontrastBrightness           = 'normal';
    $scope.dashboardcontrastText           = 'light';
    $scope.dashboardcontrastBrightness     = 'normal';
    $scope.reconcontrastText               = 'light';
    $scope.reconcontrastBrightness         = 'normal';
    $scope.notescontrastText               = 'light';
    $scope.notescontrastBrightness         = 'normal';
    $scope.clientscontrastText             = 'light';
    $scope.clientscontrastBrightness       = 'normal';
    $scope.modulescontrastText             = 'light';
    $scope.modulescontrastBrightness       = 'normal';
    $scope.filterscontrastText             = 'light';
    $scope.filterscontrastBrightness       = 'normal';
    $scope.pineapcontrastText              = 'light';
    $scope.pineapcontrastBrightness        = 'normal';
    $scope.trackingcontrastText            = 'light';
    $scope.trackingcontrastBrightness      = 'normal';
    $scope.loggingcontrastText             = 'light';
    $scope.loggingcontrastBrightness       = 'normal';
    $scope.reportingcontrastText           = 'light';
    $scope.reportingcontrastBrightness     = 'normal';
    $scope.networkingcontrastText          = 'light';
    $scope.networkingcontrastBrightness    = 'normal';
    $scope.configurationcontrastText       = 'light';
    $scope.configurationcontrastBrightness = 'normal';
    $scope.advancedcontrastText            = 'light';
    $scope.advancedcontrastBrightness      = 'normal';
    $scope.helpcontrastText                = 'light';
    $scope.helpcontrastBrightness          = 'normal';
    $scope.switchOn = {
      "position" : "relative",
      "display" : "block",
      "width" : "50px",
      "height" : "25px",
      "cursor" : "pointer",
      "border" : "2px solid darkgray",
      "background-color" : "darkgray",
      "border-radius" : "40px"
    }
    $scope.switchOff = {
      "position" : "relative",
      "display" : "block",
      "width" : "50px",
      "height" : "25px",
      "cursor" : "pointer",
      "border" : "2px solid darkgray",
      "background-color" : "white",
      "border-radius" : "40px"
    }
    $scope.selectOptions = {
        "position" : "relative",
        "display" : "block",
        "width" : "100px",
        "height" : "25px",
        "cursor" : "pointer",
        "border" : "2px solid darkgray",
        "background-color" : "white",
        "color" : "black",
        "border-radius" : "40px"
    }
    $scope.changeThrobber = function(){
	    $scope.throbbercontrast = !$scope.throbbercontrast;
	    $scope.throbbercontrastText = 'light';
	    if (!$scope.throbbercontrast) {
    		$scope.throbbercontrastText = 'dark';
	    }
	    $api.request({
       	    module: "Themes",
            action: "replaceImage",
	        img: 'Throbber',
	        light: $scope.throbbercontrast
	    }, function(response) {
            $scope.sendMessage("Throbber", "set to " + $scope.throbbercontrastText);
            log("changeThrobber", response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
	    });
    };
    $scope.changeLogo = function(){
    	$scope.logocontrast = !$scope.logocontrast;
	    $scope.logocontrastText = 'light';
	    if (!$scope.logocontrast) {
	    	$scope.logocontrastText = 'dark';
    	}
    	$api.request({
    	    module: "Themes",
            action: "replaceImage",
	        img: 'Logo',
	        light: $scope.logocontrast
    	}, function(response) {
            $scope.sendMessage("Logo", "set to " + $scope.logocontrastText);
            log("changeLogo", response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
    	});
    };

    $scope.changeFavicon = function(){
    	$scope.faviconcontrast = !$scope.faviconcontrast;
	    $scope.faviconcontrastText = 'light';
	    if (!$scope.faviconcontrast) {
    		$scope.faviconcontrastText = 'dark';
    	}
       	$api.request({
   	        module: "Themes",
            action: "replaceImage",
	        img: 'Icon',
	        light: $scope.faviconcontrast
	    }, function(response) {
            $scope.sendMessage("Icon", "set to " + $scope.faviconcontrastText);
            log("changeFavicon", response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeAllIcons = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'All',
            color: $scope.allcontrastText,
            brightness: $scope.allcontrastBrightness
        }, function(response) {
            for (msg in response) {
                $scope.sendMessage("All Icons", "set to " + $scope.allcontrastText + "(" + $scope.allcontrastBrightness + ")");
                log("changeAllIcons", "Success? " + response.success + " " + response.message);
            }
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeDashboard = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Dashboard',
            color: $scope.dashboardcontrastText,
            brightness: $scope.dashboardcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Dashboard Icon", "set to " + $scope.dashboardcontrastText + " (" + $scope.dashboardcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeRecon = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Recon',
            color: $scope.reconcontrastText,
            brightness: $scope.reconcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Recon Icon", "set to " + $scope.reconcontrastText + " (" + $scope.reconcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeNotes = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Notes',
            color: $scope.notescontrastText,
            brightness: $scope.notescontrastBrightness
        }, function(response) {
            $scope.sendMessage("Notes Icon", "set to " + $scope.notescontrastText + " (" + $scope.notescontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeClients = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Clients',
            color: $scope.clientscontrastText,
            brightness: $scope.clientscontrastBrightness
        }, function(response) {
            $scope.sendMessage("Clients Icon", "set to " + $scope.clientscontrastText + " (" + $scope.clientscontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeModules = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'ModuleManager',
            color: $scope.modulescontrastText,
            brightness: $scope.modulescontrastBrightness
        }, function(response) {
            $scope.sendMessage("ModuleManager Icon", "set to " + $scope.modulescontrastText + " (" + $scope.modulescontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeFilters = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Filters',
            color: $scope.filterscontrastText,
            brightness: $scope.filterscontrastBrightness
        }, function(response) {
            $scope.sendMessage("Filters Icon", "set to " + $scope.filterscontrastText + " (" + $scope.filterscontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changePineap = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'PineAP',
            color: $scope.pineapcontrastText,
            brightness: $scope.pineapcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Filters Icon", "set to " + $scope.pineapcontrastText + " (" + $scope.pineapcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeTracking = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Tracking',
            color: $scope.trackingcontrastText,
            brightness: $scope.trackingcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Tracking Icon", "set to " + $scope.trackingcontrastText + " (" + $scope.trackingcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeLogging = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Logging',
            color: $scope.loggingcontrastText,
            brightness: $scope.loggingcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Logging Icon", "set to " + $scope.loggingcontrastText + " (" + $scope.loggingcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeReporting = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Reporting',
            color: $scope.reportingcontrastText,
            brightness: $scope.reportingcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Reporting Icon", "set to " + $scope.reportingcontrastText + " (" + $scope.reportingcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeNetworking = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Networking',
            color: $scope.networkingcontrastText,
            brightness: $scope.networkingcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Networking Icon", "set to " + $scope.networkingcontrastText + " (" + $scope.networkingcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeConfiguration = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Configuration',
            color: $scope.configurationcontrastText,
            brightness: $scope.configurationcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Configuration Icon", "set to " + $scope.configurationcontrastText + " (" + $scope.configurationcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeAdvanced = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Advanced',
            color: $scope.advancedcontrastText,
            brightness: $scope.advancedcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Advanced Icon", "set to " + $scope.advancedcontrastText + " (" + $scope.advancedcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    $scope.changeHelp = function(){
        $api.request({
            module: "Themes",
            action: "replaceImage",
            img: 'Help',
            color: $scope.helpcontrastText,
            brightness: $scope.helpcontrastBrightness
        }, function(response) {
            $scope.sendMessage("Help Icon", "set to " + $scope.helpcontrastText + " (" + $scope.helpcontrastBrightness + ")");
            log("changeDashboard", "Success? " + response.success + " " + response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
        });
    };
    function log(fn, message) {
    	if ($scope.debug === true) {
  	        console.log("fn[" + fn + "]-> " + message);
	    }
    };
    function backupFiles() {
    	$api.request({
	        module: "Themes",
            action: "backupFiles"
	    }, function(response) {
            log('backupFiles', response.message);
            for (i=0; i<response.modules.length; i++) {
                log('backupFiles', response.modules[i]);            
            }
	    });
    };
    function getCurrentTheme() {
    	$api.request({
	        module: "Themes",
            action: "getCurrentTheme"
	    }, function(response) {
            $scope.current                         = response.current;
            $scope.logocontrastText                = response.logo;
            $scope.faviconcontrastText             = response.icon;
            $scope.throbbercontrastText            = response.throbber;
            $scope.dashboardcontrastText           = response.dashboard;
            $scope.dashboardcontrastBrightness     = response.dashboardbrightness;
            $scope.reconcontrastText               = response.recon;
            $scope.reconcontrastBrightness         = response.reconbrightness;
            $scope.notescontrastText               = response.notes;
            $scope.notescontrastBrightness         = response.notesbrightness;
            $scope.clientscontrastText             = response.clients;
            $scope.clientscontrastBrightness       = response.clientsbrightness;
            $scope.modulescontrastText             = response.modulemanager;
            $scope.modulescontrastBrightness       = response.modulemanagerbrightness;
            $scope.filterscontrastText             = response.filters;
            $scope.filterscontrastBrightness       = response.filtersbrightness;
            $scope.pineapcontrastText              = response.pineap;
            $scope.pineapcontrastBrightness        = response.pineapbrightness;
            $scope.trackingcontrastText            = response.tracking;
            $scope.trackingcontrastBrightness      = response.trackingbrightness;
            $scope.loggingcontrastText             = response.logging;
            $scope.loggingcontrastBrightness       = response.loggingbrightness;
            $scope.reportingcontrastText           = response.reporting;
            $scope.reportingcontrastBrightness     = response.reportingbrightness;
            $scope.networkingcontrastText          = response.networking;
            $scope.networkingcontrastBrightness    = response.networkingbrightness;
            $scope.configurationcontrastText       = response.configuration;
            $scope.configurationcontrastBrightness = response.configurationbrightness;
            $scope.advancedcontrastText            = response.advanced;
            $scope.advancedcontrastBrightness      = response.advancedbrightness;
            $scope.helpcontrastText                = response.help;
            $scope.helpcontrastBrightness          = response.helpbrightness;
            $scope.throbbercontrast      = true;
            $scope.faviconcontrast       = true;
            $scope.logocontrast          = true;
            if (response.throbber === 'dark') {
                $scope.throbbercontrast = false;
            }
            if (response.icon === 'dark') {
                $scope.faviconcontrast = false;
            }
            if (response.logo === 'dark') {
                $scope.logocontrast = false;
            }
            log("getCurrentTheme", "Current theme is " + $scope.current);
            log("getCurrentTheme", "Current throbber is " + response.throbber);
            log("getCurrentTheme", "Current icon is " + response.icon);
            log("getCurrentTheme", "Current logo is " + response.logo);
            log("getCurrentTheme", "Current Dashboard is " + response.dashboard);
	    });
    };
    $scope.file_changed = function(element) {
        $scope.$apply(function(scope) {
            var photofile = element.files[0];
	        var reader = new FileReader();
    	    reader.onload = function(e) {
	            $scope.$apply(function() {
	      	        $scope.prev_img = e.target.result;
	            });
	        };
  	        reader.readAsDataURL(photofile);
	    });
    };
    $scope.restoreDefault = function() {
        $scope.working = "Working..";
	    console.log("Restore Default Function Called");
        $scope.throbber = true;
    	$api.request({
            module: "Themes",
            action: "restoreDefault"
        }, function (response) {
	            log("restoreDefault", "default CSS restored");
                $scope.sendMessage("Restore", "Default CSS restored");
                $scope.throbber = false;
                log("restoreDefault", "Successful? "+ response.success + ". " + response.message);
                $scope.working = "Done!";
                if ($scope.autoRefresh) {
                    $window.location.reload();
                }
            });
    };
    $scope.sendMessage = function (t, m) {
        // Add a new message to the top of the list
        $scope.messages.unshift({title: t, msg: m});
	    log("sendMessage", m);
        // if there are 4 items in the list remove the 4th item
        if ($scope.messages.length == 4) {
            $scope.dismissMessage(3);
        }
    };
    $scope.dismissMessage = function ($index) {
        //var index = $scope.messages.indexOf(message);
        $scope.messages.splice($index, 1);
	    log("dismissMessage", "message at index " + $index + " dismissed" );
    };
    $scope.createNewTheme = function () {
        $api.request({
            module: "Themes",
            action: "createNewTheme",
            themeName: $scope.newThemeName
        }, function (response) {
            if (response.create_success) {
	    	log("createNewTheme", "success! new theme '"+ $scope.newThemeName + "' created");
                getThemes();
                $scope.newThemeName = '';
            } else {
                $scope.sendMessage("Error Creating Theme", response.create_message);
		        log("createNewTheme", "error! " + response.create_message);
            }
        });
    };
    $scope.deleteTheme = function (theme) {
        $api.request({
            module: "Themes",
            action: "deleteTheme",
            name: theme.title
        }, function (response) {
            $scope.sendMessage("Delete", response.message);
            getThemes();
        });
	    log('deleteTheme', 'Deleting Theme: ' + theme.title );
    };
    $scope.activateTheme = function (theme) {
        $api.request({
            module: "Themes",
            action: "activateTheme",
            name: theme.title
        }, function (response) {
	    if (response.return === true) {
	    	$scope.currentTheme = theme.title;
		    getThemes();
            $scope.sendMessage("Activated", response.message);
            if ($scope.autoRefresh) {
                $window.location.reload();
            }
	    }
        });
    };
    $scope.editTheme = function (theme) {
	    console.log("action: edit " + theme.name);
	    $api.request({
            module: "Themes",
            action: "getThemeCode",
            name: theme.name
        }, function (response) {
            $scope.editThemeFile.code = response.code;
            $scope.editThemeFile.file = response.file;
            $scope.editThemeFile.themeName = theme.name;
        });
    };
    $scope.saveThemeCode = function (editFile) {
	    console.log("action: save " + editFile.themeName);
	    $api.request({
            module: "Themes",
            action: "submitThemeCode",
            themeCode: editFile.code,
            name: editFile.themeName,
            fileName: editFile.file
        }, function (response) {
	        $scope.sendMessage("Saved", editFile.themeName);
	    });
	    if ($scope.current === editFile.themeName)
	    {
	        $api.request({
    	        module: "Themes",
	            action: "activateTheme",
	            name: editFile.themeName
	        }, function (response) {
    	        if (response.return === true) {
	    	        $scope.currentTheme = editFile.themeName;
		            $scope.sendMessage("Activated", response.message);
		        }
	        });
	        log('saveThemeCode', "Theme code saved, also current so set to inactive");
	    }
    };
    $scope.getThemeFields = function (theme) {
        $api.request({
            module: "Themes",
            action: "themeFields",
            name: theme.title
        }, function (response) {
            $scope.workshopTheme.themeName = theme.title;
            $scope.workshopTheme.title     = theme.title;
            $scope.workshopTheme.code      = response.code;
	        console.log(response);
	        $scope.library = false;
        });
    };
    function getThemes() {
        $api.request({
            module: "Themes",
            action: "getThemeList"
        }, function (response) {
            $scope.themes = [];
            for (var i = 0; i < response.length; i++) {
                $scope.themes.unshift({
                    title: response[i].title,
                    storage: response[i].location,
                    active:  response[i].active
                });
            }
        });
    }
}]);
