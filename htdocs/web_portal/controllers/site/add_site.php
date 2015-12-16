<?php
/*______________________________________________________
 *======================================================
 * File: add_site.php
 * Author: John Casson, David Meredith
 * Description: Processes a new site request. If the user
 *              hasn't POSTed any data we draw the new site
 *              form. If they post data we assume they've posted it from
 *              the form and validate then insert it into the DB.
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
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

/**
 * Controller for a new site request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_site() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a new site
        submit($user);
    } else { // If there is no post data, draw the new site form
        draw($user);
    }
}

/**
 * Retrieves the raw new site's data from a portal request and submit it to the
 * services layer's site functions.
 * @param \User $user current user
 * @return null
 */
function submit(\User $user = null) {
    $newValues = getSiteDataFromWeb();
    
    $serv = \Factory::getSiteService();
    try {
    	$site = $serv->addSite($newValues, $user);
    } catch(Exception $e) {
    	show_view('error.php', $e->getMessage());
    	die();
    }
    $params['site'] = $site;
    show_view("site/submit_new_site.php", $params);
    die(); 
}


/**
 * Draws a form to add a new site
 * @param \User $user current user 
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)){
        throw new Exception("Unregistered users can't add a new site"); 
    }

    $siteService = \Factory::getSiteService();

    if($user->isAdmin()){
        // can user assign reserved scopes to this site, even though site has not been created yet?
        $disableReservedScopes = false; 
        // if user is admin, then get all NGIs
        $userNGIs = \Factory::getNgiService()->getNGIs(); 
    } else {
        $disableReservedScopes = true; 
        // otherwise, get only the NGIs the non-admin user has roles over that support add_site
        $userNGIs = \Factory::getNgiService()->getNGIsBySupportedAction(Action::NGI_ADD_SITE, $user); 
        if(count($userNGIs) == 0){
           show_view('error.php', "You do not have permission to add a new site."
                        ." To add a new site you require a managing role over an NGI"); 
           die(); 
        }
    }
//	// todo - site will be created under one of the user's ngis, so we can 
//      // create a temporary site and add it to those ngis in order to apply role model. 
//	$site = new \Site(); 
//	foreach($userNGIs as $ngis){ $ngis->addSiteDoJoin($site); } 
//	if(\Factory::getRoleActionAuthorisationService()->authoriseAction(
//		"ACTION_APPLY_RESERVED_SCOPE_TAG", $site , $user)->getGrantAction()){
//	   $disableReservedScopes = false;  
//	} 

    // URL mapping
    if(isset($_GET['getAllScopesForScopedEntity']) && is_numeric($_GET['getAllScopesForScopedEntity'])){
        // Return all scopes for the parent NGI with the specified Id as a JSON object.  
        // Used in ajax requests for generating UI checkboxes. 
        // AJAX is needed here because the parent NGI is not known until the user selects 
        // which parent NGI in the pull-down which then fires the AJAX request. 
        $scopedEntityId = $_GET['getAllScopesForScopedEntity']; 
        $scopedEntity =  \Factory::getNgiService()->getNgi($scopedEntityId); 
        die(getEntityScopesAsJSON($scopedEntity, $disableReservedScopes));  
    } 
   
    $countries = $siteService->getCountries();
    $timezones = DateTimeZone::listIdentifiers(); 
    
    //Remove SC and PPS infrastructures from drop down list. TODO: Delete this block once they no longer exist
    $SCInfrastructure = $siteService->getProdStatusByName('SC');
    $PPSInfrastructure = $siteService->getProdStatusByName('PPS'); 
    $hackprodStatuses=array();
    foreach($siteService->getProdStatuses() as $ps){
        if($ps != $SCInfrastructure and $ps != $PPSInfrastructure){
            $hackprodStatuses[]=$ps;
        }
    }
    $prodStatuses = $hackprodStatuses;
    //delete up to here once pps and sc infrastructures have been removed from database
    $certStatuses = $siteService->getCertStatuses();
    $numberOfScopesRequired = \Factory::getConfigService()->getMinimumScopesRequired('site');

    $params = array('ngis' => $userNGIs, 'countries' => $countries, 'timezones' => $timezones, 
	'prodStatuses' => $prodStatuses, 'certStatuses' => $certStatuses, 
        'numberOfScopesRequired' => $numberOfScopesRequired, 
        'disableReservedScopes' => $disableReservedScopes);

    //Check that there is at least one NGI available before allowing an add site. 
    if($params['ngis'] == null){
        show_view('error.php', "GocDB requires one or more NGI's to be able to add a site.");
    }
    
    show_view("site/add_site.php", $params);
    die(); 
}

    /*
    $allScopes_PreSelected = \Factory::getScopeService()->getAllScopesMarkDefault();
    // Split $allScopes_PreSelected into $scopes + $reservedScopes to pass to UI
    // Each element is an associative array indicating if scope is selected or not 
    $reservedScopes = array(); 
    $scopes = array(); 
    foreach($allScopes_PreSelected as $scArray){
	$isReserved = false; 
	foreach($reservedScopeNames as $reservedScopeName){
	    if( $scArray['scope']->getName() == $reservedScopeName){
	        $isReserved = true; 	
		break; 
	    } 
	}
	if($isReserved){
            // each element is an associative array indicating if scope is default or not 
            // (default 'reserved' scopes are pre-selected in the UI, user can't change (de)selection) 
	    $reservedScopes[] = $scArray;  
	} else {
            // each element is an associative array indicating if scope is default or not 
            // (default 'normal' scopes are pre-selected in the UI, but user can change (de)selection) 
	    $scopes[] = $scArray; 
	}
    }*/

?>