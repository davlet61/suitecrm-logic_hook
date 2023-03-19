<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__, 5).'/.env');

class SendNotifications 
    {
        private $logger;
        
        public function __construct()
            { 
                $this->logger = LoggerManager::getLogger();
            }

        private function getLatestQuoteAmount(&$opportunityBean)
            {
                $quotes = $opportunityBean->get_linked_beans('quotes', 'Quote');
                $latestQuote = null;
                foreach ($quotes as $quote) {
                    if ($latestQuote === null || $latestQuote->date_quote_expected < $quote->date_quote_expected) {
                       $latestQuote = $quote;
                    }
                }
                if ($latestQuote !== null) {
                    return $latestQuote->amount;
                }
                return null;
            }
        
        // AFTER SAVE
        public function setOpportunityAmount(&$bean, $event, $arguments) 
            {
                $quoteAmount = self::getLatestQuoteAmount($bean);
                if ($quoteAmount != null) {
                    $bean->amount = $quoteAmount;
                }
            }

    }

?>