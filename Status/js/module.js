registerController('Status_Controller', ['$api', '$scope', '$rootScope', '$interval', '$timeout', function($api, $scope, $rootScope, $interval, $timeout) {
	$scope.title = "Loading...";
	$scope.version = "Loading...";

	$scope.refreshInfo = (function() {
		$api.request({
			module: 'Status',
			action: "refreshInfo"
		}, function(response) {
			$scope.title = response.title;
			$scope.version = "v" + response.version;
		})
	});

	$scope.refreshInfo();

}]);

registerController('Status_SystemController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		machine: "Loading...",
		currentTime: "Loading...",
		uptime: "Loading...",
		hostname: "Loading..."
	};

	$scope.getInfo = function() {
		$api.request({
			module: 'Status',
			action: 'getSystem'
		}, function(response) {
			$scope.info = response.info;
		});
	};

	$scope.getInfo();

}]);

registerController('Status_CPUController', ['$api', '$scope', '$rootScope', '$filter', '$sce', function($api, $scope, $rootScope, $filter, $sce) {
	$scope.info = {
		cpuModel: "Loading...",
		bogoMIPS: "Loading...",
		type: "Loading...",
		loadAveragePourcentage: "0",
		loadAverageAll: "Loading..."
	};

	$scope.getInfo = function() {
		$api.request({
			module: 'Status',
			action: 'getCPU'
		}, function(response) {
			$scope.info = response.info;
		});
	};

	$scope.getSrc = (function() {
		$scope.src = $sce.trustAsResourceUrl('/modules/Status/svg/graph_cpu.svg');
	});

	$scope.setSrc = (function() {
		$scope.src = $sce.trustAsResourceUrl('about:blank');
	});

	$scope.setSrc();
	$scope.getInfo();

}]);

registerController('Status_DHCPController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		clientsList: []
	};

	$scope.title = "Loading...";
	$scope.output = "Loading...";

	$scope.loading = false;

	$scope.getInfo = function() {
		$scope.loading = true;

		$api.request({
			module: 'Status',
			action: 'getDHCP'
		}, function(response) {
			$scope.info = response.info;
			$scope.loading = false;
		});
	};

	$scope.getMACInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'Status',
			action: 'getMACInfo',
			mac: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.getPingInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'Status',
			action: 'getPingInfo',
			ip: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.getInfo();

}]);

registerController('Status_MemoryController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		memoryTotal: "Loading...",
		memoryFree: "Loading...",
		memoryFreePourcentage: "0",
		memoryUsed: "Loading...",
		memoryUsedPourcentage: "0"
	};

	$scope.getInfo = function() {
		$api.request({
			module: 'Status',
			action: 'getMemory'
		}, function(response) {
			$scope.info = response.info;
		});
	};

	$scope.getInfo();

}]);

registerController('Status_WiFiController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		wifiClientsList: []
	};
	$scope.title = "Loading...";
	$scope.output = "Loading...";
	$scope.loading = false;

	$scope.getInfo = function() {
		$scope.loading = true;

		$api.request({
			module: 'Status',
			action: 'getWiFi'
		}, function(response) {
			$scope.info = response.info;
			$scope.loading = false;
		});
	};

	$scope.getMACInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'Status',
			action: 'getMACInfo',
			mac: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.getPingInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'Status',
			action: 'getPingInfo',
			ip: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.getInfo();

}]);

registerController('Status_SwapController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		swapAvailable: false,
		swapTotal: "Loading...",
		swapFree: "Loading...",
		swapFreePourcentage: "0",
		swapUsed: "Loading...",
		swapUsedPourcentage: "0"
	};

	$scope.getInfo = function() {
		$api.request({
			module: 'Status',
			action: 'getSwap'
		}, function(response) {
			$scope.info = response.info;
		});
	};

	$scope.getInfo();

}]);

registerController('Status_StorageController', ['$api', '$scope', '$rootScope', '$filter', function($api, $scope, $rootScope, $filter) {
	$scope.info = {
		storagesList: []
	};

	$scope.loading = false;

	$scope.getInfo = function() {
		$scope.loading = true;

		$api.request({
			module: 'Status',
			action: 'getStorage'
		}, function(response) {
			$scope.info = response.info;
			$scope.loading = false;
		});
	};

	$scope.getInfo();

}]);

registerController('Status_InterfaceController', ['$api', '$scope', '$rootScope', '$filter', '$sce', function($api, $scope, $rootScope, $filter, $sce) {
	$scope.info = {
		wanIpAddress: "Loading...",
		wanGateway: "Loading...",
		dnsList: [],
		interfacesList: []
	};

	$scope.title = "Loading...";
	$scope.output = "Loading...";

	$scope.loading = false;

	$scope.getMACInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'Status',
			action: 'getMACInfo',
			mac: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.getPingInfo = function(param) {
		$scope.title = "Loading...";
		$scope.output = "Loading...";

		$api.request({
			module: 'Status',
			action: 'getPingInfo',
			ip: param
		}, function(response) {
			$scope.title = response.title;
			$scope.output = response.output;
		});
	};

	$scope.getInfo = function() {
		$scope.loading = true;

		$api.request({
			module: 'Status',
			action: 'getInterfaces'
		}, function(response) {
			$scope.info = response.info;
			$scope.loading = false;
		});
	};

	$scope.getSrc = (function(param) {
		$scope.src = $sce.trustAsResourceUrl('/modules/Status/svg/graph_if.svg?' + param);
		$scope.interfaceName = param;
	});

	$scope.setSrc = (function() {
		$scope.src = $sce.trustAsResourceUrl('about:blank');
	});

	$scope.setSrc();
	$scope.getInfo();

}]);
