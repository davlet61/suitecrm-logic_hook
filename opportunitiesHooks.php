<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

//require_once('modules/EmailTemplates/EmailTemplate.php');

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

        private function getMessage($stage, $email)
            {   
                // Read the JSON file
                $json = file_get_contents('custom/modules/Opportunities/messages.json');

                // Decode the JSON file
                $messages = json_decode($json, true);
          		
                
                $filtered = array_filter($messages, fn($v) => strtolower($v['stage']) === strtolower($stage));

                $result = array_values($filtered)[0]['message'];
          		$msg = str_replace('example@example.com', $email, $result);

                return "$msg \nMvh Glass.no";
            }


        // AFTER SAVE
        public function notifyClient(&$bean, $event, $arguments)
            {   
                $relatedAccounts = $bean->get_linked_beans('accounts');
                
                $sea = new SugarEmailAddress;
                $primary = $sea->getPrimaryAddress($relatedAccounts[0]);

                $message = self::getMessage($bean->sales_stage, $primary);
                
                if(strlen($message) < 15) {
                  return $this->logger->fatal($bean->name . ': No message' . " -> $bean->sales_stage");
                }
                
                $body = array(
                    "email" => $primary,
                    "name" => $bean->name,
                    "phone" => $relatedAccounts[0]->phone_office,
                    "message" => $message
                );
                
                $ch = curl_init();
                $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                $curlopts = array(
                  CURLOPT_URL => $this->url,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => $payload,
                  CURLOPT_HTTPHEADER => array(
                      "Content-Type: application/json; charset=utf-8",
                      "secret_key: $this->secret",
                    ),
                );
                curl_setopt_array($ch, $curlopts);
                $output = curl_exec($ch);

                if ($output === false) {
                    $this->logger->fatal("SendNotifications: 82 => Curl error: " . curl_error($ch));
                }
                curl_close($ch);
            }

    }

?>