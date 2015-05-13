<?php
/*______________________________________________________
 *======================================================
 * File: view_user.php
 * Author: John Casson, David Meredith
 * Description: Retrieves and draws the data for a user
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
function view_user() {
    require_once __DIR__.'/../../../../lib/Gocdb_Services/Factory.php';
    require_once __DIR__.'/../../components/Get_User_Principle.php';
    
    if (!isset($_GET['id']) || !is_numeric($_GET['id']) ){
        throw new Exception("An id must be specified");
    }
    $userId =  $_GET['id']; 
    
    $user = \Factory::getUserService()->getUser($userId);
    if($user === null){
       throw new Exception("No user with that ID"); 
    }
    $params['user'] = $user;

    // get the targetUser's roles
    $roles = \Factory::getRoleService()->getUserRoles($user, \RoleStatus::GRANTED); //$user->getRoles();

    $callingUser = \Factory::getUserService()->getUserByPrinciple(Get_User_Principle());
   
    // can the calling user revoke the targetUser's roles?  
    if($user != $callingUser){
        foreach ($roles as $r) {
            //$ownedEntityDetail = $r->getOwnedEntity()->getName(). ' ('. $r->getOwnedEntity()->getType().')'; 
            $authorisingRoleNames = \Factory::getRoleService()->authorizeAction(
                    \Action::REVOKE_ROLE, $r->getOwnedEntity(), $callingUser); 
            if(count($authorisingRoleNames)>=1){
                $allAuthorisingRoleNames = ''; 
                foreach($authorisingRoleNames as $arName){
                    $allAuthorisingRoleNames .= $arName.', '; 
                }
                $allAuthorisingRoleNames = substr($allAuthorisingRoleNames, 0, strlen($allAuthorisingRoleNames)-2);  
                $r->setDecoratorObject('['.$allAuthorisingRoleNames.'] ');
            } 
        }
    } else {
        // current user is viewing their own roles, so they can revoke their own roles 
        foreach ($roles as $r) {
            $r->setDecoratorObject('[Self revoke own role]'); 
        }
    }

    try {
    	\Factory::getUserService()->editUserAuthorization($user, $callingUser);
        $params['ShowEdit'] = true; 
    } catch (Exception $e) {
        $params['ShowEdit'] = false; 
    }
    
    $params['roles'] = $roles;
    $params['portalIsReadOnly'] = \Factory::getConfigService()->IsPortalReadOnly();
    $title = $user->getFullName();
    show_view("user/view_user.php", $params, $title);
}
