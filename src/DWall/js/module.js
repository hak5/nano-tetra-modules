registerController('DWallController', ['$scope', '$api', function($scope, $api) {
    $scope.running = false;
    $scope.listening = false;
    $scope.throbber = false;

    $scope.enableDWall = (function() {
        $api.request({
            module: 'DWall',
            action: 'enable'
        }, function() {
            $scope.getDWallStatus();
        });
    });

    $scope.disableDWall = (function() {
        $scope.stopWS();
        $api.request({
            module: 'DWall',
            action: 'disable'
        }, function() {
            $scope.getDWallStatus();
        });
    });

    $scope.getDWallStatus = (function() {
        $api.request({
            module: 'DWall',
            action: 'getStatus'
        }, function(response) {
            $scope.running = response.running;
        });
    });

    $scope.startWS = (function() {
        $scope.throbber = true;
        $scope.ws = new WebSocket("ws://" + window.location.hostname + ":9999/");
        $scope.ws.onerror = (function() {
            $scope.ws.onclose = (function() {});
            $scope.startWS();
        });
        $scope.ws.onopen = (function() {
            $scope.ws.onerror = (function(){});
            $scope.listening = true;
            $scope.throbber = false;
        });
        $scope.ws.onclose = (function() {
            $scope.throbber = false;
            $scope.listening = false;
        });

        $scope.ws.onmessage = (function(message) {
            var data = JSON.parse(message.data);

            if (data['image'] !== undefined) {
                $("#img_container").prepend('<img src="' + encodeURI(data['image']) +'">');
            } else {
                $("#url_table").prepend("<tr><td>" + data['from'] + "</td><td></td></tr>").children().first().children().last().text(data['url']);
            }
            if (data['cookie'] !== undefined) {
                $("#cookie_table").prepend("<tr><td>" + data['from'] + "</td><td></td></tr>").children().first().children().last().text(data['cookie']);
            }
            if (data['post'] !== undefined) {
                $("#post_table").prepend("<tr><td>" + data['from'] + "</td><td></td></tr>").children().first().children().last().text(data['post']);
            }
        });
    });

    $scope.stopWS = (function() {
        if ($scope.ws !== undefined) {
            $scope.ws.onclose = (function() {});
            $scope.ws.close();
        } 
        $scope.listening = false;
    });

    $scope.$on('$destroy', function() {
        $scope.stopWS();
    });

    $scope.getDWallStatus();

    $("#img_container").css('min-height', $(".module-content").height()-50);
    $("#img_container").css('max-height', $(".module-content").height()-50);


}]);
