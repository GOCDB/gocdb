<?php
/*______________________________________________________
 *======================================================
 * File: remove_ses.php
 * Author: John Casson, David Meredith
 * Description: Removes SEs from a service group
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
 /*====================================================== */
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a request to remove SEs from a vsite
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function remove_ses() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) { // If we receive a POST request it's during form submission
        submit($user);
    } else { // If there is no post data, draw the remove ses form
        draw($user);
    }
}

/**
 * Draws a form to remove services from a vsite
 * @global array $_REQUEST only set if the browser has sent parameters
 * @param \User $user current User
 * @return null
 */
function draw(\User $user = null) {
	$serv = \Factory::getServiceGroupService();

	// The service group to remove SEs from
	$sg =  $serv->getServiceGroup($_REQUEST['id']);

	// Check the user is authorized to perform this operation
	//try { $serv->editAuthorization($sg, $user); } catch(Exception $e) {
	//    show_view('error.php', $e->getMessage()); die(); }
    if(count($serv->authorizeAction(\Action::EDIT_OBJECT, $sg, $user))==0){
       show_view('error.php', 'You do not have permission to edit this ServiceGroup'); 
       die(); 
    }

    $params = array('sg' => $sg);
    show_view('service_group/remove_ses.php', $params);
}

/**
 * Validates the user's input, removes the services and
 * returns the object ID of the removed service 
 * @global array $_REQUEST only set if the browser has sent parameters
 * @param \User $user current User
 * @return null
 */
function submit(\User $user = null) {
    $serv = \Factory::getServiceGroupService();

    // The service group to remove SEs from
    $sg =  $serv->getServiceGroup($_REQUEST['sgId']);

    $se = \Factory::getServiceService()
    		->getService($_REQUEST['seId']);

    try {
        /* If the service is siteless and was created under this
         * service group then we delete it */
    	if(is_null($se->getParentSite())) {
    		// TODO: v5 implementation
            // If 0 was returned above then the SE doesn't have a hosting site
//             $hostingVSite = \Factory::getServiceService()->
//                 getHostVirtualSite($endpointId, $gridId);
//             /* If this service group created the endpoint then delete
//              * it */
//             if($hostingVSite == $vSiteId) {
//                 $db = ConnectionFactory::getNewConnection();
//                 $promAPI = PromAPIFactory::getPromAPI($db);
//                 $returned_object_id = $promAPI->DeleteObject($endpointId, $gridId, null);
//                 if(!$promAPI->commit()) throw new Exception("Could not commit");
//                 ConnectionFactory::managedClose($db);
//                 show_view('vsite/return_removed_se.php', array('removedSe' => $_REQUEST['endpointId']), null, true);
//                 die();
//             }
        }
        /* If the SE isn't siteless and created under this service group
         * remove it as normal */
        $serv->removeService($sg, $se, $user);
    } catch (Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
    show_view('service_group/return_removed_se.php', array('se' => $se), null, true);
    die();
}