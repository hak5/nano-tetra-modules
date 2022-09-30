registerController('LogManager_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'LogManager',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('LogManager_FilesController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.files = [];
	$scope.selectedFiles = {};
	$scope.selectedFilesArray = [];
	$scope.selectedAll = false;
	$scope.fileOutput = 'Loading...';
	$scope.fileDate = 'Loading...';
	$scope.fileName = 'Loading...';

	$scope.updateSelectedFiles = (function() {
		$scope.selectedFilesArray = [];
		angular.forEach($scope.selectedFiles, function(key, value) {
			if (key) {
				$scope.selectedFilesArray.push(value);
			}
		});
	});

	$scope.updateAllSelectedFiles = (function() {
		$scope.selectedFilesArray = [];
		if ($scope.selectedAll) {
			angular.forEach($scope.files, function(key, value) {
				$scope.selectedFilesArray.push(key.path);
				$scope.selectedFiles[key.path] = true;
			});
			$scope.selectedAll = true;
		} else {
			$scope.selectedAll = false;
			$scope.selectedFiles = {};
		}
	});

	$scope.refreshFilesList = (function() {
		$api.request({
			module: "LogManager",
			action: "refreshFilesList"
		}, function(response) {
			$scope.files = response.files;
		})
	});

	$scope.downloadFilesList = (function() {
		$api.request({
			module: "LogManager",
			action: "downloadFilesList",
			files: $scope.selectedFilesArray
		}, function(response) {
			if (response.error === undefined) {
				window.location = '/api/?download=' + response.download;
			}
		})
	});

	$scope.deleteFilesList = (function() {
		$api.request({
			module: "LogManager",
			action: "deleteFilesList",
			files: $scope.selectedFilesArray
		}, function(response) {
			$scope.refreshFilesList();
			$scope.selectedFiles = {};
			$scope.updateSelectedFiles();
		})
	});

	$scope.viewFile = (function(param) {
		$api.request({
			module: "LogManager",
			action: "viewModuleFile",
			file: param
		}, function(response) {
			$scope.fileOutput = response.output;
			$scope.fileDate = response.date;
			$scope.fileName = response.name;
		})
	});

	$scope.deleteFile = (function(param) {
		$api.request({
			module: "LogManager",
			action: "deleteModuleFile",
			file: param
		}, function(response) {
			$scope.refreshFilesList();
		})
	});

	$scope.downloadFile = (function(param) {
		$api.request({
			module: 'LogManager',
			action: 'downloadModuleFile',
			file: param
		}, function(response) {
			if (response.error === undefined) {
				window.location = '/api/?download=' + response.download;
			}
		});
	});

	$scope.refreshFilesList();

}]);
