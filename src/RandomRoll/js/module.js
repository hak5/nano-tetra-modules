registerController("RandomRollController", ['$api', '$scope', '$timeout', function($api, $scope, $timeout) {
	$scope.randomRolls = [];
	$scope.running = false;
	$scope.randomRollStarted = false;
	$scope.randomRollStopped = false;

	$scope.randomRollStart = (function(){
		$api.request({
			module: "RandomRoll",
			action: "startRandomRoll",
			selected: $scope.randomRolls
		}, function(response) {
			if (response.error === undefined){
				$scope.running = true;
			}
			if (response.success === true) {
				$scope.randomRollStarted = true;
				$timeout(function(){
					$scope.randomRollStarted = false;
				}, 2000);
			}
		});
	});

	$scope.randomRollStop = (function(){
		$api.request({
			module: "RandomRoll",
			action: "stopRandomRoll"
		}, function(response) {
			if (response.error === undefined){
				$scope.running = false;
			}
			if (response.success === true) {
				$scope.randomRollStopped = true;
				$timeout(function(){
					$scope.randomRollStopped = false;
				}, 2000);
			}
		});
	});

	$api.request({
		module: "RandomRoll",
		action: "checkStatus"
	}, function(response) {
		if (response.running === true){
			$scope.running = true;
		} else {
			$scope.running = false;
		};
	});

	$api.request({
		module: "RandomRoll",
		action: "getRandomRollRolls"
	}, function(response) {
		$scope.randomRolls = response;
		console.log(response);
	});
}])

registerController("RandomRollLogs", ['$api', '$scope', function($api, $scope) {
	$scope.randomRollLogOutput = ""

	$scope.getRandomRollLogs = (function(){
		$api.request({
			module: "RandomRoll",
			action: "getRandomRollLogs"
		}, function(response) {
			$scope.randomRollLogOutput = response.randomRollLogOutput;
		});
	});

	$scope.clearRandomRollLogs = (function(){
		$api.request({
			module: "RandomRoll",
			action: "clearRandomRollLogs"
		}, function(response) {
			$scope.getRandomRollLogs();
		});
	});

	$scope.getRandomRollLogs();
}])