<?php

// if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class Requests {
  
    
 function getAccessTokens($log, $baseUrl)
  {
    $app_key = $_SERVER['PO_APP_KEY'];
    $client_key = $_SERVER['PO_CLIENT_KEY'];
    $ch = curl_init();
    $curlopts = array(
      CURLOPT_URL => "$baseUrl/oauth",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_HTTPHEADER => array(
      "application_key: $app_key",
      "client_key: $client_key",
      ),
    );
    curl_setopt_array($ch, $curlopts);
    $output = curl_exec($ch);
    if ($output === false) {
      $log->fatal("Curl error: " . curl_error($ch));
    }
    curl_close($ch);
    return $output;
  }
  

 function refreshTokens($refreshToken, $logger, $baseUrl)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/oauth/refresh");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("refresh_token: $refreshToken"));
    $output = curl_exec($ch);
    if ($output === false) {
      $logger->fatal("Curl error: " . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($output, true);
  }

  function getCustomerByName($token, $name, $logger, $baseUrl)
    {
      $ch = curl_init();
      $escapedParams = curl_escape($ch, $name);
      $curlopts = array(
          CURLOPT_URL => "$baseUrl/customers?name=$escapedParams",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => array("access_token: $token")
        );
      curl_setopt_array($ch, $curlopts);
      $output = curl_exec($ch);
    
      if ($output === false) {
        error_log("Curl error: " . curl_error($ch));
      }
      curl_close($ch);
      return json_decode($output, true);
    }


   function addCustomerToPO($bean, $event, $arguments)
    { 
        $Logger = LoggerManager::getLogger(); 
        $url = 'http://localhost:3001/v1';
    
        $tokens = self::getAccessTokens($Logger, $url);
        $accessToken = json_decode($tokens)->access_token;

        $customer = array(
          'name' => $bean->name,
          'emailAddress' => $bean->email,
          'mailAddress' => array(
              'address1' => $bean->billing_address_street,
              'zipCode' => $bean->billing_address_postalcode,
              'city' => strtoupper($bean->billing_address_city),
          )
        );

        $customerByName = self::getCustomerByName($accessToken, $bean->name, $Logger, $url);
        if ($customerByName->count > 0) {
          $customer['id'] = $customerByName->id;
        }
    
        $curl = curl_init();
        $payload = json_encode($customer);
        $Logger->fatal($payload);
        curl_setopt_array($curl, array(
          CURLOPT_URL => "$url/customers",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => array(
            "access_token: $accessToken",
            "content-type: application/json",
          ),
        ));
        $output = curl_exec($curl);
        
        if ($output === false) {
          $Logger->fatal("Curl error: " . curl_error($curl));
        }
        curl_close($curl);
      }

    }

?>