registerController('PapersController', ['$api', '$scope', '$sce', '$http', function($api, $scope, $sce, $http) {

	$scope.certKeyType				= "tls_ssl";
	$scope.certKeyComment			= "";
	$scope.certBitSize				= "2048";
	$scope.certDaysValid			= "365";
	$scope.certSigAlgo				= "sha256";
	$scope.certSANs					= "";
	$scope.certKeyName				= "";
	$scope.modifyCertInfo			= false;
	$scope.certInfoCountry			= "";
	$scope.certInfoState			= "";
	$scope.certInfoLocality			= "";
	$scope.certInfoOrganization		= "";
	$scope.certInfoSection			= "";
	$scope.certInfoCN				= "";
	$scope.certEncryptKeysBool		= false;
	$scope.certEncryptAlgo			= "aes256";
	$scope.certEncryptPassword		= "";
	$scope.certExportPKCS12			= false;
	$scope.certificates				= "";
	$scope.SSLStatus				= ['Loading...'];
	$scope.showCertThrobber			= false;
	$scope.showBuildThrobber		= false;
	$scope.showRemoveSSLButton		= true;
	$scope.showUnSSLThrobber		= false;
	$scope.logs						= "";
	$scope.changelogs				= "";
	$scope.currentLogTitle			= "";
	$scope.currentLogData			= "";
	$scope.dependsInstalled			= true;
	$scope.dependsProcessing		= false;
	$scope.selectedFiles			= [];
	$scope.uploading				= false;
	$scope.selectedKey				= '';
	$scope.certDecryptPassword		= '';
	$scope.encrypting				= false;
	$scope.decrypting				= false;
	$scope.viewCert					= '';
	$scope.selectedCert				= '';
	$scope.loadingCert				= false;
  $scope.certsInstalled			= true;
  $scope.selectedSSHKeys    = '';
  $scope.loadingSSHKeys     = false;
  $scope.sshPrivKey         = '';
  $scope.sshPubKey          = '';
  $scope.sslPrivKey         = '';
  $scope.sslCert            = '';


	$scope.checkDepends = (function(){
		$api.request({
			module: 'Papers',
			action: 'checkDepends'
		},function(response){
			if (response.success === true) {
				$scope.dependsInstalled = true;
			} else {
				$scope.dependsInstalled = false;
			}
		});
	});

	$scope.installDepends = (function(){
		$scope.dependsProcessing = true;
		$api.request({
			module: 'Papers',
			action: 'installDepends'
		},function(response){
			if (response.success === false) {
				alert("Failed to install dependencies.  Make sure you are connected to the internet.");
			}
			$scope.checkDepends();
			$scope.dependsProcessing = false;
		});
	});

	$scope.removeDepends = (function(){
		$scope.dependsProcessing = true;
		$api.request({
			module: 'Papers',
			action: 'removeDepends'
		},function(response){
			$scope.checkDepends();
			$scope.dependsProcessing = false;
		});
	});

	$scope.buildCertificate = (function() {
		var params = {};
		
		if ($scope.certKeyName != ''){
			params['keyName'] = $scope.certKeyName;
		} else {
			alert("You must enter a name for the keys!");
			return;
		}
		
		params['bitSize'] = $scope.certBitSize;

		if ($scope.certKeyType == "ssh") {
			if ($scope.certEncryptKeysBool) {
				if (!$scope.certEncryptPassword) {
					alert("You must enter a password for the private key");
					return;
				}
				params['pass'] = $scope.certEncryptPassword;
			}
			if ($scope.certKeyComment != "") {
				params['comment'] = $scope.certKeyComment;
			}
			$scope.showBuildThrobber = true;
			$api.request({
				module: 'Papers',
				action: 'genSSHKeys',
				parameters: params
			},function(response){
				$scope.getLogs();
				$scope.showBuildThrobber = false;
				$scope.loadCertificates();
				$api.reloadNavbar();
			});
		} else if ($scope.certKeyType == "tls_ssl") {
			params['sigalgo'] = $scope.certSigAlgo;

			if ($scope.certInfoCountry != ''){
				params['country'] = $scope.certInfoCountry;
			}
			if ($scope.certInfoState != '') {
				params['state'] = $scope.certInfoState;
			}
			if ($scope.certInfoLocality != ''){
				params['city'] = $scope.certInfoLocality;
			}
			if ($scope.certInfoOrganization != ''){
				params['organization'] = $scope.certInfoOrganization;
			}
			if ($scope.certInfoSection != ''){
				params['section'] = $scope.certInfoSection;
			}
			if ($scope.certInfoCN != ''){
				params['commonName'] = $scope.certInfoCN;
			}
			if ($scope.certDaysValid != ''){
				params['days'] = $scope.certDaysValid;
			}
			if ($scope.certSANs != '') {
				params['sans'] = $scope.certSANs;
			}
			if ($scope.certEncryptKeysBool === true) {
				params['encrypt'] = "";
				params['algo'] = $scope.certEncryptAlgo;
				if (!$scope.certEncryptPassword) {
					alert("You must set a password for the private key!");
					return;
				}
				params['pkey_pass'] = $scope.certEncryptPassword;
			}
			if ($scope.certExportPKCS12 === true) {
        params['container'] = "pkcs12";
        params['algo'] = $scope.certEncryptAlgo;
				if (!$scope.certEncryptPassword) {
					alert("You must set a password for the private key!");
					return;
				}
				params['pkey_pass'] = $scope.certEncryptPassword;
			}
		
			$scope.showBuildThrobber = true;
			$api.request({
				module: 'Papers',
				action: 'buildCert',
				parameters: params
			},function(response) {
				$scope.certKeyName = '';
				$scope.getLogs();
				$scope.showBuildThrobber = false;
				$scope.loadCertificates();
				$api.reloadNavbar();
			});
		}
  });
  
  $scope.loadSSHKeys = (function(key){

    $scope.loadingSSHKeys = true;
    $scope.sshPrivKey = '';
    $scope.sshPubKey = '';
    $scope.selectedSSHKeys = key;

    $api.request({
      module: 'Papers',
      action: 'loadSSHKeys',
      keyName: key
    },function(response){
      $scope.loadingSSHKeys = false;
      if (response === false) {
        $('#viewSSHKeys').modal('hide');
        return;
      }
      $scope.sshPrivKey = $sce.trustAsHtml(response.data.privkey);
      $scope.sshPubKey = $sce.trustAsHtml(response.data.pubkey);
    });
  });
	
	$scope.loadCertProps = (function(cert){
		
		$scope.loadingCert = true;
		$scope.viewCert = '';
		$scope.selectedCert = cert;
		
		$api.request({
			module: 'Papers',
			action: 'loadCertProps',
			certName: cert
		},function(response){
			$scope.loadingCert = false;
			if (response === false) {
				$('#viewCert').modal('hide');
				return;
			}
      $scope.viewCert = response.data;
      $scope.sslPrivKey =  $sce.trustAsHtml($scope.viewCert.privkey);
      $scope.sslCert =  $sce.trustAsHtml($scope.viewCert.certificate);
		});
	});
	
	$scope.selectKey = (function(key, type) {
		$scope.certEncryptAlgo = "aes256";
		$scope.certEncryptPassword = '';
		$scope.selectedKey = key;
		$scope.selectedKeyType = type;
	});
	
	$scope.encryptKey = (function(name, type, algo, pass) {
		
		if (pass.length == 0) {
			return;
		}
		
		$scope.encrypting = true;
		
		$api.request({
			module: 'Papers',
			action: 'encryptKey',
			keyName: name,
			keyType: type,
			keyAlgo: algo,
			keyPass: pass
		},function(response){
			
			$scope.encrypting = false;
			$scope.certEncryptPassword = '';
			
			if (response.success === false) {
				alert("Failed to encrypt key.  Check the logs for more info.");
				$scope.getLogs();
				return;
			}
			$scope.loadCertificates();
			$('#encryptModal').modal('hide');
		});
	});
	
	$scope.decryptKey = (function(name, type, pass) {
		
		if (pass.length == 0) {
			return;
		}
		
		$scope.decrypting = true;
		
		$api.request({
			module: 'Papers',
			action: 'decryptKey',
			keyName: name,
			keyType: type,
			keyPass: pass
		},function(response){
			
			$scope.decrypting = false;
			$scope.certDecryptPassword = '';
			
			if (response.success === false) {
				alert("Failed to decrypt key.  Ensure you have entered the password correctly.");
				$scope.getLogs();
				return;
			}
			$scope.loadCertificates();
			$('#decryptModal').modal('hide');
		});
		
		
	});

	$scope.clearForm = (function() {
			$scope.certKeyType				      = "tls_ssl";
			$scope.certDaysValid			      = "365";
      $scope.certBitSize              = "2048";
      $scope.certSigAlgo              = "sha256";
			$scope.certSANs					        = "";
      $scope.certKeyName              = "";
      $scope.certInfoCountry          = "";
      $scope.certInfoState            = "";
      $scope.certInfoLocality         = "";
      $scope.certInfoOrganization     = "";
      $scope.certInfoSection          = "";
      $scope.certInfoCN               = "";
      $scope.certEncryptKeysBool      = false;
      $scope.certEncryptAlgo          = "aes256";
      $scope.certEncryptPassword      = "";
      $scope.certExportPKCS12         = false;
	});

	$scope.loadCertificates = (function() {
		$api.request({
			module: 'Papers',
			action: 'loadCertificates'
		},function(response){
			if (response.success === true) {
				// Display certificate information
        $scope.certificates = response.data;
			} else {
				// Display error
				console.log("Failed to load certificates.");
			}
		});
	});

	$scope.downloadKeys = (function(name,type) {
		$scope.showCertThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'downloadKeys',
			parameters: {name,type}
		},function(response){
			$scope.showCertThrobber = false;
			if (response.success === true) {
				window.location = '/api/?download=' + response.data;
			} else {
				console.log(response.message);
			}
			// Clear the download archive to keep things clean
			//$scope.clearDownloadArchive();
		});
	});

	$scope.clearDownloadArchive = (function(){
		$api.request({
			module: 'Papers',
			action: 'clearDownloadArchive'
		},function(response) {
			if (response.success === false) {
				console.log(response.message);
			}
		});
	});

	$scope.deleteKeys = (function(cert,type) {
		if (confirm("Confirm key deletion by pressing OK") == false) {return;}
		$scope.showCertThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'removeCertificate',
			params: {cert,type}
		},function(response){
			$scope.showCertThrobber = false;
			$scope.getLogs();
			if (response.success === true) {
				$scope.loadCertificates();
			}
		});
	});

	$scope.securePineapple = (function(cert,type) {
		$scope.showCertThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'securePineapple',
			params: {cert,type}
		},function(response) {
			$scope.showCertThrobber = false;
			if (response.error === "HTTP Error") {
				// Redirect if key type is TLS/SSL
				if (type == "TLS/SSL") {
					$scope.redirect("https");
				}
			} else {
				// Alert error
			}
			$scope.loadCertificates();
		});
	});
	
	$scope.revokeSSHKey = (function(name){
		$api.request({
			module: 'Papers',
			action: 'revokeSSHKey',
			key: name
		},function(response) {
			$scope.loadCertificates();
		});
	});
	
	$scope.redirect = (function(proto){
		loc = window.location.href.split(":");
		if (loc[0] == proto) {
			alert("Success! Refreshing your browser now!");
			window.location.reload();			
		} else {
			loc[0] = proto;
			alert("Success!  Redirecting to " + loc.join(":") + "!");
			window.location = loc.join(":");
		}
	});

	$scope.unSSLPineapple = (function(){
		$scope.showRemoveSSLButton = false;
		$scope.showUnSSLThrobber = true;
		$api.request({
			module: 'Papers',
			action: 'unSSLPineapple'
		},function(response){
			$scope.showUnSSLThrobber = false;
			$scope.showRemoveSSLButton = true;
			$scope.refresh();
			
			if (response.error === "HTTP Error") {
				$scope.redirect("http");
			} else {
			}
		});
	});

	$scope.getNginxSSLCerts = (function(){
		$api.request({
			module: 'Papers',
			action: 'getNginxSSLCerts'
		},function(response){
			if (response.success === true) {
				$scope.certsInstalled = true;
				$scope.SSLStatus = response.data;
			} else {
				$scope.certsInstalled = false;
				$scope.SSLStatus = response.message;
			}
		});
	});

	$scope.getLogs = (function(){
		$api.request({
			module: 'Papers',
			action: 'getLogs',
			type: 'error'
		},function(response){
			$scope.logs = response.data;
		});
	});

	$scope.getChangeLogs = (function(){
		$api.request({
			module: 'Papers',
			action: 'getLogs',
			type: 'changelog'
		},function(response){
			$scope.changelogs = response.data;
		});
	});

	$scope.readLog = (function(log,type){
		$scope.currentLogTitle = log;
		$api.request({
			module: 'Papers',
			action: 'readLog',
			parameters: log,
			type: type
		},function(response){
			console.log(response);
			if (response.success === true) {
				$scope.currentLogData = $sce.trustAsHtml(response.data);
			}
		});
	});

	$scope.deleteLog = (function(log){
		$api.request({
			module: 'Papers',
			action: 'deleteLog',
			parameters: log
		},function(response){
			$scope.getLogs();
			if (response === false) {
				alert(response.message);
			}
		});
	});

	$scope.refresh = (function(){
		$scope.getLogs();
		$scope.clearDownloadArchive();
		$scope.getNginxSSLCerts();
		$scope.checkDepends();
	  $scope.loadCertificates();
	});
	
	// Upload functions
	$scope.setSelectedFiles = (function(){
		files = document.getElementById("selectedFiles").files;
		for (var x = 0; x < files.length; x++) {
			$scope.selectedFiles.push(files[x]);
		}
	});
	
	$scope.removeSelectedFile = (function(file){
		var x = $scope.selectedFiles.length;
		while (x--) {
			if ($scope.selectedFiles[x] === file) {
				$scope.selectedFiles.splice(x,1);
			}
		}
	});
	
	$scope.uploadFile = (function(){
		$scope.uploading = true;
		
		var fd = new FormData();
		for (x = 0; x < $scope.selectedFiles.length; x++) {
			fd.append($scope.selectedFiles[x].name, $scope.selectedFiles[x]);
		}
		$http.post("/modules/Papers/api/module.php", fd, {
			transformRequest: angular.identity,
			headers: {'Content-Type': undefined}
		}).then(function(response) {
			for (var key in response) {
				if (response.hasOwnProperty(key)) {
					if (response.key == "Failed") {
						alert("Failed to upload " + key);
					}
				}
			}
			$scope.selectedFiles = [];
			$scope.refresh();
			$scope.uploading = false;
		});
	});
	
	$scope.init = (function(){
		$api.request({
			module: 'Papers',
			action: 'init'
		},function(response){
			if (response.success == false) {
				if (response.message != '') {
					$scope.getLogs();
				} else {
					alert(response.message);
				}
			}
		});
	});
	
	// Init
	$scope.init();
	$scope.getChangeLogs();
	$scope.refresh();
}])
