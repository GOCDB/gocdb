<?php
/*______________________________________________________
 *======================================================
 * File: edit_service_type.php
 * Author: George Ryall, John Casson, David Meredith
 * Description: Processes an edit service type request. If the user
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
require_once __DIR__ . '/../utils.php';
require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';

/**
 * Controller for an edit service type request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_type() {
    //The following line will be needed if this controller is ever used for non administrators:
    //checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {     // If we receive a POST request it's for a service type edit
        submit();
    } else { // If there is no post data, draw the service type edit
        draw();
    }
}

/**
 * Draws the edit service type form
 * @return null
 */
function draw() {
    //Check the user has permission to see the page, will throw exception
    //if correct permissions are lacking
    checkUserIsAdmin();
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ){
        throw new Exception("An id must be specified");
    }

    // Get the service type
    $serviceType = \Factory::getServiceTypeService()->getServiceType($_REQUEST['id']);

    $params = array('Name' => $serviceType->getName(),'ID' => $serviceType->getId(),
                    'Description' => $serviceType->getDescription(),
                    'AllowMonitoringException' => $serviceType->getAllowMonitoringException());

    show_view("admin/edit_service_type.php", $params, "Edit " . $serviceType->getName());
}

/**
 * Retrieves the changed service type data from a portal request and submits it to the
 * services layer's service type functions.
 * @return null
 */
function submit() {
    require_once __DIR__ . '/../../../../htdocs/web_portal/components/Get_User_Principle.php';

    //Get the posted service type data
    $newValues = getSTDataFromWeb();

    //get the user data for the add site function (so it can check permissions)
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //get the service type service and the service type being edited
    $serv = \Factory::getServiceTypeService();
    $unalteredServiceType = $serv->getServiceType($newValues['ID']);

    try {
        //function will throw error if user does not have the correct permissions
        $alteredServiceType =$serv->editServiceType($unalteredServiceType, $newValues, $user);
        $params = array('Name' => $alteredServiceType->getName(),
                        'Description'=> $alteredServiceType->getDescription(),
                        'AllowMonitoringException' => $alteredServiceType->getAllowMonitoringException(),
                        'ID'=> $alteredServiceType->getId());
        show_view("admin/edited_service_type.php", $params);
    } catch (Exception $e) {
         show_view('error.php', $e->getMessage());
         die();
    }
}

?>