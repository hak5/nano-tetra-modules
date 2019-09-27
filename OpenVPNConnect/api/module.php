<?php 

// Define the pineapple namespace
namespace pineapple;

// Upload Directory where we store vpn_configs
define('__UPLOAD__', "/root/vpn_config/");

/* Main module class for OpenVPNConnect */
class OpenVPNConnect extends Module{

    // Set up our routes for our angular functions to call
    public function route(){

        switch ($this->request->action) {

            case 'startVPN':
                $this->startVPN();
                break;
            case 'stopVPN':
                $this->stopVPN();
                break;
            case 'checkVPNStatus':
                $this->checkVPNStatus();
                break;
            case 'initializeModule':
                $this->initializeModule();
                break;
            case 'handleDependencies':
                $this->handleDependencies(false);
                break;
            case 'handleDependenciesSDCard':
                $this->handleDependenciesSDCard();
                break;
            case 'checkDependencies':
                $this->checkDependencies();
                break;
            case 'uploadFile':
                $this->uploadFile();
                break;
        }
    }


    // Checks the dependencies using the pineapple API functions 
    private function checkDependencies(){
        $installedFlag = false;
        if($this->checkDependency('openvpn')){
            $installLabel = 'success';
            $installLabelText = 'Installed';
            $installButtonWidth = "90px";
            $installLabelSDText = "Installed (SD Card)";
            $installedFlag = true;
        }else{
            $installLabel = 'danger';
            $installLabelText = 'Not Installed (Local Storage)';
            $installButtonWidth = "210px";
            $installLabelSDText = "Not Installed (SD Card)";
        }
         
        $this->response = array("success" => true,
                                "label" => $installLabel,
                                "text"  => $installLabelText,
                                "buttonWidth" => $installButtonWidth,
                                "textSD" => $installLabelSDText,
                                "installed" => $installedFlag);

    }

    // Initializes the module by checking for/creating the required vpn_config directory in /root
    private function initializeModule(){

        $result = exec('cd /root && ls | grep vpn_config');

        if($result == 'vpn_config'){
            $result = "VPN Connect is ready!";
        }else{
            $this->execBackground('cd /root && mkdir vpn_config');

            $result = exec('cd /root && ls | grep vpn_config');

            if($result == 'vpn_config'){
                $result = "VPN Connect is ready!";
            }else{
                $result = "VPN Connect setup failed :(";
            }
        }


        //Get Available Certs

        $certs = preg_grep('/^([^.])/', scandir("/root/vpn_config/"));
        $cert_arr = array();
        foreach ($certs as $cert){
            array_push($cert_arr, (object)array('name' => $cert));
        }

        $this->response = array("success" => true,
                                "content" => $result,
                                "certs" => $cert_arr);

    }

    // Handles dependency installation and removal
    private function handleDependencies($sd){

        if($this->checkDependency('openvpn')){
            $this->execBackground('opkg remove openvpn-openssl');
            $messsage = "Dependencies should now be removed! Note: the vpn_config directory is NOT removed in this process. Please wait for the page to refresh...";
        }else{
            if($sd){
                $this->execBackground('opkg update');
                $this->execBackground('opkg install openvpn-openssl --dest sd');
                $messsage = "Depedencies should now be installed! (Installed to SD card) Please wait for the page to refresh...";
            }else{
                $this->installDependency('openvpn-openssl');
                $messsage = "Depedencies should now be installed! (Installed to local storage) Please wait for the page to refresh...";
            }

        }
         
        $this->response = array("success" => true,
                                "content" => $messsage,
                                "test" => $sd);
    }

    // Helper function to handle dependency installation and removal for sd card. Passes the SD flag to the real handleDependencies() function
    private function handleDependenciesSDCard(){

        $sd = true;

        return handleDependencies($sd);

    }


    // Checks whether or not OpenVPN is currently running
    private function checkVPNStatus(){
        $result = exec("pgrep openvpn");

        if($result){
            $this->response = array("success" => true,
            "content" => "VPN Running...");
            return;
        }

        $this->response = array("success" => true,
        "content" => "VPN Stopped...");

    }

