<?php
/*______________________________________________________
 *======================================================
 * Author: John Casson, George Ryall, David Meredith
 * Description: Processes a new service request. If the user
 *              hasn't POSTed any data we draw the new service 
 *              form. If they post data we assume they've posted it from
 *              the form and validate then insert it into the DB.
 *
 * License information
 *
 * Copyright  2009 STFC
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
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__.'/../utils.php';

/**
 * Controller for a new_service request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_service() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a new SE
        submit($user);
    } else { // If there is no post data, draw the New SE form
        draw($user);
    }
}

/**
 * Processes an add service request
 * @param \User $user Current user
 * @return null
 */
function submit(\User $user = null) { 
    $newValues = getSeDataFromWeb();

    if($user == null) throw new Exception("Unregistered users can't add services"); 
    $se = \Factory::getServiceService()->addService($newValues, $user);
    $params = array('se' => $se);
    show_view("service/submit_add_service.php", $params);
}

/**
 * Draw the add service form
 * @param \User $user current user 
 * @return null
 */
function draw($user) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't add a service .");
    }

    /* Optional site parameter is set if a user clicked
     * "add SE to this site" on the view site page */
    $site = null;
    if (isset($_REQUEST['siteId'])) {
        $site = \Factory::getSiteService()->getSite($_REQUEST['siteId']);
        if ($site == null) {
            throw new Exception('Invalid site');
        }
        if(\Factory::getRoleActionAuthorisationService()->authoriseAction(
                \Action::SITE_ADD_SERVICE, $site, $user)->getGrantAction() == FALSE){
            throw new Exception('You do not have permission to add a service to this site');
        }
    }

    $sites = array();
    if ($user->isAdmin()) {
        $disableReservedScopes = false; 
        //For admin users, return all sites instead.
        $sites = \Factory::getSiteService()->getSitesBy();
    } else {
        $disableReservedScopes = true; 
        // Collate sites which user has required action permission to array. 
        $allUserSites = \Factory::getUserService()->getSitesFromRoles($user);
        foreach ($allUserSites as $s) {
            if (\Factory::getRoleActionAuthorisationService()->authoriseAction(
                    \Action::SITE_ADD_SERVICE, $s, $user)->getGrantAction()) {
                $sites[] = $s;
            }
        }
    }

    if(count($sites)==0 and !$user->isAdmin()){
      throw new Exception("You need at least one NGI or Site level role to add a new service.");  
    }

    // URL mapping
    // Return all scopes for the parent Site with the specified Id as a JSON object 
    // Used in ajax requests for display purposes
    if(isset($_GET['getAllScopesForScopedEntity']) && is_numeric($_GET['getAllScopesForScopedEntity'])){
        // Return all scopes for the parent Site with the specified Id as a JSON object.  
        // Used in ajax requests for generating UI checkboxes. 
        // AJAX is needed here because the parent Site is not known until the user selects 
        // which parent Site in the pull-down which then fires the AJAX request. 
        $scopedEntityId = $_GET['getAllScopesForScopedEntity']; 
        $scopedEntity =  \Factory::getSiteService()->getSite($scopedEntityId); 
        die(getEntityScopesAsJSON($scopedEntity, $disableReservedScopes));  
    } 
    
    $serviceTypes = \Factory::getServiceService()->getServiceTypes();
    // remove the deprecated CE type (temp hack)
    foreach($serviceTypes as $key => $st) {
        if($st->getName() == "CE") {
            unset($serviceTypes[$key]);
        }
    }

    //get the number of scopes that we require
    $numberScopesRequired = \Factory::getConfigService()->getMinimumScopesRequired('service');
    
    $params = array('sites' => $sites, 'serviceTypes' => $serviceTypes,
                    "disableReservedScopes"=> $disableReservedScopes,
                    'site' => $site, 
                    'numberOfScopesRequired' => $numberScopesRequired);
    
    //Check that there is at least one Site available before allowing a user to add a service.
    if($params['sites'] == null){
        show_view('error.php', "GocDB requires one or more Sites to be able to add a service.");
    }
    
    show_view("service/add_service.php", $params);
}

?>