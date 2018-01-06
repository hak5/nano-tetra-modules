<?php

namespace pineapple;
define('__INCLUDES__', "/pineapple/modules/Papers/includes/");
define('__SCRIPTS__', __INCLUDES__ . "scripts/");
define('__SSLSTORE__', __INCLUDES__ . "ssl/");
define('__SSHSTORE__', __INCLUDES__ . "ssh/");
define('__LOGS__', __INCLUDES__ . "logs/");
define('__CHANGELOGS__', __INCLUDES__ . "changelog/");
define('__HELPFILES__', __INCLUDES__ . "help/");
define('__DOWNLOAD__', __INCLUDES__ . "download/");
define('__UPLOAD__', __INCLUDES__ . "upload/");
define('__SSL_TEMPLATE__', __SCRIPTS__ . "ssl.cnf");

/*
	Import keys
*/
if (!empty($_FILES)) {
	$response = [];
	foreach ($_FILES as $file) {
		$tempPath = $file[ 'tmp_name' ];
		$name = $file['name'];
		$type = pathinfo($file['name'], PATHINFO_EXTENSION);
		
		// Do not accept any file other than .zip
		if ($type != "zip") {
			continue;
		}
		
		// Ensure the upload directory exists
		if (!file_exists(__UPLOAD__)) {
			if (!mkdir(__UPLOAD__, 0755, true)) {
				Papers::logError("Failed Upload", "Failed to upload " . $file['name'] . " because the directory structure could not be created");
			}
		}
		
		$uploadPath = __UPLOAD__ . $name;
		$res = move_uploaded_file( $tempPath, $uploadPath );
		
		if ($res) {
			// Unpack the key archive and move the keys to their appropriate directories
			exec(__SCRIPTS__ . "/unpackKeyArchive.sh -f " . explode(".", $name)[0]);
			$response[$name] = "Success";
		} else {
			$response[$name] = "Failed";
		}
	}
	echo json_encode($response);
	die();
}


