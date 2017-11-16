// TODO: update known_hosts in gui on config save

registerController('autosshMainCtrl', ['$api', '$scope', function($api, $scope) {

  $scope.isRunning = false
  $scope.isEnabled = false
  $scope.getStatus = function () {
    apiCaller("status", null, function(response) {
      if (response.success) {
        $scope.isRunning = response.isRunning
        $scope.isEnabled = response.isEnabled
      }
    })
  }
  $scope.getStatus()

  $scope.startAutossh = function () {
    apiCaller("startAutossh", null, handle($scope.getStatus))
  }

  $scope.stopAutossh = function () {
    apiCaller("stopAutossh", null, handle($scope.getStatus))
  }

  $scope.enableAutossh = function () {
    apiCaller("enableAutossh", null, handle($scope.getStatus))
  }

  $scope.disableAutossh = function () {
    apiCaller("disableAutossh", null, handle($scope.getStatus))
  }

  function apiCaller (action, payload, cb) {
    var options = { module: 'autossh', action: action }
    if (payload) $.extend(options, payload)
    $api.request(options, cb)
  }

  function handle (updaterFunction) {
    return function (response) {
      response.success ? updaterFunction() : console.error(response)
    }
  }

}])

// -

registerController('autosshConfCtrl', ['$rootScope','$api', '$scope', function($rootScope, $api, $scope) {

  $scope.formData = {}
  $scope.savingConf = false

  $scope.readConf = function () {
    apiCaller('readConf', null, function(response) {
      if (response.success) {
        $scope.formData = {
          user: response.user,
          host: response.host,
          port: response.port,
          rport: response.rport,
          lport: response.lport
        }

        $rootScope.cmdThatRuns = [
          'autossh -M 20000 -i ~/.ssh/id_rsa.autossh -N -T -R ',
          $scope.formData.rport,
          ':localhost:',
          $scope.formData.lport,
          ' ',
          $scope.formData.user,
          '@',
          $scope.formData.host,
          ' -p ',
          $scope.formData.port
        ].join('')

      }
    })
  }
  $scope.readConf()

  $scope.writeConf = function () {
    $scope.savingConf = true
    apiCaller('writeConf', { data: $scope.formData }, function (response) {
      $scope.savingConf = false
      if (response.success) {
        $scope.readConf()
      } else {
        console.error(response.error)
      }
    })
  }

  $scope.resetConf = function () {
    apiCaller("resetConf", null, handle($scope.readConf))
  }

  function apiCaller (action, payload, cb) {
    var options = { module: 'autossh', action: action }
    if (payload) $.extend(options, payload)
    $api.request(options, cb)
  }

  function handle (updaterFunction) {
    return function (response) {
      response.success ? updaterFunction() : console.error(response)
    }
  }

}])

// -

registerController('firstRunCtrl', ['$api', '$scope', function($api, $scope) {

  $scope.pubKey = ""
  $scope.knownHosts = ""
  $scope.sshCopyCommand = ""
  $scope.generatingKeys = false

  $scope.getInfo = function () {
    apiCaller('getInfo', null, function(response) {
      if (response.success) {
        $scope.pubKey  = response.pubKey
        $scope.knownHosts  = response.knownHosts
        $scope.keyExists = response.keyExists
      }
    })
  }
  $scope.getInfo()

  apiCaller("readConf",null, function (resp) {
    $scope.sshCopyCommand = "cat ~/.ssh/id_rsa.autossh.pub | \
    ssh -p "+resp.port+" "+resp.user+"@"+resp.host+" \
    'mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys'"
  })

  $scope.createSshKey = function () {
    $scope.generatingKeys=true
    apiCaller("createSshKey", null, function(response) {
      $scope.generatingKeys=false
      if (response.success) {
        $scope.getInfo()
      } else {
        console.error(response)
      }
    })
  }

  $scope.deleteKey = function () {
    apiCaller("deleteKey", null, handle($scope.getInfo))
  }

  function apiCaller (action, payload, cb) {
    var options = { module: 'autossh', action: action }
    if (payload) $.extend(options, payload)
    $api.request(options, cb)
  }

  function handle (updaterFunction) {
    return function (response) {
      response.success ? updaterFunction() : console.error(response)
    }
  }

}])
