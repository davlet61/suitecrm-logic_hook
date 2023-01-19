<?php

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class Relationships
    {
        // AFTER SAVE
        public function createRelatedRecords(&$bean, $event, $arguments)
            { 
                $logger = LoggerManager::getLogger();
                
                $sea = new SugarEmailAddress;
                $email = $sea->getPrimaryAddress($bean);
                $parts = explode(" ", $bean->name);
                $lastname = array_pop($parts);
                $firstname = implode(" ", $parts);
                
                $bean->load_relationship('opportunities');
                $opportunity = BeanFactory::newBean('Opportunities');
                $opportunity->name = $bean->billing_address_street;
                $opportunity->sales_stage = 'Inspection';
                $opportunity->description = $bean->description;
                $opportunity->assigned_user_id = '3986b4ab-5ed1-0a0c-5493-610cf58154c3';
                
                $opportunity->save();
                $bean->opportunities->add($opportunity);
                
                if (!$bean->phone_fax) {
                    $bean->load_relationship('contacts');
                    $relatedContact = BeanFactory::newBean('Contacts');
                    $relatedContact->first_name = $firstname;
                    $relatedContact->last_name = $lastname;
                    $relatedContact->email1 = $email;
                    $relatedContact->phone_work = $bean->phone_office;
                    $relatedContact->phone_mobile = $bean->phone_mobile;
                    $relatedContact->primary_address_street = $bean->billing_address_street;
                    $relatedContact->primary_address_city = $bean->billing_address_city;
                    $relatedContact->primary_address_country = 'NORGE';
                    $relatedContact->primary_address_postalcode = $bean->billing_address_postalcode;
                    
                    $relatedContact->save();
                    $bean->contacts->add($relatedContact);
                }
                
            }
            
        // AFTER DELETE
        public function deleteRelatedRecords(&$bean, $event, $arguments)
            {
                $logger = LoggerManager::getLogger();
                
                $relatedContacts = $bean->get_linked_beans('contacts');
                $relatedOpportunities = $bean->get_linked_beans('opportunities');

                foreach ( $relatedContacts as $contact ) {
                  $contact->mark_deleted($contact->id);
                  $contact->save();
                }
        		
                foreach ( $relatedOpportunities as $opportunity ) {
                  $opportunity->mark_deleted($opportunity->id);
                  $opportunity->save();
                }
            }
            
    }

?>