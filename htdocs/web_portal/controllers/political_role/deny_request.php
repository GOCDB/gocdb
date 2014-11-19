<?php
/*______________________________________________________
 *======================================================
 * File: view_deny_request.php
 * Author: John Casson, David Meredith
 * Description: Denies a political role request
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
 *
 /*====================================================== */
function view_deny_request() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';
      
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if($user == null) throw new Exception("Unregistered users can't view/deny role requests"); 
    
    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if(!isset($_REQUEST['Request_ID']) || empty($_REQUEST['Request_ID'])) {
        throw new LogicException("Invalid role request id");
    }

    $requestId = $_REQUEST['Request_ID'];
    // Lookup role request with id  
    $roleRequest = \Factory::getRoleService()->getRoleById($requestId); 
    // Santity check that it has pending status 
    if($roleRequest->getStatus() != \RoleStatus::PENDING){
        throw new LogicException("Invalid role request [$requestId] - does not have status of PENDING");
    }
    
    // Check the current user has permission to DENY role request over target entity 
    $entity = $roleRequest->getOwnedEntity(); 
    if($entity == null){
       throw new LogicException('Error - target entity of role is null');    
    }
    //echo ''.$entity->getName(); 
    if($entity instanceof \NGI){
        $grantingRoles = \Factory::getNgiService()->authorizeAction(\Action::REJECT_ROLE, $entity, $user); 
    } else if($entity instanceof \Site){
        $grantingRoles = \Factory::getSiteService()->authorizeAction(\Action::REJECT_ROLE, $entity, $user); 
    } else if($entity instanceof \Project){
        $grantingRoles = \Factory::getProjectService()->authorizeAction(\Action::REJECT_ROLE, $entity, $user); 
    } else if($entity instanceof \ServiceGroup){
        $grantingRoles = \Factory::getServiceGroupService()->authorizeAction(\Action::REJECT_ROLE, $entity, $user); 
    } else {
        throw new LogicException('Unsuppored OwnedEntity type'); 
    }
    if(count($grantingRoles) == 0){
        throw new Exception('You do not have permission to reject this role request'); 
    }
    
    \Factory::getRoleService()->deleteRole($roleRequest, $user); 
   
    show_view('political_role/request_denied.php');
    die(); 

}