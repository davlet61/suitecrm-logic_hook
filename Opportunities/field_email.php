<?php

$module = 'Opportunities';
$dictionary[$module]['fields']['email1'] = array(
    'name'        => 'email1',
    'vname'        => 'LBL_EMAIL',
    'group'=>'email1',
    'type'        => 'varchar',
    'function'    => array(
        'name'        => 'getEmailAddressWidget',
        'returns'    => 'html'
    ),
    'source'    => 'non-db',
    'studio' => array('editField' => true),
);
$dictionary[$module]['fields']['email_addresses_primary'] = array(
    'name' => 'email_addresses_primary',
    'type' => 'link',
    'relationship' =>strtolower($module).'_email_addresses_primary',
    'source' => 'non-db',
    'vname' => 'LBL_EMAIL_ADDRESS_PRIMARY',
    'duplicate_merge' => 'disabled',
);
$dictionary[$module]['fields']['email_addresses'] = array (
    'name' => 'email_addresses',
    'type' => 'link',
    'relationship' =>strtolower($module).'_email_addresses',
    'source' => 'non-db',
    'vname' => 'LBL_EMAIL_ADDRESSES',
    'reportable'=>false,
    'unified_search' => true,
    'rel_fields' =>array('primary_address' => array('type'=>'bool')),
);
$dictionary[$module]['relationships'][strtolower($module).'_email_addresses'] = array(
    'lhs_module'=> $module,
    'lhs_table'=>strtolower($module),
    'lhs_key' => 'id',
    'rhs_module'=> 'EmailAddresses',
    'rhs_table'=> 'email_addresses',
    'rhs_key' => 'id',
    'relationship_type'=>'many-to-many',
    'join_table'=> 'email_addr_bean_rel',
    'join_key_lhs'=>'bean_id',
    'join_key_rhs'=>'email_address_id',
    'relationship_role_column'=>'bean_module',
    'relationship_role_column_value'=>$module
);
$dictionary[$module]['relationships'][strtolower($module).'_email_addresses_primary'] = array(
    'lhs_module'=> $module,
    'lhs_table' =>strtolower($module),
    'lhs_key' => 'id',
    'rhs_module' => 'EmailAddresses',
    'rhs_table' => 'email_addresses',
    'rhs_key' => 'id',
    'relationship_type'=>'many-to-many',
    'join_table'=> 'email_addr_bean_rel',
    'join_key_lhs'=>'bean_id',
    'join_key_rhs'=>'email_address_id',
    'relationship_role_column'=>'primary_address',
    'relationship_role_column_value'=>'1'
);

?>
