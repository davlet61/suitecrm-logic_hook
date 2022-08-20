<?php

$currentEnv = $_SERVER['_'];

if (!str_contains($currentEnv, 'doppler')) {
  require_once 'vendor/autoload.php';

  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
}


class GetAccessToken {
  private static $instance = null;
  private $ch;
  private $curlopts;
  private $output;
  private $expires;
  private $tokens;
  private $app_key;
  private $client_key;
  public $url;
   
  private function __construct()
  { 
    $this->app_key = $_SERVER['PO_APP_KEY'];
    $this->client_key = $_SERVER['PO_CLIENT_KEY'];
    $this->url = 'http://localhost:3001/v1';
    $this->ch = curl_init();
    $this->curlopts = array(
      CURLOPT_URL => $this->url . '/oauth',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_HTTPHEADER => array(
      "application_key: $this->app_key",
      "client_key: $this->client_key",
      ),
    );
    curl_setopt_array($this->ch, $this->curlopts);
    $this->output = curl_exec($this->ch);
    if ($this->output === false) {
      print_r("Curl error: " . curl_error($this->ch));
    }
    curl_close($this->ch);
    $this->tokens = json_decode($this->output, true);
    $this->expires = microtime(true) + $this->tokens['expires_in'];
  }
  
  public static function getInstance()
  {
    if(!self::$instance)
    {
      self::$instance = new GetAccessToken();
    }
   
    return self::$instance;
  }
  
  public function getTokens()
  {
    return $this->tokens;
  }

  public function getExpiry()
  {
    return $this->expires;
  }

  public function refreshTokens()
  {
    curl_setopt($this->ch, CURLOPT_URL, $this->url.'/oauth/refresh');
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("refresh_token: ".$this->tokens['refresh_token']));
    $this->output = curl_exec($this->ch);
    if ($this->output === false) {
      print_r("Curl error: " . curl_error($this->ch));
    }
    curl_close($this->ch);
    $this->tokens = json_decode($this->output, true);
    $this->expires = microtime(true) + $this->tokens['expires_in'];
    return $this->tokens;
  }
}

?>