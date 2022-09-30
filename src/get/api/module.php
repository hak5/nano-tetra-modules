<?php namespace pineapple;

require_once('DatabaseConnection.php');

define('__INCLUDES__', $pineapple->directory . "/includes/");
define('__LOGS__', __INCLUDES__ . "logs/");

class get extends Module
{
    private $dbConnection;
    private $dbPath;
    const DATABASE = "/etc/pineapple/get.db";
    
    private function prepareDatabase()
    {
        if ( $this->doesLocationFileExist("/etc/pineapple/get_database_location") )
        {
            $dbPath = trim(file_get_contents("/etc/pineapple/get_database_location")) . "get.db";
            $this->dbConnection = new DatabaseConnection($dbPath);
        }
        else
        {
            $this->dbConnection = new DatabaseConnection(self::DATABASE);
        }
        
        $this->dbConnection->exec("CREATE TABLE IF NOT EXISTS info (id INTEGER NOT NULL, mac TEXT NOT NULL, ip TEXT, hostname TEXT, info TEXT NOT NULL, timestamp TEXT NOT NULL, PRIMARY KEY(id) );");
        $this->dbConnection->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER NOT NULL, info_id INTEGER NOT NULL, mac TEXT NOT NULL, comments TEXT, PRIMARY KEY(id) );");
    }
    
    public function route()
    {
        $this->prepareDatabase();

        switch($this->request->action) {
            case 'getControlValues':
                $this->getControlValues();
                break;

            case 'handleIFrame':
                $this->handleIFrame();
                break;

            case 'handleInfoGetter':
                $this->handleInfoGetter();
                break;

            case 'handleDBLocation':
                $this->handleDBLocation();
                break;

            case 'getClientProfiles':
                $this->handlegetClientProfiles();
                break;

            case 'viewInformation':
                $this->handleviewInformation();
                break;

            case 'deleteProfile':
                $this->handledeleteProfile();
                break;

            case 'getComments':
                $this->handlegetComments();
                break;

            case 'saveComments':
                $this->handlesaveComments();
                break;
        }
    }


    public function handlesaveComments()
    {
        $this->prepareDatabase();
        $id  = $this->request->id;
        $comments = $this->request->comments;
        $mac = $this->request->mac;
        $info_id = $this->request->id;
        $record_needs_to_be_updated = false;

        // lets first try to query for the info. If it exists, we will have a row, and the loop will set the flag to true
        $result = $this->dbConnection->query("SELECT * FROM comments WHERE info_id = '%s';", $info_id);

        foreach($result as $row) {
            $count = $row['mac'];
            #$this->logError("mylog.txt", "in loop ");
            #$this->logError("mylog.txt", print_r($row, true) );
            #$this->logError("mylog.txt", "rowcount column: " . $count );
            #$this->logError("mylog.txt", "count length: " . strlen($count));
            
            
            if ( $count != "0" or strlen( $count ) > 0 )
            {
                #$this->logError("mylog.txt", "true rowcount > 0");
                $record_needs_to_be_updated = true;
                break;
            }
            else
            {
                #$this->logError("mylog.txt", "false rowcount > 0");
            }
        }


        if ( $record_needs_to_be_updated == true )
        {
            $this->dbConnection->exec("UPDATE comments SET mac = '%s', comments = '%s' WHERE info_id = '%s';", $mac, $comments, $info_id);
            $message = "Updated comments for mac [" . $mac . "]";
            #$this->logError("mylog.txt", $message);
        }   
        else
        {
            $this->dbConnection->exec("INSERT INTO comments (info_id, mac, comments) VALUES('%s','%s','%s');", $info_id, $mac, $comments);
            $message = "Saved comments for mac [" . $mac . "]";
            #$this->logError("mylog.txt", $message);
        }
        
        $control_message = $message;
        $this->response = array("message" => $message, 
                                "control_message" => $control_message
                                );
    }

    public function handlegetComments() {
        $this->prepareDatabase();
        $id  = $this->request->id;
        $mac = $this->request->mac;
        
        $result = $this->dbConnection->query("SELECT comments FROM comments WHERE id = '%s';", $id);

        $message = "Comments Section displaying info for [" . $mac . "]";
        $control_message = $message;
        $comments = $result[0]["comments"];
        $this->response = array("message" => $message, 
                                "control_message" => $control_message,
                                "mac" => $mac,
                                "comments" => $comments
                                );
    }


    public function handledeleteProfile() {
        $this->prepareDatabase();
        $id  = $this->request->id;
        $mac = $this->request->mac;
        
        $result = $this->dbConnection->query("DELETE FROM info WHERE id = '%s';", $id);

        $message = "Deleted information for [" . $mac . "]";
        $control_message = $message;

        $this->response = array("message" => $message, 
                                "control_message" => $control_message
                                );
    }

    public function handleviewInformation() {
        $this->prepareDatabase();
        $id  = $this->request->id;
        $mac = $this->request->mac;
        
        $result = $this->dbConnection->query("SELECT info FROM info WHERE id = '%s';", $id);

        $message = "Information Section displaying info for [" . $mac . "]";
        $control_message = $message;
        $info = $result[0]["info"];
        $this->response = array("message" => $message, 
                                "control_message" => $control_message,
                                "info" => $info
                                );
    }

    public function handlegetClientProfiles() {
        $this->prepareDatabase();

        $all_profiles = array();
        $result = $this->dbConnection->query("SELECT a.id, a.mac, ip, hostname, timestamp, comments FROM info a left join comments b on a.id = b.info_id WHERE a.mac != '';");

        foreach($result as $row) {
            $obj = array("mac"      => $row["mac"],
                         "id"       => $row['id'],
                         "ip"       => $row['ip'],
                         "hostname" => $row['hostname'],
                         "date"     => $row['timestamp'],
                         "comments" => $row['comments']
                   );
            array_push($all_profiles, $obj);
        }

        $this->response = $all_profiles;
    }

    public function handleDBLocation() {
        $data = $this->request->data;
        
         /* Check whether the user has acceptable input and check if the module already exists * /
        if(empty($data)){
            $this->error = "The value passed cannot be blank";
        }

        /* If an error is set, return early * /
        if($this->error){
            return;
        }
        */
        
        //if ( strcmp("false", $data) ) 
        if ( $data == false )
        {
            $message = "Database moved to SD card";
            $this->moveToSD();
            $dbonsd_status = "true";
        }
        else
        {
            $message = "Database moved to internal storage";
            $this->moveToInternal();
            $dbonsd_status = "false";
        }

        $control_message = $message;
        $this->response = array("message" => $message, 
                                "control_message" => $control_message,
                                "dbonsd_status" => $dbonsd_status
                                );
    }

    public function handleInfoGetter() {
        $data = $this->request->data;

        //if ( strcmp("false", $data) ) 
        if ( $data == false ) 
        {
            $message = "Get module enabled.";
            $this->installGet();
            $running_status = "true";
        }
        else
        {
            $message = "Get module disabled";
            $this->uninstallGet();
            $running_status = "false";
        }

        $control_message = $message;
        $this->response = array("message" => $message, 
                                "control_message" => $control_message,
                                "running_status" => $running_status
                                );
    }

    public function handleIFrame() {
        $data = $this->request->data;        
        if ( strcmp("false", $data) )
        {
            $message = "called handle iFrame [false]";
            // set status to true as the input was false and we want to move update it
            $hidden_status = "true";
        }
        else
        {
            $message = "called handle iFrame [true]";
            // set status to true as the input was true and we want to move update it
            $hidden_status = "false";
        }
        
        $control_message = $message;
        $this->response = array("message" => $message, 
                                "control_message" => $control_message,
                                "hidden_status" => $hidden_status
                                );
    }

    // this method runs first. This method loads an array of name value pairs
    // the name is defined by the author. The value is populated based on the 
    // response of the methods that are in this class.
    public function getControlValues() {
        $this->response = array(
                "enabled" => $this->checkEnabled(),
                "hidden" => $this->checkHiddenIframe(),
                "dbonsd" => $this->checkDBonSD()
            );

        // running => infogetter
        // hidden => hidden iframe
        // dbonsd => db on sd
    }

    public function checkEnabled() {
        @$splash = false;
        // write code to see if the module is deplayed by checking the /www/ directory.
        
        if (is_dir("/www/get") || is_link("/www/get")) 
        { 
            //echo "<font style='color: green'><b>installed</b></font> | <b><a style='color: red' href='javascript:getInfusion_uninstall();'>uninstall</a></b>";
            @$splash = true;
        } 
        else
        {
            //echo "<font style='color: red'><b>not installed</b></font> | <b><a style='color: green' href='javascript:getInfusion_install();'>install</a></b>";
            @$splash = false;
        } 
        return $splash;     
    }
    
    public function checkHiddenIframe() {
        @$splash = true;
        // write code to see if the redirect.php contains the code for get.
        if ( exec('cat /www/redirect.php |grep \'<iframe style="display:none;" src="/get/get.php"></iframe>\'')) 
        { 
            //echo "<font style='color: green'><b>installed</b></font> | <b><a style='color: red' href='javascript:getInfusion_unredirect()'>uninstall</a></b>"; 
            @$splash = true;
        } 
        else
        {
            //echo "<font style='color: red'><b>not installed</b></font> | <b><a style='color: green' href='javascript:getInfusion_redirect()'>install</a></b>";
            @$splash = false;
        } 
        return $splash;     
    }
    
    public function checkDBonSD() {
        @$splash = false;
        // write code to see if the module is deplayed by checking the /www/ directory.
        if (is_file("/sd/get/get.db")) 
        {
            //echo "<font style='color: green'><b> installed</b></font> | <b><a style='color: red' href='javascript:getInfusion_outSD()'>uninstall</a></b>";
            @$splash = true;
            //exec("touch /sd/true");
        } 
        else 
        {
            //echo "<font style='color: red'><b>not installed</b></font> | <b><a style='color: green' href='javascript:getInfusion_inSD()'>install</a></b>"; 
            @$splash = false;
            //exec("touch /sd/false");
        } 
        return $splash;     
    }

// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================

    private function doesLocationFileExist($path)
    {
        $filename = $path;
        $found = false;
        if (file_exists($filename)) 
        {
            $found = true;
        }
        return $found;
    }

// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================

    private function moveToInternal()
    {
        exec("cp /sd/get/get.db /etc/pineapple/get.db.tmp");
        exec("rm /sd/get/get.db");
        exec("rm /sd/get/get.db~"); 
        exec("rm -rf /sd/get");
        exec("rm /etc/pineapple/get.db");
        exec("mv /etc/pineapple/get.db.tmp /etc/pineapple/get.db");

        if ( !$this->doesLocationFileExist("/etc/pineapple/get_database_location") )
        {
            exec("touch /etc/pineapple/get_database_location");
        }

        // lets keep track of the get database location
        file_put_contents("/etc/pineapple/get_database_location", "/etc/pineapple/");
    }

// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================

    private function moveToSD()
    {
        /*
        // when the db was in a text file.....
        exec("mkdir /sd/get"); 
        //exec("cp ../includes/get.database /sd/get"); 
        exec("cp  /pineapple/modules/get/includes/get.database /sd/get/"); 
        exec("ln -s -f -b /sd/get/get.database /pineapple/modules/get/includes/get.database"); 
        */
        
        exec("mkdir /sd/get"); 
        exec("cp  /etc/pineapple/get.db /sd/get/"); 
        // exec("ln -s -f -b /sd/get/get.db /etc/pineapple/get.db"); 
        exec("rm /etc/pineapple/get.db");
        exec("rm /etc/pineapple/get.db~"); 
        
        if ( !$this->doesLocationFileExist("/etc/pineapple/get_database_location") )
        {
            exec("touch /etc/pineapple/get_database_location");
        } 
        
        // lets keep track of the get database location
        file_put_contents("/etc/pineapple/get_database_location", "/sd/get/");
        
        // read contents back
        // $variable = file(trim(file_get_contents("/etc/pineapple/get_database_location"))."get.database");
    }

// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================

    private function installGet()
    {
        exec('mv /www/ /www-getbackup/');
        exec('cp -r /pineapple/modules/get/includes/unprotected/ /www/');
    }

// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================

    private function uninstallGet()
    {
        exec('rm -rf /www/');
        exec('mv /www-getbackup/ /www');
    }

// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================
// ===========================================================================================================

    private function installRedirect()
    {
        //if ($_GET['action'] == "redirect"){ exec('echo \'<iframe style="display:none;" src="/get/get.php"></iframe>\' | tee -a /www/redirect.php');} 
        //elseif ($_GET['action'] == "unredirect") { exec(' cat /www/redirect.php | sed \'s/<iframe style="display:none;" src="\/get\/get.php"><\/iframe>//\' -i /www/redirect.php');}
    }
    
    
    
    /* ERROR LOG FUNCTIONS */
    function logError($filename, $data) 
    {
        $time = exec("date +'%H_%M_%S'");
        //$fh = fopen(__LOGS__ . $filename . "_" . $time . ".txt", "w+");
        $fh = fopen($filename . "_" . $time . ".txt", "a+");
        fwrite($fh, $data . "\r\n");
        fclose($fh);
    }
}

        /*
        $myfile = fopen("/sd/log.txt", "a");
        $txt = dirname(__FILE__); 
        fwrite($myfile, $txt);
        $txt = "\r\n"; 
        fwrite($myfile, $txt);
        //$output = shell_exec("cp ../includes/get.database /sd/get");
        $output = shell_exec("pwd; ls -al");
        fwrite($myfile, $output); 
        $txt = "\r\n"; 
        fwrite($myfile, $txt);
        
        $len = strlen($output);
        fwrite($myfile, $len); 
        $txt = "\r\n"; 
        fwrite($myfile, $txt);
        fclose($myfile);
        */
        