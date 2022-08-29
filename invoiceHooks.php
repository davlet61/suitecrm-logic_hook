<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 5).'/.env');

class InvoiceRequests 
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
                $this->app_key = $_ENV['PO_APP_KEY'];
                $this->client_key = $_ENV['PO_CLIENT_KEY'];
                $this->url = $_ENV['PO_URL'];
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

        // AFTER SAVE
        public function createInvoiceInPO(&$bean, $event, $arguments)
            { 
                $accessToken = $this->tokens['access_token'];
                $account = BeanFactory::getBean('Accounts', $bean->billing_account_id);
                $contact = BeanFactory::getBean('Contacts', $bean->billing_contact_id);
                $name= $account->name ?? $contact->name;
                $customer = self::getCustomerByName($name);
                $customerCode = $customer['data'][0]['code'];

                $invoice = array(
                    "id" => $bean->id,
                    "invoiceDeliveryType" => 0,
                    "customerCode" => $customerCode,
                    "totalAmount" => 0,
                    "netAmount" => 0,
                    "status" => 0,
                    "paymentTerms" => 14,
                    "outgoingInvoiceLines" => [],
                    "isInvoiceBeingProcessed" => false
                    );

                $curl = curl_init();
                $payload = json_encode($invoice);
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "$this->url/invoices",
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

    }

?>