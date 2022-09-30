registerController('meterpreterCtrl', ['$api', '$scope', function($api, $scope) {
  $scope.running = false
  $scope.enabled = false
  $scope.config = {}
  getState()

  $scope.startMeterpreter = function () {
    apiHelper('startMeterpreter', null, curry(getState))
  }

  $scope.stopMeterpreter = function () {
    apiHelper('stopMeterpreter', null, curry(getState))
  }

  $scope.enableMeterpreter = function () {
    apiHelper('enableMeterpreter', null, curry(getState))
  }

  $scope.disableMeterpreter = function () {
    apiHelper('disableMeterpreter', null, curry(getState))
  }

  $scope.saveConfig = function () {
    apiHelper('saveConfig', $scope.config, curry(getState))
  }

  function getState () {
    apiHelper('getState', null, function(response) {
      if (response.success) {
        $scope.running = response.running
        $scope.enabled = response.enabled
        $scope.config = response.config
      }
    })
  }

  function apiHelper (action, payload, handler) {
    $api.request({ module: 'Meterpreter', action: action, params: payload }, handler)
  }

  function curry (cb) {
    return function (response) {
      response.success ? cb() : console.error(response, response.errors)
    }
  }

}])
