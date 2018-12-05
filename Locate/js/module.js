registerController("LocateController", ['$api', '$scope','$window','$route', '$http', function ($api, $scope, $window, $route, $http) {

	/*
	 * Author: trashbo4t (github.com/trashbo4t)
	 */
	getIPs();

	$scope.ips	 = [];
	$scope.throbber = true; 
	$scope.loading = "Loading"; 
	$scope.working = "working..."; 
	$scope.library = true; 
	$scope.currentIP = false;
	$scope.currentIPData = {};
	$scope.ipToLookup = "";

	function getIPs() {
		$api.request({
			module: "Locate",
			action: "getIPs"
		}, function (response) {
			console.log("getIPs", response);
			for (var i = 0; i < response.length; i++) {
				var ok = $scope.ips.includes(response[i]) 
				if (!ok)
				{
					$scope.ips.push(response[i]);
				}
			}
		});	
	};

	$scope.reloadPage = function () {
		$scope.currentIP = false;
		$window.location.reload()
	};

	$scope.getIPFromFile = function (ip) {
		ip = ip.replace(/(\r\n\t|\n|\r\t)/gm,"");
		$scope.ipToLookup = ip;

		$api.request({
			module: "Locate",
			action: "getIPFromFile",
			ip: $scope.ipToLookup
		}, function (response) {
			console.log("getIP", response);
			$scope.currentIP = $scope.ipToLookup;
			
			if (response == false)
			{
				$scope.currentIPData = "Invalid IP address..";
			}
			else 
			{
				$scope.currentIPData = JSON.parse(response);
			}

			$scope.library = false;
		});
	};	
	
	$scope.lookupIP = function (ip) {
		$scope.loading = "Loading"; 
		$scope.working = "working..."; 
		$scope.throbber = true;

		ip = ip.replace(/(\r\n\t|\n|\r\t)/gm,"");
		$scope.ipToLookup = ip;

		$api.request({
			module: "Locate",
			action: "lookupIP",
			ip: $scope.ipToLookup
		}, function (response) {
			console.log("lookupIP", response);
			$scope.currentIP = $scope.ipToLookup;
			if (response == false)
			{
				$scope.currentIPData = "Invalid IP address...you may wish to verify you are connected to the internet as well";
			}
			else 
			{
				$scope.currentIPData = JSON.parse(response);
			}
			$scope.working = "click anywhere to continue"; 
			$scope.loading = "Done"; 
			$scope.library = false;
			$scope.throbber = false;
		});
	
	};	
}]);
