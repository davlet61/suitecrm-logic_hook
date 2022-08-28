<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class Requests 
    {
        private $output;
        private $expires;
        private $tokens;
        private $app_key;
        private $client_key;
        private $url;
        private $logger;
        
        public function __construct()
            { 
                $this->logger = LoggerManager::getLogger();
                $this->app_key = $_SERVER['PO_APP_KEY'];
                $this->client_key = $_SERVER['PO_CLIENT_KEY'];
                $this->url = $_SERVER['PO_URL'];
                $ch = curl_init();
                $curlopts = array(
                    CURLOPT_URL => "$this->url/oauth",
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
                curl_setopt_array($ch, $curlopts);
                $this->output = curl_exec($ch);
                if ($this->output === false) {
                    $this->logger->fatal("Curl error: " . curl_error($ch));
                }
                curl_close($this->ch);
                $this->tokens = json_decode($this->output, true);
                $this->expires = microtime(true) + $this->tokens['expires_in'];
            }


        private function getCustomerByName($name)
            {
                $ch = curl_init();
                $escapedParams = curl_escape($ch, $name);
                $curlopts = array(
                    CURLOPT_URL => "$this->url/customers?name=$escapedParams",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => array("access_token: ".$this->tokens['access_token'])
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);

                if ($output === false) {
                    $this->logger->fatal("Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                return json_decode($output, true);
            }

        private function deleteCustomerById($id)
            {
                $ch = curl_init();
                $curlopts = array(
                    CURLOPT_URL => "$this->url/customers/$id",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "DELETE",
                    CURLOPT_HTTPHEADER => array("access_token: ".$this->tokens['access_token'])
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 404) {
                    $this->logger->fatal("Curl error: " . json_decode($output)->error);
                }
                curl_close($ch);
                return $httpCode;
            }

        // AFTER SAVE
        public function addCustomerToPO(&$bean, $event, $arguments)
            { 
                $accessToken = $this->tokens['access_token'];
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

                $customerByName = self::getCustomerByName($bean->name);
                $this->logger->fatal("id: " . $customerByName['data'][0]['id']);

                if ($customerByName['count'] > 0) {
                    $customer['id'] = $customerByName['data'][0]['id'];
                }

                $curl = curl_init();
                $payload = json_encode($customer);
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "$this->url/customers",
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
                    $this->logger->fatal("Curl error: " . curl_error($curl));
                }
                curl_close($curl);
            }


        // AFTER DELETE  
        public function deleteCustomerFromPO(&$bean, $event, $arguments)
            { 
                $accessToken = $this->tokens['access_token'];

                $customerByName = self::getCustomerByName($bean->name);
                $id = $customerByName['data'][0]['id'];

                $res = self::deleteCustomerById($id);

                if ($res === 400) {
                    $this->logger->fatal("Curl error: $res");
                }
                return $res;
            }

    }

?>