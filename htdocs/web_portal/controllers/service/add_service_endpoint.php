<?php
/*______________________________________________________
 *======================================================
 * File: add_service_endpoint.php
 * Author: James McCarthy
 * Description: Adds a new endpoint to a service
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
    
/**
 * Controller for a endpoint request
 * @global array $_POST only set if the browser has POSTed data
 * @return null
 */
function add_service_endpoint() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if($_POST) {     	// If we receive a POST request it's for a new endpoint
        submit($user);
    } else { 			// If there is no post data, draw the new endpoint form
        draw($user);
    }
}

/**
 * Retrieves the raw new endpoint data from a portal request and submit it
 * to the services layer's functions. Provides basic validation that the interface name is set and unique
 * @param \User $user current user
 * @return null */
function submit(\User $user = null) {
    $serv = \Factory::getServiceService();    
    $newValues = getEndpointDataFromWeb();
    $serviceid = $newValues['SERVICEENDPOINT']['SERVICE'];
    $serv->addEndpoint($newValues, $user);
    show_view("service/added_service_endpoint.php", $serviceid);
}

/**
 * Draws a form to add a new service property
 * @param \User $user current user 
 * @return null
 */
function draw(\User $user = null) {

	if(is_null($user)) {
        throw new Exception("Unregistered users can't add an endpoint to a service.");
    }
    if (!isset($_REQUEST['se']) || !is_numeric($_REQUEST['se']) ){
        throw new Exception("An id must be specified");
    }
    
    $serv = \Factory::getServiceService();
    $service = $serv->getService($_REQUEST['se']); //get service by id
    $seType = $service->getServiceType()->getName(); 
    //Check user has permissions to add service endpoint
    $serv->validateAddEditDeleteActions($user, $service);
        
    $params['serviceid'] = $_REQUEST['se'];
    $params['se'] = $service;
    $params['serviceType'] = $seType; 
    $params['serviceTypes'] = $serv->getServiceTypes();
	show_view("service/add_service_endpoint.php", $params);

}

?>