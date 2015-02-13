<?php
/*______________________________________________________
 *======================================================
 * File: delete_site_property.php
 * Author: George Ryall, John Casson, David Meredith, James McCarthy
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
    if (!isset($_REQUEST['propertyid']) || !is_numeric($_REQUEST['propertyid']) ){
        throw new Exception("An id must be specified");
    }
         
    //get the service and property
    if (isset($_REQUEST['propertyid'])){
    	$property = \Factory::getServiceService()->getEndpointProperty($_REQUEST['propertyid']);
    	$endpoint = $property->getParentEndpoint(); 
        $service = $endpoint->getService(); 
    }else {
        throw new \Exception("A propertyId must be specified");
    }

    if($_POST) {
        submit($property, $service, $endpoint, $user);
    }
    else {
        draw($property, $service, $user);
    }
    
}

function draw(\EndpointProperty $property, \Service $service, \User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a service property.");
    }
    
    //Check user has permissions to add site property
    $serv = \Factory::getServiceService();    
    $serv->validateAddEditDeleteActions($user, $service);   
          
    $params['prop'] = $property;
    $params['service'] = $service;
     
    show_view('/service/delete_endpoint_property.php', $params);     
}

function submit(\EndpointProperty $property, \Service $service, \EndpointLocation $endpoint, \User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a service property.");
    }
    $serv = \Factory::getServiceService();

    $params['prop'] = $property;
    $params['service'] = $service;
    $params['endpoint'] = $endpoint; 
    
    //remove property
    try {
       	$serv->deleteEndpointProperty($user, $property);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
    
    show_view('/service/deleted_endpoint_property.php', $params);

}