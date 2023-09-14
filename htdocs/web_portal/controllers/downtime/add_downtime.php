<?php
/*______________________________________________________
 *======================================================
 * File: add_downtime.php
 * Author: James McCarthy, John Casson, George Ryall, David Meredith
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

use Exception;

/**
 * Controller for a new_downtime request.
 *
 * DM: The downtime interface/logic needs to be reworked and tidied-up:
 * - It needs to allow services from multiple sites to be put into downtime
 * (currently it only allows a single site to be selected which limits the
 * selectable services to those only from that site).
 *
 * - There is almost certainly a more elegant way to pass down the UTC offset
 * (secs) and timezoneId label for each site (rather than using an AJAX call to query
 * for these values on site selection). This will be needed in order to cater for
 * multi-site selection. Perhaps pass down a set of DataTransferObjects or JSON string
 * rather than the Site entities themselves, and specify tz, offset in the DTO/JSON.
 *
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add() {
    $dn = Get_User_Principle();
    /* @var $user \User */
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
 * @return null
 */
function submit(\User $user = null) {


    //Check if this is a confirmed submit or intial submit
    $confirmed = $_POST['CONFIRMED'];
    if($confirmed == true){
        //Downtime is confirmed, submit it
        //$downtimeInfo = unserialize($_REQUEST['newValues']);  // didn't cater for UTF-8 chars
        $downtimeInfo = json_decode($_POST['newValues'], TRUE);
        $serv = \Factory::getDowntimeService();

        $params['dt'] = $serv->addDowntime($downtimeInfo, $user);
        show_view("downtime/added_downtime.php", $params);
    }else{
        //Show user confirmation screen with their input
        $downtimeInfo = getDtDataFromWeb();

        //Need to sort the impacted_ids into impacted services and impacted endpoints
        $impactedids = $downtimeInfo['IMPACTED_IDS'];

        $services=array();
        $endpoints=array();

        //For each impacted id sort between endpoints and services using the prepended letter
        foreach($impactedids as $id){
            if (strpos($id, 's') !== FALSE){
                //This is a service id
                $services[] = str_replace('s', '', $id); //trim off the identifying char before storing in array
            }else{
                //This is an endpoint id
                $endpoints[] = str_replace('e', '', $id); //trim off the identifying char before storing in array
            }
        }

        unset($downtimeInfo['IMPACTED_IDS']); //Delete the unsorted Ids from the downtime info

        $downtimeInfo['Impacted_Endpoints'] = $endpoints;


        $serv = \Factory::getServiceService();

        /** For endpoint put into downtime we want the parent service also. If a user has selected
         * endpoints but not the parent service here we will add the service to maintain the link beteween
         * a downtime having both the service and the endpoint.
         */
        foreach($downtimeInfo['Impacted_Endpoints'] as $endpointIds){
           $endpoint = $serv->getEndpoint($endpointIds);
           $services[] = $endpoint->getService()->getId();
        }

        //Remove any duplicate service ids and store the array of ids
        $services = array_unique($services);

        //Assign the impacted services and endpoints to their own arrays for us by the addDowntime method
        $downtimeInfo['Impacted_Services'] = $services;

        show_view("downtime/confirm_add_downtime.php", $downtimeInfo);
    }
}