class Papers extends Module
{
	public function route() {
		switch ($this->request->action) {
			case 'checkDepends':
				$this->checkDepends();
				break;
			case 'installDepends':
				$this->installDepends();
				break;
			case 'removeDepends':
				$this->removeDepends();
				break;
			case 'buildCert':
				$this->buildCert($this->request->parameters);
				break;
			case 'genSSHKeys':
				$this->genSSHKeys($this->request->parameters);
				break;
			case 'loadCertificates':
				$this->loadCertificates();
				break;
			case 'downloadKeys':
				$this->downloadKeys($this->request->parameters->name, $this->request->parameters->type);
				break;
			case 'clearDownloadArchive':
				$this->clearDownloadArchive();
				break;
			case 'removeCertificate':
				$this->removeCertificate($this->request->params->cert, $this->request->params->type);
				break;
			case 'securePineapple':
				$this->securePineapple($this->request->params->cert, $this->request->params->type);
				break;
			case 'getNginxSSLCerts':
				$this->getNginxSSLCerts();
				break;
			case 'unSSLPineapple':
				$this->unSSLPineapple();
				break;
			case 'revokeSSHKey':
				$this->revokeSSHKey($this->request->key);
				break;
			case 'getLogs':
				$this->getLogs($this->request->type);
				break;
			case 'readLog':
				$this->retrieveLog($this->request->parameters, $this->request->type);
				break;
			case 'deleteLog':
				$this->deleteLog($this->request->parameters);
				break;
		}
	}
	private function checkDepends() {
		$retData = array();
		exec(__SCRIPTS__ . "checkDepends.sh", $retData);
		if (implode(" ", $retData) == "Installed") {
			$this->respond(true);
		} else {
			$this->respond(false);
		}
	}
	private function installDepends() {
		$retData = array();
		exec(__SCRIPTS__ . "installDepends.sh", $retData);
		if (implode(" ", $retData) == "Complete") {
			$this->respond(true);
		} else {
			$this->respond(false);
		}
	}
	private function removeDepends() {
		// removeDepends.sh doesn't return anything whether successful or not
		exec(__SCRIPTS__ . "removeDepends.sh");
		$this->respond(true);
	}
	private function genSSHKeys($paramsObj) {
		$keyInfo = array();
		$params = (array)$paramsObj;
		
		$keyInfo['-k'] = $params['keyName'];
		$keyInfo['-b'] = $params['bitSize'];
		if (array_key_exists('pass', $params)) {
			$keyInfo['-p'] = $params['pass'];
		}
		if (array_key_exists('comment', $params)) {
			$keyInfo['-c'] = $params['comment'];
		}
		
		// Build the argument string to pass to buildCert.sh
		foreach ($keyInfo as $k => $v) {
			$argString .= $k . " \"" . $v . "\" ";
		}
		$argString = rtrim($argString);
		
		$retData = array();
		exec(__SCRIPTS__ . "genSSHKeys.sh " . $argString, $retData);
		$res = implode("\n", $retData);
		if ($res != "") {
			$this->logError("Build SSH Key Error", "Failed to build SSH keys.  The following data was returned:\n" . $res);
			$this->respond(false);
			return;
		}
		$this->respond(true);
	}
	private function buildCert($paramsObj) {
		$certInfo = array();
		$req = array();
		$params = (array)$paramsObj;

		$keyName = (array_key_exists('keyName', $params)) ? $params['keyName'] : "newCert";
		$certInfo['-k'] = $keyName;

		if (array_key_exists('days', $params)) {
			$numberofdays = intval($params['days']);
			$certInfo['-d'] = $numberofdays;
		}
		if (array_key_exists('sigalgo', $params)) {
			$certInfo['-sa'] = $params['sigalgo'];
		}
		if (array_key_exists('bitSize', $params)) {
			$certInfo['-b'] = $params['bitSize'];
		}
		
		$req[':C:'] = array_key_exists('country', $params) ? $params['country'] : "US";
		$req[':ST:'] = array_key_exists('state', $params) ? $params['state'] : "CA";
		$req[':LOC:'] = array_key_exists('city', $params) ? $params['city'] : "San Jose";
		$req[':ORG:'] = array_key_exists('organization', $params) ? $params['organization'] : "SecTrust";
		$req[':OU:'] = array_key_exists('section', $params) ? $params['section'] : "Certificate Issue";
		$req[':COM:'] = array_key_exists('commonName', $params) ? $params['commonName'] : $keyName;
		
		if (array_key_exists('sans', $params)) {
			$req[':SAN:'] = $params['sans'];
		}
		
		// Generate an OpenSSL config file
		$certInfo['--config'] = $this->generateSSLConfig($keyName, $req);
		
		// Build the argument string to pass to buildCert.sh
		foreach ($certInfo as $k => $v) {
			$argString .= $k . " \"" . $v . "\" ";
		}
		$argString = rtrim($argString);
		
		$retData = array();
		exec(__SCRIPTS__ . "buildCert.sh " . $argString, $retData);
		$res = implode("\n", $retData);
		if ($res != "Complete") {
			$this->logError("Build Certificate Error", "The key pair failed with the following error from the console:\n\n" . $res);
			$this->respond(false, "Failed to build key pair.  Check the logs for details.");
			return;
		}
		
		// Delete the OpenSSL conf file
		unlink($certInfo['--config']);

		if (array_key_exists('container', $params) || array_key_exists('encrypt', $params)) {
			$cryptInfo = array();
			$argString = "";

			$cryptInfo['-k'] = $keyName;			

			// Check if the certificate should be encrypted
			if (array_key_exists('encrypt', $params)) {
				$argString = "--encrypt ";
	
				$cryptInfo['-a'] = (array_key_exists('algo', $params)) ? $params['algo'] : False;
				$cryptInfo['-p'] = (array_key_exists('pkey_pass', $params)) ? $params['pkey_pass'] : False;
	
				if (!$cryptInfo['-a'] || !$cryptInfo['-p']) {
					$this->logError("Build Certificate Error", "The public and private keys were generated successfully but an algorithm or password were not supplied for encryption.  The certs can still be found in your SSL store.");
					$this->respond(false, "Build finished with errors.  Check the logs for details.");
					return;
				}
			}
			// Check if the certificates should be placed into an encrypted container
			if (array_key_exists('container', $params)) {
				$cryptInfo['-c'] = (array_key_exists('container', $params)) ? $params['container'] : False;
				$cryptInfo['-calgo'] = (array_key_exists('c_algo', $params)) ? $params['c_algo'] : False;
				$cryptInfo['-cpass'] = (array_key_exists('c_pass', $params)) ? $params['c_pass'] : False;
			}
				
			// Build an argument string with all available arguments
			foreach ($cryptInfo as $k => $v) {
				if (!$v) {continue;}
				$argString .= $k . " \"" . $v . "\" ";
			}
			$argString = rtrim($argString);

			// Execute encryptKeys.sh with the parameters and check for errors
			$retData = array();
			exec(__SCRIPTS__ . "encryptKeys.sh " . $argString, $retData);
			$res = implode("\n", $retData);
			if ($res != "Complete") {
				$this->logError("Certificate Encryption Error", "The public and private keys were generated successfully but encryption failed with the following error:\n\n" . $res);
				$this->respond(false, "Build finished with errors.  Check the logs for details.");
				return;
			}
		}
		$this->respond(true, "Keys created successfully!");
	}
	
