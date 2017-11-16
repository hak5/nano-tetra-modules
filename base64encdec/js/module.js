/* This is our AngularJS controller, called "ExampleController". */
registerController('base64encdecController', ['$api', '$scope', function($api, $scope) {
    
    
    $scope.workspace = {inputcontent: "", outputcontent: ""};
    $scope.content = "";
    
    
    $scope.encode = function() {
        //console.log("encode pressed");
        $api.request({
            module: 'base64encdec', 
            action: 'encode',
            data: $scope.workspace.inputcontent
        }, function(response) {
            if (response.success === true) {
                $scope.workspace.outputcontent = response.content;
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }
    
     $scope.decode = function() {
        //console.log("decode pressed");
        $api.request({
            module: 'base64encdec', 
            action: 'decode',
            data: $scope.workspace.inputcontent
        }, function(response) {
            if (response.success === true) {
                $scope.workspace.outputcontent = response.content;
            }
            //console.log(response) //Log the response to the console, this is useful for debugging.
        });
    }
    
    
}]);