/**
 * Draws a form to add a new downtime
 * @param \User $user current user
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't add a downtime.");
    }

    $nowUtcDateTime = new \DateTime(null, new \DateTimeZone("UTC"));
    //$twoDaysAgoUtcDateTime = $nowUtcDateTime->sub(\DateInterval::createFromDateString('2 days'));
    //$twoDaysAgoUtc = $twoDaysAgoUtcDateTime->format('d/m/Y H:i'); //e.g.  02/10/2013 13:20


    // URL mapping
    // Return the specified site's timezone label and the offset from now in UTC
    // Used in ajax requests for display purposes
    if(isset($_GET['siteid_timezone']) && is_numeric($_GET['siteid_timezone'])){
        $site = \Factory::getSiteService()->getSite($_GET['siteid_timezone']);
        if($site != null){
            $siteTzId = $site->getTimeZoneId();
            if( !empty($siteTzId) ){
                $nowInTargetTz = new \DateTime(null, new \DateTimeZone($siteTzId));
                $offsetInSecsFromUtc = $nowInTargetTz->getOffset();
            } else {
                $siteTzId = 'UTC';
                $offsetInSecsFromUtc = 0;  // assume 0 (no offset from UTC)
            }
            $timezoneId_Offset = array($siteTzId, $offsetInSecsFromUtc);
            die(json_encode($timezoneId_Offset));
        }
        die(json_encode(array('UTC', 0)));
    }

    /**
     * URL Mapping for `site` and `se` (Service Endpoint).
     *
     * If a user wants to add downtime to a specific
     * `site` and `se` (Service Endpoint), the portal will
     * pre-select the service endpoint corresponding to the `se` parameter
     * and the endpoints of that service.
     */
    elseif (isset($_GET['site']) && isset($_GET['se'])) {
        displaySiteAndSeEndpoints($user);
    }

    // URL Mapping
    // If the user wants to add a downtime to a specific site, show only that site's SEs
    else if(isset($_GET['site'])) {
        $site = \Factory::getSiteService()->getSite($_GET['site']);
        if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $site, $user)->getGrantAction() == FALSE){
           throw new \Exception("You don't have permission over $site");
        }
        $ses = $site->getServices();
        $params = array('ses' => $ses, 'nowUtc' => $nowUtcDateTime->format('H:i T'), 'selectAll' => true);
        show_view("downtime/add_downtime.php", $params);
        die();
    }

    // URL Mapping
    // If the user wants to add a downtime to a specific SE, show only that SE
    else if(isset($_GET['se'])) {
        $se = \Factory::getServiceService()->getService($_GET['se']);
        $site = \Factory::getSiteService()->getSite($se->getParentSite()->getId());
        if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $se->getParentSite(), $user)->getGrantAction() == FALSE){
           throw new \Exception("You do not have permission over $se.");
        }

        //$ses = array($se);
        $ses = $site->getServices();
        $params = array('ses' => $ses, 'nowUtc' => $nowUtcDateTime->format('H:i T'), 'selectAll' => true);
        show_view("downtime/add_downtime.php", $params);
        die();
    }

    // If the user doesn't want to add a downtime to a specific SE or site show all SEs
    else {
        $ses = array();
        if($user->isAdmin()){
            //If a user is an admin, return all SEs instead
            $ses = \Factory::getServiceService()->getAllSesJoinParentSites();
        } else {
             //$allSites = \Factory::getUserService()->getSitesFromRoles($user);

            // Get all ses where the user has a GRANTED role over one of its
            // parent OwnedObjects (includes Site and NGI but not currently Project)
            $sesAll = \Factory::getRoleService()->getReachableServicesFromOwnedObjectRoles($user);
            // drop the ses where the user does not have edit permissions over
            foreach($sesAll as $se){
                if(\Factory::getRoleActionAuthorisationService()->authoriseAction(\Action::EDIT_OBJECT, $se->getParentSite(), $user)->getGrantAction() ){
                    $ses[] = $se;
                }
            }
        }
        if(empty($ses)) {
            throw new Exception("You don't hold a role over a NGI "
                    . "or site with child services.");
        }

        $params = array(
            'ses' => $ses,
            'userCannotPreSelect' => true
        );

        show_view("downtime/add_downtime.php", $params);
        die();
    }
}

/**
 * Helper method to fetch service endpoint detail(s) associated
 * with `site` provided.
 * This will help portal to pre-select endpoints corresponding
 * to the `se` (Service Endpoint) provided.
 */
function displaySiteAndSeEndpoints($user)
{
    $site = \Factory::getSiteService()->getSite($_GET['site']);
    $ses = $site->getServices();

    if (!hasEditPermission($site, $user)) {
        throwPermissionException($site, true);
    }

    $params = [
        'ses' => $ses
    ];

    show_view("downtime/add_downtime.php", $params);
    die();
}

// Validates if the user has edit permission for the given site.
function hasEditPermission($site, $user)
{
    return \Factory::getRoleActionAuthorisationService()
        ->authoriseAction(\Action::EDIT_OBJECT, $site, $user)
        ->getGrantAction();
}

/**
 * Handles exceptions for permission-related issues.
 *
 * @throws Exception
 */
function throwPermissionException($resource, $isGeneric)
{
    if ($isGeneric) {
        $errorMsg = "You do not have permission over $resource";
    } else {
        $errorMsg = "$resource";
    }

    throw new Exception($errorMsg);
}
