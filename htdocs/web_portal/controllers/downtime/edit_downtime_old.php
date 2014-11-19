<?php
/*______________________________________________________
 *======================================================
 * File: edit_downtime.php
 * Author: John Casson, George Ryall (modifications), David Meredith
 * Description: Processes an edit downtime request from the web portal
 *
 * License information
 *
 * Copyright 2009 STFC
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
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for an edit downtime request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    
    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's to update a downtime
        submit($user);
    } else { // If there is no post data, draw the edit downtime form
        draw($user);
    }
}

/**
 * Draws the edit downtime form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @return null
 */
function draw(\User $user = null) {

    if(is_null($user)) {
        throw new Exception("Unregistered users can't edit a downtime.");
    }

    $serv = \Factory::getDowntimeService();
    $dt = $serv->getDowntime($_REQUEST['id']);

    $impactedServices = array();
    //foreach($dt->getEndpointLocations() as $endpoint){
    //   $impactedServices[] = $endpoint->getService(); 
    //}
    foreach($dt->getServices() as $service){
       $impactedServices[] = $service;  
    }

    $serv->authorization($impactedServices, $user);

    //$ses = \Factory::getRoleService()->getServices($user);
    
    $params = array(/*'ses' => $ses, */'impactedSes' => $impactedServices
            , 'dt' => $dt, 'format' => getDateFormat());
    show_view('downtime/edit_downtime_old.php', $params);
}

/**
 * 
 * @param \User $user current user
 * @throws Exception
 */
function submit(\User $user = null) {
    $values = getDtDataFromWebOld();

    if(is_null($user)) {
        throw new Exception("Unregistered users can't edit a downtime.");
    }

    $serv = \Factory::getDowntimeService();

    try {
        $dt = $serv->getDowntime($_REQUEST['ID']);    
        $dt = $serv->editDowntimeOld($dt, $values, $user);
        $params = array('dt' => $dt);
        show_view("downtime/edited_downtime.php", $params);
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}

?>