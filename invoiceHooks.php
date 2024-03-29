<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 5).'/.env');

class InvoiceRequests 
    {
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
                $this->url = $_ENV['POWERAPI_URL'];
                $ch = curl_init();
                
                $curlopts = array(
                    CURLOPT_URL => "$this->url/poweroffice/oauth",
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
                $output = curl_exec($ch);
                if (!$output) {
                    $this->logger->fatal("43 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                $this->tokens = json_decode($output, true);
                $this->expires = microtime(true) + $this->tokens['expires_in'];
            }

        private function getCustomerByName($name)
            {
                $ch = curl_init();
                $escapedParams = curl_escape($ch, $name);
                $curlopts = array(
                    CURLOPT_URL => "$this->url/poweroffice/customers?name=$escapedParams",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => array("access_token: ".$this->tokens['access_token'])
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);

                if ($output === false) {
                    $this->logger->fatal("63 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                return json_decode($output, true);
            }
            
        private function createCustomer($data)
            {
                $accessToken = $this->tokens['access_token'];
                $ch = curl_init();
                $curlopts = array(
                    CURLOPT_URL => "$this->url/poweroffice/customers",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_HTTPHEADER => array(
                        "access_token: $accessToken",
                        "content-type: application/json",
                    )
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);

                if (!$output) {
                    $this->logger->fatal("91 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                return json_decode($output, true);
            }
            
        private function getProductByCode($code)
            {
                $ch = curl_init();
                $curlopts = array(
                    CURLOPT_URL => "$this->url/poweroffice/products?code=$code",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => array("access_token: ".$this->tokens['access_token'])
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);

                if ($output === false) {
                    $this->logger->fatal("109 => Curl error: " . curl_error($ch));
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

                // Create Customer in PO if no existing
                if ($customer['count'] === 0) {
                    $customerData = array(
                        "invoiceDeliveryType" => $account->email1 ? 0 : 2,
                        "isVatFree" => false,
                        "invoiceEmailAddress" => $account->email1,
                        "invoiceEmailAddressCC" => "",
                        "useFactoring" => false,
                        "sendReminders" => true,
                        "doNotAddLatePaymentFees" => false,
                        "doNotAddLatePaymentInterest" => false,
                        "reminderEmailAddress" => "",
                        "transferToDebtCollectionAgency" => true,
                        "useInvoiceFee" => true,
                        "name" => $account->name,
                        "vatNumber" => $account->phone_fax,
                        "since" => date('Y-m-j'),
                        "isPerson" => false,
                        "isActive" => true,
                        'mailAddress' => array(
                            'address1' => $account->billing_address_street,
                            'zipCode' => $account->billing_address_postalcode,
                            'city' => strtoupper($account->billing_address_city),
                        ),
                        "streetAddress" => array(
                            'address1' => $account->shipping_address_street,
                            'zipCode' => $account->shipping_address_postalcode,
                            'city' => strtoupper($account->shipping_address_city),
                        ),
                        "streetAddresses" => array(),
                        "emailAddress" => $primary,
                        "isArchived" => false,
                        "phoneNumber" => $account->phone_office,
                        "contactGroups" => array()
                    );
                    
                    $payload = json_encode($customerData);
                    
                    $poCustomer = self::createCustomer($payload);
                    $customerCode = $poCustomer['data']['code'];
                }
                
                $items = array();
                
                $link = 'aos_products_quotes';
                
                // Add line items to the outgoing invoice
                if ($bean->load_relationship($link)) {
                    $lineItems = $bean->$link->getBeans();
                    
                    foreach ($lineItems as $lineItem) {
                        $product = BeanFactory::getBean('AOS_Products', $lineItem->product_id);
                        $desc = $lineItem->item_description ? "$lineItem->name - $lineItem->item_description" : $lineItem->name;
                        $productItem = array(
                            "description" => $desc,
                            "discountPercent" => intval($lineItem->product_discount)/100,
                            "productCode" => $product->maincode,
                            "quantity" => $lineItem->product_qty,
                            "unitCost" => $lineItem->product_unit_price,
                            "unitPrice" => $lineItem->product_list_price,
                            "vatRate" => $lineItem->vat ? intval($lineItem->vat)/100 : 0.25,
                        );
                        array_push($items, $productItem);
                    }
                    
                }
                
                // Add shipping to the invoice
                if ($bean->shipping_amount > 0) {
                    $frakt = array(
                        "productCode" => 7,
                        "quantity" => 1,
                        "description" => "Frakt og avgifter",
                        "unit" => "EA",
                        "unitOfMeasureCode" => 5,
                        "costPrice" => $bean->shipping_amount,
                        "salesPrice" => $bean->shipping_amount,
                        "vatCode" => 0.25,
                    );
                    array_push($items, $frakt);
                }
                
                $invoice = array(
                    "purchaseOrderNo" => $bean->quote_number,
                    "invoiceDeliveryType" => 0,
                    "customerCode" => $customerCode,
                    "customerEmail" => $account->email1,
                    "deliveryAddress1" => $bean->billing_address_street,
                    "deliveryAddressCity" => $bean->billing_address_city,
                    "deliveryAddressZipCode" => $bean->billing_address_postalcode,
                    "totalAmount" => $bean->total_amount,
                    "paymentTerms" => 14,
                    "outgoingInvoiceLines" => $items,
                    "isInvoiceBeingProcessed" => false
                );
                

                $curl = curl_init();
                $payload = json_encode($invoice);
                $curlopts = array(
                    CURLOPT_URL => "$this->url/poweroffice/invoices",
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
                );
                curl_setopt_array($curl, $curlopts);
                $output = curl_exec($curl);
                $json = json_decode($output, true);

                if (!$output) {
                    $this->logger->fatal("242 => Curl error: " . curl_error($curl));
                }
                
                if (!$json['success']) {
                    $this->logger->fatal($json);
                }
                
                curl_close($curl);
            }

    }

?>
