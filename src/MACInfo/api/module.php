<?php namespace pineapple;


/* The class name must be the name of your module, without spaces. */
/* It must also extend the "Module" class. This gives your module access to API functions */
class MACInfo extends Module
{
    public function route()
    {
        switch ($this->request->action) {
            case 'getMACInfo':
            $this->getMACInfo($this->request->moduleMAC);
            break;
        }
    }

    private function getMACInfo($mac)
    {
        if($this->IsValidMAC($mac)){
            $url = "https://macvendors.co/api/" . $mac . "/JSON";
            $retJSON = file_get_contents($url);
            if($retJSON != false){
                $mInfo = json_decode($retJSON);
                if(isset($mInfo) && isset($mInfo->result) && $mInfo->result->error != ""){
                    $this->response = array("success" => false, "error" => $mInfo->result->error);
                }
                else{
                    $this->response = array("success" => true,
                                            "company" => $mInfo->result->company,
                                            "macprefix" => $mInfo->result->mac_prefix,
                                            "address" => $mInfo->result->address,
                                            "country" => $mInfo->result->country,
                                            "type" => $mInfo->result->type
                    );
                }
            }
            else{ $this->response = array("success" => false, "error" => "Error reading contents from: " . $url); }
        }
        else{ $this->response = array("success" => false, "error" => "Invalid MAC Address format"); }
    }
    private function IsValidMAC($mac) {
        $pregResult = preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac);
        return ($pregResult != 0 && $pregResult != NULL);
    }
}