	/*
		Generates an OpenSSL config file based on the passed in requirements ($req)
		and returns the path to the file.
	*/
	private function generateSSLConfig($keyName, $req) {
		$conf = file_get_contents(__SSL_TEMPLATE__);
		
		foreach ($req as $k => $v) {
			$conf = str_replace($k, $v, $conf);
		}
		
		// Add the common name as a SAN
		$conf .= "\nDNS.1 = " . $req[':COM:'];
		
		// Add additional SANs if they were provided
		if (isset($req[':SAN:'])) {
			$x = 2;
			foreach (explode(",", $req[':SAN:']) as $san) {

				// Skip the common name if it was included in the list since
				// we already added it above
				if ($san == $req[':COM:']) { continue; }

				$conf .= "\nDNS." . $x . " = " . $san;
				$x++;
			}
		}
		
		$path = __SCRIPTS__ . hash('md5', $keyName . time()) . ".cnf";
		file_put_contents($path, $conf);
		return $path;
	}

	private function loadCertificates() {
		$certs = $this->getKeys(__SSLSTORE__);
		$certs = array_merge($certs, $this->getKeys(__SSHSTORE__));
		$this->respond(true,null,$certs);
	}
	
	private function getKeys($dir) {
		$keyType = ($dir == __SSLSTORE__) ? "TLS/SSL" : "SSH";
		$keys = scandir($dir);
		$certs = array();
		foreach ($keys as $key) {
			if (substr($key, 0, 1) == ".") {continue;}

			$parts = explode(".", $key);
			$fname = $parts[0];
			$type = "." . $parts[1];

			// Check if the object name already exists in the array
			if ($this->objNameExistsInArray($fname, $certs)) {
				foreach ($certs as &$obj) {
					if ($obj->Name == $fname) {
						$obj->Type .= ", " . $type;
					}
				}
			} else {
				// Add a new object to the array
				$enc = ($this->keyIsEncrypted($fname, $keyType)) ? "Yes" : "No";
				array_push($certs, (object)array('Name' => $fname, 'Type' => $type, 'Encrypted' => $enc, 'KeyType' => $keyType, 'Authorized' => $this->checkSSHKeyAuth($fname, $keyType)));
			}
		}
		return $certs;
	}
	
	private function checkSSHKeyAuth($keyName, $keyType) {
		if ($keyType != "SSH") {return false;}
		$res = exec(__SCRIPTS__ . "checkSSHKey.sh -k " . $keyName);
		if ($res == "TRUE") {
			return true;
		}
		return false;
	}
	
	private function revokeSSHKey($keyName) {
		exec(__SCRIPTS__ . "revokeSSHKey.sh -k " . $keyName);
		$this->respond(true);
	}

	private function keyIsEncrypted($keyName, $keyType) {
		$data = array();
		$keyDir = ($keyType == "SSH") ? __SSHSTORE__ : __SSLSTORE__;
		exec(__SCRIPTS__ . "testEncrypt.sh -k " . $keyName . " -d " . $keyDir . " 2>&1", $data);
		if ($data[0] == "writing RSA key") {
			return false;
		} else if ($data[0] == "unable to load Private Key") {
			return true;
		}
	}

