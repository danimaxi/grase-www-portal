<?php

/* Copyright 2010 Timothy White */

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

$PAGE = 'createtickets';
require_once 'includes/pageaccess.inc.php';

require_once 'includes/session.inc.php';
require_once 'includes/misc_functions.inc.php';
require_once 'includes/database_functions.inc.php';

function validate_form()
{
	global $expirydate;
	$error = array();
	//if(! checkDBUniqueUsername($_POST['Username'])) $error.= "Username already taken<br/>";
	//if ( ! $_POST['Username'] || !$_POST['Password'] ) $error.="Username and Password are both Required<br/>";
	
	$NumberTickets = clean_int($_POST['numberoftickets'] );
	
	$MaxMb = clean_number($_POST['MaxMb'] );
	$Max_Mb = clean_number( $_POST['Max_Mb'] );	
	$MaxTime = clean_int( $_POST['MaxTime'] );
	$Max_Time = clean_int( $_POST['Max_Time'] );	
	
    
    $error[] = validate_int($NumberTickets);
	$error[] = validate_datalimit($MaxMb);
	$error[] = validate_datalimit($Max_Mb);
	$error[] = validate_timelimit($MaxTime);
	$error[] = validate_timelimit($Max_Time);		
	if((is_numeric($Max_Mb) || $_POST['Max_Mb'] == 'inherit') && is_numeric($MaxMb)) $error[] = T_("Only set one Data limit field");
	if((is_numeric($Max_Time) || $_POST['Max_Time'] == 'inherit') && is_numeric($MaxTime)) $error[] = T_("Only set one Time limit field");
	
	if($NumberTickets > 50) $error[] = T_("Max of 50 tickets per batch"); // Limit due to limit in settings length which stores batch for printing

	list($error2, $expirydate) = validate_post_expirydate();
	$error = array_merge($error, $error2);
	$error[] = validate_group("", $_POST['Group']);
	return array_filter($error);
}



if(isset($_POST['createticketssubmit']))
{
	$error = validate_form();
	if($error ){
		//$user['Username'] = clean_text($_POST['Username']);
		//$user['Password'] = clean_text($_POST['Password']);
        $user['numberoftickets'] = clean_int($_POST['numberoftickets'] );    		
		$user['MaxMb'] = displayLocales(clean_number($_POST['MaxMb']));
		$user['Max_Mb'] = displayLocales(clean_number($_POST['Max_Mb']));
		if($_POST['Max_Mb'] == 'inherit' ) $user['Max_Mb'] = 'inherit';
				
		$user['MaxTime'] = displayLocales(clean_int($_POST['MaxTime']));
		$user['Max_Time'] = displayLocales(clean_int($_POST['Max_Time']));	
		if($_POST['Max_Time'] == 'inherit' ) $user['Max_Time'] = 'inherit';
		
		$user['Group'] = clean_text($_POST['Group']);
		$user['Expiration'] = expiry_for_group(clean_text($_POST['Group'])); //"${_POST['Expirydate_Year']}-${_POST['Expirydate_Month']}-${_POST['Expirydate_Day']}";
		$user['Comment'] = clean_text($_POST['Comment']);
		$smarty->assign("user", $user);
		$smarty->assign("error", $error);
		display_page('newtickets.tpl'); //TODO: What happens if this returns?
	}else
	{
	    $group = clean_text($_POST['Group']);
	    // Load group settings so we can use Expiry, MaxMb and MaxTime
	    $groupsettings = $Settings->getGroup($group);
	
	    $user['numberoftickets'] = clean_int($_POST['numberoftickets'] );    
	    
	    // TODO: Create function to make these the same across all locations
		if(is_numeric(clean_number($_POST['Max_Mb'])))
		    $MaxMb = clean_number($_POST['Max_Mb']);
		if(is_numeric(clean_number($_POST['MaxMb'])))
		    $MaxMb = clean_number($_POST['MaxMb']);
		if($_POST['Max_Mb'] == 'inherit')
		    $MaxMb = $groupsettings[$group]['MaxMb'];
		    
		if(is_numeric(clean_int($_POST['Max_Time'])))
		    $MaxTime =  clean_int($_POST['Max_Time']);
		if(is_numeric(clean_number($_POST['MaxTime'])))
		    $MaxTime = clean_int($_POST['MaxTime']);
		if($_POST['Max_Time'] == 'inherit')
		    $MaxTime = $groupsettings[$group]['MaxTime'];
		    
		    
		for($i = 0; $i < $user['numberoftickets']; $i++)
		{
		    $username =  rand_username(5);	
		    $password =  rand_password(6);
		    database_create_new_user( // TODO: Check if successful
			    $username,
			    $password,
			    $MaxMb,
			    $MaxTime,
			    expiry_for_group($group, $groupsettings),
			    clean_text($_POST['Group']),
			    clean_text($_POST['Comment'])
		    );
		    AdminLog::getInstance()->log("Created new user $username");
		    //$createdusers[] = array("UserName" => $username, "password" => $password);
		    //$createdusernames[] = array("UserName" => $username);
		    $createdusernames[] = $username;		    		    
		}
		//print strlen(serialize($createdusernames));
		$batchID = $Settings->nextBatchID();
		$Settings->saveBatch($batchID, $createdusernames, $Auth->getUsername(), clean_text($_POST['BatchComment']));
		$Settings->setSetting('lastbatch', $batchID);
		$createdusers = database_get_users($createdusernames);
		$smarty->assign("createdusers", $createdusers);
		$success[] = T_("Tickets Successfully Created");
		$success[] = "<a target='_tickets' href='printnewtickets'>".T_("Print Tickets")."</a>";				
	    $smarty->assign("success", $success);
		display_adduser_form();
	}
}else
{
	display_adduser_form();
}

function display_adduser_form()
{
	global $smarty, $Settings;
//    $user['Username'] = rand_username(5);	
	$user['Password'] = rand_password(6);
	
		// TODO: make default settings customisable
	$user['Max_Mb'] = 'inherit';
	$user['Max_Time'] = 'inherit';
	//$user['Max_Mb'] = 50;
	
	$user['Expiration'] = "--";//date('Y-m-d', strtotime('+3 month'));
	$smarty->assign("user", $user);
	
    $smarty->assign("last_batch", $Settings->getSetting('lastbatch'));
    
	display_page('newtickets.tpl');
}

?>


