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
 *  Draws a form to add a new site
 * @param \User $user current user 
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)){
        throw new Exception("Unregistered users can't add a new site"); 
    }

    $siteService = \Factory::getSiteService();
    //try { $siteService->addAuthorization($user);
    //} catch(Exception $e) { show_view('error.php', $e->getMessage()); die(); }
    
    if($user->isAdmin()){
        // if user is admin, then get all NGIs
        $userNGIs = \Factory::getNgiService()->getNGIs(); 
    } else {
        // otherwise, get only the NGIs the non-admin user has roles over that support add_site
        $userNGIs = \Factory::getNgiService()->getNGIsBySupportedAction(Action::NGI_ADD_SITE, $user); 
        if(count($userNGIs) == 0){
           show_view('error.php', "You do not have permission to add a new site."
                        ." To add a new site you require a managing role over an NGI"); 
           die(); 
        }
    }
   

    $countries = $siteService->getCountries();
    $timezones = $siteService->getTimezones();
    $prodStatuses = $siteService->getProdStatuses();
    
    //Remove SC and PPS infrastructures from drop down list. TODO: Delete this block once they no longer exist
    $SCInfrastructure = $siteService->getProdStatusByName('SC');
    $PPSInfrastructure = $siteService->getProdStatusByName('PPS'); 
    $hackprodStatuses=array();
    foreach($prodStatuses as $ps){
        if($ps != $SCInfrastructure and $ps != $PPSInfrastructure){
            $hackprodStatuses[]=$ps;
        }
    }
    $prodStatuses = $hackprodStatuses;
    //delete up to here once pps and sc infrastructures have been removed from database

    $certStatuses = $siteService->getCertStatuses();
    $scopes = \Factory::getScopeService()->getDefaultScopesSelectedArray();
    $numberOfScopesRequired = \Factory::getConfigService()->getMinimumScopesRequired('site');
    //$dDashNgis = \Factory::getUserService()->getDDashNgis($user);

    $params = array('ngis' => $userNGIs, 'countries' => $countries, 'timezones' => $timezones
    				, 'prodStatuses' => $prodStatuses, 'certStatuses' => $certStatuses
    				, 'scopes' => $scopes, 'numberOfScopesRequired' => $numberOfScopesRequired);

    //Check that there is at least one NGI available before allowing an add site. 
    if($params['ngis'] == null){
        show_view('error.php', "GocDB requires one or more NGI's to be able to add a site.");
    
    }
    
    show_view("site/add_site.php", $params);
    die(); 
}

?>