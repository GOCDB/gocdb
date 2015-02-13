<?php
/*______________________________________________________
 *======================================================
 * File: delete_service_endpoint.php
 * Author: James McCarthy
 * Description: Deletes a service endpoint
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

function delete_endpoint() {
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    
    if (!isset($_REQUEST['endpointid']) || !is_numeric($_REQUEST['endpointid']) ){
        throw new Exception("An endpointid must be specified");
    }
    if (!isset($_REQUEST['serviceid']) || !is_numeric($_REQUEST['serviceid']) ){
        throw new Exception("A service id must be specified");
    }
    //get the service and endpoint
    $endpoint = \Factory::getServiceService()->getEndpoint($_REQUEST['endpointid']);
    $service = \Factory::getServiceService()->getService($_REQUEST['serviceid']);

    if($_POST) {
        submit($endpoint, $service, $user);
    }else{
        draw($endpoint, $service, $user);
    }
    
}

function draw(\EndpointLocation $endpoint, \Service $service, \User $user) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a endpoint.");
    }
    
    //Check user has permissions to delete endpoint
    $serv = \Factory::getServiceService();    
    $serv->validateAddEditDeleteActions($user, $service);   
          
    $params['endpoint'] = $endpoint;
    $params['service'] = $service;
     
    show_view('/service/delete_service_endpoint.php', $params);     
}

function submit(\EndpointLocation $endpoint, \Service $service, \User $user = null) {
     $serv = \Factory::getServiceService();
     try {
       	$serv->deleteEndpoint($endpoint, $user);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
   
    $params['endpoint'] = $endpoint;
    $params['service'] = $service;
    
    show_view('/service/deleted_service_endpoint.php', $params);

}