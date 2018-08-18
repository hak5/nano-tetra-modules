registerController("CabinetController", ['$api', '$scope', function($api, $scope) {

	$scope.userDirectory = '';
	$scope.currentDirectory = '/';
	$scope.directoryContents = [];
	$scope.editFile = {name: "", path: "", content: ""};
	$scope.deleteFile = {name: "", path: "", directory: false};
	$scope.newFolder = {name: "", path: $scope.currentDirectory};
	$scope.message = {};

	$scope.showMessage = function(msgTitle, msgBody) {
		$scope.message = {title: msgTitle, body: msgBody};
		$('#messageModal').modal("show");
	};

	$scope.submitChangeDirectory = function(directory) {
		console.log(directory);
	};

	$scope.getDirectoryContents = function(dir) {
		$api.request({
			module: "Cabinet",
			action: "getDirectoryContents",
			directory: dir
		}, function(response) {
			if (response.success == true) {
				$scope.currentDirectory = response.directory;
				$scope.directoryContents = [];
				for (var i = 0; i < response.contents.length; i++) {
					$scope.directoryContents.unshift({name: response.contents[i].name,
						directory: response.contents[i].directory, 
						path: response.contents[i].path, 
						permissions: response.contents[i].permissions,
						size: response.contents[i].size
					});
				}
			} else {
				$scope.showMessage("Error Loading Directory", "There was an error loading directory contents. Please verify that the directory you are navigating to exists.");
			}
		});
	};

	$scope.goToParentDirctory = function() {
		$api.request({
			module: "Cabinet",
			action: "getParentDirectory",
			directory: $scope.currentDirectory
		}, function(response) {
			if (response.success == true) {
				parent = response.parent;
				$scope.getDirectoryContents(parent);
			} else {
				$scope.showMessage("Error Finding Parent Directory", "An error occured while trying to find the parent directory. Please verify that the directory you are navigating to exists.");
			}
		});
	};

	$scope.requestDeleteFile = function(file) {
		$scope.deleteFile.name = file.name;
		$scope.deleteFile.path = file.path;
		$scope.deleteFile.directory = file.directory;
		console.log($scope.deleteFile);
	};

	$scope.sendDeleteFile = function() {
		$api.request({
			module: "Cabinet",
			action: "deleteFile",
			file: $scope.deleteFile.path
		}, function(response) {
			if (response.success == true) {
				$scope.deleteFile = {};
				$scope.getDirectoryContents($scope.currentDirectory);
			} else {
				$scope.showMessage("Error Deleting File", "An error occured while trying to delete the file " + $scope.deleteFile.path + ". Please verify that this file exists and you have permission to delete it.");
			}
		});
	};

	$scope.requestEditFile = function(file) {
		$api.request({
			module: "Cabinet",
			action: "getFileContents",
			file: file.path
		}, function(response) {
			if (response.success == true) {
				$scope.editFile = {name: file.name, path: file.path, content: response.content, size: response.size};
			} else {
				$scope.showMessage("Error Loading File Contents", "An error occured while trying to load the file " + file.name + ". Please verify that this file exists and you have permission to edit it.");
			}
		});
	};

	$scope.sendEditFile = function() {
		$api.request({
			module: "Cabinet",
			action: "editFile",
			file: $scope.currentDirectory + "/" + $scope.editFile.name,
			contents: $scope.editFile.content
		}, function(response) {
			if (response.success) {
				$scope.editFile = {};
				$scope.getDirectoryContents($scope.currentDirectory);
			} else {
				$scope.showMessage("Error Saving File", "An error occured while trying to save the file " + $scope.editFile.name + ". Please verify that this file exists and you have permission to edit it.");
			}
		});
	};

	$scope.createFolder = function() {
		$api.request({
			module: "Cabinet",
			action: "createFolder",
			name: $scope.newFolder.name,
			directory: $scope.currentDirectory
		}, function(response) {
			if (response.success == true) {
				$scope.newFolder = {};
				$scope.getDirectoryContents($scope.currentDirectory);
			} else {
				$scope.showMessage("Error Creating Directory", "An error occured while trying to create the folder " + $scope.newFolder.name + ". Please verify that you have permission to create new items in this directory.");
			}
		});
	};

    $scope.download = function(filePath) {
        $api.request({
            module: "Cabinet",
            action: "download",
            filePath: filePath
        }, function (response) {
            if (!response.success) {
                $scope.showMessage("Error", response.message);
                return;
            }
            window.location = "/api/?download=" + response.download;
        })
    };

	$scope.getDirectoryContents($scope.currentDirectory);


}]);