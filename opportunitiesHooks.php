<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 5).'/.env');

class SendNotifications 
    {
        private $secret;
        private $url;
        private $logger;
        
        public function __construct()
            { 
                $this->logger = LoggerManager::getLogger();
                $this->secret = $_ENV['N8N_SECRET_KEY'];
                $this->url = $_ENV['N8N_WEBHOOK_URL'];
            }
      
        private function getMessage($stage)
            {   
                // Read the JSON file
                $json = file_get_contents('messages.json');

                // Decode the JSON file
                $messages = json_decode($json, true);
                
                $filtered = array_filter($messages, function($v) { 
                    return strtolower($v['stage']) === strtolower($stage);
                });

                $msg = $filtered[0]['message'];

                return $msg;
            }


        // AFTER SAVE
        public function notifyClient(&$bean, $event, $arguments)
            { 
                $relatedAccounts = $bean->get_linked_beans('accounts');
                  
                $sea = new SugarEmailAddress;
                $primary = $sea->getPrimaryAddress($relatedAccounts[0]);

                $message = self::getMessage($bean->sales_stage);

                if(!$message) {
                  return $this->logger->fatal('No message');
                }
                
                $body = array(
                    "email" => $primary,
                    "name" => $bean->name,
                    "phone" => $relatedAccounts[0]->phone_office,
                    "message" => $message
                );

                $ch = curl_init();
                $payload = json_encode($body);
                $curlopts = array(
                  CURLOPT_URL => "$this->url",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => $payload,
                  CURLOPT_HTTPHEADER => array(
                      "secret_key: $this->secret",
                    ),
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);

                if ($output === false) {
                    $this->logger->fatal("SendNotifications: 81 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
            }

    }

?>