	private function downloadKeys($keyName, $keyType) {
		$argString = "-o " . $keyName . ".zip -f \"";

		// Grab all of the keys, certs, and containers
		$keyDir = ($keyType == "SSH") ? __SSHSTORE__ : __SSLSTORE__;
		$contents = scandir($keyDir);
		$certs = array();
		foreach ($contents as $cert) {
			if (substr($cert, 0, 1) == ".") {continue;}
			$parts = explode(".", $cert);
			$fname = $parts[0];
			$type = "." . $parts[1];
			
			if ($fname == $keyName) {
				$argString .= $cert ." ";
			}
		}
		$argString = rtrim($argString);
		$argString .= "\"";

		// Pack them into an archive
		exec(__SCRIPTS__ . "packKeys.sh " . $keyDir . " " . $argString);

		// Check if the files were archived properly
		$archiveExists = False;
		foreach (scandir(__DOWNLOAD__) as $file) {
			if ($file == $keyName . ".zip") {
				$archiveExists = True;
			}
		}

		// Begin downloading the archive
		if ($archiveExists) {
			$this->respond(true, null, $this->downloadFile(__DOWNLOAD__ . $keyName . ".zip"));
		} else {
			$this->respond(false, "Failed to create archive.");
		}
	}

	private function clearDownloadArchive() {
		foreach (scandir(__DOWNLOAD__) as $file) {
			if (substr($file, 0, 1) == ".") {continue;}
			unlink(__DOWNLOAD__ . $file);
		}
		$files = glob(__DOWNLOAD__ . "*");
		if (count($files) > 0) {
			$this->respond(false, "Failed to clear archive.");
		}
		$this->respond(true);
	}

	private function objNameExistsInArray($name, $arr) {
		foreach ($arr as $x) {
			if ($x->Name == $name) {
				return True;
			}
		}
		return False;
	}

	private function removeCertificate($delCert, $keyType) {
		$res = True;
		$msg = "Failed to delete the following files:";
		$keyDir = ($keyType == "SSH") ? __SSHSTORE__ : __SSLSTORE__;
		foreach (scandir($keyDir) as $cert) {
			if (substr($cert, 0, 1) == ".") {continue;}
			if (explode(".",$cert)[0] == $delCert) {
				if (!unlink($keyDir . $cert)) {
					$res = False;
					$msg .= " " . $cert;
				}
			}
		}
		$this->respond($res, $msg);
	}

	private function respond($success, $msg = null, $data = null, $error = null) {
		$this->response = array("success" => $success,"message" => $msg, "data" => $data, "error" => $error);
	}

	private function getNginxSSLCerts() {
		$res = $this->checkSSLConfig();
		if ($res == "") {
			$this->respond(false, array("[!] SSL keys not configured in nginx.conf"));
		} else {
			$this->respond(true, null, explode(" ", $res));
		}
	}

	private function checkSSLConfig() {
		$retData = array();
		exec(__SCRIPTS__ . "readKeys.sh", $retData);
		return implode(" ", $retData);
	}

	private function unSSLPineapple() {
		// First check if SSL is configured
		if ($this->checkSSLConfig() == "") {
			$this->respond(true);
			return;
		}

		// Remove the keys from /etc/nginx/ssl/ and delete the directory
		$status = True;
		if (!$this->removeKeysFromNginx()) {
			$status = False;
			$this->logError("UnSSLPineapple", "Failed to remove keys from /etc/nginx/ssl/.");
		}

		// Remove the configurations from /etc/nginx/nginx.conf
		$retData = array();
		exec("python " . __SCRIPTS__ . "cfgNginx.py --remove", $retData);
		if (implode("", $retData) != "Complete") {
			$status = False;
			$this->logError("UnSSLPineapple", "Failed to remove SSL configurations from /etc/nginx/nginx.conf");
		}
		$this->respond($status);
	}

