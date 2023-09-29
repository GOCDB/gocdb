<?php
/*______________________________________________________
 *======================================================
 * File: move_service_end_point.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Allows GOCDB Administrators to move Services
 * 				between sites
 *
 * License information
 *
 * Copyright � 2013 STFC
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
use Doctrine\Common\Collections\ArrayCollection;
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a service move request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function move_service_end_point() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {
        // If we receive a POST request it's for a service movement
        submit();
    } else {
        // If there is no post data, draw the select old site form
        drawSelectOldSite();
    }
}

/**
 * Retrieves the raw movement data from a portal request and submit it to the
 * services layer's service functions.
 * @return null
 */
function submit() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';


    //Get the submitted data
    $movementrequest = getSEPMoveDataFromWeb();

    //If the new sep is not in the array of submitted data yet,
    //then we have come form the select old site page and want the move SEP page
    if(!array_key_exists('NewSite',$movementrequest)){
        //Run the submit old site and display the service_end_point_move view
        submitOldSite($movementrequest);
    }
    else {
        //Carry out the move with submitted data then show success view
        submitMoveSEP($movementrequest);
    }
}

/**
 *  Draws a form to move a service
 *  @param \Site $oldSite Site to which the service to be moved belongs
 *  @return null
 */
function drawMoveSite(\Site $oldSite) {
    //Check the user has permission to see the page, will throw exception
    //if the user is not an admin
    checkUserIsAdmin();

    //Get a list of services and list of sites to select from
    $sites= \Factory::getSiteService()->getSitesBy();
    $services = $oldSite->getServices();

    //Put into an array to be passed to view
    $params = array('Sites' => $sites, 'Services' => $services,
                    'OldSite' => $oldSite->getShortName());

    show_view("admin/move_service_end_point.php", $params);
}

/**
 *  Draws a form to select the Site from which you wish to move a service
 *  @return null
 */
function drawSelectOldSite() {
    // Check the user has permission to see the page, will throw exception
    //if the user does not
    checkUserIsAdmin();

    //Get a list  of Sites to select from
    $sites= \Factory::getSiteService()->getSitesBy();

    //Put into an array to be passed to view
    $params = array('Sites' => $sites);

    show_view("admin/move_service_end_point_select_old_site.php", $params);
}

/**
 *  Gets the relevant fields from a user's web request
 *  ($_REQUEST) and returns an associative array compatible with
 *  move_site().
 *  @global array $_REQUEST move site data submitted by the end user
 *  @return array an array representation of the site movement
 */
function getSEPMoveDataFromWeb() {
    // Fields that are used to link other objects to the site
    $fields = array('OldSite', 'NewSite', 'Services');

    foreach($fields as $field) {
        if(array_key_exists($field,$_REQUEST)){
            $SEP_move_data[$field] = $_REQUEST[$field];
        }
    }
    return $SEP_move_data;
}

/**
 * Takes the submitvalues and draws move_service_end_point, specfying which
 * site's SEPs should be displayed on that page.
 * @param $submitvalues Array which contains the data submitted by the user
 * @return null
 */
function submitOldSite($submitvalues) {
    //Get the old site
    $oldSite_id = $submitvalues['OldSite'];
    $oldSite = \Factory::getSiteService()->getSite($oldSite_id);
    //Draw the move site form
    drawMoveSite($oldSite);
}

/**
 * Moves the service to the new site and then display the success view
 * @param type $movementDetails array containing the SEP and the site it is to be moved to
 * @return null
 */
function submitMoveSEP($movementDetails) {

    //Check that some services have been specified
    if (!array_key_exists('Services', $movementDetails)){
        throw new Exception('Please select one or more Services to move.');
    }

    //Get submitted data
    $newSite_id = $movementDetails['NewSite'];
    $service_ids = $movementDetails['Services'];

    //Convert Site id into Site object
    $newSite = \Factory::getSiteService()->getSite($newSite_id);

    //Get the users details
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $serv = \Factory::getServiceService();

    //create an array for the SEPs we can use to display the results
    // of the site move to the user
    $services = new ArrayCollection();

    //If services have been subitted, move them. Else throw exception
    //
    try {
        foreach($service_ids as $service_id){
            $serviceInstance = $serv->getService($service_id);
            $serv->moveService($serviceInstance, $newSite, $user);
            $services[] = $serviceInstance;
        }
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    //show success view
    $params['NewSite'] = $newSite;
    $params['Services'] = $services;
    show_view("admin/moved_service_end_point.php", $params);
}
