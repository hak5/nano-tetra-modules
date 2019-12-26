registerController("InternetSpeedTestController", ['$api', '$scope','$window','$route', '$http', function ($api, $scope, $window, $route, $http) {

	/*
	 * Author: trashbo4t (github.com/trashbo4t)
	 */
	getPreviousTests();

	$scope.previous = [];
	$scope.previousDisplay = [];
	$scope.throbber = true; 
	$scope.loading = "Running"; 
	$scope.working = "Running speed test..."; 
	$scope.library = true; 
	$scope.currentSpeedTest = false;
	$scope.currentSpeedTestData = {};
	$scope.fileToLookup = "";

	function getPreviousTests() {
		$api.request({
			module: "InternetSpeedTest",
			action: "getPreviousTests"
		}, function (response) {
			console.log("getPreviousTests", response);

			for (var i = 0; i < response.length; i++) 
			{
				var ok = $scope.previous.includes(response[i]) 
				if (!ok)
				{
					var res = response[i].split("/");			
					$scope.previous.push(response[i]);
					var res2 = res[5].split("-speedtest");			
					$scope.previousDisplay.push(res2[0]);
				}
			}

			$scope.previousDisplay.reverse();
		});	
	};

	$scope.clearTests = function () {

		$api.request({
			module: "InternetSpeedTest",
			action: "clearTests",
		}, function (response) {
		});
		
		$window.location.reload()
	};

	$scope.reloadPage = function () {
		$api.request({
			module: "InternetSpeedTest",
			action: "clearLogFile",
		}, function (response) {
		});

		$scope.currentSpeedTest = false;
		$window.location.reload()
	};

	$scope.getSpeedTestFromFile = function (file) {
		file = file.replace(/(\r\n\t|\n|\r\t)/gm,"");
		$scope.fileToLookup = "/pineapple/modules/InternetSpeedTest/tests/"+file+"-speedtest";
			
		console.log("getSpeedTestFromFile", "looking up speed test file " + $scope.fileToLookup);

		$api.request({
			module: "InternetSpeedTest",
			action: "getSpeedTestFromFile",
			file: $scope.fileToLookup
		}, function (response) {
			$scope.currentSpeedTest = $scope.speedTestToLookup;
			
			if (response == false)
			{
				$scope.currentSpeedTest = "Failed";
				$scope.currentSpeedTestData = "Invalid filename..";		
			}
			else 
			{
				$scope.currentSpeedTest = "Success";
				$scope.currentSpeedTestData = response;
			}

			console.log("getSpeedTestFromFile response:", $scope.currentSpeedTestData);
			$scope.library = false;
		});
	};	
	
	$scope.startSpeedTest = function () {
		$scope.loading = "Running Test"; 
		$scope.working = "Your Internet Speed Test is running. Please be patient, this may take a minute to finish depending on your internet speed."; 
		$scope.throbber = true;
			
		console.log("startSpeedTest", "starting test...");

		$api.request({
			module: "InternetSpeedTest",
			action: "startSpeedTest",
		}, function (response) {
			console.log("startSpeedTest", response);

			$scope.currentSpeedTest = "";
			if (response == false)
			{
				$scope.currentSpeedTest = "Failed";
				$scope.currentSpeedTestData = "Test failed. Verify you are connected to the internet.";
			}
			else 
			{
				$scope.currentSpeedTest = "Success";
				$scope.currentSpeedTestData = response;
				$scope.fileToLookup = "Running speed test";
			}

			// fire up the throbber
			$scope.working = "click anywhere to continue"; 
			$scope.loading = "Done"; 
			$scope.library = false;
			$scope.throbber = false;
		});
	
	};	
}]);
