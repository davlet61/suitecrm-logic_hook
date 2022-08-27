<?php

function getAccessTokens($baseUrl)
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
    error_log("Curl error: " . curl_error($ch));
  }
  curl_close($ch);
  return json_decode($output);
}

function getCustomerByName($token, $name, $baseUrl)
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

function deleteCustomerById($token, $id, $baseUrl)
{
  $ch = curl_init();
  $curlopts = array(
      CURLOPT_URL => "$baseUrl/customers/$id",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "DELETE",
      CURLOPT_HTTPHEADER => array("access_token: $token")
    );
  curl_setopt_array($ch, $curlopts);
  $output = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpCode === 404) {
    error_log("Curl error: " . json_decode($output)->error);
  }
  curl_close($ch);
  return $httpCode;
}


$url = 'http://localhost:3001/v1';
$as = 'Akkurat AS';

$tokens = getAccessTokens($url);
$tkn = $tokens->access_token;

$res = getCustomerByName($tkn, $as, $url);
$id = $res['data'][0]['id'];

// $del = deleteCustomerById($tkn, $id, $url);
file_put_contents('result.txt', print_r($res, true));
