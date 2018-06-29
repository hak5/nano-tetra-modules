<?php namespace evilportal;

abstract class Portal
{
    protected $request;
    protected $response;
    protected $error;

    protected $AUTHORIZED_CLIENTS_FILE = "/tmp/EVILPORTAL_CLIENTS.txt";
    private $BASE_EP_COMMAND = 'module EvilPortal';

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function getResponse()
    {
        if (empty($this->error) && !empty($this->response)) {
            return $this->response;
        } elseif (empty($this->error) && empty($this->response)) {
            return array('error' => 'API returned empty response');
        } else {
            return array('error' => $this->error);
        }
    }

    /**
     * Run a command in the background and don't wait for it to finish.
     * @param $command: The command to run
     */
    protected function execBackground($command)
    {
        exec("echo \"{$command}\" | at now");
    }

    /**
     * Creates an iptables rule allowing the client to access the internet and writes them to the authorized clients.
     * Override this method to add other authorization steps validation.
     * @param $clientIP: The IP address of the client to authorize
     * @return bool: True if the client was successfully authorized otherwise false.
     */
    protected function authorizeClient($clientIP)
    {
        if (!$this->isClientAuthorized($clientIP)) {
            exec("iptables -t nat -I PREROUTING -s {$clientIP} -j ACCEPT");
//            exec("{$this->BASE_EP_COMMAND} add {$clientIP}");
            file_put_contents($this->AUTHORIZED_CLIENTS_FILE, "{$clientIP}\n", FILE_APPEND);
        }
        return true;
    }

    /**
     * Handle client authorization here.
     * By default it just checks that the redirection target is in the request.
     * Override this to perform your own validation.
     */
    protected function handleAuthorization()
    {
        if (isset($this->request->target)) {
            $this->authorizeClient($_SERVER['REMOTE_ADDR']);
            $this->onSuccess();
            $this->redirect();
        } elseif ($this->isClientAuthorized($_SERVER['REMOTE_ADDR'])) {
            $this->redirect();
        } else {
            $this->showError();
        }
    }

    /**
     * Where to redirect to on successful authorization.
     */
    protected function redirect()
    {
        header("Location: {$this->request->target}", true, 302);
    }

    /**
     * Override this to do something when the client is successfully authorized.
     * By default it just notifies the Web UI.
     */
    protected function onSuccess()
    {
        $this->execBackground("notify New client authorized through EvilPortal!");
    }

    /**
     * If an error occurs then do something here.
     * Override to provide your own functionality.
     */
    protected function showError()
    {
        echo "You have not been authorized.";
    }

    /**
     * Checks if the client has been authorized.
     * @param $clientIP: The IP of the client to check.
     * @return bool|int: True if the client is authorized else false.
     */
    protected function isClientAuthorized($clientIP)
    {
        $authorizeClients = file_get_contents($this->AUTHORIZED_CLIENTS_FILE);
        return strpos($authorizeClients, $clientIP);
    }
}
