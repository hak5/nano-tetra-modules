/* This is our AngularJS controller, called "MACInfo". */
registerController('MACInfo', ['$api', '$scope', function($api, $scope) {
    $scope.company = "";
    $scope.macprefix = "";
    $scope.address = "";
    $scope.country = "";
    $scope.type = "";
    $scope.error = "";
    $scope.isLookupSuccess = false;
    $scope.isLookupFailure = false;
    $scope.moduleMAC = "";
    $scope.getMACInfo = (function() {
        $api.request({
            module:"MACInfo",
            action:"getMACInfo",
            moduleMAC: $scope.moduleMAC
        }, function(response){
            if(response.success == true){
                $scope.isLookupSuccess = true;
                $scope.isLookupFailure = false;
                $scope.company = response.company;
                $scope.macprefix = response.macprefix;
                $scope.address = response.address;
                $scope.country = response.country;
                $scope.type = response.type;
            }
            else{
                $scope.isLookupSuccess = false;
                $scope.isLookupFailure = true;
                $scope.error = response.error;
            }
        });
    });
}]);