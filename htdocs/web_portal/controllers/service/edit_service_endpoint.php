<?php
/*______________________________________________________
 *======================================================
 * File: edit_service_endpoint.php
 * Author: James McCarthy
 * Description: Processes an edit service endpoint request
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
 * Controller for an edit service endpoint request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_endpoint() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);

    if($_POST) {
        submit($user);
    } else {
        draw($user);
    }
}

/**
 * Draws the edit endpoint form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @throws Exception
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't edit a service property.");
    }
    if (!isset($_REQUEST['serviceid']) || !is_numeric($_REQUEST['serviceid']) ){
        throw new Exception("A serviceid must be specified");
    }
    if (!isset($_REQUEST['endpointid']) || !is_numeric($_REQUEST['endpointid']) ){
        throw new Exception("An endpointid must be specified");
    }
    $serv = \Factory::getServiceService();
    $service = $serv->getService($_REQUEST['serviceid']);
    $endpoint = $serv->getEndpoint($_REQUEST['endpointid']);
    //Check user has permissions to edit endpoints
    $serv->validateAddEditDeleteActions($user, $service);


    $params['endpoint'] = $endpoint;
    $params['service'] = $service;
    $params['serviceTypes'] = $serv->getServiceTypes();
    show_view("service/edit_service_endpoint.php", $params);
}

/**
 * Processes an edit endpoint request from a web request
 * @param \User $user current user
 * return null
 */
function submit(\User $user = null) {
    try {
        $newValues = getEndpointDataFromWeb();
        $endpointID = $newValues['SERVICEENDPOINT']['ENDPOINTID'];

        $serv = \Factory::getServiceService();
        $endpoint = $serv->getEndpoint($endpointID);
        $serv->editEndpoint($user, $endpoint, $newValues);
        $params['serviceid'] = $endpoint->getService()->getId();
        $params['endpointid'] = $endpointID;
        show_view('service/service_endpoint_updated.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>
