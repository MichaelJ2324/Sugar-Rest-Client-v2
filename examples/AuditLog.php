<?php
/**
 * ©[2019] SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */

require_once 'include.php';

$SugarAPI = new \Sugarcrm\REST\Client\Sugar7API($server,$credentials);
try{
    if ($SugarAPI->login()){
        echo "Logged In: ";
        pre($SugarAPI->getAuth()->getToken());
        $Account = $SugarAPI->module('Accounts')->set("name","Audit Log Test");
        $Account->save();
        pre("Created Account: {$Account['id']}");
        $Account->set('phone_office','555-555-5555');
        $Account['name'] = 'Audit Log Test - Updated';
        $Account['assigned_user_id'] = 'seed_max_id';
        $Account->save();
        echo "Account Updated:";
        pre($Account->asArray());
        $Account->audit();
        echo "Audit Log: ";
        pre($Account->getResponse()->getBody());
    } else {
        echo "Could not login.";
        pre($SugarAPI->getAuth()->getActionEndpoint('authenticate')->getResponse());
    }
}catch (Exception $ex){
    echo "Error Occurred: ";
    pre($ex->getMessage());
    pre($ex->getTraceAsString());
}