<?php

/* Copyright 2008 Timothy White */

/*  This file is part of GRASE Hotspot.

    http://hotspot.purewhite.id.au/

    GRASE Hotspot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    GRASE Hotspot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GRASE Hotspot.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'includes/session.inc.php';
require_once 'includes/misc_functions.inc.php';
require_once 'includes/database_functions.inc.php';

$error = array();
$success = array();

// Options for Chilli Config that can be more than 1
$multichillioptions = array(
    'uamallowed' => array(
        "label" => T_("Walled Garden allowed hosts"),
        "description" => T_("IP's and Hostnames that are accesible without logging in. DNS Lookup is only done at startup time so not suitable for domains with Round Robin IP Addresses"),
        "type" => "string"),
    'uamdomain' => array(
        "label" => T_("Walled Garden allowed domains"),
        "description" => T_("Domains (and their subdomains) that are accesible without logging in."),
        "type" => "string"),           
    );
    
// Options for Chilli Config that can only be one
$singlechillioptions = array(
    'macpasswd' => array(
        "label" => T_("MAC Auth Password"),
        "description" => T_("The MAC Password used to autologin Computer Accounts. Change this to something obscure as it can be used to login using a known MAC address as the username."),
        "type" => "string"),
    'defidletimeout' => array(
        "label" => T_("Default Session Idle Timeout"),
        "description" => T_("Default Idle Timeout for sessions. Logout after this number of seconds have passed without any traffic."),
        "type" => "int")
    );    
    
load_chillioptions();   

if(isset($_POST['submit']))
{
    
    foreach($singlechillioptions as $singleoption => $attributes)
    {
        switch ($attributes['type'])
        {
            case "string":
                $postvalue = trim(clean_text($_POST[$singleoption]));
                break;
            case "int":
                $postvalue = trim(clean_int($_POST[$singleoption]));
                break;
            case "number":
                $postvalue = trim(clean_number($_POST[$singleoption]));
                break;
                
        }
        
        if($postvalue != $attributes['value'])
        {
            // TODO: Special case to change all machine account passwords
            if($singleoption == 'macpasswd')
            {
                $machineaccounts = DatabaseFunctions::getInstance()->getUsersByGroup(MACHINE_GROUP_NAME);
                foreach($machineaccounts as $machine)
                {
                    database_change_password($machine, $postvalue);
                }
            }
            
            // Update options in database
            DatabaseFunctions::getInstance()->setPortalConfigSingle($singleoption, $postvalue);
            $success[] = sprintf(
                T_("%s portal config option update"),
                $attributes['label']);
        }
        
    }
    
    foreach($multichillioptions as $multioption => $attributes)
    {
        $postvalue = array();
        foreach($_POST[$multioption] as $value)
        {
            switch ($attributes['type'])
            {
                case "string":
                    $postvalue[] = trim(clean_text($value));
                    break;
                case "int":
                    $postvalue[] = trim(clean_int($value));
                    break;
                case "number":
                    $postvalue[] = trim(clean_number($value));
                    break;
                    
            }
        
//        if($postvalue != $attributes['value'])
//        {
//        }
        }
        $postvalue = array_filter($postvalue);
        sort($postvalue);        
        sort($attributes['value']);
     
        if($postvalue != $attributes['value'])
        {
            DatabaseFunctions::getInstance()->delPortalConfig($multioption);
            foreach($postvalue as $value)
            {
                DatabaseFunctions::getInstance()->setPortalConfigMulti($multioption, $value);
            }
            $success[] = sprintf(
                T_("%s portal config option update"),
                $attributes['label']);
        
        }

        
    }
        
    // Call validate&change functions for changed items
    load_chillioptions(); // Reload due to changes in POST    
}

	
if(sizeof($error) > 0) $smarty->assign("error", $error);	
if(sizeof($success) > 0) $smarty->assign("success", $success);

function load_chillioptions()
{
    global $multichillioptions, $singlechillioptions;
    // Load all Multi option values from database 

    foreach($multichillioptions as $multioption => $attributes)
    {
        $multichillioptions[$multioption]['value'] = 
            DatabaseFunctions::getInstance()->getPortalConfigMulti($multioption);
    }

    // Load all Single option values from database

    foreach($singlechillioptions as $singleoption => $attributes)
    {
        $singlechillioptions[$singleoption]['value'] = 
            DatabaseFunctions::getInstance()->getPortalConfigSingle($singleoption);
    }
}

    
//    DatabaseFunctions::getInstance()->setPortalConfigSingle('macpasswd', 'passwords');
//    DatabaseFunctions::getInstance()->setPortalConfigSingle('defidletimeout', '600');
//    DatabaseFunctions::getInstance()->setPortalConfigMulti('uamallowed', 'google.com.au');    
    

    $smarty->assign("singlechillioptions", $singlechillioptions);
    $smarty->assign("multichillioptions", $multichillioptions);    
	display_page('portalconfig.tpl');

?>

