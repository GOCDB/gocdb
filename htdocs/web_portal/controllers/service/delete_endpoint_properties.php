<?php
/*______________________________________________________
 *======================================================
 * File: delete_endpoint_properties.php
 * Author: Tom Byrne, George Ryall, John Casson, David Meredith, James McCarthy
 * Description: Answers a site delete request
 *
 * License information
 *
 * Copyright � 2009 STFC
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
require_once __DIR__.'/../../../web_portal/components/Get_User_Principle.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../../../lib/Gocdb_Services/Factory.php';

function delete() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if (empty($_REQUEST['selectedPropIDs'])) {
        throw new Exception("At least one property must be selected for deletion");
    }

    //get the service and properties
    //$service = \Factory::getServiceService()->getService($_REQUEST['serviceID']);
    foreach ($_REQUEST['selectedPropIDs'] as $i => $propID){
        $propertyArray[$i] = \Factory::getServiceService()->getEndpointProperty($propID);
    }

    //it's useful to have the endpoint object to display to the user and so on.
    //And since it's impossible to try and delete props from different endpoints
    //we can just find the parent endpoint and service of the first prop
    //throw new Exception(var_dump($propertyArray));
    $endpoint = $propertyArray[0]->getParentEndpoint();
    $service = $endpoint->getService();

    submit($propertyArray, $service, $endpoint, $user);
}

function submit(array $propertyArray, \Service $service, \EndpointLocation $endpoint, \User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a service property.");
    }
    $serv = \Factory::getServiceService();

    $params['propArr'] = $propertyArray;
    $params['service'] = $service;
    $params['endpoint'] = $endpoint; 
    
    //remove property
    try {
        $serv->deleteEndpointProperties($user, $propertyArray);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
    
    show_view('/service/deleted_endpoint_properties.php', $params);

}