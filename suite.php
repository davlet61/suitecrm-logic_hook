<?php

class Requests {


   function addCustomerToPO($event, $arguments)
    {  
      require_once 'functions.php';
        
      $logger = LoggerManager::getLogger(); 
      $logger->debug('token: ');
      
      $accessTokenInstance = GetAccessToken::getInstance();
      $url = $accessTokenInstance->url;
      $tokens = $accessTokenInstance->getTokens();
      $expires = $accessTokenInstance->getExpiry();
      $refreshedTokens = $accessTokenInstance->refreshTokens();
      $accessToken = ($expires - time() < 1) ?  $refreshedTokens['access_token'] : $tokens['access_token'];
      
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
          CURLOPT_URL => $url . '/customers',
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
          $logger->fatal("Curl error: " . curl_error($ch));
        }
        curl_close($ch);
      }

 function getCustomerList($event, $arguments)
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

?>