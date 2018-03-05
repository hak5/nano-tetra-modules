<?php 

// Define the pineapple namespace
namespace pineapple;

// Upload Directory where we store vpn_configs
define('__UPLOAD__', "/root/vpn_config/");

// File upload form that the angular function makes a call to
if (!empty($_FILES)) {
	$response = [];
	foreach ($_FILES as $file) {
		$tempPath = $file[ 'tmp_name' ];
		$name = $file['name'];
		$type = pathinfo($file['name'], PATHINFO_EXTENSION);
		
		// Do not accept any file other than .ovpn
		if ($type != "ovpn") {
			continue;
		}
		
		// Ensure the upload directory exists
		if (!file_exists(__UPLOAD__)) {
			if (!mkdir(__UPLOAD__, 0755, true)) {
                $response[$name] = "Failed. Unable to upload because vpn_certs directory does not exist/could not be created!";
                continue;
			}
		}
		
		$uploadPath = __UPLOAD__ . $name;
		$res = move_uploaded_file( $tempPath, $uploadPath );
		
		if ($res) {
			$response[$name] = "Success";
		} else {
			$response[$name] = "Failed";
		}
	}
    echo json_encode($response);
    
    die();
}





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
            case 'initializeModule':
                $this->initializeModule();
                break;
            case 'handleDependencies':
                $this->handleDependencies();
                break;
            case 'checkDependencies':
                $this->checkDependencies();
                break;
        }
    }


    // Checks the dependencies using the pineapple API functions 
    private function checkDependencies(){

        if($this->checkDependency('openvpn')){
            $installLabel = 'success';
            $installLabelText = 'Installed';
        }else{
            $installLabel = 'danger';
            $installLabelText = 'Not Installed';
        }
         
        $this->response = array("success" => true,
                                "label" => $installLabel,
                                "text"   => $installLabelText);

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
    private function handleDependencies(){
    

        if($this->checkDependency('openvpn')){
            exec('opkg remove openvpn-openssl');
            $messsage = "Dependencies should now be removed! Note: the vpn_config directory is NOT removed in this process. Please wait for the page to refresh...";
        }else{
            $this->installDependency('openvpn-openssl');
            $messsage = "Depedencies should now be installed! Please wait for the page to refresh...";
        }
         
        $this->response = array("success" => true,
                                "content" => $messsage);
    }

    // Builds the openvpn command string and calls it to star the VPN
    private function startVPN(){

        $inputData = $this->request->data;

        $open_vpn_cmd = "openvpn --config ";
        
        if($inputData[0] != ''){
            $config_name = $inputData[0];
            $open_vpn_cmd .= "/root/vpn_config/" . $config_name . " ";
        }else{
            $this->response = array("success" => false,
                                    "content" => "Please specify a VPN config name..");
            return;
        }

        if($inputData[1] != ''){

            //Create password file for openvpn command to read in
            $config_pass = $inputData[1];
            $pass_file = fopen("/tmp/vpn_pass.txt", "w");
            fwrite($pass_file, $config_pass);
            fclose($pass_file);
            $open_vpn_cmd .= "--auth-nocache --askpass /tmp/vpn_pass.txt ";
        }

        if($inputData[2] != ''){
            $openvpn_flags = $inputData[2];
            $open_vpn_cmd .= $openvpn_flags;
        }

        
        if($inputData[3] == true){
        //Share VPN With Clients Connecting
            $this->execBackground("iptables -t nat -A POSTROUTING -s 172.16.42.0/24 -o tun0 -j MASQUERADE");
            $this->execBackground("iptables -A FORWARD -s 172.16.42.0/24 -o tun0 -j ACCEPT");
            $this->execBackground("iptables -A FORWARD -d 172.16.42.0/24 -m state --state ESTABLISHED,RELATED -i tun0 -j ACCEPT");
        }

        $result = $this->execBackground($open_vpn_cmd);
        
        $this->response = array("success" => true,
                                "content" => "VPN Running... ");
    }


    // Calls pkill to kill the OpenVPN process and stop the VPN
    private function stopVPN(){

        //Remove password file that could have been created, don't want any creds lying around ;)
        unlink("/tmp/vpn_pass.txt");

        exec("pkill openvpn");

        $this->response = array("success" => true,
                                "content" => "VPN Stopped...");
    }

  
}






