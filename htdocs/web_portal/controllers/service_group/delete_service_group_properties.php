<?php
/*______________________________________________________
 *======================================================
 * File: delete_site_property.php
 * Author: James McCarthy
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

    if (!isset($_REQUEST['parentID']) || !is_numeric($_REQUEST['parentID']) ){
        throw new Exception("A service group id must be specified");
    }

    //get the service group and properties
    $serviceGroup = \Factory::getServiceGroupService()->getServiceGroup($_REQUEST['parentID']);
    foreach ($_REQUEST['selectedPropIDs'] as $i => $propID){
        $propertyArray[$i] = \Factory::getServiceGroupService()->getProperty($propID);
    }

    if(isset($_REQUEST['UserConfirmed'])) {
        submit($propertyArray, $serviceGroup, $user);
    }
    else {
        draw($propertyArray, $serviceGroup, $user);
    }
    
}

function draw(array $propertyArray, \ServiceGroup $serviceGroup, \User $user) {
     if(is_null($user)) {
        throw new Exception("Unregistered users can't edit a service property.");
     }
  
     //Check user has permissions to add site property
     $serv = \Factory::getServiceGroupService();
     $serv->validatePropertyActions($user, $serviceGroup);
     
     $params['PropArr'] = $propertyArray;
     $params['serviceGroup'] = $serviceGroup;
     
     show_view('/service_group/delete_service_group_properties.php', $params);
     
}

function submit(array $propertyArray, \ServiceGroup $serviceGroup, \User $user = null) {

     $params['propArr'] = $propertyArray;
     $params['serviceGroup'] = $serviceGroup;
     
     //remove service group property
     try {
     	$serv = \Factory::getServiceGroupService();
       	$serv->deleteServiceGroupProperties($serviceGroup, $user, $propertyArray);
    } catch(\Exception $e) {
        show_view('error.php', $e->getMessage());
        die();
    }   
    
    
    show_view('/service_group/deleted_service_group_properties.php', $params);

}