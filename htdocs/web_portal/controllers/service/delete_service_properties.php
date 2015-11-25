<?php
/*______________________________________________________
 *======================================================
 * File: delete_service_properties.php
 * Author: Tom Byrne, George Ryall, John Casson, David Meredith, James McCarthy
 * Description: accepts an array of service property id's and then either
 * deletes them or prompts the user for confirmation
 *
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
    if (!isset($_REQUEST['parentID']) || !is_numeric($_REQUEST['parentID']) ){
        throw new Exception("A service id must be specified");
    }
    //get the service and properties, with the properties stored in an array
    $service = \Factory::getServiceService()->getService($_REQUEST['parentID']);
    foreach ($_REQUEST['selectedPropIDs'] as $i => $propID){
        $propertyArray[$i] = \Factory::getServiceService()->getProperty($propID);

    }

    if(isset($_REQUEST['UserConfirmed'])) {
        submit($propertyArray, $service, $user);
    }
    else {
        draw($propertyArray, $service, $user);
    }
    
}

function draw(array $propertyArray, \Service $service, \User $user=null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a service property.");
    }
    
    //Check user has permissions to add site property
    $serv = \Factory::getServiceService();    
    $serv->validateAddEditDeleteActions($user, $service);   
          
    $params['propArr'] = $propertyArray;
    $params['service'] = $service;

    show_view('/service/delete_service_properties.php', $params);
}

function submit(array $propertyArray, \Service $service, \User $user = null) {
    if(is_null($user)) {
        throw new Exception("Unregistered users can't delete a service property.");
    }

    $params['propArr'] = $propertyArray;
    $params['service'] = $service;

    //remove site property
    try {
     	$serv = \Factory::getServiceService();
       	$serv->deleteServiceProperties($service, $user, $propertyArray);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
    
    
    show_view('/service/deleted_service_properties.php', $params);

}