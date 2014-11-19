<?php
/*______________________________________________________
 *======================================================
 * File: new_vsite.php
 * Author: John Casson, George Ryall, David Meredith
 * Description: Processes a new service group request. If the user
 *              hasn't POSTed any data we draw the new vsite
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
require_once __DIR__ . '/../../components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';

/**
 * Controller for a new virtual site request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_service_group() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     // If we receive a POST request it's for a new service group
        submit($user);
    } else {     // If there is no post data, draw the new service group  form
        draw($user);
    }
}

/**
 * Retrieves the new vsite's data from a portal request and submit it to the
 * services layer's vsite functions.
 * @return null
 */
function submit($user) {
    $serv = \Factory::getServiceGroupService();
    try {
        //$serv->addAuthorization($user);
        if(is_null($user)) {
	        throw new \Exception("Unregistered users can't create service groups.");
	    }
        $newValues = getSGroupDataFromWeb();
        $sg = $serv->addServiceGroup($newValues, $user);
        $params = array('sg' => $sg);
        show_view("service_group/submit_add_service_group.php", $params);
    } catch (Exception $e) {
        show_view("error.php", $e->getMessage());
        die();
    }
}

function draw($user) {
    try {
        //\Factory::getServiceGroupService()->addAuthorization($user);
        if(is_null($user)) {
	        throw new \Exception("Unregistered users can't create service groups.");
	    }
        // If the user is registered they're allowed to add a service group
        
        $configService= \Factory::getConfigService();
        
        $scopes = \Factory::getScopeService()->getDefaultScopesSelectedArray();
        $numberScopesRequired = $configService->getMinimumScopesRequired('service_group');
        
        $params = array('scopes' => $scopes
                , 'numberOfScopesRequired'=>$numberScopesRequired);
        
        show_view("service_group/add_service_group.php", $params);
    } catch (Exception $e) {
        show_view("error.php", $e->getMessage());
        die();
    }
}

?>