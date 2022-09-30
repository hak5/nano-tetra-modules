registerController("APITokenController", ['$api', '$scope', function($api, $scope) {
    $scope.apiTokens = [];
    $scope.newToken = {
        name: "",
        token: ""
    };

    $scope.getApiTokens = function(){
        $api.request({
            'module': 'APITokens',
            'action': 'getApiTokens'
        }, function(response){
            $scope.apiTokens = response.tokens;
        });
    };

    $scope.genApiToken = function(){
        $api.request({
            'module': 'APITokens',
            'action': 'addApiToken',
            'name': $scope.newToken.name
        }, function(response){
            $scope.newToken.name = "";
            $scope.newToken.token = response.token;
            $scope.getApiTokens();
        });
    };

    $scope.revokeApiToken = function($event){
        var id = $event.target.getAttribute('tokenid');
        $api.request({
            'module': 'APITokens',
            'action': 'revokeApiToken',
            'id': id
        }, function(){
            $scope.getApiTokens();
        });
    };

    $scope.selectElem = function(elem){
        var selectRange = document.createRange();
        selectRange.selectNodeContents(elem);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(selectRange);
    }

    $scope.selectOnClick = function($event){
        var elem = $event.target;
        $scope.selectElem(elem);
    };

    $scope.getApiTokens();
}]);
