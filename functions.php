<?php

class GetAccessToken {
  private static $instance = null;
  private $ch;
  private $curlopts;
  private $output;
  private $expires;
  private $tokens;
  
  private $app_key = '9e33d5d9-46df-42dc-9f9c-196ce48ed91f';
  private $client_key = 'd23d7c8e-e068-4d03-bca6-ac5e8d026975';
  public $url = 'https://api.glasserviceoslo.no/v1';
   
  private function __construct()
  {
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