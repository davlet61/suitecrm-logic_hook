<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 5).'/.env');

class Requests 
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
                if ($output === false) {
                    $this->logger->fatal("43 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                $this->tokens = json_decode($output, true);
                $this->expires = microtime(true) + $this->tokens['expires_in'];
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
                    $this->logger->fatal("63 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
                return json_decode($output, true);
            }

        private function deleteProductById($id)
            {
                $ch = curl_init();
                $curlopts = array(
                    CURLOPT_URL => "$this->url/poweroffice/products/$id",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "DELETE",
                    CURLOPT_HTTPHEADER => array("access_token: ".$this->tokens['access_token'])
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 404) {
                    $this->logger->fatal("82 => Curl error: " . json_decode($output)->error);
                }
                curl_close($ch);
                return $httpCode;
            }

        // AFTER SAVE
        public function addProductToPO(&$bean, $event, $arguments)
            { 
                $accessToken = $this->tokens['access_token'];
                
                $product = array(
                    "code" => $bean->maincode,
                    "name" => $bean->name,
                    "type" => $bean->type,
                    "unit" => $bean->part_number,
                    "description" => $bean->description,
                    "salesPrice" => $bean->price,
                    "costPrice" => $bean->cost,
                    "categoryName" => $bean->aos_product_category_name,
                    "categoryId" => $bean->aos_product_category_id,
                    "isActive" => true,
                );

                $productByCode = self::getProductByCode($bean->maincode);

                if ($productByCode['count'] > 0) {
                    $product['id'] = $productByCode['data'][0]['id'];
                }

                $curl = curl_init();
                $payload = json_encode($product);
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "$this->url/poweroffice/products",
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
                    $this->logger->fatal("131 => Curl error: " . curl_error($curl));
                }
                curl_close($curl);
            }


        // AFTER DELETE  
        public function deleteProductFromPO(&$bean, $event, $arguments)
            { 
                $accessToken = $this->tokens['access_token'];

                $productByCode = self::getProductByCode($bean->maincode);
                $id = $productByCode['data'][0]['id'];

                $res = self::deleteProductById($id);

                if ($res === 400) {
                    $this->logger->fatal("148 => Curl error: $res");
                }
                return $res;
            }

    }

?>