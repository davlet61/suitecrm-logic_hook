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
                switch ($stage) {
                    case 'Send_quote':
                        return 'We have sent you a quote';
                  
                    case 'Quote_follow_up':
                        return 'We have sent you a qoute a while ago, please do not hesitate to contact if you have any questions';
                  
                    case 'Proforma':
                        return 'We have now sent you a proforma invoice.';

                    default:
                        return '';
                }
            }


        // AFTER SAVE
        public function notifyClient(&$bean, $event, $arguments)
            { 

                $message = self::getMessage($bean->sales_stage);
                
                $product = array(
                    "customerEmail" => $bean->maincode,
                    "name" => $bean->name,
                    "phone" => $bean->price,
                    "message" => $message
                );

                // $ch = curl_init();
                // $payload = json_encode($product);
                // $curlopts = array(
                //   CURLOPT_URL => "$this->url",
                //   CURLOPT_RETURNTRANSFER => true,
                //   CURLOPT_ENCODING => "",
                //   CURLOPT_MAXREDIRS => 10,
                //   CURLOPT_TIMEOUT => 30,
                //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //   CURLOPT_CUSTOMREQUEST => "POST",
                //   CURLOPT_HTTPHEADER => array(
                //       "secret_key: $this->secret",
                //     ),
                // );
                // curl_setopt_array($ch, $curlopts);
                // $output = curl_exec($ch);

                // if ($output === false) {
                //     $this->logger->fatal("SendNotifications: 74 => Curl error: " . curl_error($ch));
                // }
                // curl_close($ch);
            }

    }

?>