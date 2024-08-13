<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/EmailTemplates/EmailTemplate.php');

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

    private function getEmailTemplate($stage, $accountId)
    {   
        $template = new EmailTemplate();
        $tilbud = $template->retrieve_by_string_fields(array('name' => 'Tilbud','type'=>'email'));
        
        $beanArray = ['Accounts' => $accountId];

        $subject = $template->parse_template($template->subject, $beanArray);
        $body = $template->parse_template($template->body, $beanArray);
    }

    // AFTER SAVE
    public function notifyClient(&$bean, $event, $arguments)
    { 
        // Check if the processed_stage is equal to the current stage
        if ($bean->processed_stage_c === $bean->sales_stage) {
            return;
        }

        $relatedAccounts = $bean->get_linked_beans('accounts');
        
        $sea = new SugarEmailAddress;
        $primary = $sea->getPrimaryAddress($relatedAccounts[0]);
        $phone = $relatedAccounts[0]->phone_office;

        // Create a unique identifier for this notification
        $notificationId = md5($bean->id . $bean->sales_stage . $phone);

        // Check if this notification has already been sent
        if ($this->isNotificationSent($bean, $notificationId)) {
            $this->logger->info("SendNotifications: Notification already sent. Skipping. ID: {$notificationId}");
            return;
        }

        $message = self::getMessage($bean->sales_stage, $primary);
        
        if(strlen($message) < 15) {
            return;
        }
        
        $body = array(
            "email" => $primary,
            "name" => $bean->name,
            "phone" => $phone,
            "message" => $message
        );
        
        $ch = curl_init();
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        $curlopts = array(
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json; charset=utf-8",
                "secret_key: $this->secret"
            ),
        );
        curl_setopt_array($ch, $curlopts);
        $output = curl_exec($ch);
        if ($output === false) {
           $this->logger->fatal("SendNotifications: 103 => Curl error: " . curl_error($ch));
        } else {
            // Mark this notification as sent
            $this->markNotificationSent($bean, $notificationId);
        }
        curl_close($ch);
        
        $bean->processed_stage_c = $bean->sales_stage;
        $bean->save();
    }

    private function isNotificationSent($bean, $notificationId)
    {
        // Check if the notification ID exists in the sent_notifications field
        $sentNotifications = json_decode($bean->sent_notifications_c, true) ?: array();
        return in_array($notificationId, $sentNotifications);
    }

    private function markNotificationSent(&$bean, $notificationId)
    {
        // Get the current sent notifications
        $sentNotifications = json_decode($bean->sent_notifications_c, true) ?: array();
        
        // Add the new notification ID if it doesn't already exist
        if (!in_array($notificationId, $sentNotifications)) {
            $sentNotifications[] = $notificationId;
        }
        
        // Update the bean with the new array
        $bean->sent_notifications_c = json_encode($sentNotifications);
        
        // Save the bean, but don't trigger the logic hook again
        $bean->processed_stage_c = $bean->sales_stage;  // Set this to prevent recursion
        $bean->save(false);  // The false parameter prevents the logic hooks from running again
    }
}
?>
