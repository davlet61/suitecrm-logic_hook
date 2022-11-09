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
              $randomNumber = rand();
          }

      public function run_action(SugarBeand $bean, $params = array(), $in_save = false) 
          {}

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