    // Builds the openvpn command string and calls it to start the VPN
    private function startVPN(){

        $inputData = $this->request->data;

        $open_vpn_cmd = "openvpn --log /pineapple/modules/OpenVPNConnect/log/vpn.log --status /pineapple/modules/OpenVPNConnect/log/status.log --config ";
        
        if($inputData[0] != ''){
            $config_name = escapeshellcmd($inputData[0]);
            $open_vpn_cmd .= "/root/vpn_config/" . $config_name . " ";
        }else{
            $this->response = array("success" => false,
                                    "content" => "Please specify a VPN config name..");
            return;
        }


        if($inputData[1] != '' && $inputData[2] != ''){
            //Create auth.txt file for openvpn command to read in
            $config_user = $inputData[1];
            $config_pass = $inputData[2];
            $config_string = $config_user . PHP_EOL . $config_pass;
            $auth_file = fopen("/tmp/vpn_auth.txt", "w");
            fwrite($auth_file, $config_string);
            fclose($auth_file);
            $open_vpn_cmd .= "--auth-nocache --auth-user-pass /tmp/vpn_auth.txt ";

        }else if($inputData[2] != ''){

            //Create password file for openvpn command to read in
            $config_pass = $inputData[2];
            $pass_file = fopen("/tmp/vpn_pass.txt", "w");
            fwrite($pass_file, $config_pass);
            fclose($pass_file);
            $open_vpn_cmd .= "--auth-nocache --askpass /tmp/vpn_pass.txt ";
        }


        if($inputData[3] != ''){
            $openvpn_flags = escapeshellcmd($inputData[3]);
            $open_vpn_cmd .= $openvpn_flags;
        }

        
        if($inputData[4] == true){
        //Share VPN With Clients Connecting
            $gateway = $this->uciGet("network.lan.gateway");
            $netmask = $this->uciGet("network.lan.netmask");

            $this->execBackground("iptables -t nat -A POSTROUTING -s ". $gateway ."/". $netmask. " -o tun0 -j MASQUERADE");
            $this->execBackground("iptables -A FORWARD -s ". $gateway ."/". $netmask . " -o tun0 -j ACCEPT");
            $this->execBackground("iptables -A FORWARD -d ". $gateway ."/". $netmask ." -m state --state ESTABLISHED,RELATED -i tun0 -j ACCEPT");
        }

        $result = $this->execBackground($open_vpn_cmd);
        
        $this->response = array("success" => true,
                                "content" => "VPN Running... ");
    }


    // Calls pkill to kill the OpenVPN process and stop the VPN
    private function stopVPN(){

        //Remove any creds files that could have been created, don't want any creds lying around ;)
        unlink("/tmp/vpn_auth.txt");
        unlink("/tmp/vpn_pass.txt");

        //Delete any iptable rules that may have been created for sharing connection with clients                
        $gateway = $this->uciGet("network.lan.gateway");
        $netmask = $this->uciGet("network.lan.netmask");

        $this->execBackground("iptables -t nat -D POSTROUTING -s ". $gateway ."/". $netmask. " -o tun0 -j MASQUERADE");
        $this->execBackground("iptables -D FORWARD -s ". $gateway ."/". $netmask . " -o tun0 -j ACCEPT");
        $this->execBackground("iptables -D FORWARD -d ". $gateway ."/". $netmask ." -m state --state ESTABLISHED,RELATED -i tun0 -j ACCEPT");
        
        //Kill openvpn
        $this->execBackground("pkill openvpn");

        $this->response = array("success" => true,
                                "content" => "VPN Stopped...");
    }

    // Uploads the .ovnp recieved from the service
    private function uploadFile(){
                   
            $inputData = $this->request->file;

            $fileName = $inputData[0];

            $file = base64_decode($inputData[1]);

            $response = [];
                
            $name = $fileName;
            $type = pathinfo($fileName, PATHINFO_EXTENSION);
                    
            // Do not accept any file other than .ovpn
            if ($type != "ovpn") {
                $this->response = array("success" => false);
                return;
            }
                    
            // Ensure the upload directory exists
            if (!file_exists(__UPLOAD__)) {
                if (!mkdir(__UPLOAD__, 0755, true)) {
                    $response[$name] = "Failed. Unable to upload because vpn_certs directory does not exist/could not be created!";
                        $this->response = array("success" => false);
                        return;
                    }
                }
                    
            $uploadPath = __UPLOAD__ . $name;
            $res = file_put_contents( $uploadPath, $file );
                    
            if ($res) {
                $response[$name] = true;
            } else {
                $response[$name] = false;
            }
        

            $this->response = array("success" => $response[$name]);
                
    }
    

  
}






