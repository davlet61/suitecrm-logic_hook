<?php

require_once('modules/AOW_Actions/actions/actionBase.php');

class actionTwilioAction extends actionBase
    {
      function actionTwilioIntegration($id = '') 
          {
              parent::actionBase($id);
          }
    
      public function loadJS() 
          {
              parent::loadJS();
              return array();
              // $randomNumber = rand();
          }

      public function run_action(SugarBean $bean,$params = array(),$in_save = false)
          {
              $projectStatus = $bean->status;

              $field = $bean->getFieldDefinition($params['related_module_name']);

              $relateId = $field['id_name'];

              if($projectStatus == "Completed"){

              $relatedModule = BeanFactory::getBean($field['module'], $relateId);

              $relatedFieldId = $relatedModule->$params['related_module_fields'];

              $userBean = BeanFactory::getBean('Users', $relatedFieldId);

              $email = $userBean->email1;

              require_once('modules/Emails/Email.php');

              require_once('include/SugarPHPMailer.php');

              $emailObj = BeanFactory::newBean('Emails');

              $defaults = $emailObj->getSystemDefaultEmail();

              $mail = new SugarPHPMailer();

              $mail->setMailerForSystem();

              $mail->From = $defaults['email'];

              isValidEmailAddress($mail->From);

              $mail->FromName = $defaults['name'];

              $mail->Subject = $emailSubject;

              $mail->Body = $emailBody;

              $mail->IsHTML(true);

              $mail->prepForOutbound();

              $mail->AddAddress($email);

              $mail->Send();

              }

              return false;
            
          }         

      public function edit_display($line, SugarBean $bean = null, $params = array())
          {
              //Get Module
              $modulename = $bean->module_dir;

              //URL To Notify
              $urlNotifyLabel = '"'.translate('LBL_URL_NOTIFY_INFO','AOW_Actions').'"';

              $parameterFields = getModuleFieldsDropdown('Accounts', '', 0);

              $html .= "<fieldset>";

              $html .= "<div class='ParametersContainer'>";

              $html .= "<table class='tbl_action_method'>";

              $html .= "<tr rowspan = '4'><td><b>Related Module Fields:</b></td></tr>";

              $html .= "<tr rowspan = '4'>";

              $html .= "<td><b><select option='select' name='aow_actions_param[".$line."][action_method]'>";

              $html .= "<option selected='selected'>".$parameterFields."</option></select></td>";

              $html .= "<td><b><select option='select' name='aow_actions_param[".$line."][action_method]'>";

              $html .= "<option selected='selected'>".$parameterFields."</option></select></td>";

              $html .= "</table>";

              $html .= "</div>";

              return $html;
            
          }
    }

?>
