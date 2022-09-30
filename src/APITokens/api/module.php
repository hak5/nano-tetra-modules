<?php namespace pineapple;

require_once('DatabaseConnection.php');

class APITokens extends Module
{
    private $dbConnection;

    const DATABASE = "/etc/pineapple/pineapple.db";

    public function __construct($request)
    {
        parent::__construct($request, __CLASS__);
        $this->dbConnection = new DatabaseConnection(self::DATABASE);
        $this->dbConnection->exec("CREATE TABLE IF NOT EXISTS api_tokens (token VARCHAR NOT NULL, name VARCHAR NOT NULL);");
    }

    public function getApiTokens()
    {
        $this->response = array("tokens" => $this->dbConnection->query("SELECT ROWID, token, name FROM api_tokens;"));
    }

    public function checkApiToken()
    {
        if (isset($this->request->token)) {
            $token = $this->request->token;
            $result = $this->dbConnection->query("SELECT token FROM api_tokens WHERE token='%s';", $token);
            if (!empty($result) && isset($result[0]["token"]) && $result[0]["token"] === $token) {
                $this->response = array("valid" => true);
            }
        }
        $this->response = array("valid" => false);
    }

    public function addApiToken()
    {
        if (isset($this->request->name)) {
            $token = hash('sha512', openssl_random_pseudo_bytes(32));
            $name = $this->request->name;
            $this->dbConnection->exec("INSERT INTO api_tokens(token, name) VALUES('%s','%s');", $token, $name);
            $this->response = array("success" => true, "token" => $token, "name" => $name);
        } else {
            $this->error = "Missing token name";
        }
    }

    public function revokeApiToken()
    {
        if (isset($this->request->id)) {
            $this->dbConnection->exec("DELETE FROM api_tokens WHERE ROWID='%s'", $this->request->id);
        } elseif (isset($this->request->token)) {
            $this->dbConnection->exec("DELETE FROM api_tokens WHERE token='%s'", $this->request->token);
        } elseif (isset($this->request->name)) {
            $this->dbConnection->exec("DELETE FROM api_tokens WHERE name='%s'", $this->request->name);
        } else {
            $this->error = "The revokeApiToken API call requires either a 'id', 'token', or 'name' parameter";
        }
    }

    public function route()
    {
        switch ($this->request->action) {
            case 'checkApiToken':
                $this->checkApiToken();
                break;

            case 'addApiToken':
                $this->addApiToken();
                break;

            case 'getApiTokens':
                $this->getApiTokens();
                break;

            case 'revokeApiToken':
                $this->revokeApiToken();
                break;

            default:
                $this->error = "Unknown action";
        }
    }
}
