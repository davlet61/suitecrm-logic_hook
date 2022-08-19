<?php

require_once 'functions.php';

// class SendRequest_LogicHook
//      {
//        public function sendRequest($event, $arguments)
//        {  
//             $logger = LoggerManager::getLogger(); 
//             $url = "https://api.glasserviceoslo.no/v1"; 
//             $ch = curl_init();
//             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//             curl_setopt($ch, CURLOPT_URL, $url);
//             $output = curl_exec($ch);
            
//             if ($output === false) {
//               $logger->fatal("Curl error: " . curl_error($ch));
//             }
//             $logger->debug('Output' . $output);
//             curl_close($ch);
//          }

//      }

class Requests {
  private $accessTokenInstance;
  private $tokens;
  private $expires;
  private $refreshedTokens;
  private $accessToken;
  private $url;

 function __construct() 
    {
      $this->accessTokenInstance = GetAccessToken::getInstance();
      $this->url = $this->accessTokenInstance->url;
      $this->tokens = $this->accessTokenInstance->getTokens();
      $this->expires = $this->accessTokenInstance->getExpiry();
      $this->refreshedTokens = $this->accessTokenInstance->refreshTokens();
      $this->accessToken = ($this->expires - time() < 1) ?  $this->refreshedTokens['access_token'] : $this->tokens['access_token'];
    }


  public function addCustomerToPO()
    {  
        $customer = array(
          'name' => 'Company Ltd. Inc.',
          'emailAddress' => 'example@mail.com',
          'address1' => '123 Main St',
          'city' => 'Anytown',
          'zipCode' => '12345',
          'countryCode' => 'US',
        );

        $payload = json_encode($customer);
        $ch = curl_init();
        curl_setopt_array($ch, array(
          CURLOPT_URL => $this->url . '/customers',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => array(
            "access_token: $this->accessToken",
            "content-type: application/json",
          ),
        ));
        $output = curl_exec($ch);
        
        if ($output === false) {
          print_r("Curl error: " . curl_error($ch));
        }
        curl_close($ch);
        return json_decode($output);
      }

public function getCustomerList()
      {  
          $ch = curl_init();
          curl_setopt_array($ch, array(
            CURLOPT_URL => $this->url . '/customers',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array("access_token: $this->accessToken"),
          ));
          $output = curl_exec($ch);
          
          if ($output === false) {
            print_r("Curl error: " . curl_error($ch));
          }
          curl_close($ch);
          return json_decode($output);
      }
    }

    $requests = new Requests();
    $newCustomer = $requests->addCustomerToPO();
    print_r($newCustomer);

?>