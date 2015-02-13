<?php
/*______________________________________________________
 *======================================================
 * File: edit_sgroup.php
 * Author: David Meredith, John Casson, George Ryall
 * Description: Processes an edit service group request. If the user
 *              hasn't POSTed any data we draw the edit sgroup
 *              form. If they post data we assume they've posted it from
 *              the form and validate then insert it into the DB.
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
 * Controller for an edit service group request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_service_group() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a new vsite
        submit($user);
    } else { // If there is no post data, draw the new vsite form
        draw($user);
    }
}

/**
 * Retrieves the new vsite's data from a portal request and submit it to the
 * services layer's vsite functions.
 * @param \User $user current user
 * @return null
 */
function submit(\User $user = null) {
    $serv = \Factory::getServiceGroupService();
    $newValues = getSGroupDataFromWeb();
    $sg = $serv->getServiceGroup($newValues['ID']);

    try {
        $sg = $serv->editServiceGroup($sg, $newValues, $user);
        $params = array('serviceGroup' => $sg);
        show_view("service_group/submit_edited_service_group.php", $params);
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}

/**
 * Draws the edit service group form
 * @param \User $user Current User
 * @return null
 */
function draw(\User $user = null) {
    $serv = \Factory::getServiceGroupService();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }
    // Get the service group
    $sg = $serv->getServiceGroup($_REQUEST['id']);
    //try { $serv->editAuthorization($sg, $user); } catch(Exception $e) {
    //	show_view('error.php', $e->getMessage()); die(); }
    if(count($serv->authorizeAction(\Action::EDIT_OBJECT, $sg, $user))==0){
       show_view('error.php', 'You do not have permission to edit this ServiceGroup'); 
       die(); 
    }
    // If the user is registered they're allowed to add a service group
    
    $configService= \Factory::getConfigService();
    
    $scopes = \Factory::getScopeService()->getScopesSelectedArray($sg->getScopes());
    $numberScopesRequired = $configService->getMinimumScopesRequired('service_group');

    $params = array('serviceGroup' => $sg, 'scopes' => $scopes
            , 'numberOfScopesRequired'=>$numberScopesRequired);

    show_view("service_group/edit_service_group.php", $params, "Edit " . $sg->getName());
}

?>