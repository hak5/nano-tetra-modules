<?php namespace pineapple;

/*
 * Author: trashbo4t (github.com/trashbo4t)
 */

class InternetSpeedTest extends Module
{
	// CONSTANTS
	private $SPEED_TEST_DIR = '/pineapple/modules/InternetSpeedTest/tests';
	private $ALL_TESTS_FILE = '/pineapple/modules/InternetSpeedTest/tests/all';
	private $LOG_FILE = '/pineapple/modules/InternetSpeedTest/log.txt';
	
	public function route()
	{
		switch ($this->request->action) {
			case 'clearTests':
				$this->clearTests();
				break;
			case 'clearLogFile':
				$this->clearLogFile();
				break;
			case 'startSpeedTest':
				$this->startSpeedTest();
				break;
			case 'getSpeedTestFromFile':
				$this->getSpeedTestFromFile();
				break;
			case 'getPreviousTests':
				$this->getPreviousTests();
				break;
		}
	}

	//
	// log
	// this function will write to the log file inside the IST directory
	//
	private function log($msg)
	{
		exec("echo {$msg} >> {$this->LOG_FILE}");
	}

	//
	// clearLogFile
	// this function will wipe the log file inside the IST directory
	//
	private function clearLogFile()
	{
		exec("echo '' > {$this->LOG_FILE}");
	}

	// makeSpeedTestDir
	// this function will create the directory for speed test outputs
	// IFF the directory does not exist
	public function makeSpeedTestsDir()
	{
		exec("mkdir {$this->SPEED_TEST_DIR}");
	} 

	//
	// touchSpeedTestFile
	// this function will create an empty speedtest file
	//
	public function touchSpeedTestFile($file)
	{
		if ($file == null) 
		{
			exec("touch {$this->ALL_TESTS_FILE}");
			return "{$this->ALL_TESTS_FILE}";
		}
		else 
		{
			exec("touch {$file}");
			return $file;
		}
	}

	//
	// clearTests
	// this function will wipe all of the tests and test file inside the IST directory
	//
	private function clearTests()
	{
		exec("rm  {$this->ALL_TESTS_FILE}");
		exec("rm -rf {$this->SPEED_TEST_DIR}");
		$this->makeSpeedTestsDir();
	}

	//
	// runSpeedTest
	// this function will execute a wget command and download 50 MB worth of data 
	// from the hak5 repo on github.com returning the formatted output of @getSpeedTestFile 
	//
	public function runSpeedTest($file)
	{
		$firstcmd = "wget -a ".$file." --output-document=/dev/null https://www.wifipineapple.com/downloads/tetra/2.5.2";
		$secondcmd = "echo Downloading 50 MB of data finished on `tail -n 2 ".$file." | cut -d '-' -f 1,2,3` > ".$file;
		$this->log("running new test");
		$this->log("file: ".$file);
		$this->log("first command: ".$firstcmd);
		$this->log("second command: ".$secondcmd);

		$tsStart = time(); 
		
		// run the 25MB download twice
		exec($firstcmd);
		exec($firstcmd);
		exec($secondcmd);

		$tsTook = time() - $tsStart; 
		
		exec("echo took ".$tsTook." seconds >> ".$file);
	
		return $this->getSpeedTestFile($file);	
	}

	//
	// getSpeedTestFile
	// this function will return the contents of a speed test file
	//
	public function getSpeedTestFile($file)
	{
		$this->log("getSpeedTestFile");
		$this->log($file);

		return file_get_contents($file);	
	}

	//
	//  getSpeedTestFromFile
	//  return the wget output associated with a speed test via the file contents
	//
	public function getSpeedTestFromFile()
	{
		$this->log("requesting file");
		$this->log($this->request->file);

		$this->makeSpeedTestsDir();
		$file = $this->touchSpeedTestFile($this->request->file);
		$output = $this->getSpeedTestFile($file);
		$this->response = $output;

		$this->log($output);
	}

	//
	// addToSpeedTestFile
	// this function will add the results of a speedtest to a file
	//
	public function addToSpeedTestFile($file)
	{
		exec("echo {$file} >> {$this->ALL_TESTS_FILE}");
	}

	//
	//  startSpeedTest
	//  return the output of wget speed test
	//
	public function startSpeedTest()
	{
		$this->makeSpeedTestsDir();
		$file = "{$this->SPEED_TEST_DIR}/".date("d-m-Y-h-i-s")."-speedtest";
		$output = $this->runSpeedTest($file);
	
		$this->addToSpeedTestFile($file);

		$this->response = $output;
	}
	
	//
	//  getPreviousTests
	//  return the tests file as an array
	//
	public function getPreviousTests()
	{

		$this->makeSpeedTestsDir();
		$this->touchSpeedTestFile();

		$this->response = array();
		
		$lines = file($this->ALL_TESTS_FILE);
		foreach ($lines as $line) 
		{
			array_push($this->response, $line);
		}  
	}
}
