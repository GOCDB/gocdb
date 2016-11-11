<?php
/*______________________________________________________
 *======================================================
 * File: edit_site_property.php
 * Author: John Casson, George Ryall (modifications), James McCarthy
 * Description: Processes an edit site property request from the web portal
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
 * Controller for an edit endpoint property request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function edit_property() {
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
 * Draws the edit site property form using the object ID passed in $_REQUEST
 * @param \User $user current user
 * @throws Exception
 * @return null
 */
function draw(\User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't edit an endpoint property.");
    }
    if (!isset($_REQUEST['propertyid']) || !is_numeric($_REQUEST['propertyid']) ){
        throw new Exception("An id must be specified");
    }
    $serv = \Factory::getServiceService();
    //$service = $serv->getService($_REQUEST['serviceid']);
    $property = $serv->getEndpointProperty($_REQUEST['propertyid']);
    $endpoint = $property->getParentEndpoint();
    $service = $endpoint->getService();

    //Check user has permissions to edit endpoint property
    $serv->validateAddEditDeleteActions($user, $service);

    $params['prop'] = $property;
    $params['service'] = $service;
    $params['endpoint'] = $endpoint;

    show_view("service/edit_endpoint_property.php", $params);


}

/**
 * Processes an edit endpoint property request from a web request
 * @param \User $user current user
 * return null
 */
function submit(\User $user = null) {
    try {
        $newValues = getEndpointPropDataFromWeb();
        $endpointID = $newValues['ENDPOINTPROPERTIES']['ENDPOINTID'];
        $propID = $newValues['ENDPOINTPROPERTIES']['PROP'];
        if($newValues['ENDPOINTPROPERTIES']['NAME'] == null || $newValues['ENDPOINTPROPERTIES']['VALUE'] == null){
            show_view('error.php', "A property name and value must be provided.");
            die();
        }
        $property = \Factory::getServiceService()->getEndpointProperty($propID);
        $service = $property->getParentEndpoint()->getService();
        \Factory::getServiceService()->editEndpointProperty($service, $user, $property, $newValues);

        $params['endpointid'] = $endpointID;
        show_view('service/endpoint_property_updated.php', $params);
    } catch(Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }
}

?>