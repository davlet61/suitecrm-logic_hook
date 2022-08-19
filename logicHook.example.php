<?php 

class SendRequest_LogicHook
     {
       public function sendRequest($event, $arguments)
       {    
            // SuiteCRM Logger
            $logger = LoggerManager::getLogger(); 
            $url = getenv('API_URL'); 
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            $output = curl_exec($ch);
            
            if ($output === false) {
              $logger->fatal("Curl error: " . curl_error($ch));
            }
            $logger->debug('Output' . $output);
            curl_close($ch);
         }

     }

  ?>
