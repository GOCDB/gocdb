<?php
/*______________________________________________________
 *======================================================
 * File: view_deny_request.php
 * Author: John Casson, David Meredith
 * Description: Revokes a role regardless of its status 
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

function view_revoke_request(){
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';
    require_once __DIR__ . '/../utils.php';
    
    $dn = Get_User_Principle();
    $user = \Factory::getUserService()->getUserByPrinciple($dn);
    if($user == null) throw new Exception("Unregistered users can't revoke roles"); 

    //Check the portal is not in read only mode, returns exception if it is and user is not an admin
    checkPortalIsNotReadOnlyOrUserIsAdmin($user);
    
    if(!isset($_REQUEST['id']) || empty($_REQUEST['id'])) {
        throw new LogicException("Invalid role id");
    }

    // Either a self revocation or revoke is requested by 2nd party 
    // check to see that user has permission to revoke role 
    $role = \Factory::getRoleService()->getRoleById($_REQUEST['id']); 
    $entity = $role->getOwnedEntity(); 
    if($entity == null){
       throw new LogicException('Error - target entity of role is null');    
    }
    //echo ''.$entity->getName(); 
    
    // test for self revocation - current user is same as role's linked user. 
    if($role->getUser() != $user){
        // Revocation by 2nd party 

        // We could delegate all calls to the relevant service authorizeAction from within 
        // a single RoleService (i.e. RoleService would delegate to the appropriate 
        // servcie authAction according to the specified target object type). 
        //$grantingRoles = \Factory::getRoleService()->authorizeAction(\Action::REVOKE_ROLE, $entity, $user); 
        if($entity instanceof \NGI){
            $grantingRoles = \Factory::getNgiService()->authorizeAction(\Action::REVOKE_ROLE, $entity, $user); 
        } else if($entity instanceof \Site){
            $grantingRoles = \Factory::getSiteService()->authorizeAction(\Action::REVOKE_ROLE, $entity, $user); 
        } else if($entity instanceof \Project){
            $grantingRoles = \Factory::getProjectService()->authorizeAction(\Action::REVOKE_ROLE, $entity, $user); 
        } else if($entity instanceof \ServiceGroup){
            $grantingRoles = \Factory::getServiceGroupService()->authorizeAction(\Action::REVOKE_ROLE, $entity, $user); 
        } else {
            throw new LogicException('Unsuppored OwnedEntity type'); 
        }
        if(count($grantingRoles) == 0){
            throw new Exception('You do not have permission to revoke this role'); 
        }
        // simply delete the role (rather than setting its status to Revoked)
        \Factory::getRoleService()->deleteRole($role, $user); 
        show_view('political_role/role_revoked.php');
    } else {
        // Self revocation 
        \Factory::getRoleService()->deleteRole($role, $user); 
        show_view('political_role/role_self_revoked.php');
    }
    
    die(); 
}
?>
