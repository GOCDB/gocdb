<?php
/*______________________________________________________
 *======================================================
 * File: move_site.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Allows GOCDB Administrators to move sites
 * 				Between NGIs
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
use Doctrine\Common\Collections\ArrayCollection;
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a site move request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function move_site() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's for a site move
        submit();
    } else { // If there is no post data, draw the select old NGI form
        drawSelectOldNgi();
    }
}

/**
 * Retrieves the raw movement data from a portal request and submit it to the
 * services layer's site functions.
 * @return null
 */
function submit() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';


    //Get the submitted data
    $movementrequest = getSiteMoveDataFromWeb();

    //If new NGI is not in the array of submitted data yet,
    //then we have come form the select old NGI page and want the move site page
    if(!array_key_exists('NewNGI',$movementrequest)){
        //Run the submit old NGI and display the site_move view
        submitOldNgi($movementrequest);
    }
    else {
        //Carry out the move with submitted data then show success view
        submitMoveSite($movementrequest);
    }
}

/**
 *  Draws a form to move a site
 *  @param \NGI $oldNgi NGI to which the site to be moved belongs
 *  @return null
 */
function drawMoveSite(\NGI $oldNgi) {
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    //Get a list of sites and list of NGIs to select from
    $ngis= \Factory::getNgiService()->getNGIs();
    $sites = $oldNgi->getSites();

    //Put into an array to be passed to view
    $params = array('Ngis' => $ngis, 'sites' => $sites, 'OldNgi' => $oldNgi->getName());

    show_view("admin/move_site.php", $params);
}

/**
 *  Draws a form to select the NGI from which you wish to move a site
 *  @return null
 */
function drawSelectOldNgi() {
    // Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();

    //Get a list  of NGIs to select from
    $ngis= \Factory::getSiteService()->getNGIs();

    //Put into an array to be passed to view
    $params = array('Ngis' => $ngis);

    show_view("admin/move_site_select_old_ngi.php", $params);
}

/**
 *  Gets the relevant fields from a user's web request
 *  ($_REQUEST) and returns an associative array compatible with
 *  move_site().
 *  @global array $_REQUEST move site data submitted by the end user
 *  @return array an array representation of the site movement
 */
function getSiteMoveDataFromWeb() {
    // Fields that are used to link other objects to the site
    $fields = array('OldNGI', 'NewNGI', 'Sites');

    foreach($fields as $field) {
        if(array_key_exists($field,$_REQUEST)){
            $site_move_data[$field] = $_REQUEST[$field];
        }
    }
    return $site_move_data;
}

/**
 * Takes the submitvalues and draws move_site, specfying which NGI's sites
 * should be displayed on that page.
 * @param $submitvalues Array which contains the data submitted by the user
 * @return null
 */
function submitOldNgi($submitvalues) {
    //Get the NGI
    $oldNgi_id = $submitvalues['OldNGI'];
    $oldNgi = \Factory::getNgiService()->getNGI($oldNgi_id);
    //Draw the move site form
    drawMoveSite($oldNgi);
}

/**
 * Move the site to the new NGI and then display the success view
 * @param type $movementDetails array containing the site and the NGI it is to be moved to
 * @return null
 */
function submitMoveSite($movementDetails) {

    //Check that some sites have been specified
    if (!array_key_exists('Sites', $movementDetails)){
        throw new Exception('Please select one or more sites to move.');
    }

    //Get submitted data
    $newNgi_id = $movementDetails['NewNGI'];
    $site_ids = $movementDetails['Sites'];

    //Convert NGI id into objects
    $newNgi = \Factory::getNgiService()->getNGI($newNgi_id);

    //Get the users details
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    $serv = \Factory::getSiteService();

    //create an array for the sites we can use to display the results
    // of the site move to the user
    $sites = new ArrayCollection();

    //If sites have been subitted, move them. Else throw exception
    //
    try {
        foreach($site_ids as $site_id){
            $site= $serv->getSite($site_id);
            $serv->moveSite($site, $newNgi, $user);
            $sites[] = $site;
        }
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }

    //show success view
    $params['NewNGI'] = $newNgi;
    $params['sites'] = $sites;

    show_view("admin/moved_site.php", $params);
}