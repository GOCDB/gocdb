<?php
/*______________________________________________________
 *======================================================
 * File: add_downtime.php
 * Author: John Casson, George Ryall, David Meredith
 * Description: Processes a new downtime request. If the user
 *              hasn't POSTed any data we draw the add downtime
 *              form. If they post data we assume they've posted it from
 *              the form and add it.
 *
 * License information
 *
 * Copyright 2013 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 /*======================================================*/
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
    
/**
 * Controller for a new_downtime request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a new downtime
        submit($user);
    } else { // If there is no post data, draw the New downtime form
        draw($user);
    }
}

/**
 * Retrieves the raw new downtime's data from a portal request and submit it
 * to the services layer's downtime functions.
 * @param \User $user current user
 * @return null */
function submit(\User $user = null) {

	//Check if this is a confirmed submit or intial submit
	$confirmed = $_REQUEST['CONFIRMED'];	
	    
    if($confirmed == true){
    	//Downtime is confirmed, submit it
    	$newValues = unserialize($_REQUEST['newValues']);    	 
    	$serv = \Factory::getDowntimeService();    	
    	$dt = $serv->addDowntimeOld($newValues, $user);    	
    	$params['dt'] = $dt;    	  	
    	show_view("downtime/added_downtime_old.php", $params);
    }else{
    	//Show user confirmation screen with their input
    	$params = getDtDataFromWebOld();    	
    	show_view("downtime/confirm_add_downtime_old.php", $params);
    }  
}

/**
 *  Draws a form to add a new downtime
 * @param \User $user current user 
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't add a downtime.");
    }


    // TODO: Siteless services 
    
	// Make sure all dates are treated as UTC!
	date_default_timezone_set("UTC");
	$nowUtc = time();
    $nowUtcDateTime = new \DateTime();
    $twoDaysAgoUtcDateTime = $nowUtcDateTime->sub(\DateInterval::createFromDateString('2 days'));
    $twoDaysAgoUtc = $twoDaysAgoUtcDateTime->format('d/m/Y H:i'); //e.g.  02/10/2013 13:20 

	// If the user wants to add a downtime to a specific site, show only that site's SEs
	if(isset($_REQUEST['site'])) {
		$site = \Factory::getSiteService()->getSite($_REQUEST['site']);
	    //old way: \Factory::getSiteService()->edit Authorization($site, $user);
        if(count(\Factory::getSiteService()->authorizeAction(\Action::EDIT_OBJECT, $site, $user))==0){
           throw new \Exception("You don't have permission over $site"); 
        }
		$ses = $site->getServices();
		$params = array('ses' => $ses, 'nowUtc' => $nowUtc, 'selectAll' => true, 'twoDaysAgoUtc' => $twoDaysAgoUtc);
		show_view("downtime/add_downtime_old.php", $params);
		die();
	}

	// If the user wants to add a downtime to a specific SE, show only that SE
	else if(isset($_REQUEST['se'])) {
	    $se = \Factory::getServiceService()->getService($_REQUEST['se']);
        if(count(\Factory::getServiceService()->authorizeAction(\Action::SE_ADD_DOWNTIME, $se, $user))==0){
           throw new \Exception("You do not have permission over $se."); 
        } 
        
	    $ses = array($se);
		$params = array('ses' => $ses, 'nowUtc' => $nowUtc, 'selectAll' => true, 'twoDaysAgoUtc' => $twoDaysAgoUtc);
		show_view("downtime/add_downtime_old.php", $params);
		die();
	}

	// If the user doesn't want to add a downtime to a specific SE or site show all SEs
    else {
        if($user->isAdmin()){
            //If a user is an admin, return all SEs instead
            $ses = \Factory::getServiceService()->getAllSesJoinParentSites(); 
        } else {
            // All ses the user has permission over
            $ses = \Factory::getRoleService()->getServices($user);
        }
        if(empty($ses)) {
            throw new Exception("You don't hold a role over a NGI " 
                    . "or site with child services.");
        }
        $params = array('ses' => $ses, 'nowUtc' => $nowUtc, 'twoDaysAgoUtc' => $twoDaysAgoUtc);
        show_view("downtime/add_downtime_old.php", $params);
        die(); 
    }
}

?>