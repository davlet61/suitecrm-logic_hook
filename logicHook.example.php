<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class Requests 
    {

        function getAccessTokens($logger, $baseUrl)
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
                    $logger->fatal("Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                return json_decode($output);
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
                return json_decode($output);
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
                    $logger->fatal("Curl error: " . curl_error($ch));
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
                    $logger->fatal("Curl error: " . json_decode($output)->error);
                }
                curl_close($ch);
                return $httpCode;
            }

        // AFTER SAVE
        function addCustomerToPO($bean, $event, $arguments)
            { 
                $Logger = LoggerManager::getLogger(); 
                $url = $_SERVER['PO_URL'];

                $tokens = self::getAccessTokens($Logger, $url);
                $accessToken = $tokens->access_token;
                $sea = new SugarEmailAddress;
                $primary = $sea->getPrimaryAddress($bean);

                $customer = array(
                    "invoiceDeliveryType" => $primary ? 0 : 2,
                    "isVatFree" => false,
                    "invoiceEmailAddress" => $primary,
                    "invoiceEmailAddressCC" => "",
                    "useFactoring" => false,
                    "sendReminders" => true,
                    "doNotAddLatePaymentFees" => false,
                    "doNotAddLatePaymentInterest" => false,
                    "reminderEmailAddress" => "",
                    "transferToDebtCollectionAgency" => true,
                    "customerCreatedDate" => $bean->date_entered,
                    "useInvoiceFee" => true,
                    "name" => $bean->name,
                    "vatNumber" => $bean->phone_fax,
                    "since" => date('Y-m-j'),
                    "isPerson" => false,
                    "isActive" => true,
                    'mailAddress' => array(
                        'address1' => $bean->billing_address_street,
                        'zipCode' => $bean->billing_address_postalcode,
                        'city' => strtoupper($bean->billing_address_city),
                    ),
                    "streetAddress" => array(
                        'address1' => $bean->shipping_address_street,
                        'zipCode' => $bean->shipping_address_postalcode,
                        'city' => strtoupper($bean->shipping_address_city),
                    ),
                    "streetAddresses" => array(),
                    "emailAddress" => $primary,
                    "isArchived" => false,
                    "lastChanged" => $bean->date_modified,
                    "createdDate" => $bean->date_entered,
                    "phoneNumber" => $bean->phone_office,
                    "contactGroups" => array()
                );

                $customerByName = self::getCustomerByName($accessToken, $bean->name, $Logger, $url);

                if ($customerByName['count'] > 0) {
                    $customer['id'] = $customerByName['data'][0]['id'];
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


        // AFTER DELETE  
        function deleteCustomerFromPO($bean, $event, $arguments)
            { 
                $Logger = LoggerManager::getLogger(); 
                $url = 'https://api.glasserviceoslo.no/v1';

                $tokens = self::getAccessTokens($Logger, $url);
                $accessToken = $tokens->access_token;

                $customerByName = self::getCustomerByName($accessToken, $bean->name, $Logger, $url);
                $id = $customerByName['data'][0]['id'];

                $res = self::deleteCustomerById($accessToken, $id, $url);

                if ($res === 400) {
                    $Logger->fatal("Curl error: $res");
                }
                return $res;
            }

    }

?>