	private function securePineapple($certName, $keyType) {
		// Check the key type to determine whether we are adding an SSH key or SSL keys
		if ($keyType == "SSH") {
			// Modify authorized_keys file
			exec(__SCRIPTS__ . "addSSHKey.sh -k " . $certName);
			$this->respond(true);
		} else {
			// Update SSL configs
			$this->SSLPineapple($certName);
		}
	}
	
	private function SSLPineapple($certName) {
		// Check if nginx SSL directory exists
		$nginx_ssl_dir = "/etc/nginx/ssl/";
		if (!file_exists($nginx_ssl_dir)) {
			if (!mkdir($nginx_ssl_dir)) {
				$this->logError("SSL Config Failure", "nginx SSL directory does not exist and it could not be created.");
				$this->respond(false, "An error occurred.  Check the logs for details.");
				return;
			}
		}

		// Check if SSL is already configured, if so simply replace the keys
		// and skip the rest of this function
		if ($this->checkSSLConfig() != "") {
			$this->replaceSSLCerts($certName);
			return;
		}

		// Copy selected key pair to the SSL directory
		if (!$this->copyKeysToNginx($certName)) {
			$this->respond(false, "An error occurred.  Check the logs for details.");
			return;
		}

		// Call the nginx configuration script cfgNginx.py
		$retData = array();
		exec("python " . __SCRIPTS__ . "cfgNginx.py --add -k " . $certName, $retData);
		if (implode("", $retData) == "Complete") {
			$this->respond(true);
			return;
		}
	
		// Log whatever message came from cfgNginx.py and return False
		$this->logError("SSL Config Failure", implode("", $retData));
		$this->respond(false, "An error occurred.  Check the logs for details.");
	}

	private function replaceSSLCerts($certName) {
		// Remove the old keys from the SSL store
		$this->removeKeysFromNginx();

		// Copy selected key pair to the SSL directory
		if (!$this->copyKeysToNginx($certName)) {
			$this->respond(false, "An error occurred.  Check the logs for details.");
			return;
		}

		$retData = array();
		exec("python " . __SCRIPTS__ . "cfgNginx.py --replace -k " . $certName, $retData);
		if (implode("", $retData) == "Complete") {
			$this->respond(true);
			return;
		}
		$this->logError("Replace SSL Cert Failure", $retData);
		$this->respond(false);
		return;
	}
	private function copyKeysToNginx($certName) {
		// Copy selected key pair to the SSL directory
		$retData = array();
		$res = exec(__SCRIPTS__ . "copyKeys.sh " . __SSLSTORE__ . $certName, $retData);
		if ($res) {
			$this->logError("Replace SSL Cert Failure", $retData);
			return False;
		}
		return True;
	}
	private function removeKeysFromNginx() {
		$keys = $this->checkSSLConfig();
		$retData = array();
		$res = exec(__SCRIPTS__ . "removeKeys.sh {$keys}", $retData);
		if ($res) {
			$this->logError("Key Removal Failed", "Old keys may still exist in /etc/nginx/ssl/.  Continuing process anyway...");
			return False;
		}
		return True;
	}
	private function getLogs($type) {
		$dir = ($type == "error") ? __LOGS__ : __CHANGELOGS__;
		$contents = array();
		foreach (scandir($dir) as $log) {
			if (substr($log, 0, 1) == ".") {continue;}
			array_push($contents, $log);
		}
		$this->respond(true, null, $contents);
	}
	private function logError($filename, $data) {
		$time = exec("date +'%H_%M_%S'");
		$fh = fopen(__LOGS__ . str_replace(" ","_",$filename) . "_" . $time . ".txt", "w+");
		fwrite($fh, $data);
		fclose($fh);
	}
	private function retrieveLog($logname, $type) {
		$dir = ($type == "error") ? __LOGS__ : ($type == "help") ? __HELPFILES__ : __CHANGELOGS__;
		$data = file_get_contents($dir . $logname);
		if (!$data) {
			$this->respond(false);
			return;
		}
		$this->respond(true, null, $data);
	}
	private function deleteLog($logname) {
		$data = unlink(__LOGS__ . $logname);
		if (!$data) {
			$this->respond(false, "Failed to delete log.");
			return;
		}
		$this->respond(true);
	}
}
?>
