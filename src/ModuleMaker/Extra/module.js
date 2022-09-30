/* This is our AngularJS controller, called "ExampleController". */
registerController('ExampleController', ['$api', '$scope', function($api, $scope) {
    /* It is good practice to 'initialize' your variables with nothing */
    $scope.greeting = "";
    $scope.content = "";

    /* Use the API to send a request to your module.php */
    $api.request({
        module: '_MODULE_NAME', //Your module name
        action: 'getContents'   //Your action defined in module.php
    }, function(response) {
        if (response.success === true) {           //If the response has an index called "success" that returns the boolean "true", then:
            $scope.greeting = response.greeting;   // Set the variable $scope.greeting to the response index "greeting"
            $scope.content = response.content;     // Set the variable $scope.content to the response index "content".
        }
        console.log(response) //Log the response to the console, this is useful for debugging.
    });
}]);